<?php

namespace App\Http\Controllers;

use App\Models\Partido; // Importamos el modelo Partido (que mapea a la tabla 'matches')
use App\Models\Player;   // Importamos el modelo Player
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Para usar consultas de base de datos crudas

class CleanSheetController extends Controller
{
    /**
     * Calcula y retorna la lista de porteros con la "valla menos vencida".
     * Este método es llamado por la ruta /api/clean-sheets.
     *
     * Se considera "valla menos vencida" si el equipo del portero no recibió goles en un partido,
     * y el portero jugó el partido completo.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // 1. Identificar a los jugadores que son porteros.
        // Asumimos que los porteros tienen 'Portero' en su campo 'position'.
        $goalkeeperPosition = 'Portero';

        $cleanSheets = Player::select(
            'players.id',
            'players.name',
            'teams.name as team_name', // Obtener el nombre del equipo del portero
            // Contar los partidos distintos donde este portero jugó y su equipo mantuvo la valla invicta
            DB::raw('COUNT(DISTINCT matches.id) as clean_sheets_count')
        )
            ->join('teams', 'players.team_id', '=', 'teams.id') // Unir con la tabla de equipos
            ->join('match_players', 'players.id', '=', 'match_players.player_id') // Unir con la tabla pivote
            ->join('matches', 'match_players.match_id', '=', 'matches.id') // Unir con la tabla de partidos
            ->where('players.position', $goalkeeperPosition) // Filtrar solo por jugadores que son porteros
            ->where('match_players.played_full_match', true) // Asumimos que el portero jugó todo el partido
            ->where(function ($query) {
                // Lógica para determinar si el equipo del portero tuvo la valla invicta:
                // Si el portero era del equipo LOCAL, el marcador del equipo VISITANTE debe ser 0.
                $query->where(function ($q) {
                    $q->whereColumn('matches.home_team_id', 'players.team_id') // El equipo local es el equipo del portero
                        ->where('matches.away_team_score', 0); // El equipo visitante no anotó goles
                })
                    // O si el portero era del equipo VISITANTE, el marcador del equipo LOCAL debe ser 0.
                    ->orWhere(function ($q) {
                        $q->whereColumn('matches.away_team_id', 'players.team_id') // El equipo visitante es el equipo del portero
                            ->where('matches.home_team_score', 0); // El equipo local no anotó goles
                    });
            })
            // Agrupar por el ID, nombre del jugador y nombre del equipo para contar correctamente los clean sheets por portero
            ->groupBy('players.id', 'players.name', 'teams.name')
            ->orderByDesc('clean_sheets_count') // Ordenar de forma descendente por el número de vallas invictas
            ->get();

        return response()->json($cleanSheets);
    }
}
