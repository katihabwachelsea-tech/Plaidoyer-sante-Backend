<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $message;
    public int   $appointmentId;

    public function __construct(array $message, int $appointmentId)
    {
        $this->message       = $message;
        $this->appointmentId = $appointmentId;
    }

    /**
     * Canal privé par RDV — seuls patient et médecin de ce RDV écoutent.
     * Nom du canal : private-appointment.{id}
     */
    public function broadcastOn(): Channel
    {
        return new Channel('appointment.' . $this->appointmentId);
    }

    /**
     * Nom de l'événement tel que Flutter l'écoutera.
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
