<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use Illuminate\Foundation\Events\Dispatchable;

use Illuminate\Queue\SerializesModels;

class PlayerDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $playerId;

    public function __construct(int $playerId)
    {
        $this->playerId = $playerId;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('players'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'player.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->playerId,
        ];
    }
}
