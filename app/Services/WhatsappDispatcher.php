<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\WhatsappConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class WhatsappDispatcher
{
    public static function rulesPath(): string
    {
        return storage_path('app/whatsapp_rules.json');
    }

    public static function bridgePath(): string
    {
        return storage_path('app/whatsapp_bridge_state.json');
    }

    public static function defaultRules(): array
    {
        $dbCfg = WhatsappConfig::first();
        $daysBefore = $dbCfg ? max(1, (int)$dbCfg->reminder_days_before) : 1;

        return [
            'is_active' => $dbCfg ? (bool)$dbCfg->is_active : true,
            'clinic_name' => 'Consultorio Dr. Cortalezzi',
            // Disparadores ("Cuándo enviar")
            'send_on_create' => true,
            'send_on_reschedule' => true,
            'send_reminder_before' => true,
            'reminder_days_before' => $daysBefore, // 1 = 24hs, 2 = 48hs, 3 = 72hs, 7 = 7 días
            'reminder_time' => $dbCfg && $dbCfg->reminder_time ? substr($dbCfg->reminder_time, 0, 5) : '09:00',
            // Destinatarios ("A quién enviar")
            'doctor_scope' => 'all', // 'all' | 'selected'
            'doctor_ids' => [],
            'include_overbooked' => true,
            // Plantillas ("Cómo se envía")
            'template_instant_new' => "Hola {nombre}, te confirmamos que tu turno en *{clinica}* quedó registrado para el día *{fecha_turno}* a las *{hora_turno} hs* con *{medico}*.",
            'template_instant_reschedule' => "Hola {nombre}, te informamos que tu turno en *{clinica}* fue reprogramado para el día *{fecha_turno}* a las *{hora_turno} hs* con *{medico}*.",
            'template_reminder' => ($dbCfg && !empty($dbCfg->message_template))
                ? $dbCfg->message_template
                : "Hola {nombre}, te recordamos tu turno en *{clinica}* para el día *{fecha_turno}* a las *{hora_turno} hs* con *{medico}*. Por favor, confirmá tu asistencia respondiendo este mensaje.",
        ];
    }

    public static function getRules(): array
    {
        $defaults = self::defaultRules();
        $path = self::rulesPath();
        if (!file_exists($path)) {
            return $defaults;
        }
        $decoded = json_decode((string)@file_get_contents($path), true);
        return is_array($decoded) ? array_merge($defaults, $decoded) : $defaults;
    }

    public static function saveRules(array $input): array
    {
        $current = self::getRules();
        $merged = array_merge($current, $input);
        $dir = dirname(self::rulesPath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(self::rulesPath(), json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // Sincronizar también con la tabla WhatsappConfig por compatibilidad
        $cfg = WhatsappConfig::first() ?? new WhatsappConfig();
        $cfg->is_active = !empty($merged['is_active']);
        $cfg->reminder_days_before = max(1, (int)($merged['reminder_days_before'] ?? 1));
        $remTime = substr((string)($merged['reminder_time'] ?? '09:00'), 0, 5);
        $cfg->reminder_time = $remTime . ':00';
        $cfg->message_template = $merged['template_reminder'] ?? '';
        if (!empty($input['api_url'])) {
            $cfg->api_url = $input['api_url'];
        }
        $cfg->save();

        return $merged;
    }

    public static function defaultBridgeState(): array
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
            'history' => [],
            'last_reminder_check' => 0,
        ];
    }

    public static function readBridgeState(): array
    {
        $path = self::bridgePath();
        if (!file_exists($path)) {
            return self::defaultBridgeState();
        }
        $raw = @file_get_contents($path);
        $decoded = $raw ? json_decode($raw, true) : null;
        return is_array($decoded) ? array_merge(self::defaultBridgeState(), $decoded) : self::defaultBridgeState();
    }

    public static function mutateBridgeState(callable $callback): array
    {
        $path = self::bridgePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fp = @fopen($path, 'c+');
        if (!$fp) {
            $state = self::readBridgeState();
            $updated = $callback($state);
            @file_put_contents($path, json_encode($updated, JSON_UNESCAPED_UNICODE));
            return $updated;
        }

        flock($fp, LOCK_EX);
        $contents = stream_get_contents($fp);
        $state = $contents ? json_decode($contents, true) : null;
        $state = is_array($state) ? array_merge(self::defaultBridgeState(), $state) : self::defaultBridgeState();

        $updated = $callback($state);

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($updated, JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $updated;
    }

    public static function isBridgeOnline(?array $state = null): bool
    {
        $state = $state ?? self::readBridgeState();
        return !empty($state['last_heartbeat']) && (time() - (int)$state['last_heartbeat']) <= 15;
    }

    public static function shouldNotifyAppointment(Appointment $appt, array $rules): bool
    {
        if (empty($rules['is_active'])) {
            return false;
        }
        if (empty($rules['include_overbooked']) && !empty($appt->is_overbooked)) {
            return false;
        }
        if (($rules['doctor_scope'] ?? 'all') === 'selected') {
            $allowedDocs = array_map('intval', $rules['doctor_ids'] ?? []);
            if (!in_array((int)$appt->doctor_id, $allowedDocs, true)) {
                return false;
            }
        }
        return true;
    }

    public static function renderAppointmentTemplate(string $template, Appointment $appt, array $rules): string
    {
        $appt->loadMissing(['patient', 'doctor']);
        $patient = $appt->patient;
        $doctor = $appt->doctor;

        $firstName = trim((string)($patient->first_name ?? 'Paciente'));
        $lastName = trim((string)($patient->last_name ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);
        $doctorName = $doctor ? $doctor->name : 'Dr. Cortalezzi';
        if ($doctorName && !str_starts_with(mb_strtolower($doctorName, 'UTF-8'), 'dr')) {
            $doctorName = 'Dr(a). ' . $doctorName;
        }

        return str_replace(
            ['{nombre}', '{apellido}', '{nombre_completo}', '{fecha_turno}', '{hora_turno}', '{medico}', '{clinica}'],
            [
                $firstName,
                $lastName,
                $fullName,
                Carbon::parse($appt->date)->format('d/m/Y'),
                substr((string)$appt->time, 0, 5),
                $doctorName,
                $rules['clinic_name'] ?? 'Consultorio Dr. Cortalezzi',
            ],
            $template
        );
    }

    /**
     * Encola un mensaje de WhatsApp para un turno (o envío directo) y registra en el historial.
     */
    public static function dispatchForAppointment(Appointment $appt, string $eventType, bool $bypassTriggerCheck = false): array
    {
        $appt->loadMissing(['patient', 'doctor']);
        $rules = self::getRules();

        if (!$bypassTriggerCheck) {
            if (!self::shouldNotifyAppointment($appt, $rules)) {
                return ['queued' => false, 'reason' => 'rule_filtered'];
            }
            if ($eventType === 'instant_new' && empty($rules['send_on_create'])) {
                return ['queued' => false, 'reason' => 'trigger_disabled'];
            }
            if ($eventType === 'instant_reschedule' && empty($rules['send_on_reschedule'])) {
                return ['queued' => false, 'reason' => 'trigger_disabled'];
            }
        }

        $patient = $appt->patient;
        $rawPhone = trim((string)($patient->phone ?? ''));
        $digits = preg_replace('/[^0-9]/', '', $rawPhone);

        $templateKey = match ($eventType) {
            'instant_new' => 'template_instant_new',
            'instant_reschedule' => 'template_instant_reschedule',
            default => 'template_reminder',
        };
        $message = self::renderAppointmentTemplate($rules[$templateKey] ?? '', $appt, $rules);
        $msgId = $eventType . '_' . $appt->id . '_' . uniqid();
        $nowIso = now()->toIso8601String();
        $patientLabel = $patient ? trim($patient->last_name . ', ' . $patient->first_name) : 'Paciente';
        $doctorLabel = $appt->doctor->name ?? 'Staff';
        $apptSlotLabel = Carbon::parse($appt->date)->format('d/m/Y') . ' ' . substr((string)$appt->time, 0, 5) . ' hs';

        if (strlen($digits) < 7) {
            // Registrar como omitido por teléfono inválido
            self::mutateBridgeState(function (array $st) use ($msgId, $eventType, $patientLabel, $rawPhone, $doctorLabel, $apptSlotLabel, $message, $nowIso) {
                array_unshift($st['history'], [
                    'id' => $msgId,
                    'type' => $eventType,
                    'patient_name' => $patientLabel,
                    'phone' => $rawPhone ?: 'Sin teléfono',
                    'doctor_name' => $doctorLabel,
                    'appointment_label' => $apptSlotLabel,
                    'message' => $message,
                    'status' => 'failed',
                    'error' => 'Paciente sin teléfono celular válido cargado en su ficha',
                    'created_at' => $nowIso,
                    'delivered_at' => null,
                ]);
                $st['history'] = array_slice($st['history'], 0, 150);
                return $st;
            });
            return ['queued' => false, 'reason' => 'invalid_phone'];
        }

        // Encolar en el puente para entrega inmediata desde la PC del consultorio
        self::mutateBridgeState(function (array $st) use ($msgId, $eventType, $patientLabel, $rawPhone, $doctorLabel, $apptSlotLabel, $message, $nowIso) {
            $st['outbox'][] = [
                'id' => $msgId,
                'phone' => $rawPhone,
                'message' => $message,
                'created_at' => time(),
            ];
            array_unshift($st['history'], [
                'id' => $msgId,
                'type' => $eventType,
                'patient_name' => $patientLabel,
                'phone' => $rawPhone,
                'doctor_name' => $doctorLabel,
                'appointment_label' => $apptSlotLabel,
                'message' => $message,
                'status' => 'queued',
                'error' => null,
                'created_at' => $nowIso,
                'delivered_at' => null,
            ]);
            $st['history'] = array_slice($st['history'], 0, 150);
            return $st;
        });

        return ['queued' => true, 'id' => $msgId];
    }

    /**
     * Revisa y encola recordatorios automáticos previos (ej: 24 hs antes a la hora elegida).
     * Se ejecuta dentro del mutex de mutateBridgeState cuando la PC del consultorio está conectada.
     */
    public static function enqueueScheduledRemindersInState(array $state): array
    {
        try {
            $rules = self::getRules();
            if (empty($rules['is_active']) || empty($rules['send_reminder_before'])) {
                return $state;
            }

            $reminderHm = substr((string)($rules['reminder_time'] ?? '09:00'), 0, 5);
            if (now()->format('H:i') < $reminderHm) {
                return $state;
            }

            $daysBefore = max(1, (int)($rules['reminder_days_before'] ?? 1));
            $targetDate = Carbon::today()->addDays($daysBefore)->toDateString();

            $query = Appointment::with(['patient', 'doctor'])
                ->whereDate('date', $targetDate)
                ->whereIn('status', ['pending', 'confirmed']);

            if (empty($rules['include_overbooked'])) {
                $query->where(function ($q) {
                    $q->whereNull('is_overbooked')->orWhere('is_overbooked', false);
                });
            }
            if (($rules['doctor_scope'] ?? 'all') === 'selected') {
                $docIds = array_map('intval', $rules['doctor_ids'] ?? []);
                if (empty($docIds)) {
                    return $state;
                }
                $query->whereIn('doctor_id', $docIds);
            }

            $appointments = $query->orderBy('time')->get();
            $nowIso = now()->toIso8601String();

            foreach ($appointments as $appt) {
                $remKey = $appt->id . '_' . $targetDate;
                if (isset($state['sent_reminders'][$remKey])) {
                    continue;
                }

                $patient = $appt->patient;
                $rawPhone = trim((string)($patient->phone ?? ''));
                $digits = preg_replace('/[^0-9]/', '', $rawPhone);
                $patientLabel = $patient ? trim($patient->last_name . ', ' . $patient->first_name) : 'Paciente';
                $doctorLabel = $appt->doctor->name ?? 'Staff';
                $apptSlotLabel = Carbon::parse($appt->date)->format('d/m/Y') . ' ' . substr((string)$appt->time, 0, 5) . ' hs';
                $msg = self::renderAppointmentTemplate($rules['template_reminder'] ?? '', $appt, $rules);
                $msgId = 'rem_' . $appt->id . '_' . uniqid();

                $state['sent_reminders'][$remKey] = time();

                if (strlen($digits) < 7) {
                    array_unshift($state['history'], [
                        'id' => $msgId,
                        'type' => 'reminder',
                        'patient_name' => $patientLabel,
                        'phone' => $rawPhone ?: 'Sin teléfono',
                        'doctor_name' => $doctorLabel,
                        'appointment_label' => $apptSlotLabel,
                        'message' => $msg,
                        'status' => 'failed',
                        'error' => 'Sin teléfono válido en ficha',
                        'created_at' => $nowIso,
                        'delivered_at' => null,
                    ]);
                    continue;
                }

                $state['outbox'][] = [
                    'id' => $msgId,
                    'phone' => $rawPhone,
                    'message' => $msg,
                    'created_at' => time(),
                ];

                array_unshift($state['history'], [
                    'id' => $msgId,
                    'type' => 'reminder',
                    'patient_name' => $patientLabel,
                    'phone' => $rawPhone,
                    'doctor_name' => $doctorLabel,
                    'appointment_label' => $apptSlotLabel,
                    'message' => $msg,
                    'status' => 'queued',
                    'error' => null,
                    'created_at' => $nowIso,
                    'delivered_at' => null,
                ]);
            }

            $state['history'] = array_slice($state['history'], 0, 150);

            $cutoff = time() - (45 * 86400);
            foreach ($state['sent_reminders'] as $rk => $ts) {
                if ($ts < $cutoff) unset($state['sent_reminders'][$rk]);
            }
        } catch (\Throwable $e) {
            // Silencioso
        }

        return $state;
    }
}
