<?php

namespace App\Http\Controllers;

use App\Models\Team; // Importa el modelo Team
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException; // Para manejar errores de validación
use App\Events\TeamCreated; // Importa los eventos de WebSocket para equipos
use App\Events\TeamUpdated;
use App\Events\TeamDeleted;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     * Obtiene y lista todos los equipos.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(Team::all()); // Retorna todos los equipos en formato JSON
    }

    /**
     * Store a newly created resource in storage.
     * Almacena un nuevo equipo en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Valida los datos de entrada
        $request->validate([
            'name' => 'required|string|max:255|unique:teams,name', // El nombre es requerido, string, max 255 y debe ser único
            'city' => 'nullable|string|max:255', // La ciudad es opcional
        ]);

        $team = Team::create($request->all()); // Crea el equipo con los datos validados
        event(new TeamCreated($team)); // Dispara un evento WebSocket informando la creación
        return response()->json($team, 201); // Retorna el equipo creado con un código de estado 201 (Created)
    }

    /**
     * Display the specified resource.
     * Muestra un equipo específico por su ID.
     *
     * @param  \App\Models\Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Team $team)
    {
        return response()->json($team); // Retorna el equipo encontrado
    }

    /**
     * Update the specified resource in storage.
     * Actualiza un equipo existente en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Team $team)
    {
        // Valida los datos de entrada para la actualización
        $request->validate([
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id, // El nombre es único, excepto para el propio ID del equipo
            'city' => 'nullable|string|max:255',
        ]);

        $team->update($request->all()); // Actualiza el equipo
        event(new TeamUpdated($team)); // Dispara un evento WebSocket informando la actualización
        return response()->json($team); // Retorna el equipo actualizado
    }

    /**
     * Remove the specified resource from storage.
     * Elimina un equipo de la base de datos.
     *
     * @param  \App\Models\Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Team $team)
    {
        $team->delete(); // Elimina el equipo
        event(new TeamDeleted($team->id)); // Dispara un evento WebSocket informando la eliminación (solo el ID)
        return response()->json(null, 204); // Retorna una respuesta sin contenido con código 204 (No Content)
    }
}
