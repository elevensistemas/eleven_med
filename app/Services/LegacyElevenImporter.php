<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Visit;
use App\Models\PatientSurgery;
use App\Models\User;
use App\Models\DoctorConfiguration;
use App\Models\DoctorSchedule;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LegacyElevenImporter
{
    /**
     * Decodifica el campo `examen` del Viejo Eleven (separado por `---`).
     */
    public static function parseExamen(?string $raw): array
    {
        $parts = explode('---', (string)$raw);
        $clean = function ($idx) use ($parts) {
            $v = isset($parts[$idx]) ? trim($parts[$idx]) : '';
            $v = trim($v, " \t\n\r\0\x0B-");
            return $v !== '' ? $v : null;
        };

        return [
            'av_od_cerca'  => $clean(0),
            'av_oi_cerca'  => $clean(1),
            'pio'          => $clean(2),
            'bmc'          => $clean(3),
            'obi'          => $clean(4),
            'av_od_lejos'  => $clean(5),
            'av_oi_lejos'  => $clean(6),
            'otros_examen' => $clean(7),
        ];
    }

    /**
     * Infiere sexo a partir del primer nombre cuando en Viejo Eleven no existía columna sexo.
     */
    public static function inferGender(?string $firstName): ?string
    {
        $fn = mb_strtolower(trim(strtok(trim((string)$firstName), ' ') ?: ''), 'UTF-8');
        if ($fn === '') return null;

        $femaleNames = ['maria', 'maría', 'ana', 'rosa', 'marta', 'martha', 'silvia', 'claudia', 'patricia', 'laura', 'susana', 'graciela', 'alicia', 'monica', 'mónica', 'norma', 'beatriz', 'liliana', 'adriana', 'andrea', 'gabriela', 'valentina', 'lucia', 'lucía', 'camila', 'sofia', 'sofía', 'carolina', 'veronica', 'verónica', 'natalia', 'paola', 'florencia', 'sonia', 'elena', 'carmen', 'irma', 'nora', 'estela', 'stella', 'mirta', 'mirtha', 'nelida', 'nélida', 'juana', 'julia', 'teresa', 'inés', 'ines', 'cecilia', 'marcela', 'alejandra', 'daniela', 'mariana', 'victoria', 'agustina'];
        $maleNames = ['juan', 'carlos', 'jose', 'josé', 'jorge', 'luis', 'miguel', 'roberto', 'oscar', 'óscar', 'daniel', 'alejandro', 'mario', 'ricardo', 'eduardo', 'fernando', 'gustavo', 'sergio', 'pablo', 'diego', 'martin', 'martín', 'marcelo', 'raul', 'raúl', 'pedro', 'ruben', 'rubén', 'hector', 'héctor', 'alberto', 'francisco', 'antonio', 'manuel', 'nicolas', 'nicolás', 'matias', 'matías', 'santiago', 'lucas', 'federico', 'guillermo', 'rodolfo', 'horacio', 'enrique', 'arturo', 'julio', 'cesar', 'césar'];

        if (in_array($fn, $femaleNames, true)) return 'Femenino';
        if (in_array($fn, $maleNames, true)) return 'Masculino';
        if (str_ends_with($fn, 'a') && !in_array($fn, ['luca', 'borja', 'nikita'], true)) return 'Femenino';
        if (str_ends_with($fn, 'o')) return 'Masculino';
        return null;
    }

    /**
     * Provisiona los roles, usuarios solicitados (Sonia, María y rol Administrador adicional para Cortalezzi)
     * e importa los 6 pacientes reales de muestra del Viejo Eleven con todo su historial clínico y quirúrgico.
     */
    public static function provisionClinicUsersAndSample(): array
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['administrador', 'médico', 'técnico', 'recepcionista', 'supervisor'] as $rName) {
            Role::firstOrCreate(['name' => $rName, 'guard_name' => 'web']);
        }
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Asegurar que todas las cuentas titulares del Dr. Cortalezzi tengan rol médico + administrador (para poder cambiar su propia clave)
        $cortalezziAccounts = User::whereIn('email', [
            'admin@cortalezzi.com',
            'juan@cortalezzi.com',
            'cortalezzivision@gmail.com',
        ])->get();

        foreach ($cortalezziAccounts as $acc) {
            if (!$acc->hasRole('médico') && $acc->email !== 'admin@cortalezzi.com') {
                $acc->assignRole('médico');
            }
            if (!$acc->hasRole('administrador')) {
                $acc->assignRole('administrador');
            }
        }

        $cortalezziDoctor = User::whereIn('email', ['juan@cortalezzi.com', 'cortalezzivision@gmail.com', 'medico@cortalezzi.com'])->first()
            ?: User::role('médico')->first()
            ?: User::first();

        // 2. Crear / asegurar accesos de Sonia y María para Intercomunicador y Agendas Técnicas
        $sonia = User::firstOrCreate(
            ['email' => 'sonia@cortalezzi.com'],
            [
                'name' => 'Sonia (Secretaría)',
                'password' => Hash::make('cortalezzi2026'),
            ]
        );
        if ($sonia->roles->isEmpty()) {
            $sonia->syncRoles(['recepcionista']);
        }

        $maria = User::firstOrCreate(
            ['email' => 'maria@cortalezzi.com'],
            [
                'name' => 'María (Secretaría / Técnica)',
                'password' => Hash::make('cortalezzi2026'),
            ]
        );
        if (!$maria->hasRole('recepcionista') || !$maria->hasRole('técnico')) {
            $currentRoles = $maria->roles->pluck('name')->toArray();
            $merged = array_values(array_unique(array_merge($currentRoles, ['recepcionista', 'técnico'])));
            $maria->syncRoles($merged);
        }

        // Configurar una Agenda Técnica inicial para María si aún no tiene una (ej: Campos Visuales / Estudios)
        $mariaTechCfg = DoctorConfiguration::where('doctor_id', $maria->id)
            ->where('agenda_type', 'tecnica')
            ->first();

        if (!$mariaTechCfg) {
            $techDays = [];
            foreach ([1, 2, 3, 4, 5] as $d) {
                $techDays[$d] = [
                    'active' => true,
                    'morning_active' => true,
                    'morning_start' => '09:00',
                    'morning_end' => '12:30',
                    'afternoon_active' => true,
                    'afternoon_start' => '15:00',
                    'afternoon_end' => '18:30',
                ];
            }
            $techDays[6] = ['active' => false, 'morning_active' => false, 'morning_start' => null, 'morning_end' => null, 'afternoon_active' => false, 'afternoon_start' => null, 'afternoon_end' => null];

            DoctorConfiguration::create([
                'doctor_id' => $maria->id,
                'agenda_type' => 'tecnica',
                'agenda_label' => 'Campos Visuales / OCT / Estudios',
                'working_days' => $techDays,
                'shift_1_start' => '09:00',
                'shift_1_end' => '12:30',
                'shift_2_start' => '15:00',
                'shift_2_end' => '18:30',
                'appointment_duration' => 20,
            ]);

            foreach ([1, 2, 3, 4, 5] as $d) {
                DoctorSchedule::create([
                    'doctor_id' => $maria->id,
                    'agenda_type' => 'tecnica',
                    'day_of_week' => $d,
                    'start_time' => '09:00',
                    'end_time' => '18:30',
                    'slot_duration_minutes' => 20,
                ]);
            }
        }

        // 3. Cargar los 6 pacientes reales de muestra del Viejo Eleven
        $jsonPath = database_path('seeders/legacy_sample_6.json');
        $importedSummary = [];
        $doctorId = $cortalezziDoctor ? $cortalezziDoctor->id : 1;

        if (file_exists($jsonPath)) {
            $samplePatients = json_decode(file_get_contents($jsonPath), true) ?: [];
            foreach ($samplePatients as $row) {
                $res = self::upsertLegacyPatientRecord($row, $doctorId);
                if ($res) {
                    $importedSummary[] = $res;
                }
            }
        }

        return [
            'cortalezzi_user' => $cortalezziDoctor ? ['id' => $cortalezziDoctor->id, 'email' => $cortalezziDoctor->email, 'roles' => $cortalezziDoctor->getRoleNames()] : null,
            'sonia' => ['id' => $sonia->id, 'email' => $sonia->email, 'roles' => $sonia->getRoleNames()],
            'maria' => ['id' => $maria->id, 'email' => $maria->email, 'roles' => $maria->getRoleNames()],
            'sample_patients' => $importedSummary,
        ];
    }

    /**
     * Inserta o integra de manera idempotente un paciente histórico del Viejo Eleven junto con sus consultas y cirugías.
     */
    public static function upsertLegacyPatientRecord(array $row, int $defaultDoctorId): ?array
    {
        $dni = preg_replace('/[^0-9A-Za-z]/', '', trim((string)($row['dni'] ?? '')));
        $firstName = trim((string)($row['first_name'] ?? ''));
        $lastName = trim((string)($row['last_name'] ?? ''));
        if ($firstName === '' && $lastName === '') return null;

        if ($dni === '') {
            $dni = 'LEG-' . ($row['legacy_id'] ?? uniqid());
        }

        $legacyCode = trim((string)($row['code'] ?? ''));
        if ($legacyCode === '' && !empty($row['legacy_id'])) {
            $legacyCode = (string)$row['legacy_id'];
        }

        $dob = trim((string)($row['date_of_birth'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) || str_starts_with($dob, '0000')) {
            $dob = '1965-01-01';
        }

        $createdAt = trim((string)($row['created_at'] ?? ''));
        if ($createdAt === '' || str_starts_with($createdAt, '0000')) {
            $createdAt = now()->toDateTimeString();
        }

        $patient = Patient::where('dni', $dni)->first();
        $phone = trim((string)($row['phone'] ?? ''));
        if ($phone !== '') {
            $phone = \App\Http\Controllers\PatientController::formatArgentinePhone($phone);
        }

        $gender = self::inferGender($firstName);

        if (!$patient) {
            $patient = new Patient([
                'code' => $legacyCode ?: null,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'dni' => $dni,
                'date_of_birth' => $dob,
                'gender' => $gender,
                'obra_social' => trim((string)($row['obra_social'] ?? '')) ?: null,
                'plan' => trim((string)($row['plan'] ?? '')) ?: null,
                'affiliate_number' => trim((string)($row['affiliate_number'] ?? '')) ?: null,
                'phone' => $phone ?: null,
                'email' => trim((string)($row['email'] ?? '')) ?: null,
                'address' => trim((string)($row['address'] ?? '')) ?: null,
                'profession' => trim((string)($row['profession'] ?? '')) ?: null,
                'recommendation' => trim((string)($row['recommendation'] ?? '')) ?: null,
                'medical_notes' => trim((string)($row['medical_notes'] ?? '')) ?: null,
                'director_id' => $defaultDoctorId,
            ]);
            $patient->timestamps = false;
            $patient->created_at = Carbon::parse($createdAt);
            $patient->updated_at = Carbon::parse($createdAt);
            $patient->save();
        } else {
            if (empty($patient->code) && $legacyCode !== '') {
                $patient->code = $legacyCode;
            }
            if (empty($patient->gender) && $gender) {
                $patient->gender = $gender;
            }
            $patient->save();
        }

        // Importar consultas previas sin duplicar (verificando timestamp exacto + patient_id)
        $visitsAdded = 0;
        foreach ($row['visits'] ?? [] as $v) {
            $vDateStr = trim((string)($v['created_at'] ?? ''));
            if ($vDateStr === '' || str_starts_with($vDateStr, '0000')) {
                $f = trim((string)($v['fecha'] ?? ''));
                $vDateStr = ($f !== '' && !str_starts_with($f, '0000')) ? ($f . ' 10:00:00') : now()->toDateTimeString();
            }
            $vCreated = Carbon::parse($vDateStr);

            $exists = Visit::where('patient_id', $patient->id)
                ->where('created_at', $vCreated->toDateTimeString())
                ->exists();

            if (!$exists) {
                $ex = self::parseExamen($v['examen'] ?? '');
                $visit = new Visit([
                    'patient_id' => $patient->id,
                    'doctor_id' => $defaultDoctorId,
                    'is_open' => false,
                    'motivo_consulta' => trim((string)($v['motivo_consulta'] ?? '')) ?: null,
                    'diagnostico' => trim((string)($v['diagnostico'] ?? '')) ?: null,
                    'antecedentes_oftalmologicos' => trim((string)($v['antecedentes'] ?? '')) ?: null,
                    'tratamiento_oftalmologico' => trim((string)($v['tratamiento'] ?? '')) ?: null,
                    'pio' => $ex['pio'],
                    'bmc' => $ex['bmc'],
                    'obi' => $ex['obi'],
                    'av_od_lejos' => $ex['av_od_lejos'],
                    'av_oi_lejos' => $ex['av_oi_lejos'],
                    'av_od_cerca' => $ex['av_od_cerca'],
                    'av_oi_cerca' => $ex['av_oi_cerca'],
                    'otros_examen' => $ex['otros_examen'],
                ]);
                $visit->timestamps = false;
                $visit->created_at = $vCreated;
                $visit->updated_at = $vCreated;
                $visit->save();
                $visitsAdded++;
            }
        }

        // Importar cirugías previas respetando fecha y ojo (OD / OI)
        $surgeriesAdded = 0;
        foreach ($row['surgeries'] ?? [] as $s) {
            $desc = trim((string)($s['description'] ?? ''));
            $dOd = trim((string)($s['date_od'] ?? ''));
            $dOi = trim((string)($s['date_oi'] ?? ''));
            if (str_starts_with($dOd, '0000')) $dOd = '';
            if (str_starts_with($dOi, '0000')) $dOi = '';

            $entries = [];
            if ($dOd !== '' && $dOi !== '') {
                $entries[] = ['eye' => 'OD', 'date' => $dOd];
                $entries[] = ['eye' => 'OI', 'date' => $dOi];
            } elseif ($dOd !== '') {
                $entries[] = ['eye' => 'OD', 'date' => $dOd];
            } elseif ($dOi !== '') {
                $entries[] = ['eye' => 'OI', 'date' => $dOi];
            } else {
                $fallbackDate = substr(trim((string)($s['created_at'] ?? '')), 0, 10);
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fallbackDate) || str_starts_with($fallbackDate, '0000')) {
                    $fallbackDate = substr($createdAt, 0, 10);
                }
                if (preg_match('/\bao\b/i', $desc)) {
                    $entries[] = ['eye' => 'OD', 'date' => $fallbackDate];
                    $entries[] = ['eye' => 'OI', 'date' => $fallbackDate];
                } elseif (preg_match('/\boi\b/i', $desc)) {
                    $entries[] = ['eye' => 'OI', 'date' => $fallbackDate];
                } else {
                    $entries[] = ['eye' => 'OD', 'date' => $fallbackDate];
                }
            }

            foreach ($entries as $ent) {
                $existsSg = PatientSurgery::where('patient_id', $patient->id)
                    ->whereDate('surgery_date', $ent['date'])
                    ->where('eye', $ent['eye'])
                    ->where('notes', $desc ?: 'Cirugía histórica')
                    ->exists();

                if (!$existsSg) {
                    PatientSurgery::create([
                        'patient_id' => $patient->id,
                        'doctor_id' => $defaultDoctorId,
                        'eye' => $ent['eye'],
                        'surgery_date' => $ent['date'],
                        'notes' => $desc ?: 'Cirugía histórica',
                    ]);
                    $surgeriesAdded++;
                }
            }
        }

        return [
            'id' => $patient->id,
            'patient_number' => $patient->patient_number,
            'name' => $patient->last_name . ', ' . $patient->first_name,
            'dni' => $patient->dni,
            'visits_total' => $patient->visits()->count(),
            'surgeries_total' => $patient->surgeries()->count(),
            'visits_added' => $visitsAdded,
            'surgeries_added' => $surgeriesAdded,
        ];
    }
}
