<?php

namespace App\Events;

use App\Models\Player;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $player;

    public function __construct(Player $player)
    {
        $this->player = $player->load('team');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('players'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'player.created';
    }

    public function broadcastWith(): array
    {
        return [
            'player' => $this->player->toArray(),
        ];
    }
}
