<?php

namespace App\Http\Controllers;

use App\Models\Player; // Importa el modelo Player
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException; // Para manejar errores de validación
use App\Events\PlayerCreated; // Eventos de WebSocket para jugadores
use App\Events\PlayerUpdated;
use App\Events\PlayerDeleted;

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
}
