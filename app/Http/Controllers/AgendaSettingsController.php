<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ScheduleBlock;
use Illuminate\Http\Request;

class AgendaSettingsController extends Controller
{
    /**
     * Display the Agenda Settings screen.
     */
    public function index()
    {
        // Get doctors to populate the select drop-down
        $doctors = User::role('médico')->get();
        
        // Include upcoming existing blocks, eager load doctor info
        $blocks = ScheduleBlock::with('doctor')
                    ->where('end_datetime', '>=', now()->toDateString())
                    ->orderBy('start_datetime', 'asc')
                    ->get();
                    
        return view('agenda.settings', compact('doctors', 'blocks'));
    }

    /**
     * Store a new ScheduleBlock for a doctor.
     */
    public function storeBlock(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after_or_equal:start_datetime',
            'reason' => 'required|string|max:255',
        ]);

        ScheduleBlock::create([
            'doctor_id' => $request->doctor_id,
            'start_datetime' => $request->start_datetime,
            'end_datetime' => $request->end_datetime,
            'reason' => $request->reason,
        ]);

        return redirect()->route('agenda.settings')->with('success', 'El bloque de disponibilidad fue configurado exitosamente.');
    }

    /**
     * Delete an existing ScheduleBlock.
     */
    public function destroyBlock(ScheduleBlock $block)
    {
        $block->delete();
        
        return redirect()->route('agenda.settings')->with('success', 'Bloque horario eliminado. La agenda vuelve a estar libre.');
    }

    /**
     * Phase 19: Get Doctor Configuration
     */
    public function getConfig($doctorId)
    {
        $config = \App\Models\DoctorConfiguration::where('doctor_id', $doctorId)->first();
        if (!$config) {
            return response()->json(['exists' => false]);
        }
        return response()->json([
            'exists' => true,
            'config' => $config
        ]);
    }

    /**
     * Phase 19: Destroy Doctor Configuration and its schedules
     */
    public function destroyConfig($doctorId)
    {
        \App\Models\DoctorConfiguration::where('doctor_id', $doctorId)->delete();
        \App\Models\DoctorSchedule::where('doctor_id', $doctorId)->delete();

        return redirect()->route('agenda.settings')->with('success', 'Patrón de agenda eliminado completamente. El calendario ha sido liberado.');
    }

    /**
     * Store or update the base Doctor Configuration for the week.
     */
    public function storeConfig(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'working_days' => 'required|array',
            'appointment_duration' => 'required|integer',
            'shift_1_start' => 'required|date_format:H:i',
            'shift_1_end' => 'required|date_format:H:i|after:shift_1_start',
            'shift_2_start' => 'nullable|date_format:H:i',
            'shift_2_end' => 'nullable|date_format:H:i|after:shift_2_start',
        ]);

        \App\Models\DoctorConfiguration::updateOrCreate(
            ['doctor_id' => $validated['doctor_id']],
            [
                'working_days' => $validated['working_days'],
                'shift_1_start' => $validated['shift_1_start'],
                'shift_1_end' => $validated['shift_1_end'],
                'shift_2_start' => $validated['shift_2_start'],
                'shift_2_end' => $validated['shift_2_end'],
                'appointment_duration' => $validated['appointment_duration']
            ]
        );

        // Phase 19: Sincronización Automática con `doctor_schedules`
        // 1. Limpiamos cualquier schedule previo
        \App\Models\DoctorSchedule::where('doctor_id', $validated['doctor_id'])->delete();

        // 2. Iteramos por cada día operativo que se ha marcado (ej: "1" para lunes, "3" para miércoles...)
        // OJO: Sunday was 0, our DB uses 0=Sun, 1=Mon, ..., 6=Sat. Check what settings form sent.
        foreach ($validated['working_days'] as $dayVal) {
            // El formulario manda strings "1", "2"... "6"
            // If they selected 1 (Lunes), map it:
            $dayOfWeek = (int)$dayVal;
            
            // Turno Mañana (Principal)
            \App\Models\DoctorSchedule::create([
                'doctor_id' => $validated['doctor_id'],
                'day_of_week' => $dayOfWeek, // 1 for Monday
                'start_time' => $validated['shift_1_start'],
                'end_time' => $validated['shift_1_end'],
                'slot_duration_minutes' => $validated['appointment_duration']
            ]);

            // Turno Tarde (Opcional - Partido)
            if (!empty($validated['shift_2_start']) && !empty($validated['shift_2_end'])) {
                // IMPORTANT: The migration constrained `doctor_id` and `day_of_week` to be UNIQUE.
                // Si el turno es partido, 'doctor_schedules' en la tabla actual no soporta dos filas para el mismo día (por la clave única).
                // WORKAROUND FASE 19: Actualizamos el End Time del turno creado recién para que cubra todo el día (desde el inicio de la mañana hasta el fin de la tarde). 
                // Esto permite que el Master Calendar (Phase 16) pinte todos los turnos. El médico verá slots desde la mañana hasta la tarde de corrido.
                // En futuras versiones se tendría que borrar la unique key de migration.
                \App\Models\DoctorSchedule::where('doctor_id', $validated['doctor_id'])
                    ->where('day_of_week', $dayOfWeek)
                    ->update(['end_time' => $validated['shift_2_end']]);
            }
        }

        return redirect()->route('agenda.settings')->with('success', 'El patrón base de trabajo semanal para este profesional se ha registrado y reflejado automáticamente en el Calendario Maestro.');
    }
}
