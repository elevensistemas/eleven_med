<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorConfiguration extends Model
{
    protected $fillable = [
        'doctor_id',
        'working_days',
        'shift_1_start',
        'shift_1_end',
        'shift_2_start',
        'shift_2_end',
        'appointment_duration'
    ];

    protected $casts = [
        'working_days' => 'array',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
