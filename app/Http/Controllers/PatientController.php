<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        
        $patients = Patient::with('latestVisit.doctor')->withCount(['appointments as ausencias_count' => function ($query) {
            $query->where('status', 'no_show');
        }])->when($search, function ($query, $search) {
            $altPhone = null;
            if (preg_match('/^11([\s\-]*)(.*)$/', $search, $matches)) {
                $altPhone = '15' . $matches[1] . $matches[2];
            } elseif (preg_match('/^15([\s\-]*)(.*)$/', $search, $matches)) {
                $altPhone = '11' . $matches[1] . $matches[2];
            }

            return $query->where(function($q) use ($search, $altPhone) {
                $q->where('last_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
                  
                if ($altPhone) {
                    $q->orWhere('phone', 'like', "%{$altPhone}%");
                }
            });
        })->latest()->paginate(15);
        
        return view('patients.index', compact('patients', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $doctors = User::role('médico')->get();
        $obrasSociales = \App\Models\ObraSocial::orderBy('name')->get();
        return view('patients.create', compact('doctors', 'obrasSociales'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'dni' => 'required|string|unique:patients|max:50',
            'date_of_birth' => 'required|date',
            'obra_social' => 'nullable|string|max:255',
            'plan' => 'nullable|string|max:255',
            'affiliate_number' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'iva_condition' => 'nullable|string|max:255',
            'nro_siniestro' => 'nullable|string|max:255',
            'director_id' => 'nullable|exists:users,id',
            'medical_notes' => 'nullable|string',
        ]);

        $patient = Patient::create($validated);

        return redirect()->route('patients.show', $patient)->with('success', 'Paciente registrado correctamente.');
    }

    /**
     * Display the specific patient card and clinical tabs.
     */
    public function show(Patient $patient)
    {
        // Phase 20: Track in Session for "Últimos Pacientes Buscados"
        $this->pushToRecent($patient);

        // Load relationships like director, appointments, studies later
        $patient->load(['director', 'studies.uploader', 'assignments.doctor', 'surgeries.doctor', 'visits.doctor', 'appointments.doctor', 'comments.user']);
        $doctors = \App\Models\User::role('médico')->get();
        return view('patients.show', compact('patient', 'doctors'));
    }

    /**
     * Phase 20: Global Omnibar Search
     */
    public function globalSearch(Request $request)
    {
        $search = $request->get('q');
        if (!$search) return redirect()->back();

        $altPhone = null;
        if (preg_match('/^11([\s\-]*)(.*)$/', $search, $matches)) {
            $altPhone = '15' . $matches[1] . $matches[2];
        } elseif (preg_match('/^15([\s\-]*)(.*)$/', $search, $matches)) {
            $altPhone = '11' . $matches[1] . $matches[2];
        }

        $query = Patient::where(function($q) use ($search, $altPhone) {
            $q->where('dni', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('first_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
              
            if ($altPhone) {
                $q->orWhere('phone', 'like', "%{$altPhone}%");
            }
        });
                        
        // Si encontramos uno único, vamos directo a su ficha
        if ($query->count() === 1) {
            return redirect()->route('patients.show', $query->first());
        }

        // Si hay varios o cero, lo mandamos al index con el filtro
        return redirect()->route('patients.index', ['search' => $search]);
    }

    /**
     * Phase 21: Live API Search for Modals
     */
    public function apiSearch(Request $request)
    {
        $search = $request->get('q');
        if (!$search || strlen($search) < 2) {
            return response()->json([]);
        }

        $altPhone = null;
        if (preg_match('/^11([\s\-]*)(.*)$/', $search, $matches)) {
            $altPhone = '15' . $matches[1] . $matches[2];
        } elseif (preg_match('/^15([\s\-]*)(.*)$/', $search, $matches)) {
            $altPhone = '11' . $matches[1] . $matches[2];
        }

        $query = Patient::where(function($q) use ($search, $altPhone) {
            $q->where('dni', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('first_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
              
            if ($altPhone) {
                $q->orWhere('phone', 'like', "%{$altPhone}%");
            }
        });

        $patients = $query->limit(10)
                        ->get(['id', 'first_name', 'last_name', 'dni']);
        return response()->json($patients);
    }

    /**
     * Upload Profile Photo
     */
    public function uploadPhoto(Request $request, Patient $patient)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        if ($request->hasFile('photo')) {
            if ($patient->photo_path && \Illuminate\Support\Facades\Storage::disk('uploads')->exists(str_replace('uploads/', '', $patient->photo_path))) {
                \Illuminate\Support\Facades\Storage::disk('uploads')->delete(str_replace('uploads/', '', $patient->photo_path));
            } elseif ($patient->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($patient->photo_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($patient->photo_path);
            }

            $path = $request->file('photo')->store('patients/photos', 'uploads');
            $patient->photo_path = 'uploads/' . $path;
            $patient->save();

            return response()->json([
                'success' => true,
                'photo_url' => asset($patient->photo_path)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file provided'], 400);
    }

    /**
     * Upload Profile Photo from WebRTC Camera (Base64)
     */
    public function uploadBase64Photo(Request $request, Patient $patient)
    {
        $request->validate([
            'photo_base64' => 'required|string',
        ]);

        $base64_image = $request->input('photo_base64');
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $type)) {
            $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, etc
            
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                return response()->json(['success' => false, 'message' => 'Invalid image type'], 400);
            }
            
            $base64_image = str_replace(' ', '+', $base64_image);
            $image_data = base64_decode($base64_image);

            if ($image_data === false) {
                return response()->json(['success' => false, 'message' => 'Base64 decode failed'], 400);
            }

            if ($patient->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($patient->photo_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($patient->photo_path);
            }

            $fileName = uniqid() . '.' . $type;
            $path = 'patients/photos/' . $fileName;
            
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $image_data);
            
            $patient->photo_path = $path;
            $patient->save();

            return response()->json([
                'success' => true,
                'photo_url' => asset('storage/' . $path)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid base64 string'], 400);
    }

    /**
     * Remove Profile Photo
     */
    public function removePhoto(Patient $patient)
    {
        if ($patient->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($patient->photo_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($patient->photo_path);
            $patient->photo_path = null;
            $patient->save();
        }

        return back()->with('success', 'Foto de perfil desasignada. Volvieron las iniciales.');
    }

    /**
     * Helper para Phase 20 Omnibar
     */
    private function pushToRecent(Patient $patient)
    {
        $recent = session()->get('recent_patients', []);

        // Filter out if already exists
        $recent = array_filter($recent, function($p) use ($patient) {
            return $p['id'] !== $patient->id;
        });

        // Prepend current
        array_unshift($recent, [
            'id' => $patient->id,
            'name' => $patient->first_name . ' ' . $patient->last_name,
            'dni' => $patient->dni,
        ]);

        // Keep only top 5
        $recent = array_slice($recent, 0, 5);

        session()->put('recent_patients', $recent);
    }

    public function edit(Patient $patient)
    {
        $doctors = User::role('médico')->get();
        $obrasSociales = \App\Models\ObraSocial::orderBy('name')->get();
        return view('patients.edit', compact('patient', 'doctors', 'obrasSociales'));
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'dni' => 'required|string|max:50|unique:patients,dni,'.$patient->id,
            'date_of_birth' => 'required|date',
            'obra_social' => 'nullable|string|max:255',
            'plan' => 'nullable|string|max:255',
            'affiliate_number' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'iva_condition' => 'nullable|string|max:255',
            'nro_siniestro' => 'nullable|string|max:255',
            'director_id' => 'nullable|exists:users,id',
            'medical_notes' => 'nullable|string',
        ]);

        $patient->update($validated);

        return redirect()->route('patients.show', $patient)->with('success', 'Datos actualizados.');
    }

    public function destroy(Patient $patient)
    {
        if (!auth()->user()->hasRole('administrador')) {
            return redirect()->route('patients.index')->with('error', 'No tiene permisos para eliminar pacientes. Solo el Administrador puede realizar esta acción.');
        }

        // Here we could soft-delete to preserve medical records
        $patient->delete();
        return redirect()->route('patients.index')->with('success', 'Paciente eliminado.');
    }

    public function storeComment(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $patient->comments()->create([
            'body' => $validated['body'],
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('patients.show', ['patient' => $patient->id, 'tab' => 'comentarios'])->with('success', 'Comentario guardado.');
    }

    public function printHistory(Patient $patient)
    {
        $patient->load([
            'visits' => function($q) { clone $q->oldest(); }, 
            'visits.doctor'
        ]);
        
        // Cargar las visitas explícitamente ordenadas por oldest (la relación 'visits' tiene latest() definido)
        $visits = $patient->visits()->with('doctor')->oldest()->get();

        return view('patients.print', compact('patient', 'visits'));
    }

    /**
     * Phase 17: Motor analítico para determinar los hábitos de asistencia de un paciente
     */
    public function getHabits($id)
    {
        $patient = Patient::with('appointments')->find($id);
        
        if (!$patient || $patient->appointments->isEmpty()) {
            return response()->json(['message' => '']); // Sin historial
        }

        $daysCount = [];
        $shiftsCount = ['Mañana' => 0, 'Tarde' => 0];
        
        $dayNames = [
            0 => 'Domingos',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábados'
        ];

        foreach ($patient->appointments as $appt) {
            $date = \Carbon\Carbon::parse($appt->date);
            $dayOfWeek = $date->dayOfWeek;
            
            if (!isset($daysCount[$dayOfWeek])) {
                $daysCount[$dayOfWeek] = 0;
            }
            $daysCount[$dayOfWeek]++;

            $hour = (int) date('H', strtotime($appt->time));
            if ($hour < 14) {
                $shiftsCount['Mañana']++;
            } else {
                $shiftsCount['Tarde']++;
            }
        }

        // Find most frequent day
        arsort($daysCount);
        $topDayIndex = array_key_first($daysCount);
        $topDayName = $dayNames[$topDayIndex];

        // Find most frequent shift
        $topShift = $shiftsCount['Mañana'] >= $shiftsCount['Tarde'] ? 'Mañana' : 'Tarde';

        return response()->json([
            'message' => "El paciente suele atenderse los {$topDayName} por la {$topShift}."
        ]);
    }

    /**
     * AI Clinical Assistant: Analyze Patient History using Groq API
     */
    public function analyzeHistory(Request $request, Patient $patient)
    {
        $apiKey = env('GROQ_API_KEY');
        
        if (empty($apiKey) || $apiKey === 'tu_clave_aqui') {
            return response()->json([
                'success' => false,
                'message' => 'API Key de Groq Cloud no configurada. Por favor, agregue GROQ_API_KEY en su archivo .env.'
            ], 400);
        }

        $patient->load(['visits', 'surgeries']);

        // Build the prompt context
        $age = \Carbon\Carbon::parse($patient->date_of_birth)->age;
        
        $context = "Paciente: {$patient->first_name} {$patient->last_name}. Edad: {$age} años. Sexo: No especificado. Obra Social: {$patient->obra_social}.\n";
        $context .= "Antecedentes Médicos: " . ($patient->medical_notes ?? 'Ninguno') . "\n\n";
        
        $context .= "--- HISTORIAL DE VISITAS ---\n";
        if ($patient->visits->count() > 0) {
            foreach ($patient->visits->take(5) as $visit) {
                $date = $visit->created_at->format('Y-m-d');
                $context .= "- Fecha: $date\n  Motivo: {$visit->motivo_consulta}\n  Diagnóstico: {$visit->diagnostico}\n  Tratamiento: {$visit->tratamiento_oftalmologico}\n";
            }
        } else {
            $context .= "Sin visitas registradas.\n";
        }

        $context .= "\n--- HISTORIAL QUIRÚRGICO ---\n";
        if ($patient->surgeries->count() > 0) {
            foreach ($patient->surgeries as $surg) {
                $date = $surg->surgery_date->format('Y-m-d');
                $context .= "- Fecha: $date | Ojo: {$surg->eye} | Notas: {$surg->notes}\n";
            }
        } else {
            $context .= "Sin cirugías registradas.\n";
        }

        $systemPrompt = "Eres un Asistente Médico de IA especializado en oftalmología (Ateneo Clínico). Tu objetivo es analizar el siguiente historial de un paciente y proveer un breve y estructurado informe. 
Debes incluir:
1. Un resumen breve del estado del paciente.
2. Una evaluación o pronóstico basado en los datos.
3. Recomendaciones sobre próximos pasos a seguir clínicamente.
4. ¿Falta algún estudio u observación importante?
Formatea tu respuesta en texto plano ordenado, usando viñetas. Se conciso y profesional, dirigido a médicos.";

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.3-70b-versatile',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Historial del Paciente:\n\n" . $context]
                ],
                'temperature' => 0.4,
                'max_tokens' => 1024
            ]);

            if ($response->successful()) {
                $aiResponse = $response->json()['choices'][0]['message']['content'];
                
                // Save it as a comment so it persists in the patient's record
                $patient->comments()->create([
                    'body' => "[Análisis Clínico IA]\n" . $aiResponse,
                    'user_id' => auth()->id() ?? 1, // fallback if needed
                ]);

                return response()->json([
                    'success' => true,
                    'analysis' => $aiResponse
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error en la respuesta de la IA: ' . $response->body()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión con la IA: ' . $e->getMessage()
            ], 500);
        }
    }
}
