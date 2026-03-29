<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientSurgery extends Model
{
    protected $fillable = [
        'patient_id',
        'eye',
        'surgery_date',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'surgery_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
