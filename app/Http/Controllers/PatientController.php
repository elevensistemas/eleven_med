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
        
        $patients = Patient::with(['latestVisit.doctor', 'appointments:id,patient_id,notes'])
            ->withMin('visits as first_visit_at', 'created_at')
            ->withCount(['appointments as ausencias_count' => function ($query) {
                $query->where('status', 'no_show');
            }])
            ->when($search, function ($query, $search) {
                return $this->applyPatientSearch($query, $search);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        foreach ($patients as $p) {
            $reprog = 0;
            foreach ($p->appointments as $ap) {
                if (!empty($ap->notes)) {
                    $reprog += substr_count($ap->notes, 'Reprogramado');
                }
            }
            $p->reprogramaciones_count = $reprog;
        }
        
        return view('patients.index', compact('patients', 'search'));
    }

    /**
     * Comando / Herramienta para detectar fichas duplicadas por Teléfono, DNI, Apellido o Email.
     */
    public function duplicates(Request $request)
    {
        $criterion = $request->get('by', 'all'); // all | phone | dni | last_name | email
        $filterTerm = trim((string)$request->get('q', ''));

        $allPatients = Patient::withCount(['visits', 'surgeries', 'studies', 'appointments'])
            ->orderBy('id', 'asc')
            ->get();

        $groups = [];

        $matchesFilter = function ($collection) use ($filterTerm) {
            if ($filterTerm === '') return true;
            $needle = mb_strtolower($filterTerm, 'UTF-8');
            $needleDigits = preg_replace('/[^0-9]/', '', $filterTerm);
            foreach ($collection as $pt) {
                $hay = mb_strtolower(
                    ($pt->last_name ?? '') . ' ' . ($pt->first_name ?? '') . ' ' . ($pt->dni ?? '') . ' ' . ($pt->email ?? '') . ' ' . ($pt->phone ?? ''),
                    'UTF-8'
                );
                if (str_contains($hay, $needle)) return true;
                if ($needleDigits !== '' && strlen($needleDigits) >= 4) {
                    $pDigits = preg_replace('/[^0-9]/', '', ($pt->phone ?? '') . ($pt->dni ?? ''));
                    if (str_contains($pDigits, $needleDigits)) return true;
                }
            }
            return false;
        };

        // 1. Duplicados por DNI normalizado (solo dígitos)
        if ($criterion === 'all' || $criterion === 'dni') {
            $byDni = $allPatients->filter(function ($p) {
                $d = preg_replace('/[^0-9]/', '', (string)$p->dni);
                return strlen($d) >= 5;
            })->groupBy(function ($p) {
                return preg_replace('/[^0-9]/', '', (string)$p->dni);
            })->filter(fn($g) => $g->count() > 1);

            foreach ($byDni as $dniKey => $items) {
                if (!$matchesFilter($items)) continue;
                $groups[] = [
                    'type' => 'dni',
                    'badge' => 'DNI Coincidente',
                    'color' => 'danger',
                    'icon' => 'bi-card-text',
                    'key_label' => 'DNI: ' . $dniKey,
                    'patients' => $items->values(),
                ];
            }
        }

        // 2. Duplicados por Teléfono normalizado (últimos 8 dígitos para ignorar +54 / 0 / 15)
        if ($criterion === 'all' || $criterion === 'phone') {
            $byPhone = $allPatients->filter(function ($p) {
                $digits = preg_replace('/[^0-9]/', '', (string)$p->phone);
                return strlen($digits) >= 7;
            })->groupBy(function ($p) {
                $digits = preg_replace('/[^0-9]/', '', (string)$p->phone);
                return strlen($digits) > 8 ? substr($digits, -8) : $digits;
            })->filter(fn($g) => $g->count() > 1);

            foreach ($byPhone as $phoneKey => $items) {
                if (!$matchesFilter($items)) continue;
                $samplePhone = $items->first()->phone ?: $phoneKey;
                $groups[] = [
                    'type' => 'phone',
                    'badge' => 'Teléfono Coincidente',
                    'color' => 'primary',
                    'icon' => 'bi-telephone-fill',
                    'key_label' => 'Tel: ' . $samplePhone,
                    'patients' => $items->values(),
                ];
            }
        }

        // 3. Duplicados por Email
        if ($criterion === 'all' || $criterion === 'email') {
            $byEmail = $allPatients->filter(function ($p) {
                return !empty($p->email) && str_contains($p->email, '@');
            })->groupBy(function ($p) {
                return mb_strtolower(trim($p->email), 'UTF-8');
            })->filter(fn($g) => $g->count() > 1);

            foreach ($byEmail as $mailKey => $items) {
                if (!$matchesFilter($items)) continue;
                $groups[] = [
                    'type' => 'email',
                    'badge' => 'Email Coincidente',
                    'color' => 'info',
                    'icon' => 'bi-envelope-fill',
                    'key_label' => 'Email: ' . $mailKey,
                    'patients' => $items->values(),
                ];
            }
        }

        // 4. Duplicados por Apellido (y Nombre / Homónimos)
        if ($criterion === 'all' || $criterion === 'last_name') {
            if ($criterion === 'last_name') {
                // Agrupar por Apellido completo normalizado (muestra homónimos y familiares / posibles duplicados por apellido)
                $byLastName = $allPatients->filter(fn($p) => !empty(trim((string)$p->last_name)))
                    ->groupBy(function ($p) {
                        return mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string)$p->last_name)), 'UTF-8');
                    })
                    ->filter(fn($g) => $g->count() > 1);

                foreach ($byLastName as $lnKey => $items) {
                    if (!$matchesFilter($items)) continue;
                    $groups[] = [
                        'type' => 'last_name',
                        'badge' => 'Mismo Apellido',
                        'color' => 'warning',
                        'icon' => 'bi-people-fill',
                        'key_label' => 'Apellido: ' . $lnKey,
                        'patients' => $items->values(),
                    ];
                }
            } else {
                // En vista "Todos", agrupar por Apellido + Primer Nombre para máxima precisión sin ruido de familiares
                $byFullName = $allPatients->filter(fn($p) => !empty(trim((string)$p->last_name)))
                    ->groupBy(function ($p) {
                        $ln = mb_strtoupper(trim((string)$p->last_name), 'UTF-8');
                        $fn = mb_strtoupper(strtok(trim((string)$p->first_name), ' ') ?: '', 'UTF-8');
                        return $ln . ' | ' . $fn;
                    })
                    ->filter(fn($g) => $g->count() > 1);

                foreach ($byFullName as $fnKey => $items) {
                    if (!$matchesFilter($items)) continue;
                    $groups[] = [
                        'type' => 'last_name',
                        'badge' => 'Apellido y Nombre Coincidente',
                        'color' => 'warning',
                        'icon' => 'bi-person-vcard-fill',
                        'key_label' => $fnKey,
                        'patients' => $items->values(),
                    ];
                }
            }
        }

        return view('patients.duplicates', compact('groups', 'criterion', 'filterTerm'));
    }

    /**
     * Unificar una ficha duplicada secundaria dentro de la ficha principal conservando TODA su historia clínica.
     */
    public function mergeDuplicates(Request $request)
    {
        $validated = $request->validate([
            'primary_id' => 'required|exists:patients,id|different:duplicate_id',
            'duplicate_id' => 'required|exists:patients,id',
        ]);

        $primary = Patient::findOrFail($validated['primary_id']);
        $duplicate = Patient::findOrFail($validated['duplicate_id']);

        \Illuminate\Support\Facades\DB::transaction(function () use ($primary, $duplicate) {
            \App\Models\Visit::where('patient_id', $duplicate->id)->update(['patient_id' => $primary->id]);
            \App\Models\PatientSurgery::where('patient_id', $duplicate->id)->update(['patient_id' => $primary->id]);
            \App\Models\PatientStudy::where('patient_id', $duplicate->id)->update(['patient_id' => $primary->id]);
            \App\Models\Appointment::where('patient_id', $duplicate->id)->update(['patient_id' => $primary->id]);
            \App\Models\PatientAssignment::where('patient_id', $duplicate->id)->update(['patient_id' => $primary->id]);
            \App\Models\PatientComment::where('patient_id', $duplicate->id)->update(['patient_id' => $primary->id]);

            // Completar campos vacíos de la ficha principal con los datos de la duplicada
            foreach (['phone', 'email', 'obra_social', 'plan', 'affiliate_number', 'address', 'profession', 'recommendation'] as $field) {
                if (empty($primary->{$field}) && !empty($duplicate->{$field})) {
                    $primary->{$field} = $duplicate->{$field};
                }
            }
            if (!empty($duplicate->medical_notes) && $duplicate->medical_notes !== $primary->medical_notes) {
                $primary->medical_notes = trim(($primary->medical_notes ? $primary->medical_notes . "\n" : '') . $duplicate->medical_notes);
            }
            $primary->save();

            $duplicate->delete();
        });

        return back()->with('success', "Ficha #{$duplicate->id} unificada exitosamente dentro de {$primary->last_name}, {$primary->first_name} (#{$primary->id}). Se transfirieron todas sus consultas, cirugías, estudios y turnos.");
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
            'recommendation' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string',
        ]);

        if (!empty($validated['phone'])) { $validated['phone'] = self::formatArgentinePhone($validated['phone']); }
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

        $query = $this->applyPatientSearch(Patient::query(), $search);
                        
        // Si encontramos un único resultado, vamos directo a su ficha
        if ($query->count() === 1) {
            return redirect()->route('patients.show', $query->first());
        }

        // Si hay varios o cero, lo mandamos a la lista filtrada
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

        $query = $this->applyPatientSearch(Patient::query(), $search);

        $patients = $query->limit(15)
                        ->get(['id', 'first_name', 'last_name', 'dni', 'phone', 'code']);
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
            // Eliminar foto anterior si existe
            if ($patient->photo_path) {
                if (\Illuminate\Support\Str::startsWith($patient->photo_path, 'uploads/')) {
                    $oldPath = public_path($patient->photo_path);
                    if (file_exists($oldPath) && is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                } else {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($patient->photo_path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($patient->photo_path);
                    }
                }
            }

            $file = $request->file('photo');
            $filename = md5(uniqid() . time()) . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/patients/photos');
            
            // Mover físicamente el archivo sin usar los Disks de Laravel
            $file->move($destinationPath, $filename);
            
            $patient->photo_path = 'uploads/patients/photos/' . $filename;
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
     * Registra de forma persistente (14 días) la revisión/apertura de la ficha del paciente
     * para los relojes superiores del sistema.
     */
    private function pushToRecent(Patient $patient)
    {
        $nowIso = now()->toIso8601String();
        $cutoff = now()->subDays(14)->timestamp;

        // 1. Persistir en disco por 14 días (sobrevive cierres de navegador y cambios de día)
        try {
            $logPath = storage_path('app/recent_patient_views.json');
            $entries = [];
            if (file_exists($logPath)) {
                $decoded = json_decode(file_get_contents($logPath), true);
                if (is_array($decoded)) {
                    $entries = $decoded;
                }
            }

            // Filtrar entradas vencidas (> 14 días) y el mismo paciente para reinsertar arriba
            $entries = array_values(array_filter($entries, function ($item) use ($patient, $cutoff) {
                if (!isset($item['id'], $item['viewed_at'])) return false;
                if ((int)$item['id'] === (int)$patient->id) return false;
                $ts = strtotime($item['viewed_at']);
                return $ts && $ts >= $cutoff;
            }));

            array_unshift($entries, [
                'id' => $patient->id,
                'name' => $patient->last_name . ', ' . $patient->first_name,
                'dni' => $patient->dni,
                'viewed_at' => $nowIso,
                'user_id' => auth()->id(),
            ]);

            // Conservar hasta 200 fichas revisadas en los últimos 14 días
            $entries = array_slice($entries, 0, 200);
            @file_put_contents($logPath, json_encode($entries, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // Silencioso en caso de permisos
        }

        // 2. Mantener copia en sesión por compatibilidad
        $recent = session()->get('recent_patients', []);
        $recent = array_filter($recent, fn($p) => ($p['id'] ?? null) !== $patient->id);
        array_unshift($recent, [
            'id' => $patient->id,
            'name' => $patient->last_name . ', ' . $patient->first_name,
            'dni' => $patient->dni,
            'viewed_at' => $nowIso,
        ]);
        session()->put('recent_patients', array_slice($recent, 0, 30));
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
            'recommendation' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string',
        ]);

        if (!empty($validated['phone'])) { $validated['phone'] = self::formatArgentinePhone($validated['phone']); }
        $patient->update($validated);

        return redirect()->route('patients.show', $patient)->with('success', 'Datos actualizados.');
    }

    /**
     * Quick update for recommendation and medical notes from anywhere in the patient file or consult.
     */
    public function quickUpdate(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'recommendation' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string',
        ]);

        if ($request->has('recommendation')) {
            $patient->recommendation = $validated['recommendation'];
        }
        if ($request->has('medical_notes')) {
            $patient->medical_notes = $validated['medical_notes'];
        }
        $patient->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'recommendation' => $patient->recommendation,
                'medical_notes' => $patient->medical_notes,
                'message' => 'Ficha del paciente actualizada correctamente.'
            ]);
        }

        return back()->with('success', 'Ficha del paciente actualizada correctamente.');
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
            'visits.doctor',
            'surgeries.doctor'
        ]);
        
        // Cargar las visitas explícitamente ordenadas por oldest (la relación 'visits' tiene latest() definido)
        $visits = $patient->visits()->with('doctor')->oldest()->get();

        $surgeries = $patient->surgeries()->with('doctor')->oldest('surgery_date')->get();
        return view('patients.print', compact('patient', 'visits', 'surgeries'));
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
                'model' => config('services.groq.model') ?? env('GROQ_MODEL', 'openai/gpt-oss-120b'),
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

    /**
     * Enhanced universal patient search matching:
     * DNI, First Name, Last Name, Full Name, Phone, Email,
     * Recommendation / Deriving Doctor, Medical Notes / Antecedentes,
     * Folder Code or numeric ID.
     */
    protected function applyPatientSearch($query, $search)
    {
        if (!$search) return $query;
        $term = trim($search);

        $altPhone = null;
        if (preg_match('/^11([\s\-]*)(.*)$/', $term, $matches)) {
            $altPhone = '15' . $matches[1] . $matches[2];
        } elseif (preg_match('/^15([\s\-]*)(.*)$/', $term, $matches)) {
            $altPhone = '11' . $matches[1] . $matches[2];
        }

        $cleanDigits = preg_replace('/[^0-9]/', '', $term);

        return $query->where(function($q) use ($term, $altPhone, $cleanDigits) {
            $q->where('dni', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('first_name', 'like', "%{$term}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"])
              ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$term}%"])
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('recommendation', 'like', "%{$term}%")
              ->orWhere('medical_notes', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%");

            if ($cleanDigits && strlen($cleanDigits) <= 8) {
                $intVal = intval($cleanDigits);
                if ($intVal > 0) {
                    $q->orWhere('id', $intVal);
                }
            }

            if ($altPhone) {
                $q->orWhere('phone', 'like', "%{$altPhone}%");
            }
        });
    }

    /**
     * Format and standardize Argentine phone numbers to +54 9 [area] [num]
     * Eliminates leading 0 and redundant 15 so WhatsApp and dialers always succeed.
     */
    public static function formatArgentinePhone($phone)
    {
        if (!$phone) return null;
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (empty($digits)) return $phone;

        if (str_starts_with($digits, '549')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '54')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        if (str_starts_with($digits, '11') && substr($digits, 2, 2) === '15') {
            $digits = '11' . substr($digits, 4);
        } elseif (preg_match('/^(2\d{2,3}|3\d{2,3})15(\d{6,7})$/', $digits, $m)) {
            $digits = $m[1] . $m[2];
        }

        if (str_starts_with($digits, '11')) {
            return '+54 9 11 ' . substr($digits, 2);
        } elseif (preg_match('/^(2\d{2}|3\d{2})(.*)$/', $digits, $m)) {
            return '+54 9 ' . $m[1] . ' ' . $m[2];
        } elseif (preg_match('/^(2\d{3}|3\d{3})(.*)$/', $digits, $m)) {
            return '+54 9 ' . $m[1] . ' ' . $m[2];
        }

        return '+54 9 ' . $digits;
    }
}