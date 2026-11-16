<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Visit;
use App\Models\VisitLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitController extends Controller
{
    public function create(Patient $patient)
    {
        $openVisit = Visit::where('patient_id', $patient->id)->where('is_open', true)->first();
        if ($openVisit) {
            return redirect()->route('visits.edit', $openVisit)->with('info', 'Existe una evolución abierta para este paciente. Aquí puede continuarla, guardarla o cerrarla.');
        }

        $previousVisits = $patient->visits()->with('doctor')->orderBy('created_at', 'desc')->get();
        $lastVisit = $previousVisits->first();
        $surgeries = $patient->surgeries()->with('doctor')->orderBy('surgery_date', 'desc')->get();
        $doctors = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['médico', 'administrador']);
        })->orderBy('name')->get();

        return view('visits.create', compact('patient', 'lastVisit', 'previousVisits', 'surgeries', 'doctors'));
    }

    public function store(Request $request, Patient $patient)
    {
        $openVisit = Visit::where('patient_id', $patient->id)->where('is_open', true)->first();
        if ($openVisit) {
            return redirect()->route('visits.edit', $openVisit)->with('warning', 'Ya existe una consulta abierta para este paciente. Puede continuarla o cerrarla desde aquí.');
        }

        $validated = $request->validate([
            'doctor_id' => 'nullable|exists:users,id',
            'motivo_consulta' => 'nullable|string',
            'diagnostico' => 'nullable|string',
            'antecedentes_oftalmologicos' => 'nullable|string',
            'tratamiento_oftalmologico' => 'nullable|string',
            'antecedentes_generales' => 'nullable|string',
            'tratamientos_generales' => 'nullable|string',
            
            'pio' => 'nullable|string|max:255',
            'bmc' => 'nullable|string|max:255',
            'obi' => 'nullable|string|max:255',
            'otros_examen' => 'nullable|string|max:255',
            
            'av_od_lejos' => 'nullable|string|max:255',
            'av_oi_lejos' => 'nullable|string|max:255',
            'av_od_cerca' => 'nullable|string|max:255',
            'av_oi_cerca' => 'nullable|string|max:255',
        ]);
        
        $validated['patient_id'] = $patient->id;
        $validated['doctor_id'] = $request->filled('doctor_id') ? $request->input('doctor_id') : Auth::id();
        $validated['is_open'] = ($request->input('close_visit') == '1') ? false : true;

        // Actualizar derivación / recomendación o comentarios médicos si se enviaron en la consulta
        if ($request->has('recommendation')) {
            $patient->recommendation = $request->input('recommendation');
        }
        if ($request->has('medical_notes')) {
            $patient->medical_notes = $request->input('medical_notes');
        }
        $patient->save();

        $visit = Visit::create($validated);

        try {
            VisitLog::create([
                'visit_id' => $visit->id,
                'user_id' => Auth::id(),
                'action' => $validated['is_open'] ? 'open' : 'close',
            ]);
        } catch (\Throwable $e) {
            // Evitar que un fallo de auditoría bloquee la atención médica
        }

        return redirect()->route('patients.show', $patient)->with('success', $validated['is_open'] ? 'Visita clínica abierta y registrada correctamente.' : 'Consulta registrada y cerrada correctamente.')->with('active_tab', 'historial');
    }

    public function edit(Visit $visit)
    {
        $patient = $visit->patient;
        $visitLogs = collect();
        try {
            $visitLogs = $visit->logs()->with('user')->get();
        } catch (\Throwable $e) {
            $visitLogs = collect();
        }
        $previousVisits = $patient->visits()->with('doctor')->where('id', '!=', $visit->id)->orderBy('created_at', 'desc')->get();
        $surgeries = $patient->surgeries()->with('doctor')->orderBy('surgery_date', 'desc')->get();
        $doctors = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['médico', 'administrador']);
        })->orderBy('name')->get();

        return view('visits.edit', compact('visit', 'patient', 'previousVisits', 'surgeries', 'doctors', 'visitLogs'));
    }

    public function update(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'doctor_id' => 'nullable|exists:users,id',
            'motivo_consulta' => 'nullable|string',
            'diagnostico' => 'nullable|string',
            'antecedentes_oftalmologicos' => 'nullable|string',
            'tratamiento_oftalmologico' => 'nullable|string',
            'antecedentes_generales' => 'nullable|string',
            'tratamientos_generales' => 'nullable|string',
            
            'pio' => 'nullable|string|max:255',
            'bmc' => 'nullable|string|max:255',
            'obi' => 'nullable|string|max:255',
            'otros_examen' => 'nullable|string|max:255',
            
            'av_od_lejos' => 'nullable|string|max:255',
            'av_oi_lejos' => 'nullable|string|max:255',
            'av_od_cerca' => 'nullable|string|max:255',
            'av_oi_cerca' => 'nullable|string|max:255',
        ]);

        if ($request->filled('doctor_id')) {
            $validated['doctor_id'] = $request->input('doctor_id');
        }

        $visit->update($validated);
        $patient = $visit->patient;

        // Actualizar derivación / recomendación o comentarios médicos en la ficha del paciente
        if ($request->has('recommendation')) {
            $patient->recommendation = $request->input('recommendation');
        }
        if ($request->has('medical_notes')) {
            $patient->medical_notes = $request->input('medical_notes');
        }
        $patient->save();

        if ($request->has('close_visit') && $request->input('close_visit') == '1') {
            $visit->is_open = false;
            $visit->save();

            try {
                VisitLog::create([
                    'visit_id' => $visit->id,
                    'user_id' => Auth::id(),
                    'action' => 'close',
                ]);
            } catch (\Throwable $e) {}

            return redirect()->route('patients.show', $patient)->with('success', 'Visita clínica guardada y cerrada correctamente.')->with('active_tab', 'historial');
        }

        if ($request->has('reopen_visit') && $request->input('reopen_visit') == '1') {
            // Cerrar cualquier otra visita abierta del paciente para evitar bloqueos cruzados
            Visit::where('patient_id', $patient->id)->where('id', '!=', $visit->id)->where('is_open', true)->update(['is_open' => false]);
            $visit->is_open = true;
            $visit->save();

            try {
                VisitLog::create([
                    'visit_id' => $visit->id,
                    'user_id' => Auth::id(),
                    'action' => 'open',
                ]);
            } catch (\Throwable $e) {}

            return redirect()->route('visits.edit', $visit)->with('success', 'Consulta reabierta para continuar atendiendo.');
        }

        return redirect()->route('patients.show', $patient)->with('success', 'Evolución clínica actualizada correctamente.')->with('active_tab', 'historial');
    }

    public function toggleStatus(Visit $visit)
    {
        $patient = $visit->patient;

        if ($visit->is_open) {
            $visit->is_open = false;
            $visit->save();

            try {
                VisitLog::create([
                    'visit_id' => $visit->id,
                    'user_id' => Auth::id(),
                    'action' => 'close',
                ]);
            } catch (\Throwable $e) {}

            return redirect()->back()->with('success', 'Consulta cerrada correctamente.');
        } else {
            // Cerrar automáticamente otra consulta previa que hubiera quedado abierta accidentalmente
            Visit::where('patient_id', $patient->id)->where('id', '!=', $visit->id)->where('is_open', true)->update(['is_open' => false]);

            $visit->is_open = true;
            $visit->save();

            try {
                VisitLog::create([
                    'visit_id' => $visit->id,
                    'user_id' => Auth::id(),
                    'action' => 'open',
                ]);
            } catch (\Throwable $e) {}

            return redirect()->route('visits.edit', $visit)->with('success', 'Consulta reabierta y lista para editar.');
        }
    }
}
