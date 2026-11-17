<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappConfig;
use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class WhatsappConfigController extends Controller
{
    private function getServiceConfig(): array
    {
        $config = WhatsappConfig::first();
        $url = rtrim($config->api_url ?? config('services.whatsapp.url') ?? env('WHATSAPP_SERVICE_URL', 'http://127.0.0.1:3001'), '/');
        $key = $config->token ?? config('services.whatsapp.key') ?? env('WHATSAPP_SERVICE_KEY', 'eleven_med_secret_wa_key_2026');
        return [$url, $key];
    }

    private function bridgeFilePath(): string
    {
        return storage_path('app/whatsapp_bridge_state.json');
    }

    private function defaultBridgeState(): array
    {
        return [
            'last_heartbeat' => 0,
            'status' => 'offline',
            'phone' => null,
            'name' => null,
            'qr' => null,
            'command' => null,
            'outbox' => [],
            'acks' => [],
            'sent_reminders' => [],
            'last_reminder_check' => 0,
        ];
    }

    private function readBridgeState(): array
    {
        $path = $this->bridgeFilePath();
        if (!file_exists($path)) {
            return $this->defaultBridgeState();
        }
        $raw = @file_get_contents($path);
        $decoded = $raw ? json_decode($raw, true) : null;
        return is_array($decoded) ? array_merge($this->defaultBridgeState(), $decoded) : $this->defaultBridgeState();
    }

    private function mutateBridgeState(callable $callback): array
    {
        $path = $this->bridgeFilePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fp = @fopen($path, 'c+');
        if (!$fp) {
            $state = $this->readBridgeState();
            $updated = $callback($state);
            @file_put_contents($path, json_encode($updated, JSON_UNESCAPED_UNICODE));
            return $updated;
        }

        flock($fp, LOCK_EX);
        $contents = stream_get_contents($fp);
        $state = $contents ? json_decode($contents, true) : null;
        if (!is_array($state)) {
            $state = $this->defaultBridgeState();
        } else {
            $state = array_merge($this->defaultBridgeState(), $state);
        }

        $updated = $callback($state);

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($updated, JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $updated;
    }

    private function isBridgeOnline(array $state): bool
    {
        return !empty($state['last_heartbeat']) && (time() - (int)$state['last_heartbeat']) <= 15;
    }

    /**
     * Endpoint receptor del pulso saliente desde el .BAT de la PC del Consultorio (cada 2.5 seg).
     */
    public function bridgeSync(Request $request)
    {
        list($url, $expectedKey) = $this->getServiceConfig();
        $providedKey = $request->header('x-api-key') ?: $request->input('api_key');

        if ($expectedKey && $providedKey !== $expectedKey) {
            return response()->json(['error' => 'Unauthorized bridge key'], 401);
        }

        $status = (string)$request->input('status', 'disconnected');
        $phone = $request->input('phone');
        $name = $request->input('name');
        $qr = $request->input('qr');
        $incomingAcks = $request->input('acks', []);

        $responsePayload = ['command' => null, 'outbox' => []];

        $this->mutateBridgeState(function (array $state) use ($status, $phone, $name, $qr, $incomingAcks, &$responsePayload) {
            $now = time();
            $state['last_heartbeat'] = $now;
            $state['status'] = $status;
            $state['phone'] = $phone;
            $state['name'] = $name;
            $state['qr'] = ($status === 'qr_ready') ? $qr : null;

            // Procesar confirmaciones de envío desde la PC
            if (is_array($incomingAcks) && count($incomingAcks) > 0) {
                $ackedIds = [];
                foreach ($incomingAcks as $ack) {
                    if (!empty($ack['id'])) {
                        $ackedIds[] = $ack['id'];
                        $state['acks'][$ack['id']] = [
                            'success' => !empty($ack['success']),
                            'error' => $ack['error'] ?? null,
                            'at' => $now,
                        ];
                    }
                }
                $state['outbox'] = array_values(array_filter($state['outbox'], function ($item) use ($ackedIds) {
                    return !in_array($item['id'] ?? '', $ackedIds, true);
                }));
            }

            // Limpiar acks y mensajes expirados (> 10 min)
            $state['outbox'] = array_values(array_filter($state['outbox'], fn($m) => ($now - ($m['created_at'] ?? $now)) < 600));
            foreach ($state['acks'] as $aid => $ainfo) {
                if (($now - ($ainfo['at'] ?? $now)) > 300) {
                    unset($state['acks'][$aid]);
                }
            }

            // Encolar recordatorios automáticos de turnos si la línea está conectada y activa
            if ($status === 'connected' && ($now - (int)($state['last_reminder_check'] ?? 0)) >= 60) {
                $state['last_reminder_check'] = $now;
                $state = $this->enqueueDueReminders($state);
            }

            // Entregar comando pendiente una sola vez
            $responsePayload['command'] = $state['command'];
            $state['command'] = null;
            $responsePayload['outbox'] = $state['outbox'];

            return $state;
        });

        return response()->json($responsePayload);
    }

    /**
     * Encola recordatorios automáticos de turnos próximos cuando la regla está activa.
     */
    private function enqueueDueReminders(array $state): array
    {
        try {
            $config = WhatsappConfig::first();
            if (!$config || !$config->is_active) {
                return $state;
            }

            $reminderHm = substr((string)($config->reminder_time ?: '08:00'), 0, 5);
            if (now()->format('H:i') < $reminderHm) {
                return $state;
            }

            $daysBefore = max(1, (int)($config->reminder_days_before ?: 1));
            $targetDate = Carbon::today()->addDays($daysBefore)->toDateString();

            $appointments = Appointment::with(['patient', 'doctor'])
                ->whereDate('date', $targetDate)
                ->whereIn('status', ['pending', 'confirmed'])
                ->get();

            $template = $config->message_template ?: "Hola {nombre}, te recordamos tu turno en Consultorio Dr. Cortalezzi para el día {fecha_turno} a las {hora_turno} hs con {medico}. Por favor, confirma asistencia.";

            foreach ($appointments as $appt) {
                $remKey = $appt->id . '_' . $targetDate;
                if (isset($state['sent_reminders'][$remKey])) {
                    continue;
                }
                $patient = $appt->patient;
                if (!$patient || empty($patient->phone)) {
                    continue;
                }

                $msg = str_replace(
                    ['{nombre}', '{fecha_turno}', '{hora_turno}', '{medico}', '{clinica}'],
                    [
                        trim($patient->first_name . ' ' . $patient->last_name),
                        Carbon::parse($appt->date)->format('d/m/Y'),
                        substr((string)$appt->time, 0, 5),
                        $appt->doctor->name ?? 'Dr. Cortalezzi',
                        'Consultorio Dr. Cortalezzi'
                    ],
                    $template
                );

                $state['outbox'][] = [
                    'id' => 'rem_' . $appt->id . '_' . time(),
                    'phone' => $patient->phone,
                    'message' => $msg,
                    'created_at' => time(),
                ];
                $state['sent_reminders'][$remKey] = time();
            }

            // Limpiar historial de recordatorios de más de 40 días
            $cutoff = time() - (40 * 86400);
            foreach ($state['sent_reminders'] as $rk => $ts) {
                if ($ts < $cutoff) unset($state['sent_reminders'][$rk]);
            }
        } catch (\Throwable $e) {
            // Evitar interrumpir el pulso ante cualquier excepción
        }

        return $state;
    }

    public function index()
    {
        $config = WhatsappConfig::first();
        if (!$config) {
            $config = new WhatsappConfig();
            $config->reminder_days_before = 1;
            $config->reminder_time = '08:00:00';
            $config->message_template = "Hola {nombre}, te recordamos tu turno en Consultorio Dr. Cortalezzi para el día {fecha_turno} a las {hora_turno} hs con {medico}. Por favor, confirma asistencia.";
        }

        list($url, $key) = $this->getServiceConfig();
        $bridge = $this->readBridgeState();

        $serviceStatus = 'offline';
        $connectedPhone = null;

        if ($this->isBridgeOnline($bridge)) {
            $serviceStatus = $bridge['status'] ?? 'disconnected';
            $connectedPhone = $bridge['phone'] ?? null;
        } else {
            try {
                $response = Http::withHeaders(['x-api-key' => $key])->timeout(2)->get("{$url}/status");
                if ($response->successful()) {
                    $data = $response->json();
                    $serviceStatus = $data['status'] ?? 'disconnected';
                    $connectedPhone = $data['phone'] ?? null;
                }
            } catch (\Exception $e) {
                $serviceStatus = 'offline';
            }
        }

        return view('whatsapp.index', compact('config', 'serviceStatus', 'connectedPhone'));
    }

    public function status()
    {
        $bridge = $this->readBridgeState();
        if ($this->isBridgeOnline($bridge)) {
            return response()->json([
                'status' => $bridge['status'],
                'phone' => $bridge['phone'],
                'name' => $bridge['name'],
                'hasQr' => !empty($bridge['qr']),
                'mode' => 'cloud_bridge',
            ]);
        }

        list($url, $key) = $this->getServiceConfig();
        try {
            $response = Http::withHeaders(['x-api-key' => $key])->timeout(2)->get("{$url}/status");
            if ($response->successful()) {
                return response()->json($response->json());
            }
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'status' => 'offline',
            'message' => 'Conector de WhatsApp en PC aún no iniciado.'
        ]);
    }

    public function qr()
    {
        $bridge = $this->readBridgeState();
        if ($this->isBridgeOnline($bridge)) {
            if ($bridge['status'] === 'connected') {
                return response()->json([
                    'status' => 'connected',
                    'phone' => $bridge['phone'],
                ]);
            }
            if (empty($bridge['qr'])) {
                $this->mutateBridgeState(function ($st) {
                    $st['command'] = 'start_qr';
                    return $st;
                });
            }
            return response()->json([
                'status' => $bridge['status'],
                'qr' => $bridge['qr'],
                'phone' => $bridge['phone'],
            ]);
        }

        list($url, $key) = $this->getServiceConfig();
        try {
            $response = Http::withHeaders(['x-api-key' => $key])->timeout(5)->get("{$url}/qr");
            if ($response->successful()) {
                return response()->json($response->json());
            }
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'status' => 'offline',
            'error' => 'Ejecute Vincular_WhatsApp_Consultorio.bat en la PC del consultorio para ver el QR.'
        ], 503);
    }

    public function disconnect()
    {
        $bridge = $this->readBridgeState();
        if ($this->isBridgeOnline($bridge)) {
            $this->mutateBridgeState(function ($st) {
                $st['command'] = 'disconnect';
                $st['status'] = 'disconnected';
                $st['phone'] = null;
                $st['qr'] = null;
                return $st;
            });
            return response()->json([
                'success' => true,
                'status' => 'disconnected',
                'message' => 'Orden de desvinculación enviada a la PC del consultorio.'
            ]);
        }

        list($url, $key) = $this->getServiceConfig();
        try {
            $response = Http::withHeaders(['x-api-key' => $key])->timeout(5)->post("{$url}/disconnect");
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'offline',
                'error' => 'No se pudo contactar el conector de WhatsApp.'
            ], 500);
        }
    }

    public function testSend(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $bridge = $this->readBridgeState();
        if ($this->isBridgeOnline($bridge)) {
            if (($bridge['status'] ?? '') !== 'connected') {
                return response()->json([
                    'success' => false,
                    'error' => 'El conector en la PC está activo, pero aún falta escanear el código QR.'
                ], 422);
            }

            $msgId = 'test_' . uniqid();
            $this->mutateBridgeState(function ($st) use ($msgId, $request) {
                $st['outbox'][] = [
                    'id' => $msgId,
                    'phone' => $request->phone,
                    'message' => $request->message,
                    'created_at' => time(),
                ];
                return $st;
            });

            // Esperar hasta 4.5 segundos la confirmación en tiempo real desde la PC del consultorio
            for ($i = 0; $i < 9; $i++) {
                usleep(500000);
                $check = $this->readBridgeState();
                if (isset($check['acks'][$msgId])) {
                    $ack = $check['acks'][$msgId];
                    if (!empty($ack['success'])) {
                        return response()->json([
                            'success' => true,
                            'message' => '¡Mensaje de prueba enviado con éxito desde el WhatsApp del consultorio!'
                        ]);
                    }
                    return response()->json([
                        'success' => false,
                        'error' => $ack['error'] ?: 'WhatsApp rechazó el envío. Verifique el número.'
                    ], 500);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado a la cola de salida de la PC del consultorio (se entregará en segundos).'
            ]);
        }

        list($url, $key) = $this->getServiceConfig();
        try {
            $response = Http::withHeaders(['x-api-key' => $key])->timeout(10)->post("{$url}/send", [
                'phone' => $request->phone,
                'message' => $request->message
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json(),
                    'message' => '¡Mensaje de prueba enviado con éxito!'
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $response->json('error') ?? 'Error al enviar mensaje'
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'El conector de WhatsApp no está abierto en la PC del consultorio.'
            ], 500);
        }
    }

    /**
     * Descarga un paquete .ZIP listo para descomprimir y ejecutar en la PC del consultorio del Dr. Cortalezzi.
     */
    public function downloadConnector()
    {
        list($url, $key) = $this->getServiceConfig();
        $cloudUrl = rtrim(url('/'), '/');

        $tmpZip = tempnam(sys_get_temp_dir(), 'wa_conn_') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo generar el paquete ZIP.');
        }

        $folder = 'Conector_WhatsApp_Consultorio/';
        $svcBase = base_path('whatsapp-service');

        foreach (['Vincular_WhatsApp_Consultorio.bat', 'Instalar_Inicio_Automatico_Windows.bat', 'package.json', 'server.js'] as $file) {
            $full = $svcBase . DIRECTORY_SEPARATOR . $file;
            if (file_exists($full)) {
                $zip->addFile($full, $folder . $file);
            }
        }

        $envContent = "PORT=3001\r\nCLOUD_URL={$cloudUrl}\r\nAPI_KEY={$key}\r\n";
        $zip->addFromString($folder . '.env', $envContent);

        $readme = "======================================================================\r\n"
            . "  CONECTOR DE WHATSAPP PARA PC - CONSULTORIO DR. CORTALEZZI\r\n"
            . "  Sistema vinculado: {$cloudUrl}\r\n"
            . "======================================================================\r\n\r\n"
            . "PASOS PARA VINCULAR EL WHATSAPP DE LA CLINICA:\r\n\r\n"
            . "1) Extraiga (descomprima) toda la carpeta 'Conector_WhatsApp_Consultorio'\r\n"
            . "   en el Escritorio o en Documentos de la PC del consultorio (Windows).\r\n\r\n"
            . "2) Haga doble clic en:  Vincular_WhatsApp_Consultorio.bat\r\n"
            . "   - La primera vez instalara automaticamente los componentes necesarios.\r\n"
            . "   - A los pocos segundos se abrira sola una ventana con el CODIGO QR.\r\n\r\n"
            . "3) En el celular del consultorio, abra WhatsApp -> Dispositivos vinculados\r\n"
            . "   -> Vincular un dispositivo, y escanee el Codigo QR.\r\n"
            . "   (Tambien vera el mismo QR y el estado 'Linea Conectada' dentro de\r\n"
            . "    {$cloudUrl}/configuracion/whatsapp).\r\n\r\n"
            . "4) OPCIONAL RECOMENDADO:\r\n"
            . "   Haga doble clic una vez en 'Instalar_Inicio_Automatico_Windows.bat'\r\n"
            . "   para que el conector arranque solo y minimizado cada manana al prender la PC.\r\n";

        $zip->addFromString($folder . 'LEEME_PASO_A_PASO.txt', $readme);
        $zip->close();

        return response()->download($tmpZip, 'Conector_WhatsApp_Consultorio_ElevenMed.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'reminder_days_before' => 'required|integer|min:1|max:30',
            'reminder_time' => 'required|date_format:H:i',
            'message_template' => 'nullable|string',
            'api_url' => 'nullable|url',
        ]);

        $config = WhatsappConfig::first() ?? new WhatsappConfig();
        $config->reminder_days_before = $request->reminder_days_before;
        $config->reminder_time = $request->reminder_time;
        $config->message_template = $request->message_template;
        $config->is_active = $request->has('is_active');

        if ($request->filled('api_url')) {
            $config->api_url = $request->api_url;
        }

        if (strlen($config->reminder_time) == 5) {
            $config->reminder_time .= ':00';
        }

        $config->save();

        return redirect()->route('whatsapp.index')->with('success', 'Configuración de WhatsApp guardada exitosamente.');
    }
}
