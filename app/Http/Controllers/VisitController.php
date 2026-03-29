<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitController extends Controller
{
    public function create(Patient $patient)
    {
        return view('visits.create', compact('patient'));
    }

    public function store(Request $request, Patient $patient)
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
        
        $validated['patient_id'] = $patient->id;
        $validated['doctor_id'] = Auth::id();

        $patient->visits()->create($validated);

        return redirect()->route('patients.show', $patient)->with('success', 'Visita clínica registrada correctamente.')->with('active_tab', 'last-visit');
    }
}
