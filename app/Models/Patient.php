<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Patient extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    /**
     * Get the doctor/director attached to this patient profile.
     */
    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    /**
     * Ensure first name is always title case
     */
    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8') : '',
            set: fn (?string $value) => $value ? mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8') : '',
        );
    }

    /**
     * Ensure last name is always title case
     */
    protected function lastName(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8') : '',
            set: fn (?string $value) => $value ? mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8') : '',
        );
    }

    /**
     * Computed Last Name, First Name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->last_name}, {$this->first_name}";
    }

    /**
     * Número de Paciente correlativo (heredado de Viejo Eleven en `code` o `id`).
     */
    public function getPatientNumberAttribute(): string
    {
        $raw = trim((string)($this->code ?? ''));
        if ($raw !== '') {
            return ltrim($raw, '0') !== '' ? ltrim($raw, '0') : '0';
        }
        return (string)$this->id;
    }

    /**
     * Lee o actualiza la configuración global liviana del sistema (piso correlativo Viejo Eleven, URL puente de estudios, etc.)
     */
    public static function getSystemSettings(): array
    {
        $defaults = [
            'legacy_patient_number_floor' => 3000,
            'legacy_studies_base_url' => '',
        ];
        try {
            $path = storage_path('app/system_settings.json');
            if (file_exists($path)) {
                $decoded = json_decode(file_get_contents($path), true);
                if (is_array($decoded)) {
                    return array_merge($defaults, $decoded);
                }
            }
        } catch (\Throwable $e) {
            // Fallback a defaults
        }
        return $defaults;
    }

    /**
     * Calcula el próximo Número de Paciente correlativo respetando el piso del Viejo Eleven para evitar números duplicados.
     */
    public static function nextCorrelativeNumber(): string
    {
        $settings = static::getSystemSettings();
        $legacyFloor = max(0, (int)($settings['legacy_patient_number_floor'] ?? 3000));
        $maxId = (int)(static::max('id') ?? 0);
        $maxCode = 0;

        try {
            $codes = static::whereNotNull('code')->where('code', '!=', '')->pluck('code');
            foreach ($codes as $c) {
                $clean = trim((string)$c);
                if (ctype_digit($clean)) {
                    $val = (int)$clean;
                    if ($val > 0 && $val < 1000000 && $val > $maxCode) {
                        $maxCode = $val;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return (string)(max($legacyFloor, $maxId, $maxCode) + 1);
    }

    /**
     * Get the medical studies files tied to this patient.
     */
    public function studies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientStudy::class)->latest();
    }

    public function visits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Visit::class)->latest();
    }

    public function latestVisit(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Visit::class)->latestOfMany();
    }

    public function getObraSocialColorAttribute()
    {
        if (empty($this->obra_social)) {
            return '#198754'; // Particular (Green)
        }
        
        return \Illuminate\Support\Facades\Cache::remember('os_color_' . md5($this->obra_social), 3600, function() {
            $osList = \App\Models\ObraSocial::all();
            foreach ($osList as $os) {
                if (stripos($this->obra_social, $os->name) !== false) {
                    return $os->color;
                }
            }
            return '#5e6ad2'; // Default blue
        });
    }

    
    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientAssignment::class)->latest('started_at');
    }

    public function surgeries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientSurgery::class)->latest('surgery_date');
    }

    public function appointments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Appointment::class)->latest('date')->orderBy('time', 'desc');
    }

    public function todaysAppointments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Appointment::class)->whereDate('date', today())->orderBy('time', 'asc');
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientComment::class)->latest();
    }
}
