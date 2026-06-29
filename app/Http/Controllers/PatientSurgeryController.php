<?php

namespace App\Http\Controllers;

use App\Models\PatientSurgery;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientSurgeryController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $request->validate([
            'od_date' => 'nullable|required_with:od_notes|date',
            'od_notes' => 'nullable|required_with:od_date|string',
            'oi_date' => 'nullable|required_with:oi_notes|date',
            'oi_notes' => 'nullable|required_with:oi_date|string',
        ]);

        $hasOd = $request->filled('od_date') && $request->filled('od_notes');
        $hasOi = $request->filled('oi_date') && $request->filled('oi_notes');

        if (!$hasOd && !$hasOi) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Debe completar la fecha y notas de al menos un ojo (OD u OI) para poder guardar.');
        }

        $createdCount = 0;

        if ($hasOd) {
            PatientSurgery::create([
                'patient_id' => $patient->id,
                'eye' => 'OD',
                'surgery_date' => $request->od_date,
                'notes' => $request->od_notes,
                'created_by' => \Auth::id(),
            ]);
            $createdCount++;
        }

        if ($hasOi) {
            PatientSurgery::create([
                'patient_id' => $patient->id,
                'eye' => 'OI',
                'surgery_date' => $request->oi_date,
                'notes' => $request->oi_notes,
                'created_by' => \Auth::id(),
            ]);
            $createdCount++;
        }

        $msg = $createdCount > 1 
            ? 'Procedimientos quirúrgicos registrados correctamente para ambos ojos.'
            : 'Procedimiento quirúrgico registrado correctamente.';

        return redirect()->route('patients.show', ['patient' => $patient, 'active_tab' => 'surgeries'])
                         ->with('success', $msg);
    }

    public function destroy(PatientSurgery $patientSurgery)
    {
        $patientId = $patientSurgery->patient_id;
        $patientSurgery->delete();

        return redirect()->route('patients.show', ['patient' => $patientId, 'active_tab' => 'surgeries'])
                         ->with('success', 'Registro quirúrgico eliminado.');
    }
}
