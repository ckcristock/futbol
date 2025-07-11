<?php

namespace App\Http\Controllers;

use App\Models\Partido; // Importa el modelo Partido (nuestro antiguo Match)
use App\Models\Player; // Necesitamos el modelo Player para las relaciones
use App\Models\MatchPlayer; // Necesitamos el modelo MatchPlayer para la tabla pivote de estadísticas
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Events\PartidoCreated; // Importa los eventos de WebSocket para Partido
use App\Events\PartidoUpdated;
use App\Events\PartidoDeleted;
use App\Events\MatchPlayerStatsUpdated; // Importa el evento para estadísticas de jugadores

class PartidoController extends Controller
{
    /**
     * Obtiene y lista todos los partidos.
     * Incluye los equipos local y visitante, y los jugadores participantes con sus estadísticas.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(
            Partido::with(['homeTeam', 'awayTeam', 'players']) // Carga las relaciones definidas en el modelo Partido
                ->orderBy('match_date', 'desc') // Ordena los partidos por fecha descendente
                ->get()
        );
    }

    /**
     * Almacena un nuevo partido en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Valida los datos de entrada para crear un partido
        $request->validate([
            'home_team_id' => 'required|exists:teams,id', // ID del equipo local, debe existir en la tabla 'teams'
            'away_team_id' => 'required|exists:teams,id|different:home_team_id', // ID del equipo visitante, debe existir y ser diferente del local
            'match_date' => 'required|date', // Fecha y hora del partido
            'location' => 'nullable|string|max:255', // Ubicación (opcional)
            'home_team_score' => 'integer|min:0', // Marcador local (entero, mínimo 0)
            'away_team_score' => 'integer|min:0', // Marcador visitante (entero, mínimo 0)
            'status' => 'string|in:scheduled,in_progress,finished,cancelled', // Estado válido del partido
        ]);

        $partido = Partido::create($request->all()); // Crea el partido en la DB
        event(new PartidoCreated($partido)); // Dispara un evento WebSocket informando la creación del partido
        return response()->json($partido->load(['homeTeam', 'awayTeam']), 201); // Retorna el partido creado con sus relaciones
    }

    /**
     * Muestra un partido específico por su ID.
     * Carga sus equipos y jugadores participantes.
     *
     * @param  \App\Models\Partido  $partido
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Partido $partido)
    {
        return response()->json($partido->load(['homeTeam', 'awayTeam', 'players'])); // Retorna el partido con todas sus relaciones
    }

    /**
     * Actualiza un partido existente en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Partido  $partido
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Partido $partido)
    {
        // Valida los datos de entrada para actualizar un partido (campos opcionales con 'sometimes')
        $request->validate([
            'home_team_id' => 'sometimes|required|exists:teams,id',
            'away_team_id' => 'sometimes|required|exists:teams,id|different:home_team_id',
            'match_date' => 'sometimes|required|date',
            'location' => 'nullable|string|max:255',
            'home_team_score' => 'sometimes|integer|min:0',
            'away_team_score' => 'sometimes|integer|min:0',
            'status' => 'sometimes|string|in:scheduled,in_progress,finished,cancelled',
        ]);

        $partido->update($request->all()); // Actualiza el partido
        event(new PartidoUpdated($partido)); // Dispara un evento WebSocket informando la actualización
        return response()->json($partido->load(['homeTeam', 'awayTeam'])); // Retorna el partido actualizado
    }

    /**
     * Elimina un partido de la base de datos.
     *
     * @param  \App\Models\Partido  $partido
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Partido $partido)
    {
        $partidoId = $partido->id; // Guarda el ID antes de eliminarlo
        $partido->delete(); // Elimina el partido
        event(new PartidoDeleted($partidoId)); // Dispara un evento WebSocket informando la eliminación (solo el ID)
        return response()->json(null, 204); // Retorna una respuesta sin contenido (éxito)
    }

    // --- Endpoints para MatchPlayer (Estadísticas de Jugadores en Partidos) ---

    /**
     * Obtiene los jugadores y sus estadísticas para un partido específico.
     *
     * @param  \App\Models\Partido  $partido
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMatchPlayers(Partido $partido)
    {
        // Carga los jugadores relacionados con el partido a través de la tabla pivote,
        // incluyendo los datos de su equipo.
        return response()->json($partido->players()->with('team')->get());
    }

    /**
     * Adjunta jugadores a un partido y establece sus estadísticas iniciales o las actualiza.
     * Espera un array de objetos JSON en el cuerpo de la solicitud:
     * [{"player_id": 1, "goals": 0, "assists": 0, "played_full_match": false}, ...]
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Partido  $partido
     * @return \Illuminate\Http\JsonResponse
     */
    public function attachPlayersToMatch(Request $request, Partido $partido)
    {
        // Valida la estructura del array de jugadores y sus datos
        $request->validate([
            'players' => 'required|array',
            'players.*.player_id' => 'required|exists:players,id',
            'players.*.goals' => 'integer|min:0|nullable',
            'players.*.assists' => 'integer|min:0|nullable',
            'players.*.played_full_match' => 'boolean|nullable',
        ]);

        $attachedPlayers = [];
        foreach ($request->players as $playerData) {
            // updateOrCreate intenta encontrar un registro por match_id y player_id,
            // si lo encuentra, lo actualiza; si no, lo crea.
            $matchPlayer = MatchPlayer::updateOrCreate(
                [
                    'match_id' => $partido->id,
                    'player_id' => $playerData['player_id'],
                ],
                [
                    'goals' => $playerData['goals'] ?? 0, // Usa el valor del request o 0 por defecto
                    'assists' => $playerData['assists'] ?? 0,
                    'played_full_match' => $playerData['played_full_match'] ?? false,
                ]
            );
            $attachedPlayers[] = $matchPlayer->load('player.team'); // Carga la relación con el jugador y su equipo
            event(new MatchPlayerStatsUpdated($matchPlayer)); // Dispara un evento por cada estadística actualizada
        }

        return response()->json($attachedPlayers, 200); // Retorna los jugadores adjuntos/actualizados
    }

