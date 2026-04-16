<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DoctorAssignedAlert implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $doctorId;
    public $patientName;
    public $eventType;

    public function __construct($doctorId, $patientName, $eventType)
    {
        $this->doctorId = $doctorId;
        $this->patientName = $patientName;
        $this->eventType = $eventType;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('doctor.alerts.' . $this->doctorId),
        ];
    }
}
