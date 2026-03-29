<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentController extends Controller
{
    /**
     * Shows the Medical Agenda Calendar UI
     */
    public function index()
    {
        $doctors = User::role('médico')->get();
        // Useful for the quick-add form
        $patients = Patient::select('id', 'first_name', 'last_name', 'dni')->get();
        
        return view('agenda.index', compact('doctors', 'patients'));
    }

    /**
     * API Endpoint for FullCalendar to fetch events.
     */
    public function getEvents(Request $request)
    {
        $start = Carbon::parse($request->start)->toDateString();
        $end = Carbon::parse($request->end)->toDateString();
        $doctorId = $request->doctor_id;

        // 1. Fetch Normal Appointments
        $query = Appointment::with(['patient', 'doctor'])
            ->whereBetween('date', [$start, $end]);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $appointments = $query->get();

        $events = $appointments->map(function ($appointment) {
            $startDateTime = $appointment->date . 'T' . $appointment->time;
            $endDateTime = Carbon::parse($startDateTime)->addMinutes($appointment->duration_minutes)->toIso8601String();

            // Color coding logic based on status
            $color = '#5E6AD2'; // Default primary
            if ($appointment->status === 'confirmed') $color = '#28a745';
            if ($appointment->status === 'cancelled') $color = '#dc3545';
            if ($appointment->status === 'no_show') $color = '#6c757d';

            return [
                'id' => 'evt_' . $appointment->id,
                'title' => substr($appointment->patient->first_name, 0, 1) . '. ' . $appointment->patient->last_name,
                'start' => $startDateTime,
                'end' => $endDateTime,
                'color' => $color,
                'extendedProps' => [
                    'doctor' => 'Dr. '.$appointment->doctor->name,
                    'status' => $appointment->status,
                    'reason' => $appointment->reason,
                    'isBackground' => false
                ]
            ];
        })->toArray();

        // 2. Saturated Days Logic (Ex: 8 appointments = fully booked MVP)
        if ($doctorId) {
            $counts = $appointments->groupBy('date')->map->count();
            foreach ($counts as $date => $count) {
                if ($count >= 6) { // 6 turns is considered 'saturated' for MVP
                    $events[] = [
                        'start' => $date,
                        'display' => 'background',
                        'backgroundColor' => 'rgba(220, 53, 69, 0.15)', // Reddish
                        'extendedProps' => [
                            'isBackground' => true,
                            'reason' => 'Agenda Saturada'
                        ]
                    ];
                }
            }
        }

        // 3. Fetch ScheduleBlocks (Vacations, Conferences)
        if ($doctorId) {
            $blocks = \App\Models\ScheduleBlock::where('doctor_id', $doctorId)
                        ->where(function($q) use ($start, $end) {
                            $q->whereBetween('start_datetime', [$start, $end])
                              ->orWhereBetween('end_datetime', [$start, $end]);
                        })->get();
                        
            foreach ($blocks as $block) {
                $events[] = [
                    'id' => 'blk_' . $block->id,
                    'title' => $block->reason,
                    'start' => $block->start_datetime,
                    'end' => $block->end_datetime,
                    'display' => 'background',
                    'backgroundColor' => 'rgba(108, 117, 125, 0.3)', // Grey out
                    'extendedProps' => [
                        'isBackground' => true,
                        'reason' => $block->reason
                    ]
                ];
            }
        }

        return response()->json($events);
    }

    /**
     * Store new appointment via AJAX
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'time' => 'required',
            'duration_minutes' => 'integer',
            'reason' => 'nullable|string'
        ]);

        $validated['created_by'] = \Auth::id();
        $validated['status'] = 'pending';

        $appointment = Appointment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Turno agendado correctamente',
            'appointment' => $appointment
        ]);
    }

    /**
     * Delete an appointment via AJAX
     */
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Turno eliminado correctamente'
        ]);
    }

    /**
     * Devuelve el status de cada día del mes para el mini-calendario.
     * Status posbiles: 'available' (verde), 'full' (rosa), 'unavailable' (gris)
     */
    public function getMonthAvailability(Request $request)
    {
        $doctorId = $request->doctor_id;
        $year = $request->year;
        $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);

        if (!$doctorId) return response()->json([]);

        // Obtener el esquema de trabajo del doctor
        $schedules = \App\Models\DoctorSchedule::where('doctor_id', $doctorId)->get();
        if ($schedules->isEmpty()) {
            return response()->json([]);
        }

        $scheduleMap = []; // day_of_week => schedule
        foreach ($schedules as $s) {
            // PHP uses 0 (for Sunday) through 6 (for Saturday) by default in Carbon dayOfWeek
            // But verify: 0=Sun, 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat
            $scheduleMap[$s->day_of_week] = $s;
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        // Cargar todos los turnos del doctor en ese mes para checkear saturación
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->groupBy('date');

        $availability = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::createFromDate($year, $month, $day);
            $dateString = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeek; // 0 (Sunday) to 6 (Saturday)

            if (!isset($scheduleMap[$dayOfWeek])) {
                $availability[$dateString] = 'unavailable';
            } else {
                // Es un día laborable. Revisamos capacidad.
                $sch = $scheduleMap[$dayOfWeek];
                $start = Carbon::parse($sch->start_time);
                $end = Carbon::parse($sch->end_time);
                $totalMinutes = $end->diffInMinutes($start);
                $totalSlotsPossible = floor($totalMinutes / $sch->slot_duration_minutes);

                $bookedCount = isset($appointments[$dateString]) ? $appointments[$dateString]->count() : 0;

                if ($bookedCount >= $totalSlotsPossible && $totalSlotsPossible > 0) {
                    $availability[$dateString] = 'full';
                } else {
                    $availability[$dateString] = 'available';
                }
            }
        }

        return response()->json($availability);
    }

    /**
     * Return list of exact time slots for a specific day, marking booked ones.
     */
    public function getDaySlots(Request $request)
    {
        $doctorId = $request->doctor_id;
        $date = Carbon::parse($request->date);
        
        if (!$doctorId) return response()->json(['slots' => []]);

        $schedule = \App\Models\DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $date->dayOfWeek)
            ->first();

        if (!$schedule) {
            return response()->json(['slots' => [], 'message'=> 'El médico no atiende este día.']);
        }

        $appointments = Appointment::with('patient')->where('doctor_id', $doctorId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->keyBy('time');

        $slots = [];
        $start = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $end = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);

        while ($start < $end) {
            $timeString = $start->format('H:i:00');
            $timeDisplay = $start->format('H:i');
            
            if (isset($appointments[$timeString])) {
                $app = $appointments[$timeString];
                $slots[] = [
                    'time' => $timeDisplay,
                    'status' => 'booked',
                    'patient_name' => $app->patient->last_name . ', ' . $app->patient->first_name,
                    'appointment_id' => $app->id
                ];
            } else {
                $slots[] = [
                    'time' => $timeDisplay,
                    'status' => 'free'
                ];
            }

            $start->addMinutes($schedule->slot_duration_minutes);
        }

        return response()->json([
            'slots' => $slots,
            'slot_duration' => $schedule->slot_duration_minutes
        ]);
    }

    /**
     * Phase 17: Escanea los próximos días para ubicar el turno mas cercano disponible para cada médico.
     */
    public function getNearestSlots()
    {
        $doctors = User::role('médico')->with('schedules')->get();
        $results = [];

        foreach ($doctors as $doctor) {
            if ($doctor->schedules->isEmpty()) continue;
            
            $scheduleMap = $doctor->schedules->keyBy('day_of_week');
            
            // Limitamos a 60 días al futuro
            $found = null;
            $currentDate = Carbon::today();
            $now = Carbon::now();
            
            for ($i = 0; $i < 60; $i++) {
                $targetDate = $currentDate->copy()->addDays($i);
                $dayOfWeek = $targetDate->dayOfWeek;
                
                if (isset($scheduleMap[$dayOfWeek])) {
                    $sch = $scheduleMap[$dayOfWeek];
                    $dateStr = $targetDate->format('Y-m-d');
                    
                    // Buscar turnos reales en este día
                    $bookedAppts = Appointment::where('doctor_id', $doctor->id)
                        ->whereDate('date', $dateStr)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->pluck('time', 'time')
                        ->map(fn($t) => Carbon::parse($t)->format('H:i:00'))
                        ->toArray();

                    $start = Carbon::parse($dateStr . ' ' . $sch->start_time);
                    $end = Carbon::parse($dateStr . ' ' . $sch->end_time);

                    while ($start < $end) {
                        $timeStr = $start->format('H:i:00');
                        // No podemos dar un turno de hace minutos atrás en el día de HOY
                        if ($start > $now && !in_array($timeStr, $bookedAppts)) {
                            $found = [
                                'doctor_id' => $doctor->id,
                                'doctor_name' => $doctor->name,
                                'date' => $dateStr,
                                'time' => $start->format('H:i'),
                                'slot_duration' => $sch->slot_duration_minutes,
                                'formatted_date' => $targetDate->locale('es')->isoFormat('dddd D \d\e MMMM, YYYY') // Ej: Lunes 2 de Marzo, 2026
                            ];
                            break 2; // Romper ambos while y for loops para saltar al sig. doctor
                        }
                        $start->addMinutes($sch->slot_duration_minutes);
                    }
                }
            }

            if ($found) {
                $results[] = $found;
            }
        }

        return response()->json($results);
    }
}