    /**
     * Actualiza las estadísticas de un jugador específico en un partido específico.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Partido  $partido
     * @param  \App\Models\Player  $player
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMatchPlayerStats(Request $request, Partido $partido, Player $player)
    {
        // Valida los campos de estadísticas
        $request->validate([
            'goals' => 'integer|min:0|nullable',
            'assists' => 'integer|min:0|nullable',
            'played_full_match' => 'boolean|nullable',
        ]);

        // Encuentra el registro específico en la tabla pivote MatchPlayer
        $matchPlayer = MatchPlayer::where('match_id', $partido->id)
            ->where('player_id', $player->id)
            ->firstOrFail(); // Lanza 404 si no se encuentra

        $matchPlayer->update($request->all()); // Actualiza las estadísticas
        event(new MatchPlayerStatsUpdated($matchPlayer)); // Dispara el evento WebSocket
        return response()->json($matchPlayer->load('player.team')); // Retorna el registro actualizado
    }

    /**
     * Desvincula (elimina) un jugador de un partido en la tabla pivote.
     *
     * @param  \App\Models\Partido  $partido
     * @param  \App\Models\Player  $player
     * @return \Illuminate\Http\JsonResponse
     */
    public function detachPlayerFromMatch(Partido $partido, Player $player)
    {
        // Elimina el registro de la tabla MatchPlayer
        $deleted = MatchPlayer::where('match_id', $partido->id)
            ->where('player_id', $player->id)
            ->delete();

        if ($deleted) {
            return response()->json(null, 204); // Éxito, sin contenido
        }

        return response()->json(['message' => 'Player not found in this match.'], 404); // No encontrado
    }
}
