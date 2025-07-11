<?php

namespace App\Events;

use App\Models\Partido;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartidoCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $partido;

    public function __construct(Partido $partido)
    {
        $this->partido = $partido->load(['homeTeam', 'awayTeam', 'players']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('matches'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'partido.created';
    }

    public function broadcastWith(): array
    {
        return [
            'partido' => $this->partido->toArray(),
        ];
    }
}
