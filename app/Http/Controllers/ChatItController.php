<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ChatItController extends Controller
{
    public function index()
    {
        return view('chatit.index');
    }

    public function ask(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000'
        ]);

        $apiKey = env('GROQ_API_KEY');
        
        if (empty($apiKey) || $apiKey === 'tu_clave_aqui') {
            return response()->json([
                'error' => 'API Key de Groq Cloud no configurada. Por favor, agregue su clave GROQ_API_KEY en el archivo .env ubicado en la raíz del proyecto.'
            ], 400);
        }

        $user = Auth::user();
        
        // --- 1. RAG: Extracción de Estadísticas Reales ---
        // Extracción de palabras frecuentes del diagnóstico
        $diagnosticosRaw = Visit::whereNotNull('diagnostico')
            ->where('doctor_id', $user->id)
            ->pluck('diagnostico');
            
        $diagnosticosList = collect();
        foreach($diagnosticosRaw as $d) {
            $words = explode(',', $d);
            foreach($words as $w) {
                $clean = trim(mb_strtolower(preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/u', '', $w)));
                if (strlen($clean) > 4 && !in_array($clean, ['paciente', 'presenta', 'ambos', 'ojos', 'derecho', 'izquierdo'])) {
                    $diagnosticosList->push($clean);
                }
            }
        }
        $topDiagnosticos = $diagnosticosList->countBy()->sortDesc()->take(7)->map(function($count, $word) {
            return ucfirst($word) . " (" . $count . "x)";
        })->implode(', ');
        
        if(empty($topDiagnosticos)) $topDiagnosticos = "Aún sin datos consistentes.";

        // --- 1. RAG: Extracción de Estadísticas Reales ---
        if ($user->hasRole('administrador')) {
            $totalPacientes = Patient::count();
            $consultasMesActual = Visit::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();
            $turnosProximos = Appointment::where('date', '>=', Carbon::today()->format('Y-m-d'))
                ->where('date', '<=', Carbon::today()->addDays(7)->format('Y-m-d'))
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();
        } else {
            // Total Pacientes
            $totalPacientes = Patient::where('director_id', $user->id)->count();
            if ($totalPacientes == 0) {
                $totalPacientes = Visit::where('doctor_id', $user->id)->distinct('patient_id')->count('patient_id');
            }

            // Consultas mes
            $consultasMesActual = Visit::where('doctor_id', $user->id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();

            // Turnos agendados preventivamente
            $turnosProximos = Appointment::where('doctor_id', $user->id)
                ->where('date', '>=', Carbon::today()->format('Y-m-d'))
                ->where('date', '<=', Carbon::today()->addDays(7)->format('Y-m-d'))
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();
        }

        $ultimoPacienteObj = Visit::with('patient')->orderBy('created_at', 'desc')->first();
        $ultimoPaciente = $ultimoPacienteObj && $ultimoPacienteObj->patient ? $ultimoPacienteObj->patient->first_name . ' ' . $ultimoPacienteObj->patient->last_name . ' (' . ($ultimoPacienteObj->created_at ? \Carbon\Carbon::parse($ultimoPacienteObj->created_at)->format('d/m/Y H:i') : '') . ') - Diagnóstico: ' . ($ultimoPacienteObj->diagnostico ?? 'No registrado') : 'Ninguno';

        $diasMapa = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 7=>'Domingo'];
        $doctoresString = "";
        $schedules = DoctorSchedule::with('doctor')->get()->groupBy('doctor_id');
        foreach($schedules as $docId => $docSchedules) {
             if($docSchedules->first() && $docSchedules->first()->doctor) {
                 $docName = $docSchedules->first()->doctor->name;
                 $dias = $docSchedules->map(function($s) use ($diasMapa) {
                     return $diasMapa[$s->day_of_week] . " de " . substr($s->start_time, 0, 5) . " a " . substr($s->end_time, 0, 5);
                 })->implode(', ');
                 $doctoresString .= " $docName ($dias).";
             }
        }

        // --- 2. Armado del Contexto (System Instruction) ---
        $assistantName = 'Secretaria Médica IA';
        $assistantRole = 'secretaria médica y asistente corporativa interactiva';
        
        $memoryFilePath = storage_path('app/ai_memory/' . $user->id . '_memory.txt');
        $userMemory = file_exists($memoryFilePath) ? file_get_contents($memoryFilePath) : 'Ninguna regla o preferencia personal guardada todavía.';

        $now = Carbon::now('America/Argentina/Buenos_Aires');
        $hoy = $now->format('Y-m-d');
        $horaActual = $now->format('H:i');
        
        $proximosTurnosHoy = \App\Models\Appointment::with('patient')
            ->where('doctor_id', $user->id)
            ->where('date', $hoy)
            ->where('time', '>=', $now->format('H:i:00'))
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('time', 'asc')
            ->take(5)
            ->get();
            
        $agendaHoyStr = "";
        if ($proximosTurnosHoy->isEmpty()) {
            $agendaHoyStr = "No hay más pacientes agendados para lo que resta de hoy.";
        } else {
            foreach ($proximosTurnosHoy as $t) {
                $pName = $t->patient ? $t->patient->first_name . " " . $t->patient->last_name : "Paciente Eliminado";
                $agendaHoyStr .= "- A las " . substr($t->time, 0, 5) . " hrs: " . $pName . " (" . ($t->reason ?? 'Sin motivo') . ").\n";
            }
        }

        $contextString = "Eres '$assistantName', $assistantRole de la clínica Eleven Med.\n"
            . "Te estás comunicando por voz con el doctor: {$user->name}. Eres altamente profesional, ejecutiva y sobria. Tus respuestas deben ser extremadamente sintéticas, de lenguaje institucional amigable pero preciso.\n\n"
            . "--- EXPERTISE CLÍNICO ---\n"
            . "Tienes conocimientos médicos avanzados, con especialización experta en Oftalmología. Si el médico te hace una pregunta médica general o te pide recomendaciones de tratamiento, RESPONDE CON TU OPINIÓN CLÍNICA DIRECTA como un colega médico o interconsultor experto.\n"
            . "REGLA DE ORO: EL USUARIO CON EL QUE HABLAS YA ES EL OFTALMÓLOGO TITULAR. TIENES ESTRICTAMENTE PROHIBIDO decir frases como 'consulte con un oftalmólogo', 'busque atención médica' o dar disclaimers legales. Asume tu rol de Inteligencia Clínica y dale la información u opinión que el doctor pide sin vueltas.\n\n"
            . "--- CONTEXTO ---\n"
            . "=> Fecha Actual (HOY): {$hoy}. Hora Actual: {$horaActual}. (Zona horaria: Buenos Aires, Argentina).\n"
            . "=> DÍAS LABORALES DEL STAFF:$doctoresString\n"
            . "=> Pacientes Totales: {$totalPacientes}. Consultas mes: {$consultasMesActual}. Turnos semana: {$turnosProximos}.\n"
            . "=> Último paciente atendido históricamente: {$ultimoPaciente}\n"
            . "=> AGENDA DE HOY (Próximos 5 pacientes): \n{$agendaHoyStr}\n"
            . "--- TUS RECUERDOS Y PREFERENCIAS PARA ESTE USUARIO ---\n"
            . "Aquí están las notas que has guardado sobre este médico. DEBES obedecer a rajatabla estos comportamientos si existen:\n"
            . "{$userMemory}\n\n"
            . "REGLAS DE OPERACIÓN (CRÍTICAS):\n"
            . "1. Usa la herramienta 'lookup_patient_history' para buscar antecedentes de un paciente. SI USAS ESTA HERRAMIENTA, LUEGO HAZ EL RESUMEN CLÍNICO DETALLADO QUE EL MÉDICO TE PIDIÓ Y RESPONDE SU PREGUNTA.\n"
            . "2. ASISTENTE DE VISITAS (WIZARD): Si el médico dice 'necesito registrar visita' o 'anotar consulta', NO LLAMES A LA HERRAMIENTA DE INMEDIATO. Debes actuar como un asistente paso a paso:\n"
            . "   - Paso 1: Pregúntale '¿Cuál es el nombre del paciente?'. Espera su respuesta.\n"
            . "   - Paso 2: Luego pregúntale '¿Cuál fue el motivo de consulta?'. Espera su respuesta.\n"
            . "   - Paso 3: Luego pregúntale '¿Cuál es el diagnóstico?'. Espera su respuesta.\n"
            . "   - Paso 4: Luego pregúntale '¿Cuál es el tratamiento indicado?'. Espera su respuesta.\n"
            . "   - Paso 5: Al final dile '¿Desea guardar la consulta ahora?'. Si dice que SÍ, ENTONCES y SOLO ENTONCES llama a la herramienta 'create_patient_visit' con todos los datos recopilados.\n"
            . "3. Usa la herramienta 'schedule_patient_appointment' SIEMPRE que pidan turnos. En tu primer llamado, usa siempre confirm_save=false para leer silenciosamente la disponibilidad. Luego comunícale verbalmente las sugerencias al médico. Solo cuando él acepte, llama la herramienta con confirm_save=true.\n"
            . "4. Usa la herramienta 'memorize_information' CUANDO el médico te pida aprender una orden persistente.\n"
            . "5. Usa la herramienta 'get_clinic_statistics' CUANDO te pidan estadísticas globales numéricas y necesites un resumen masivo.\n"
            . "6. Usa la herramienta 'check_doctor_agenda' CUANDO el médico pregunte por la agenda de un día específico (ej: mañana o una fecha puntual).\n"
            . "7. Usa la herramienta 'check_waiting_room' CUANDO pregunten quién está en la sala de espera, tiempos de dilatación, espera o qué pacientes hay en la clínica ahora mismo.\n"
            . "IMPORTANTE SOBRE GRÁFICOS: Si el médico te pide un GRÁFICO, y tienes los datos, DEBES dibujar el gráfico usando ESTRICTAMENTE este código en tu texto: [CHART:pie|Titulo|Etiqueta1:Valor1,Etiqueta2:Valor2].\n"
            . "IMPORTANTE SOBRE IMÁGENES/ESTUDIOS: Si el doctor te pide ver un estudio, el historial de paciente te devolverá códigos Markdown (ej: ![OCT](/storage...)). DEBES escribir ese código exacto en tu respuesta para que la imagen aparezca visualmente.\n"
            . "ADVERTENCIA DE SISTEMA: Tienes estrictamente prohibido escribir diccionarios JSON, bloques de código, o variables técnicas en tus mensajes de voz al usuario. Las herramientas debes llamarlas nativamente por el protocolo API, nunca escribiendo datos crudos en tu texto.";

        // --- 3. Ejecución de GROQ REST API (OpenAI Standard) ---
        $url = "https://api.groq.com/openai/v1/chat/completions";
        
        $tools = [
            [
                "type" => "function",
                "function" => [
                    "name" => "lookup_patient_history",
                    "description" => "Llama a esta función para buscar en la Base de Datos privada de la clínica la historia de un paciente, sus cirugías, atenciones y próximos turnos agendados.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "nombre_paciente" => [
                                "type" => "string",
                                "description" => "Nombre, apellido o DNI del paciente a buscar (ej: 'Alejandro Lo Presti', 'Valentina', '30137102')"
                            ]
                        ],
                        "required" => ["nombre_paciente"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "create_patient_visit",
                    "description" => "Anota permanentemente una Visita Clínica en la base de datos MySQL de Eleven Med. Llámala SOLO cuando el doctor ya te haya dicho todos los datos requeridos.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "nombre_paciente" => [ "type" => "string", "description" => "Nombre completo del paciente" ],
                            "motivo_consulta" => [ "type" => "string", "description" => "Por qué vino a consultar." ],
                            "diagnostico" => [ "type" => "string", "description" => "Patología detectada o estado de salud" ],
                            "tratamiento" => [ "type" => "string", "description" => "Tratamiento / Recta indicada" ]
                        ],
                        "required" => ["nombre_paciente", "motivo_consulta", "diagnostico"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "schedule_patient_appointment",
                    "description" => "Ejecuta esta función para revisar agenda o confirmar un turno médico real.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "nombre_paciente" => [ "type" => "string", "description" => "Nombre del paciente" ],
                            "doctor_name" => [ "type" => "string", "description" => "Nombre o apellido del médico al que se le reserva (Ej: Cortalezzi). Si no se dice, pon vacio." ],
                            "fecha_deseada" => [ "type" => "string", "description" => "Fecha OBLIGATORIAMENTE en formato: YYYY-MM-DD." ],
                            "hora_deseada" => [ "type" => "string", "description" => "Hora concreta (HH:MM). Si no dio hora, usa '00:00'." ],
                            "confirm_save" => [ "type" => "boolean", "description" => "false para buscar disponibilidad en agenda. true SOLO para grabar definitivamente. No false en true a no ser que el usuario responda que SÍ expresamente." ]
                        ],
                        "required" => ["nombre_paciente", "fecha_deseada", "hora_deseada", "confirm_save"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "memorize_information",
                    "description" => "Llama a esta función cuando el médico te pida que recuerdes una regla, dato, preferencia o instrucción personal a largo plazo (ej: 'recuerda que me gusta que me digas jefe', 'acuerdate que no atiendo PAMI', etc).",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "informacion" => [ "type" => "string", "description" => "El dato o regla exacta a guardar en la libreta personal del doctor." ]
                        ],
                        "required" => ["informacion"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "get_clinic_statistics",
                    "description" => "Obtiene todas las estadísticas avanzadas de la clínica: cantidad de pacientes por obra social, totales, etc. Úsala cuando pidan reportes numéricos o gráficos.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => (object)[]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "check_waiting_room",
                    "description" => "Consulta el estado en vivo de la Sala de Espera de la clínica. Llama a esta función para saber quiénes están, en qué estado se encuentran (Dilatación, Atención Médica, etc.) y cuánto tiempo llevan esperando.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => (object)[]
                    ]
                ]
            ]
        ];

        $messages = [
            ["role" => "system", "content" => $contextString]
        ];

        // Inyectar historial conversacional (memoria) del Frontend
        $historyData = $request->input('history');
        if (is_array($historyData)) {
            foreach ($historyData as $msg) {
                if (isset($msg['role']) && isset($msg['content'])) {
                    $messages[] = ["role" => $msg['role'], "content" => $msg['content']];
                }
            }
        }
        
        $messages[] = ["role" => "user", "content" => $request->prompt];

        // Guardar el historial completo actualizado en un archivo JSON para que el administrador pueda verlo
        $chatDir = storage_path('app/ai_chats');
        if (!file_exists($chatDir)) {
            mkdir($chatDir, 0755, true);
        }
        
        $fullHistory = is_array($historyData) ? $historyData : [];
        $fullHistory[] = ["role" => "user", "content" => $request->prompt];
        file_put_contents($chatDir . '/' . $user->id . '_chat.json', json_encode($fullHistory));

        $payload = [
            "model" => "llama-3.1-8b-instant", // Usamos modelo instantáneo para evitar el límite de 100k tokens por día del modelo 70b
            "messages" => $messages,
            "tools" => $tools,
            "tool_choice" => "auto",
            "temperature" => 0.5,
            "max_tokens" => 1024
        ];

        try {
            // Groq uses Bearer tokens like OpenAI!
            $response = Http::withToken($apiKey)->timeout(30)->post($url, $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                $messageResponse = $data['choices'][0]['message'] ?? null;

                // Si la IA decide usar la herramienta, frena su respuesta
                if (isset($messageResponse['tool_calls'])) {
                    $toolCall = $messageResponse['tool_calls'][0];
                    $funcName = $toolCall['function']['name'];
                    
                    if ($funcName === 'lookup_patient_history') {
                        $args = json_decode($toolCall['function']['arguments'], true);
                        $searchQuery = $args['nombre_paciente'] ?? '';
                        
                        $patientResult = $this->findPatientFuzzy($searchQuery);
                        $targetPatientBase = $patientResult->count() === 1 ? $patientResult->first() : null;

                        $functionResponseData = [];
                        if ($targetPatientBase) {
                            $patientResultEager = Patient::with(['visits' => function($q) { $q->latest()->take(10); }, 'appointments' => function($q) { $q->where('date', '>=', date('Y-m-d')); }, 'surgeries' => function($q) { $q->latest(); }, 'studies' => function($q) { $q->latest(); }])->find($targetPatientBase->id);

                            $history = $patientResultEager->visits->map(function($v) {
                                return "Fecha: " . $v->created_at->format('d/m/Y') . " -> Diagnostico: " . ($v->diagnostico ?? 'S/D') . ". Motivo: " . ($v->motivo_consulta ?? 'S/D');
                            })->implode(' || ');
                            
                            $futurosTurnos = $patientResultEager->appointments->map(function($a) {
                                return "Turno pendiente el: {$a->date} a las {$a->time}";
                            })->implode(' || ');

                            $cirugias = $patientResultEager->surgeries->map(function($s) {
                                return "Cirugía ojo {$s->eye} el {$s->surgery_date->format('d/m/Y')}. Notas: " . ($s->notes ?? 'N/A');
                            })->implode(' || ');

                            $estudios = $patientResultEager->studies->map(function($s) {
                                $path = \Illuminate\Support\Facades\Storage::url($s->file_path);
                                return "Estudio: " . ($s->study_type ?? 'Estudio Médico') . " del " . $s->created_at->format('d/m/Y') . ". Para mostrar la imagen incrustada en tu respuesta usa este código markdown exacto: ![" . ($s->study_type ?? 'Imagen') . "](" . $path . ")";
                            })->implode(' || ');

                            $functionResponseData = [
                                "estado" => "éxito",
                                "paciente_encontrado_dni" => $patientResultEager->dni,
                                "paciente_encontrado_nombre" => mb_strtoupper($patientResultEager->last_name . ', ' . $patientResultEager->first_name),
                                "ultimo_historial_clinico" => empty($history) ? "Paciente nuevo, sin historia." : $history,
                                "proximos_turnos_agendados" => empty($futurosTurnos) ? "No tiene turnos futuros." : $futurosTurnos,
                                "historial_quirurgico" => empty($cirugias) ? "No tiene cirugías registradas." : $cirugias,
                                "estudios_medicos" => empty($estudios) ? "No tiene estudios cargados en el sistema. Avisar al doctor." : $estudios
                            ];
                        } elseif ($patientResult->count() > 1) {
                            $functionResponseData = ["estado" => "fallo", "error" => "Detecté múltiples pacientes parecidos a '{$searchQuery}'. Pídele al doctor el nombre más exacto posible."];
                        } else {
                            $functionResponseData = ["estado" => "fallo", "error" => "No se encontró ningún paciente asimilable al nombre '{$searchQuery}' en la sede."];
                        }

                        // --- 4. Double Trip (Segundo Request inyectando la respuesta de DB) ---
                        $secondPayload = $payload;
                        $secondPayload['tool_choice'] = 'none'; // Force text response, no more tool calls
                        
                        // Añadir la memoria de por qué estamos haciendo un segundo viaje
                        $secondPayload['messages'][] = $messageResponse;
                        $secondPayload['messages'][] = [
                            "role" => "tool",
                            "tool_call_id" => $toolCall['id'],
                            "name" => "lookup_patient_history",
                            "content" => json_encode($functionResponseData) // OpenAI Standard expects String for content!
                        ];

                        $res2 = Http::withToken($apiKey)->post($url, $secondPayload);
                        if ($res2->successful()) {
                            $data2 = $res2->json();
                            $finalReply = $data2['choices'][0]['message']['content'] ?? '';
                            
                            // Check if it's trying to call a tool again instead of responding
                            if (empty(trim($finalReply)) && isset($data2['choices'][0]['message']['tool_calls'])) {
                                $finalReply = "Recuperé la historia de la base de datos, pero la IA entró en un ciclo de búsqueda. Aquí tienes los datos en crudo:\n\n" . json_encode($functionResponseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            } elseif (empty(trim($finalReply))) {
                                // Forced fallback with the raw data so the user at least sees it!
                                $finalReply = "Aquí están los datos recuperados:\n\n" . $functionResponseData['ultimo_historial_clinico'] . "\n\n" . ($functionResponseData['estudios_medicos'] ?? '');
                            }
                            
                            return response()->json(['success' => true, 'reply' => $finalReply]);
                        } else {
                            return response()->json(['error' => 'Groq falló procesando tu historia de BD: ' . $res2->body()], 500);
                        }
                    } elseif ($funcName === 'create_patient_visit') {
                        $args = json_decode($toolCall['function']['arguments'], true);
                        $searchQuery = $args['nombre_paciente'] ?? '';
                        $patientResult = $this->findPatientFuzzy($searchQuery);

                        if ($patientResult->count() === 1) {
                            $targetPatient = $patientResult->first();
                            Visit::create([
                                'patient_id' => $targetPatient->id,
                                'doctor_id' => $user->id,
                                'motivo_consulta' => $args['motivo_consulta'] ?? 'Sin motivo',
                                'diagnostico' => $args['diagnostico'] ?? 'S/D',
                                'tratamiento_oftalmologico' => $args['tratamiento'] ?? null
                            ]);
                            $functionResponseData = ["estado" => "éxito", "mensaje" => "La visita se guardó exitosamente. Háblale al doctor de forma triunfal confirmándole que 'ya guardaste en su historia la evolución'."];
                        } elseif ($patientResult->count() > 1) {
                            $functionResponseData = ["estado" => "fallo", "error" => "Hay varios pacientes con ese nombre. Dile al médico que sea más específico."];
                        } else {
                            $functionResponseData = ["estado" => "fallo", "error" => "No encontré al paciente en el sistema DB de la Clínica. Pídele que intente otro nombre."];
                        }

                        // --- 4. Double Trip (Segundo Request) ---
                        $secondPayload = $payload;
                        $secondPayload['tool_choice'] = 'none';
                        $secondPayload['messages'][] = $messageResponse;
                        $secondPayload['messages'][] = [
                            "role" => "tool",
                            "tool_call_id" => $toolCall['id'],
                            "name" => "create_patient_visit",
                            "content" => json_encode($functionResponseData)
                        ];

                        $res2 = Http::withToken($apiKey)->post($url, $secondPayload);
                        if ($res2->successful()) {
                            $data2 = $res2->json();
                            $replyContent = $data2['choices'][0]['message']['content'] ?? '';
                            if (empty(trim($replyContent))) $replyContent = "Completé la tarea clínica solicitada, pero no pude generar una respuesta conversacional fluida.";
                            return response()->json(['success' => true, 'reply' => $replyContent]);
                        } else {
                            return response()->json(['error' => 'Error BD: ' . $res2->body()], 500);
                        }
                    } elseif ($funcName === 'schedule_patient_appointment') {
                        $args = json_decode($toolCall['function']['arguments'], true);
                        $searchQuery = $args['nombre_paciente'] ?? '';
                        
                        $docQuery = $args['doctor_name'] ?? '';
                        $cleanDocQ = str_replace(' ', '', mb_strtolower($docQuery));
                        
                        $isConfirm = filter_var($args['confirm_save'] ?? false, FILTER_VALIDATE_BOOLEAN);
                        $fechaDeseada = $args['fecha_deseada'] ?? Carbon::today()->format('Y-m-d');
                        $horaDeseada = $args['hora_deseada'] ?? '00:00';
                        
                        $patientResult = $this->findPatientFuzzy($searchQuery);

                        // Encontrar al Doctor (o fallback a Sí mismo)
                        if (!empty($cleanDocQ)) {
                            $docResult = User::whereRaw("LOWER(REPLACE(name, ' ', '')) LIKE ?", ["%{$cleanDocQ}%"])->get();
                            $targetDoc = $docResult->count() === 1 ? $docResult->first() : null;
                        } else {
                            $targetDoc = $user;
                        }

                        if (!$targetDoc) {
                             $functionResponseData = ["estado" => "fallo", "error" => "No encontré al doctor {$docQuery} en el sistema. Pídele al usuario que asocie mejor el apellido del médico."];
                        } elseif ($patientResult->count() === 1) {
                            $targetPatient = $patientResult->first();

                            if ($isConfirm) {
                                // Grabar definitvo
                                Appointment::create([
                                    'patient_id' => $targetPatient->id,
                                    'doctor_id' => $targetDoc->id,
                                    'created_by' => $user->id,
                                    'date' => $fechaDeseada,
                                    'time' => substr($horaDeseada, 0, 5) . ':00',
                                    'duration_minutes' => 15,
                                    'status' => 'pending',
                                    'reason' => 'Turno Agendado IA Mariana'
                                ]);
                                $functionResponseData = ["estado" => "éxito", "mensaje" => "Turno perfectamente GRABADO. Háblale al doctor de forma cordial confirmándole el turno grabado en la agenda del doctor {$targetDoc->name}."];
                            } else {
                                // Verificador Lógico (Consultor)
                                $agendaResult = $this->getNextAvailableSlots($targetDoc->id, $fechaDeseada, $horaDeseada);
                                $functionResponseData = ["estado" => "verificacion_agenda", "resultado_calendario" => $agendaResult];
                            }
                        } elseif ($patientResult->count() > 1) {
                            $functionResponseData = ["estado" => "fallo", "error" => "Hay varios pacientes con el mismo nombre. Pídele al doctor indicación precisa."];
                        } else {
                            $functionResponseData = ["estado" => "fallo", "error" => "No encontré al paciente. Pídele que intente otro nombre."];
                        }

                        // --- Double Trip para Agenda ---
                        $secondPayload = $payload;
                        $secondPayload['tool_choice'] = 'none';
                        $secondPayload['messages'][] = $messageResponse;
                        $secondPayload['messages'][] = [
                            "role" => "tool",
                            "tool_call_id" => $toolCall['id'],
                            "name" => "schedule_patient_appointment",
                            "content" => json_encode($functionResponseData)
                        ];

                        $res2 = Http::withToken($apiKey)->post($url, $secondPayload);
                        if ($res2->successful()) {
                            $data2 = $res2->json();
                            $replyContent = $data2['choices'][0]['message']['content'] ?? '';
                            if (empty(trim($replyContent))) $replyContent = "He analizado el calendario y procesado el turno, pero la inteligencia artificial no devolvió una respuesta textual.";
                            return response()->json(['success' => true, 'reply' => $replyContent]);
                        } else {
                            return response()->json(['error' => 'Error BD Agenda: ' . $res2->body()], 500);
                        }
                    } elseif ($funcName === 'get_clinic_statistics') {
                        $counts = \App\Models\Patient::select('obra_social', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                            ->whereNotNull('obra_social')
                            ->where('obra_social', '!=', '')
                            ->groupBy('obra_social')
                            ->get();
                        
                        $formatted = [];
                        foreach ($counts as $row) {
                            $formatted[$row->obra_social] = $row->total;
                        }
                        
                        $total = \App\Models\Patient::count();
                        
                        $functionResponseData = [
                            "estado" => "éxito",
                            "total_pacientes_registrados" => $total,
                            "datos_pacientes_por_obra_social" => $formatted
                        ];

                        $secondPayload = $payload;
                        $secondPayload['tool_choice'] = 'none';
                        $secondPayload['messages'][] = $messageResponse;
                        $secondPayload['messages'][] = [
                            "role" => "tool",
                            "tool_call_id" => $toolCall['id'],
                            "name" => "get_clinic_statistics",
                            "content" => json_encode($functionResponseData)
                        ];

                        $res2 = Http::withToken($apiKey)->post($url, $secondPayload);
                        if ($res2->successful()) {
                            $finalReply = $res2->json()['choices'][0]['message']['content'] ?? '';
                            if (empty(trim($finalReply))) $finalReply = "Aquí están los datos del sistema en crudo:\n\n" . json_encode($functionResponseData);
                            return response()->json(['success' => true, 'reply' => $finalReply]);
                        } else {
                            return response()->json(['error' => 'Error en métricas AI.'], 500);
                        }
                    } elseif ($funcName === 'memorize_information') {
                        $args = json_decode($toolCall['function']['arguments'], true);
                        $info = $args['informacion'] ?? '';
                        
                        $memoryDir = storage_path('app/ai_memory');
                        if (!file_exists($memoryDir)) {
                            mkdir($memoryDir, 0755, true);
                        }
                        $memoryFilePath = $memoryDir . '/' . $user->id . '_memory.txt';
                        $timestamp = date('d/m/Y H:i');
                        file_put_contents($memoryFilePath, "- [{$timestamp}] {$info}\n", FILE_APPEND);

                        $functionResponseData = ["estado" => "éxito", "mensaje" => "La regla personal se grabó en la memoria del doctor. Dile que de ahora en adelante siempre recordarás eso."];

                        // --- Double Trip para Memoria ---
                        $secondPayload = $payload;
                        $secondPayload['tool_choice'] = 'none';
                        $secondPayload['messages'][] = $messageResponse;
                        $secondPayload['messages'][] = [
                            "role" => "tool",
                            "tool_call_id" => $toolCall['id'],
                            "name" => "memorize_information",
                            "content" => json_encode($functionResponseData)
                        ];

                        $res2 = Http::withToken($apiKey)->post($url, $secondPayload);
                        if ($res2->successful()) {
                            $data2 = $res2->json();
                            $replyContent = $data2['choices'][0]['message']['content'] ?? '';
                            return response()->json(['success' => true, 'reply' => $replyContent]);
                        } else {
                            return response()->json(['error' => 'Error BD Memoria: ' . $res2->body()], 500);
                        }
                    } elseif ($funcName === 'check_doctor_agenda') {
                        $args = json_decode($toolCall['function']['arguments'], true);
                        $fecha = $args['fecha'] ?? \Carbon\Carbon::today()->format('Y-m-d');
                        
                        $turnos = \App\Models\Appointment::with('patient')
                            ->where('doctor_id', $user->id)
                            ->where('date', $fecha)
                            ->whereIn('status', ['pending', 'confirmed'])
                            ->orderBy('time', 'asc')
                            ->get();
                            
                        if ($turnos->isEmpty()) {
                            $functionResponseData = ["estado" => "éxito", "mensaje" => "No tienes pacientes agendados para la fecha $fecha."];
                        } else {
                            $lista = [];
                            foreach ($turnos as $t) {
                                $pName = $t->patient ? $t->patient->first_name . " " . $t->patient->last_name : "Paciente Eliminado";
                                $lista[] = "A las " . substr($t->time, 0, 5) . " hrs: " . $pName . " (Motivo: " . ($t->reason ?? 'Sin motivo') . ")";
                            }
                            $functionResponseData = [
                                "estado" => "éxito", 
                                "fecha" => $fecha,
                                "total_pacientes" => $turnos->count(),
                                "agenda" => $lista
                            ];
                        }

                        $secondPayload = $payload;
                        $secondPayload['tool_choice'] = 'none';
                        $secondPayload['messages'][] = $messageResponse;
                        $secondPayload['messages'][] = [
                            "role" => "tool",
                            "tool_call_id" => $toolCall['id'],
                            "name" => "check_doctor_agenda",
                            "content" => json_encode($functionResponseData)
                        ];

                        $res2 = Http::withToken($apiKey)->post($url, $secondPayload);
                        if ($res2->successful()) {
                            $replyContent = $res2->json()['choices'][0]['message']['content'] ?? '';
                            if (empty(trim($replyContent))) $replyContent = "Aquí tienes la información de la agenda:\n\n" . json_encode($functionResponseData['agenda'] ?? $functionResponseData, JSON_UNESCAPED_UNICODE);
                            return response()->json(['success' => true, 'reply' => $replyContent]);
                        } else {
                            return response()->json(['error' => 'Error consultando agenda: ' . $res2->body()], 500);
                        }
                    } elseif ($funcName === 'check_waiting_room') {
                        $activePatients = Patient::whereHas('assignments', function ($q) {
                                $q->whereDate('created_at', Carbon::today())
                                  ->whereIn('status', ['in_progress', 'pending']);
                            })
                            ->with(['assignments' => function ($q) {
                                $q->whereDate('created_at', Carbon::today())->orderBy('id', 'asc');
                            }])
                            ->get();
                            
                        if ($activePatients->isEmpty()) {
                            $functionResponseData = ["estado" => "éxito", "mensaje" => "La sala de espera está completamente vacía en este momento."];
                        } else {
                            $lista = [];
                            $now = Carbon::now();
                            foreach ($activePatients as $p) {
                                $activeEvent = $p->assignments->where('status', 'in_progress')->first();
                                $currentStatus = $activeEvent ? $activeEvent->event_type : 'En Cola';
                                $timeInStatus = $activeEvent && $activeEvent->started_at ? $activeEvent->started_at->diffInMinutes($now) . " min" : "N/A";
                                
                                $totals = 0;
                                foreach($p->assignments as $a) {
                                    if ($a->status === 'completed' && $a->started_at && $a->ended_at) {
                                        $totals += $a->started_at->diffInMinutes($a->ended_at);
                                    }
                                }
                                $totalTime = $totals + ($activeEvent && $activeEvent->started_at ? $activeEvent->started_at->diffInMinutes($now) : 0);
                                
                                $lista[] = "Paciente: {$p->first_name} {$p->last_name} | Estado Actual: {$currentStatus} (hace {$timeInStatus}) | Tiempo Total en Clínica: {$totalTime} min";
                            }
                            $functionResponseData = [
                                "estado" => "éxito",
                                "total_pacientes_activos" => $activePatients->count(),
                                "detalle_sala_espera" => $lista
                            ];
                        }

                        $secondPayload = $payload;
                        $secondPayload['tool_choice'] = 'none';
                        $secondPayload['messages'][] = $messageResponse;
                        $secondPayload['messages'][] = [
                            "role" => "tool",
                            "tool_call_id" => $toolCall['id'],
                            "name" => "check_waiting_room",
                            "content" => json_encode($functionResponseData)
                        ];

                        $res2 = Http::withToken($apiKey)->post($url, $secondPayload);
                        if ($res2->successful()) {
                            $replyContent = $res2->json()['choices'][0]['message']['content'] ?? '';
                            if (empty(trim($replyContent))) $replyContent = "Aquí tienes los datos de la sala de espera:\n\n" . json_encode($functionResponseData['detalle_sala_espera'] ?? $functionResponseData);
                            return response()->json(['success' => true, 'reply' => $replyContent]);
                        } else {
                            return response()->json(['error' => 'Error consultando sala de espera: ' . $res2->body()], 500);
                        }
                    }
                } else {
                    // Respuesta normal conversacional (no requirió base de datos remota)
                    $replyContent = $messageResponse['content'] ?? '';
                    if (empty(trim($replyContent))) $replyContent = "Entendido, consulta terminada pero me quedé sin palabras.";
                    return response()->json(['success' => true, 'reply' => $replyContent]);
                }
            }

            if ($response->status() == 429) {
                return response()->json([
                    'error' => 'Has sobrepasado temporalmente la cuota de la Inteligencia Artificial. Espera y reintenta.'
                ], 429);
            }

            return response()->json([
                'error' => 'Error de respuesta de Groq Cloud: ' . $response->body()
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Fallo la conexión o timeout con los servidores de Groq Cloud.'
            ], 500);
        }
    }

    // --- HELPER FUNCTIONS PARA AGENDA INTELIGENTE ---
    private function getNextAvailableSlots($doctorId, $requestedDateStr, $requestedTimeStr) {
        try {
            // Manejar si la IA manda basura como fecha
            $date = Carbon::parse($requestedDateStr);
        } catch (\Exception $e) {
            $date = Carbon::today();
        }

        $schedules = DoctorSchedule::where('doctor_id', $doctorId)->get();
        if ($schedules->isEmpty()) {
            return "ERROR CRÍTICO: El doctor no tiene configurado ningún día laborable en su agenda del sistema. Pidele que entre a Configuración y fije sus días de atención primero.";
        }

        $dayOfWeekRequested = $date->dayOfWeekIso; // 1=Mon, 7=Sun
        $scheduleForDay = $schedules->firstWhere('day_of_week', $dayOfWeekRequested);

        if ($scheduleForDay) {
            $slots = $this->generateSlotsForDay($doctorId, $date, $scheduleForDay);
            $cleanRequestedTimeStr = substr($requestedTimeStr, 0, 5); // 10:00

            if ($cleanRequestedTimeStr !== '00:00' && $cleanRequestedTimeStr !== 'Cualq') {
               $slotExists = array_search($cleanRequestedTimeStr, array_column($slots, 'time')) !== false;
               if ($slotExists) {
                   return "DISPONIBLE Y LIBRE: El doctor atiende el {$date->format('Y-m-d')} y tiene libre a las {$cleanRequestedTimeStr}. ¡Puedes PREGUNTAR si graba el turno definitivamente!";
               } else {
                   $sugerencias = implode(', ', array_slice(array_column($slots, 'time'), 0, 3));
                   return "NO DISPONIBLE: Ese día atiende pero a las {$cleanRequestedTimeStr} ya está ocupado. Ofrecele sí o sí a las: {$sugerencias}";
               }
            } else {
               $sugerencias = implode(', ', array_slice(array_column($slots, 'time'), 0, 3));
               return "DISPONIBLE Y LIBRE: El doctor atiende ese día. Como no dio hora, dile que tiene libre a las: {$sugerencias} y pregúntale cuál prefiere grabar.";
            }
        }

        // Si llegó aquí: El doctor no atiende el dia solicitado. Buscar próximos 3 dias reales
        $suggestions = [];
        $checkDate = clone $date;
        $checkDate->addDay();
        $loops = 0;
        
        while (count($suggestions) < 3 && $loops < 30) {
            $loops++;
            $sched = $schedules->firstWhere('day_of_week', $checkDate->dayOfWeekIso);
            if ($sched) {
                $slots = $this->generateSlotsForDay($doctorId, $checkDate, $sched);
                if (count($slots) > 0) {
                    $suggestions[] = "Día " . $checkDate->format('Y-m-d') . " a las " . $slots[0]['time'];
                }
            }
            $checkDate->addDay();
        }

        return "NO DISPONIBLE: El doctor no trabaja los días " . $date->format('l') . ". Ofrécele SOLO ESTAS alternativas confirmadas: " . implode(' | ', $suggestions);
    }

    private function generateSlotsForDay($doctorId, Carbon $date, $schedule) {
        $slots = [];
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);
        $duration = $schedule->slot_duration_minutes;
        if($duration <= 0) $duration = 15;

        // Turnos Ocupados
        $busyTimes = Appointment::where('doctor_id', $doctorId)
                ->where('date', $date->format('Y-m-d'))
                ->whereIn('status', ['pending', 'confirmed'])
                ->pluck('time')
                ->map(function($t) { return substr($t, 0, 5); })
                ->toArray();

        $now = Carbon::now('America/Argentina/Buenos_Aires');

        while ($startTime < $endTime) {
            $timeString = $startTime->format('H:i');
            
            $isValid = true;
            // Si el turno que analizamos es de HOY pero su franja horaria ya pasó, lo bloqueamos.
            if ($date->isSameDay($now) && $startTime < $now) {
                $isValid = false;
            }

            if ($isValid && !in_array($timeString, $busyTimes)) {
                $slots[] = ['time' => $timeString];
            }
            $startTime->addMinutes($duration);
        }
        return $slots;
    }

    private function findPatientFuzzy($searchQuery) {
        $searchQueryOriginal = mb_strtolower(trim($searchQuery));
        $searchQueryClean = str_replace(' ', '', $searchQueryOriginal);
        $dniSearch = str_replace('dni', '', $searchQueryClean); // Extraer posible DNI
        
        $allPatients = Patient::all();
        
        $matchedPatients = $allPatients->filter(function($p) use ($searchQueryClean, $searchQueryOriginal, $dniSearch) {
            // Match DNI exacto o sub-string
            if (!empty($p->dni) && !empty($dniSearch)) {
                if (strpos($p->dni, $dniSearch) !== false || strpos($dniSearch, $p->dni) !== false) {
                    return true;
                }
            }

            $fullName1 = str_replace(' ', '', mb_strtolower($p->first_name . $p->last_name));
            $fullName2 = str_replace(' ', '', mb_strtolower($p->last_name . $p->first_name));
            
            // Match substring basico sin espacios
            if (strpos($fullName1, $searchQueryClean) !== false || strpos($fullName2, $searchQueryClean) !== false) {
               return true;
            }
            
            // Evaluacion de error de tipeo / asimilacion sonora (Levenshtein) si la longitud es util
            if (strlen($searchQueryClean) > 3 && abs(strlen($searchQueryClean) - strlen($fullName1)) <= 3) {
                if (levenshtein($searchQueryClean, $fullName1) <= 3) return true;
                if (levenshtein($searchQueryClean, $fullName2) <= 3) return true;
            }
            
            // Buscar por palabras clave, ideal si el usuario dictó dos nombres desordenados
            $pieces = explode(' ', $searchQueryOriginal);
            foreach($pieces as $piece) {
                if (strlen($piece) > 3) {
                     if (strpos($fullName1, $piece) !== false) return true;
                     
                     // Si el apellido era Presti y Google Voice mandó "preste", Levenshtein lo atrapa (distancia 1)
                     if (levenshtein($piece, mb_strtolower($p->first_name)) <= 2) return true;
                     if (levenshtein($piece, mb_strtolower($p->last_name)) <= 2) return true;
                     
                     // Separar apellidos compuestos
                     $dbPieces = explode(' ', mb_strtolower($p->last_name));
                     foreach ($dbPieces as $dbP) {
                         if (strlen($dbP) > 3 && levenshtein($piece, $dbP) <= 2) return true;
                     }
                }
            }
            return false;
        });

        return $matchedPatients->values();
    }
}
