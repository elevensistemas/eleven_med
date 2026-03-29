<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientAssignment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PatientAssignmentController extends Controller
{
    /**
     * Store a newly created assignment for a patient.
     */
    public function store(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|max:100',
            'doctor_id' => 'required|exists:users,id',
            'notes' => 'nullable|string'
        ]);

        $patient->assignments()->create([
            'event_type' => $validated['event_type'],
            'doctor_id' => $validated['doctor_id'],
            'notes' => $validated['notes'],
            'status' => 'in_progress',
            'started_at' => Carbon::now()
        ]);

        return back()->with('success', 'Asignación iniciada correctamente.')->with('active_tab', 'assignment');
    }

    /**
     * Mark an assignment as completed.
     */
    public function complete(Request $request, PatientAssignment $assignment)
    {
        $assignment->update([
            'status' => 'completed',
            'ended_at' => Carbon::now()
        ]);

        return back()->with('success', 'Proceso finalizado. Tiempo registrado.')->with('active_tab', 'assignment');
    }
}
