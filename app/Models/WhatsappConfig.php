<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WhatsappConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_url',
        'instance_id',
        'token',
        'reminder_days_before',
        'reminder_time',
        'message_template',
        'is_active',
    ];
}
