<?php

namespace App\Events;

use App\Models\Team;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;

    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('teams'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'team.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'team' => $this->team->toArray(),
        ];
    }
}
