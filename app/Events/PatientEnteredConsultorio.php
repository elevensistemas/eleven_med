<?php

namespace App\Events;

use App\Models\Patient;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientEnteredConsultorio implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $patient_name;
    public $doctor_name;
    public $sender_id;

    /**
     * Create a new event instance.
     */
    public function __construct(Patient $patient, $doctorName = null, $senderId = null)
    {
        $this->patient_name = $patient->first_name . ' ' . $patient->last_name;
        $this->doctor_name = $doctorName;
        $this->sender_id = $senderId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('system'),
        ];
    }
}
