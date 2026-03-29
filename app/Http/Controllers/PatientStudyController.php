<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientStudy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PatientStudyController extends Controller
{
    /**
     * Store a newly created study file.
     */
    public function store(Request $request, Patient $patient)
    {
        $request->validate([
            'study_file' => 'required|file|max:20480', // 20MB limit
            'study_type' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $file = $request->file('study_file');
        
        // Define isolated path per patient: patients/{id}/studies/filename
        $path = $file->store("patients/{$patient->id}/studies", 'public');

        PatientStudy::create([
            'patient_id' => $patient->id,
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'original_name' => $file->getClientOriginalName(),
            'study_type' => $request->study_type,
            'notes' => $request->notes,
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', 'Estudio médico adjuntado correctamente.')->with('active_tab', 'studies');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PatientStudy $patientStudy)
    {
        // Delete actual file
        if (Storage::disk('public')->exists($patientStudy->file_path)) {
            Storage::disk('public')->delete($patientStudy->file_path);
        }

        $patientStudy->delete();

        return back()->with('success', 'Estudio eliminado de la historia clínica.')->with('active_tab', 'studies');
    }
}
