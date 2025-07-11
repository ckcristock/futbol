<?php

namespace App\Events;

use App\Models\MatchPlayer; // Importa el modelo MatchPlayer
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchPlayerStatsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $matchPlayer; // La instancia de la tabla pivote MatchPlayer

    public function __construct(MatchPlayer $matchPlayer)
    {
        // Carga las relaciones necesarias para el frontend
        $this->matchPlayer = $matchPlayer->load(['match', 'player.team']);
    }

    public function broadcastOn(): array
    {
        // Puedes emitir a un canal específico del partido si quieres actualizaciones más granulares
        return [
            new Channel('matches'), // Canal general de partidos
            new Channel('match.' . $this->matchPlayer->match_id . '.players'), // Canal específico para este partido
        ];
    }

    public function broadcastAs(): string
    {
        return 'match_player_stats.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'match_player' => $this->matchPlayer->toArray(),
        ];
    }
}
