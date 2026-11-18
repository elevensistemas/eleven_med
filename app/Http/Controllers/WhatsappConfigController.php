<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappConfig;
use App\Models\Appointment;
use App\Models\User;
use App\Services\WhatsappDispatcher;
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

        WhatsappDispatcher::mutateBridgeState(function (array $state) use ($status, $phone, $name, $qr, $incomingAcks, &$responsePayload) {
            $now = time();
            $nowIso = now()->toIso8601String();
            $state['last_heartbeat'] = $now;
            $state['status'] = $status;
            $state['phone'] = $phone;
            $state['name'] = $name;
            $state['qr'] = ($status === 'qr_ready') ? $qr : null;

            // Procesar confirmaciones de envío desde la PC y actualizar el historial
            if (is_array($incomingAcks) && count($incomingAcks) > 0) {
                $ackedIds = [];
                $ackMap = [];
                foreach ($incomingAcks as $ack) {
                    if (!empty($ack['id'])) {
                        $ackedIds[] = $ack['id'];
                        $ackMap[$ack['id']] = $ack;
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

                if (!empty($state['history']) && is_array($state['history'])) {
                    foreach ($state['history'] as &$hItem) {
                        $hid = $hItem['id'] ?? '';
                        if ($hid && isset($ackMap[$hid])) {
                            $isOk = !empty($ackMap[$hid]['success']);
                            $hItem['status'] = $isOk ? 'sent' : 'failed';
                            $hItem['error'] = $isOk ? null : ($ackMap[$hid]['error'] ?? 'Error al enviar');
                            $hItem['delivered_at'] = $nowIso;
                        }
                    }
                    unset($hItem);
                }
            }

            // Limpiar acks y mensajes expirados (> 15 min)
            $state['outbox'] = array_values(array_filter($state['outbox'], fn($m) => ($now - ($m['created_at'] ?? $now)) < 900));
            foreach ($state['acks'] as $aid => $ainfo) {
                if (($now - ($ainfo['at'] ?? $now)) > 300) {
                    unset($state['acks'][$aid]);
                }
            }

            // Encolar recordatorios automáticos de turnos previos (24hs / 48hs / etc.) una vez por minuto
            if ($status === 'connected' && ($now - (int)($state['last_reminder_check'] ?? 0)) >= 60) {
                $state['last_reminder_check'] = $now;
                $state = WhatsappDispatcher::enqueueScheduledRemindersInState($state);
            }

            $responsePayload['command'] = $state['command'];
            $state['command'] = null;
            $responsePayload['outbox'] = $state['outbox'];

            return $state;
        });

        return response()->json($responsePayload);
    }

    public function index()
    {
        $config = WhatsappConfig::first() ?? new WhatsappConfig();
        $rules = WhatsappDispatcher::getRules();
        $doctors = User::role('médico')->orderBy('name')->get();

        list($url, $key) = $this->getServiceConfig();
        $bridge = WhatsappDispatcher::readBridgeState();
        $history = $bridge['history'] ?? [];

        $serviceStatus = 'offline';
        $connectedPhone = null;

        if (WhatsappDispatcher::isBridgeOnline($bridge)) {
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

        return view('whatsapp.index', compact('config', 'rules', 'doctors', 'history', 'serviceStatus', 'connectedPhone'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'clinic_name' => 'nullable|string|max:120',
            'reminder_days_before' => 'required|integer|min:1|max:30',
            'reminder_time' => 'required|date_format:H:i',
            'doctor_scope' => 'required|string|in:all,selected',
            'doctor_ids' => 'nullable|array',
            'template_instant_new' => 'required|string',
            'template_instant_reschedule' => 'required|string',
            'template_reminder' => 'required|string',
            'api_url' => 'nullable|url',
        ]);

        WhatsappDispatcher::saveRules([
            'is_active' => $request->has('is_active'),
            'clinic_name' => trim((string)$request->input('clinic_name', 'Consultorio Dr. Cortalezzi')) ?: 'Consultorio Dr. Cortalezzi',
            'send_on_create' => $request->has('send_on_create'),
            'send_on_reschedule' => $request->has('send_on_reschedule'),
            'send_reminder_before' => $request->has('send_reminder_before'),
            'reminder_days_before' => (int)$request->input('reminder_days_before', 1),
            'reminder_time' => substr((string)$request->input('reminder_time', '09:00'), 0, 5),
            'doctor_scope' => $request->input('doctor_scope', 'all'),
            'doctor_ids' => array_map('intval', $request->input('doctor_ids', [])),
            'include_overbooked' => $request->has('include_overbooked'),
            'template_instant_new' => $request->input('template_instant_new'),
            'template_instant_reschedule' => $request->input('template_instant_reschedule'),
            'template_reminder' => $request->input('template_reminder'),
            'api_url' => $request->input('api_url'),
        ]);

        return redirect()->route('whatsapp.index')->with('success', 'Reglas de envío, destinatarios y plantillas de WhatsApp guardadas correctamente.');
    }

    /**
     * Disparador manual de recordatorios para todos los turnos de una fecha elegida (ej: mañana o lunes).
     */
    public function sendManualBatch(Request $request)
    {
        $validated = $request->validate([
            'target_date' => 'required|date',
            'doctor_id' => 'nullable',
            'force_resend' => 'nullable|boolean',
        ]);

        $targetDate = Carbon::parse($validated['target_date'])->toDateString();
        $doctorId = !empty($validated['doctor_id']) && $validated['doctor_id'] !== 'all' ? (int)$validated['doctor_id'] : null;
        $forceResend = $request->boolean('force_resend');

        $query = Appointment::with(['patient', 'doctor'])
            ->whereDate('date', $targetDate)
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('time');

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $appointments = $query->get();
        if ($appointments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay turnos agendados en esa fecha para enviar recordatorios.'
            ], 422);
        }

        $bridge = WhatsappDispatcher::readBridgeState();
        $sentReminders = $bridge['sent_reminders'] ?? [];
        $queuedCount = 0;
        $skippedAlreadySent = 0;
        $skippedNoPhone = 0;

        foreach ($appointments as $appt) {
            $remKey = $appt->id . '_' . $targetDate;
            if (!$forceResend && isset($sentReminders[$remKey])) {
                $skippedAlreadySent++;
                continue;
            }

            $res = WhatsappDispatcher::dispatchForAppointment($appt, 'manual_batch', true);
            if (!empty($res['queued'])) {
                $queuedCount++;
                WhatsappDispatcher::mutateBridgeState(function (array $st) use ($remKey) {
                    $st['sent_reminders'][$remKey] = time();
                    return $st;
                });
            } elseif (($res['reason'] ?? '') === 'invalid_phone') {
                $skippedNoPhone++;
            }
        }

        return response()->json([
            'success' => true,
            'queued' => $queuedCount,
            'skipped_already_sent' => $skippedAlreadySent,
            'skipped_no_phone' => $skippedNoPhone,
            'message' => "Se encolaron {$queuedCount} mensaje(s) de WhatsApp"
                . ($skippedNoPhone > 0 ? " ({$skippedNoPhone} sin celular válido)" : '')
                . ($skippedAlreadySent > 0 ? " ({$skippedAlreadySent} ya enviados previamente)" : '') . "."
        ]);
    }

    public function history()
    {
        $bridge = WhatsappDispatcher::readBridgeState();
        return response()->json([
            'history' => array_slice($bridge['history'] ?? [], 0, 80),
            'outbox_pending' => count($bridge['outbox'] ?? []),
        ]);
    }

    public function clearHistory()
    {
        WhatsappDispatcher::mutateBridgeState(function (array $st) {
            $st['history'] = [];
            return $st;
        });
        return response()->json(['success' => true]);
    }

    public function status()
    {
        $bridge = WhatsappDispatcher::readBridgeState();
        if (WhatsappDispatcher::isBridgeOnline($bridge)) {
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
        $bridge = WhatsappDispatcher::readBridgeState();
        if (WhatsappDispatcher::isBridgeOnline($bridge)) {
            if ($bridge['status'] === 'connected') {
                return response()->json([
                    'status' => 'connected',
                    'phone' => $bridge['phone'],
                ]);
            }
            if (empty($bridge['qr'])) {
                WhatsappDispatcher::mutateBridgeState(function ($st) {
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
        $bridge = WhatsappDispatcher::readBridgeState();
        if (WhatsappDispatcher::isBridgeOnline($bridge)) {
            WhatsappDispatcher::mutateBridgeState(function ($st) {
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

        $bridge = WhatsappDispatcher::readBridgeState();
        if (WhatsappDispatcher::isBridgeOnline($bridge)) {
            if (($bridge['status'] ?? '') !== 'connected') {
                return response()->json([
                    'success' => false,
                    'error' => 'El conector en la PC está activo, pero aún falta escanear el código QR.'
                ], 422);
            }

            $msgId = 'test_' . uniqid();
            $nowIso = now()->toIso8601String();

            WhatsappDispatcher::mutateBridgeState(function ($st) use ($msgId, $request, $nowIso) {
                $st['outbox'][] = [
                    'id' => $msgId,
                    'phone' => $request->phone,
                    'message' => $request->message,
                    'created_at' => time(),
                ];
                array_unshift($st['history'], [
                    'id' => $msgId,
                    'type' => 'test',
                    'patient_name' => 'Envío de Prueba',
                    'phone' => $request->phone,
                    'doctor_name' => '-',
                    'appointment_label' => 'Prueba manual',
                    'message' => $request->message,
                    'status' => 'queued',
                    'error' => null,
                    'created_at' => $nowIso,
                    'delivered_at' => null,
                ]);
                $st['history'] = array_slice($st['history'], 0, 150);
                return $st;
            });

            for ($i = 0; $i < 9; $i++) {
                usleep(500000);
                $check = WhatsappDispatcher::readBridgeState();
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

        foreach (['Vincular_WhatsApp_Consultorio.bat', 'Instalar_Inicio_Automatico_Windows.bat', 'INSTRUCTIVO_PASO_A_PASO.txt', 'package.json', 'server.js'] as $file) {
            $full = $svcBase . DIRECTORY_SEPARATOR . $file;
            if (file_exists($full)) {
                $zip->addFile($full, $folder . $file);
            }
        }

        $envContent = "PORT=3001\r\nCLOUD_URL={$cloudUrl}\r\nAPI_KEY={$key}\r\n";
        $zip->addFromString($folder . '.env', $envContent);
        $zip->close();

        return response()->download($tmpZip, 'Conector_WhatsApp_Consultorio_ElevenMed.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
