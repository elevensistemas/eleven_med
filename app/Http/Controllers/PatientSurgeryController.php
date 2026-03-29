<?php

namespace App\Http\Controllers;

use App\Models\PatientSurgery;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientSurgeryController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'eye' => 'required|in:OD,OI,Bilateral,Anexos/Otro',
            'surgery_date' => 'required|date',
            'notes' => 'required|string',
        ]);

        $validated['patient_id'] = $patient->id;
        $validated['created_by'] = \Auth::id();

        PatientSurgery::create($validated);

        return redirect()->route('patients.show', ['patient' => $patient, 'active_tab' => 'surgeries'])
                         ->with('success', 'Procedimiento quirúrgico registrado correctamente.');
    }

    public function destroy(PatientSurgery $patientSurgery)
    {
        $patientId = $patientSurgery->patient_id;
        $patientSurgery->delete();

        return redirect()->route('patients.show', ['patient' => $patientId, 'active_tab' => 'surgeries'])
                         ->with('success', 'Registro quirúrgico eliminado.');
    }
}
