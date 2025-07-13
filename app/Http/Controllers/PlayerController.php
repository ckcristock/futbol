<?php

namespace App\Http\Controllers;

use App\Models\Player; // Importa el modelo Player
use App\Models\Team;   // Importa el modelo Team para usar en getTopScorers
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException; // Para manejar errores de validación
use App\Events\PlayerCreated; // Eventos de WebSocket para jugadores
use App\Events\PlayerUpdated;
use App\Events\PlayerDeleted;
use Illuminate\Support\Facades\DB; // Para usar consultas de base de datos crudas en getTopScorers

class PlayerController extends Controller
{
    /**
     * Obtiene y lista todos los jugadores.
     * Carga la relación con el equipo al que pertenece cada jugador.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(Player::with('team')->get()); // Retorna todos los jugadores con su equipo
    }

    /**
     * Almacena un nuevo jugador en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Valida los datos de entrada para crear un jugador
        $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'team_id' => 'nullable|exists:teams,id', // El ID del equipo es opcional pero debe existir si se proporciona
        ]);

        $player = Player::create($request->all()); // Crea el jugador
        event(new PlayerCreated($player)); // Dispara un evento WebSocket informando la creación
        return response()->json($player->load('team'), 201); // Retorna el jugador creado con su equipo
    }

    /**
     * Muestra un jugador específico por su ID.
     * Carga la relación con el equipo.
     *
     * @param  \App\Models\Player  $player
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Player $player)
    {
        return response()->json($player->load('team')); // Retorna el jugador con su equipo
    }

    /**
     * Actualiza un jugador existente en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Player  $player
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Player $player)
    {
        // Valida los datos de entrada para la actualización
        $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $player->update($request->all()); // Actualiza el jugador
        event(new PlayerUpdated($player)); // Dispara un evento WebSocket informando la actualización
        return response()->json($player->load('team')); // Retorna el jugador actualizado
    }

    /**
     * Elimina un jugador de la base de datos.
     *
     * @param  \App\Models\Player  $player
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Player $player)
    {
        $playerId = $player->id; // Guarda el ID antes de eliminarlo
        $player->delete(); // Elimina el jugador
        event(new PlayerDeleted($playerId)); // Dispara un evento WebSocket informando la eliminación
        return response()->json(null, 204); // Retorna una respuesta sin contenido (éxito)
    }

    /**
     * Calcula y retorna la lista de los máximos goleadores.
     * Este método es llamado por la ruta /api/players/top-scorers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTopScorers()
    {
        $topScorers = Player::select(
            'players.id',
            'players.name',
            // Asegúrate de que 'birth_date' esté en tu tabla players si lo necesitas para la edad
            // 'players.birth_date', 
            'teams.name as team_name',
            DB::raw('SUM(match_players.goals) as total_goals')
        )
            ->join('teams', 'players.team_id', '=', 'teams.id') // Une con la tabla teams
            ->join('match_players', 'players.id', '=', 'match_players.player_id') // Une con la tabla pivote
            // Agrupar por todos los campos seleccionados que no son agregados (SUM)
            ->groupBy('players.id', 'players.name', 'teams.name')
            ->orderByDesc('total_goals') // Ordena por total de goles de forma descendente
            ->limit(10) // Limita el resultado a los 10 primeros
            ->get();

        /*
        // Opcional: Calcular la edad si tienes 'birth_date' y la necesitas en el frontend
        // Asegúrate de que Carbon esté importado: use Carbon\Carbon;
        $topScorers->each(function ($player) {
            if (isset($player->birth_date) && $player->birth_date) {
                $player->age = Carbon\Carbon::parse($player->birth_date)->age;
            } else {
                $player->age = null;
            }
        });
        */

        return response()->json($topScorers);
    }
}
