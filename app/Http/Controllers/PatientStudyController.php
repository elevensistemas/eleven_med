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
     * Display the study file inline in the browser.
     */
    public function show(PatientStudy $patientStudy)
    {
        // 1. Verificar si existe en el disco public de Laravel (storage/app/public)
        if (Storage::disk('public')->exists($patientStudy->file_path)) {
            $path = Storage::disk('public')->path($patientStudy->file_path);
            $mime = Storage::disk('public')->mimeType($patientStudy->file_path) ?? @mime_content_type($path) ?? 'application/octet-stream';
            return response()->file($path, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . addslashes($patientStudy->original_name ?? basename($patientStudy->file_path)) . '"',
            ]);
        }

        // 2. Fallback: verificar si existe en public/storage
        $altPath = public_path('storage/' . $patientStudy->file_path);
        if (file_exists($altPath)) {
            $mime = @mime_content_type($altPath) ?: 'application/octet-stream';
            return response()->file($altPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . addslashes($patientStudy->original_name ?? basename($patientStudy->file_path)) . '"',
            ]);
        }

        abort(404, 'Estudio médico no encontrado en el servidor.');
    }

    /**
     * Download the study file.
     */
    public function download(PatientStudy $patientStudy)
    {
        if (Storage::disk('public')->exists($patientStudy->file_path)) {
            return Storage::disk('public')->download($patientStudy->file_path, $patientStudy->original_name);
        }

        $altPath = public_path('storage/' . $patientStudy->file_path);
        if (file_exists($altPath)) {
            return response()->download($altPath, $patientStudy->original_name);
        }

        abort(404, 'Estudio médico no encontrado en el servidor.');
    }

    /**
     * Update study metadata (date, original name, type, and clinical notes)
     */
    public function update(Request $request, PatientStudy $patientStudy)
    {
        $validated = $request->validate([
            'original_name' => 'required|string|max:255',
            'study_type' => 'nullable|string|max:100',
            'study_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $patientStudy->original_name = $validated['original_name'];
        $patientStudy->study_type = $validated['study_type'];
        if (!empty($validated['study_date'])) {
            $patientStudy->created_at = \Carbon\Carbon::parse($validated['study_date'] . ' 12:00:00');
        }
        $patientStudy->notes = $validated['notes'];
        $patientStudy->save();

        return back()->with('success', 'Datos del estudio médico actualizados correctamente.')->with('active_tab', 'studies');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PatientStudy $patientStudy)
    {
        // Delete actual file from public disk
        if (Storage::disk('public')->exists($patientStudy->file_path)) {
            Storage::disk('public')->delete($patientStudy->file_path);
        }

        // Also delete from public/storage if present
        $altPath = public_path('storage/' . $patientStudy->file_path);
        if (file_exists($altPath)) {
            @unlink($altPath);
        }

        $patientStudy->delete();

        return back()->with('success', 'Estudio eliminado de la historia clínica.')->with('active_tab', 'studies');
    }
}
