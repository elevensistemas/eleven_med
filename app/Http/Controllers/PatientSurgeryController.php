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
            'od_date' => 'nullable|date',
            'od_notes' => 'nullable|string',
            'oi_date' => 'nullable|date',
            'oi_notes' => 'nullable|string',
        ]);

        $hasOd = $request->filled('od_notes');
        $hasOi = $request->filled('oi_notes');

        if (!$hasOd && !$hasOi) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Debe completar el procedimiento o notas de al menos un ojo (OD u OI) para poder guardar.');
        }

        $createdCount = 0;

        if ($hasOd) {
            PatientSurgery::create([
                'patient_id' => $patient->id,
                'eye' => 'OD',
                'surgery_date' => $request->filled('od_date') ? $request->od_date : now()->toDateString(),
                'notes' => trim($request->od_notes),
                'created_by' => \Auth::id(),
            ]);
            $createdCount++;
        }

        if ($hasOi) {
            PatientSurgery::create([
                'patient_id' => $patient->id,
                'eye' => 'OI',
                'surgery_date' => $request->filled('oi_date') ? $request->oi_date : now()->toDateString(),
                'notes' => trim($request->oi_notes),
                'created_by' => \Auth::id(),
            ]);
            $createdCount++;
        }

        $msg = $createdCount > 1 
            ? 'Procedimientos quirúrgicos registrados correctamente para ambos ojos.'
            : 'Procedimiento quirúrgico registrado correctamente.';

        return redirect()->route('patients.show', ['patient' => $patient, 'tab' => 'cirugias'])
                         ->with('active_tab', 'cirugias')
                         ->with('success', $msg);
    }

    public function destroy(PatientSurgery $patientSurgery)
    {
        $patientId = $patientSurgery->patient_id;
        $patientSurgery->delete();

        return redirect()->back()
                         ->with('active_tab', 'cirugias')
                         ->with('success', 'Registro quirúrgico eliminado correctamente.');
    }
}
