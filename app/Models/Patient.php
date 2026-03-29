<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Computed Last Name, First Name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->last_name}, {$this->first_name}";
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

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientComment::class)->latest();
    }
}
