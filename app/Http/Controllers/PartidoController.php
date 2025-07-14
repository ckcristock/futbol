<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use App\Models\Player;
use App\Models\MatchPlayer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
// Aunque no estemos usando WebSockets activos, Laravel sigue intentando emitir.
// Puedes comentar o eliminar estas líneas si quieres evitar cualquier referencia a eventos.
use App\Events\PartidoCreated;
use App\Events\PartidoUpdated;
use App\Events\PartidoDeleted;
use App\Events\MatchPlayerStatsUpdated;

class PartidoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(
            Partido::with(['homeTeam', 'awayTeam', 'players']) // Cargar relaciones
                ->orderBy('match_date', 'desc')
                ->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     * Modificado para recibir y guardar las estadísticas de jugadores.
     */
    public function store(Request $request)
    {
        $request->validate([
            'home_team_id' => 'required|exists:teams,id',
            'away_team_id' => 'required|exists:teams,id|different:home_team_id',
            'match_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'home_team_score' => 'integer|min:0',
            'away_team_score' => 'integer|min:0',
            'status' => 'string|in:scheduled,in_progress,finished,cancelled',
            // Validación para los datos de los jugadores en el partido
            'players_stats' => 'array',
            'players_stats.*.player_id' => 'required|exists:players,id',
            'players_stats.*.goals' => 'integer|min:0|nullable',
            'players_stats.*.played_full_match' => 'boolean|nullable',
            'players_stats.*.assists' => 'integer|min:0|nullable', // Si también registras asistencias
        ]);

        // 1. Crear el Partido
        $partido = Partido::create($request->except('players_stats')); // Excluye 'players_stats' de la creación del partido

        // Opcional: Si quieres emitir eventos, descomenta
        // event(new PartidoCreated($partido));

        // 2. Adjuntar Jugadores y Estadísticas al Partido (tabla match_players)
        if ($request->has('players_stats') && is_array($request->players_stats)) {
            foreach ($request->players_stats as $playerStatsData) {
                // Asegura que los campos tengan valores por defecto si no vienen
                $goals = $playerStatsData['goals'] ?? 0;
                $playedFullMatch = $playerStatsData['played_full_match'] ?? false;
                $assists = $playerStatsData['assists'] ?? 0; // Si usas asistencias

                MatchPlayer::create([
                    'match_id' => $partido->id,
                    'player_id' => $playerStatsData['player_id'],
                    'goals' => $goals,
                    'played_full_match' => $playedFullMatch,
                    'assists' => $assists, // Asegúrate de que esta columna exista en match_players
                ]);

                // Opcional: Si quieres emitir eventos para cada estadística de jugador, descomenta
                // event(new MatchPlayerStatsUpdated(MatchPlayer::find(el_id_reciente)));
            }
        }

        return response()->json($partido->load(['homeTeam', 'awayTeam', 'players']), 201); // Retorna el partido con relaciones
    }

    /**
     * Display the specified resource.
     */
    public function show(Partido $partido)
    {
        return response()->json($partido->load(['homeTeam', 'awayTeam', 'players']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Partido $partido)
    {
        $request->validate([
            'home_team_id' => 'sometimes|required|exists:teams,id',
            'away_team_id' => 'sometimes|required|exists:teams,id|different:home_team_id',
            'match_date' => 'sometimes|required|date',
            'location' => 'nullable|string|max:255',
            'home_team_score' => 'sometimes|integer|min:0',
            'away_team_score' => 'sometimes|integer|min:0',
            'status' => 'sometimes|string|in:scheduled,in_progress,finished,cancelled',
            // Validación para la actualización de stats si se envía
            'players_stats' => 'array',
            'players_stats.*.player_id' => 'required|exists:players,id',
            'players_stats.*.goals' => 'integer|min:0|nullable',
            'players_stats.*.played_full_match' => 'boolean|nullable',
            'players_stats.*.assists' => 'integer|min:0|nullable',
        ]);

        $partido->update($request->except('players_stats'));

        // Si se envían stats de jugadores para actualizar
        if ($request->has('players_stats') && is_array($request->players_stats)) {
            foreach ($request->players_stats as $playerStatsData) {
                MatchPlayer::updateOrCreate(
                    [
                        'match_id' => $partido->id,
                        'player_id' => $playerStatsData['player_id'],
                    ],
                    [
                        'goals' => $playerStatsData['goals'] ?? 0,
                        'played_full_match' => $playerStatsData['played_full_match'] ?? false,
                        'assists' => $playerStatsData['assists'] ?? 0,
                    ]
                );
            }
        }

        // Opcional: Si quieres emitir eventos, descomenta
        // event(new PartidoUpdated($partido));
        return response()->json($partido->load(['homeTeam', 'awayTeam', 'players']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partido $partido)
    {
        $partidoId = $partido->id;
        $partido->delete();
        // Opcional: Si quieres emitir eventos, descomenta
        // event(new PartidoDeleted($partidoId));
        return response()->json(null, 204);
    }

    // --- Endpoints para MatchPlayer (Estadísticas de Jugadores en Partidos) ---

    /**
     * Get players and their stats for a specific match.
     */
    public function getMatchPlayers(Partido $partido)
    {
        return response()->json($partido->players()->with('team')->get());
    }

    /**
     * Attach players to a match and set their initial stats.
     * Expected JSON body: {"players": [{"player_id": 1, "goals": 0, "assists": 0, "played_full_match": false}, ...]}
     */
    public function attachPlayersToMatch(Request $request, Partido $partido)
    {
        $request->validate([
            'players' => 'required|array',
            'players.*.player_id' => 'required|exists:players,id',
            'players.*.goals' => 'integer|min:0|nullable',
            'players.*.assists' => 'integer|min:0|nullable',
            'players.*.played_full_match' => 'boolean|nullable',
        ]);

        $attachedPlayers = [];
        foreach ($request->players as $playerData) {
            $matchPlayer = MatchPlayer::updateOrCreate(
                [
                    'match_id' => $partido->id,
                    'player_id' => $playerData['player_id'],
                ],
                [
                    'goals' => $playerData['goals'] ?? 0,
                    'assists' => $playerData['assists'] ?? 0,
                    'played_full_match' => $playerData['played_full_match'] ?? false,
                ]
            );
            $attachedPlayers[] = $matchPlayer->load('player.team');
            // Opcional: Si quieres emitir eventos, descomenta
            // event(new MatchPlayerStatsUpdated($matchPlayer));
        }

        return response()->json($attachedPlayers, 200);
    }

    /**
     * Update stats for a specific player in a specific match.
     */
    public function updateMatchPlayerStats(Request $request, Partido $partido, Player $player)
    {
        $request->validate([
            'goals' => 'integer|min:0|nullable',
            'assists' => 'integer|min:0|nullable',
            'played_full_match' => 'boolean|nullable',
        ]);

        $matchPlayer = MatchPlayer::where('match_id', $partido->id)
            ->where('player_id', $player->id)
            ->firstOrFail();

        $matchPlayer->update($request->all());
        // Opcional: Si quieres emitir eventos, descomenta
        // event(new MatchPlayerStatsUpdated($matchPlayer));
        return response()->json($matchPlayer->load('player.team'));
    }

    /**
     * Detach a player from a match.
     */
    public function detachPlayerFromMatch(Partido $partido, Player $player)
    {
        $deleted = MatchPlayer::where('match_id', $partido->id)
            ->where('player_id', $player->id)
            ->delete();

        if ($deleted) {
            return response()->json(null, 204);
        }

        return response()->json(['message' => 'Player not found in this match.'], 404);
    }
}
