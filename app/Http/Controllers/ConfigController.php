<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Patient;

class ConfigController extends Controller
{
    /**
     * Muestra el panel maestro de configuración del sistema.
     */
    public function index()
    {
        $obrasSociales = \App\Models\ObraSocial::orderBy('name')->get();
        
        // Cargar Memorias de IA
        $aiMemories = [];
        $memoryDir = storage_path('app/ai_memory');
        if (file_exists($memoryDir)) {
            $files = \Illuminate\Support\Facades\File::files($memoryDir);
            foreach ($files as $file) {
                if (preg_match('/^(\d+)_memory\.txt$/', $file->getFilename(), $matches)) {
                    $userId = $matches[1];
                    $user = \App\Models\User::find($userId);
                    if ($user) {
                        $chatContent = [];
                        $chatPath = storage_path('app/ai_chats/' . $userId . '_chat.json');
                        if (file_exists($chatPath)) {
                            $chatContent = json_decode(file_get_contents($chatPath), true) ?? [];
                        }
                        
                        $aiMemories[] = [
                            'user' => $user,
                            'content' => file_get_contents($file->getPathname()),
                            'chat_history' => array_reverse($chatContent), // Reverse to show newest first or newest at bottom
                            'size' => round($file->getSize() / 1024, 2) . ' KB',
                            'last_modified' => \Carbon\Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i')
                        ];
                    }
                }
            }
        }
        
        // Cargar chats de usuarios que quizas NO tengan memoria RAG pero sí tengan chats
        $chatDir = storage_path('app/ai_chats');
        if (file_exists($chatDir)) {
            $files = \Illuminate\Support\Facades\File::files($chatDir);
            foreach ($files as $file) {
                if (preg_match('/^(\d+)_chat\.json$/', $file->getFilename(), $matches)) {
                    $userId = $matches[1];
                    // Si ya está en aiMemories, lo salteamos
                    if (!collect($aiMemories)->contains(function($m) use ($userId) { return $m['user']->id == $userId; })) {
                        $user = \App\Models\User::find($userId);
                        if ($user) {
                            $aiMemories[] = [
                                'user' => $user,
                                'content' => 'Ninguna regla o preferencia personal guardada todavía.',
                                'chat_history' => array_reverse(json_decode(file_get_contents($file->getPathname()), true) ?? []),
                                'size' => round($file->getSize() / 1024, 2) . ' KB',
                                'last_modified' => \Carbon\Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i')
                            ];
                        }
                    }
                }
            }
        }

        return view('config.index', compact('obrasSociales', 'aiMemories'));
    }

    /**
     * Limpia la memoria de un bot específico.
     */
    public function clearAiMemory(\App\Models\User $user)
    {
        $memoryFilePath = storage_path('app/ai_memory/' . $user->id . '_memory.txt');
        $chatFilePath = storage_path('app/ai_chats/' . $user->id . '_chat.json');
        
        if (file_exists($memoryFilePath)) {
            unlink($memoryFilePath);
        }
        if (file_exists($chatFilePath)) {
            unlink($chatFilePath);
        }
        
        return back()->with('success', 'Memoria y Chat IA vaciados para el usuario: ' . $user->name);
    }

    /**
     * Exporta la base de pacientes a Excel (CSV nativo).
     */
    public function exportPatientsToExcel()
    {
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=Listado_Pacientes_' . date('Ymd_His') . '.csv',
            'Expires'             => '0',
            'Pragma'              => 'public'
        ];

        $patients = Patient::with(['visits', 'surgeries'])->get();

        $callback = function() use ($patients) {
            $file = fopen('php://output', 'w');
            
            // BOM to force Excel to read UTF-8 correctly
            fputs($file, "\xEF\xBB\xBF");
            
            fputcsv($file, [
                'ID',
                'DNI',
                'Apellido',
                'Nombre',
                'Nacimiento (Edad)',
                'Obra Social o Plan',
                'Telefono',
                'Resumen Visitas Clinicas',
                'Resumen Cirugias',
                'Antecedentes'
            ], ';');

            foreach ($patients as $pt) {
                // Formatear Visitas
                $visitsText = collect($pt->visits)->map(function ($visit) {
                    $date = $visit->created_at->format('d/m/Y');
                    return "[{$date}] Motivo: " . ($visit->motivo_consulta ?? '-') 
                        . " | Diag: " . ($visit->medical_history ?? 'Sin registro evolutivo');
                })->implode("\n\n");

                // Formatear Cirugias
                $surgeriesText = collect($pt->surgeries)->map(function ($surg) {
                    $date = \Carbon\Carbon::parse($surg->surgery_date)->format('d/m/Y');
                    return "[{$date}] Ojo: {$surg->eye_operated} | Cirugia: {$surg->surgery_type}" 
                        . ($surg->notes ? " | Obs: {$surg->notes}" : '');
                })->implode("\n\n");

                // Calcular Edad y fecha segura
                $age = $pt->date_of_birth ? \Carbon\Carbon::parse($pt->date_of_birth)->age . ' años' : 'S/D';
                $dob = $pt->date_of_birth ? \Carbon\Carbon::parse($pt->date_of_birth)->format('d/m/Y') : 'S/D';

                fputcsv($file, [
                    $pt->id,
                    $pt->dni,
                    $pt->last_name,
                    $pt->first_name,
                    "{$dob} ({$age})",
                    trim($pt->obra_social . ' ' . $pt->plan),
                    $pt->phone,
                    $visitsText,
                    $surgeriesText,
                    $pt->medical_notes
                ], ';');
            }
            
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Guarda una nueva obra social
     */
    public function storeObraSocial(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:obra_socials,name',
            'color' => 'nullable|string|max:7'
        ]);
        \App\Models\ObraSocial::create([
            'name' => $request->name,
            'color' => $request->color ?? '#5e6ad2'
        ]);
        // Limpiar cache potencial
        \Illuminate\Support\Facades\Cache::forget('os_color_' . md5($request->name));
        return back()->with('success', 'Obra Social / Cobertura creada exitosamente.');
    }

    /**
     * Elimina una obra social
     */
    public function destroyObraSocial(\App\Models\ObraSocial $obraSocial)
    {
        $obraSocial->delete();
        return back()->with('success', 'Obra Social eliminada exitosamente.');
    }
}
