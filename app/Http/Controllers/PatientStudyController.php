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
     * Resuelve la ruta física local del estudio (nuevo almacenamiento o carpeta migrada por FTP del Viejo Eleven).
     */
    protected function resolveLocalStudyPath(PatientStudy $patientStudy): ?string
    {
        if (Storage::disk('public')->exists($patientStudy->file_path)) {
            return Storage::disk('public')->path($patientStudy->file_path);
        }

        $candidates = [
            public_path('storage/' . $patientStudy->file_path),
            public_path('uploads/' . $patientStudy->file_path),
            public_path('uploads/legacy_studies/' . basename($patientStudy->file_path)),
            storage_path('app/public/legacy_studies/' . basename($patientStudy->file_path)),
        ];

        foreach ($candidates as $cand) {
            if (file_exists($cand) && is_file($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Display the study file inline in the browser (compatible con estudios nuevos y estudios históricos del Viejo Eleven).
     */
    public function show(PatientStudy $patientStudy)
    {
        $localPath = $this->resolveLocalStudyPath($patientStudy);
        if ($localPath) {
            $mime = @mime_content_type($localPath) ?: 'application/octet-stream';
            return response()->file($localPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . addslashes($patientStudy->original_name ?? basename($patientStudy->file_path)) . '"',
            ]);
        }

        // Puente opcional hacia el servidor del Viejo Eleven mientras se terminan de copiar archivos pesados por FTP
        $settings = \App\Models\Patient::getSystemSettings();
        $legacyBaseUrl = trim((string)($settings['legacy_studies_base_url'] ?? ''));
        if ($legacyBaseUrl !== '' && str_starts_with((string)$patientStudy->file_path, 'legacy_studies/')) {
            $remoteUrl = rtrim($legacyBaseUrl, '/') . '/' . rawurlencode(basename($patientStudy->file_path));
            return redirect()->away($remoteUrl);
        }

        abort(404, 'Archivo de estudio pendiente de sincronización desde la carpeta de imágenes del Viejo Eleven (/public_html/uploads/legacy_studies/).');
    }

    /**
     * Download the study file.
     */
    public function download(PatientStudy $patientStudy)
    {
        $localPath = $this->resolveLocalStudyPath($patientStudy);
        if ($localPath) {
            return response()->download($localPath, $patientStudy->original_name ?: basename($localPath));
        }

        $settings = \App\Models\Patient::getSystemSettings();
        $legacyBaseUrl = trim((string)($settings['legacy_studies_base_url'] ?? ''));
        if ($legacyBaseUrl !== '' && str_starts_with((string)$patientStudy->file_path, 'legacy_studies/')) {
            $remoteUrl = rtrim($legacyBaseUrl, '/') . '/' . rawurlencode(basename($patientStudy->file_path));
            return redirect()->away($remoteUrl);
        }

        abort(404, 'Archivo de estudio pendiente de sincronización desde el Viejo Eleven.');
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
