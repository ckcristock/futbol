<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $teamId;

    public function __construct(int $teamId)
    {
        $this->teamId = $teamId;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('teams'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'team.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->teamId,
        ];
    }
}
