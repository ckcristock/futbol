<?php

namespace App\Http\Controllers;

use App\Models\Partido; // Importamos el modelo Partido
use App\Models\Player;   // Importamos el modelo Player
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Para consultas más complejas

class CleanSheetController extends Controller
{
    /**
     * Calcula y retorna la lista de porteros con la "valla menos vencida".
     *
     * Se considera "valla menos vencida" si el partido terminó 0-0 para ambos equipos,
     * y el portero jugó el partido completo. Esta es una interpretación común.
     * Puedes ajustar la lógica según tus reglas específicas (ej: solo un equipo no recibe goles).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // 1. Identificar a los jugadores que son porteros.
        // Asumimos que los porteros tienen 'Portero' o 'Goalkeeper' en su campo 'position'.
        // Es importante que el campo 'position' en tu tabla 'players' sea consistente.
        $goalkeeperPosition = 'Portero'; // Puedes hacer esto configurable o buscar por ID de posición

        $cleanSheets = Player::select(
            'players.id',
            'players.name',
            'teams.name as team_name',
            DB::raw('COUNT(DISTINCT partido_id) as clean_sheets_count') // Contar partidos sin goles en contra
        )
            ->join('teams', 'players.team_id', '=', 'teams.id')
            ->join('match_players', 'players.id', '=', 'match_players.player_id')
            ->join('matches', 'match_players.match_id', '=', 'matches.id')
            ->where('players.position', $goalkeeperPosition) // Filtrar por porteros
            ->where('match_players.played_full_match', true) // Asumimos que jugó todo el partido
            ->where(function ($query) {
                // Un "clean sheet" para un portero de equipo LOCAL significa que el equipo VISITANTE no metió goles.
                $query->where(function ($q) {
                    $q->whereColumn('matches.home_team_id', 'players.team_id')
                        ->where('matches.away_team_score', 0);
                })
                    // Un "clean sheet" para un portero de equipo VISITANTE significa que el equipo LOCAL no metió goles.
                    ->orWhere(function ($q) {
                        $q->whereColumn('matches.away_team_id', 'players.team_id')
                            ->where('matches.home_team_score', 0);
                    });
            })
            ->groupBy('players.id', 'players.name', 'teams.name')
            ->orderByDesc('clean_sheets_count')
            ->get();

        return response()->json($cleanSheets);
    }
}
