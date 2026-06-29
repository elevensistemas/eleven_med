<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Visit;
use App\Models\VisitLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitController extends Controller
{
    public function create(Patient $patient)
    {
        $openVisit = Visit::where('patient_id', $patient->id)->where('is_open', true)->first();
        if ($openVisit) {
            return redirect()->route('visits.edit', $openVisit)->with('info', 'Redirigido a la evolución clínica que está abierta.');
        }

        return view('visits.create', compact('patient'));
    }

    public function store(Request $request, Patient $patient)
    {
        $openVisit = Visit::where('patient_id', $patient->id)->where('is_open', true)->first();
        if ($openVisit) {
            return redirect()->route('visits.edit', $openVisit)->with('warning', 'Ya existe una consulta abierta para este paciente.');
        }

        $validated = $request->validate([
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
        $validated['doctor_id'] = Auth::id();
        $validated['is_open'] = true;

        $visit = Visit::create($validated);

        // Log open status
        VisitLog::create([
            'visit_id' => $visit->id,
            'user_id' => Auth::id(),
            'action' => 'open',
        ]);

        return redirect()->route('patients.show', $patient)->with('success', 'Visita clínica abierta y registrada correctamente.')->with('active_tab', 'last-visit');
    }

    public function edit(Visit $visit)
    {
        $patient = $visit->patient;
        return view('visits.edit', compact('visit', 'patient'));
    }

    public function update(Request $request, Visit $visit)
    {
        $validated = $request->validate([
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

        $visit->update($validated);
        $patient = $visit->patient;

        if ($request->has('close_visit') && $request->input('close_visit') == '1') {
            $visit->is_open = false;
            $visit->save();

            // Log close status
            VisitLog::create([
                'visit_id' => $visit->id,
                'user_id' => Auth::id(),
                'action' => 'close',
            ]);

            return redirect()->route('patients.show', $patient)->with('success', 'Visita clínica cerrada correctamente.')->with('active_tab', 'last-visit');
        }

        return redirect()->route('patients.show', $patient)->with('success', 'Evolución clínica actualizada.')->with('active_tab', 'last-visit');
    }

    public function toggleStatus(Visit $visit)
    {
        $patient = $visit->patient;

        if ($visit->is_open) {
            $visit->is_open = false;
            $visit->save();

            // Log close status
            VisitLog::create([
                'visit_id' => $visit->id,
                'user_id' => Auth::id(),
                'action' => 'close',
            ]);

            return redirect()->back()->with('success', 'Consulta cerrada correctamente.');
        } else {
            // Check if there is another open visit
            $hasOpen = Visit::where('patient_id', $patient->id)->where('is_open', true)->exists();
            if ($hasOpen) {
                return redirect()->back()->with('error', 'No se puede reabrir esta consulta. Ya existe otra consulta abierta para este paciente.');
            }

            $visit->is_open = true;
            $visit->save();

            // Log open status
            VisitLog::create([
                'visit_id' => $visit->id,
                'user_id' => Auth::id(),
                'action' => 'open',
            ]);

            return redirect()->back()->with('success', 'Consulta reabierta correctamente.');
        }
    }
}
