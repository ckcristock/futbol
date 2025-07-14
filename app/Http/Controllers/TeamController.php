<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Partido;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
// Eventos (comentados si no se usan WebSockets activos)
// use App\Events\TeamCreated;
// use App\Events\TeamUpdated;
// use App\Events\TeamDeleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; // ¡IMPORTANTE: Importar la clase Rule para validaciones avanzadas!

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     * Obtiene y lista todos los equipos.
     * Este método es llamado por GET /api/teams
     * Ahora puede incluir los jugadores asociados si se pide con 'withPlayers=true'.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if ($request->query('withPlayers')) {
            // Carga equipos con sus jugadores (eager loading)
            return response()->json(Team::with('players')->get());
        }

        // Por defecto, retorna solo los equipos sin sus jugadores
        return response()->json(Team::all());
    }

    /**
     * Store a newly created resource in storage.
     * Almacena un nuevo equipo en la base de datos.
     * Normaliza el nombre del equipo a minúsculas y valida su unicidad sin importar mayúsculas/minúsculas.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Normaliza el nombre: convierte a minúsculas y quita espacios en blanco al inicio/final
        $normalizedName = strtolower(trim($request->input('name')));

        // Reemplaza el nombre original del request con el normalizado ANTES de la validación
        $request->merge(['name' => $normalizedName]);

        $request->validate([
            // Regla de unicidad avanzada para que sea case-insensitive en la base de datos (si la DB lo soporta con COLLATE)
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams')->where(function ($query) use ($normalizedName) {
                    return $query->whereRaw('lower(name) = ?', [$normalizedName]);
                }),
            ],
            'city' => 'nullable|string|max:255',
        ]);

        $team = Team::create($request->all());
        // event(new TeamCreated($team)); // Descomentar si usas broadcasting
        return response()->json($team, 201);
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
        return response()->json($team);
    }

    /**
     * Update the specified resource in storage.
     * Actualiza un equipo existente en la base de datos.
     * Normaliza el nombre del equipo y valida su unicidad.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Team $team)
    {
        // Normaliza el nombre: convierte a minúsculas y quita espacios en blanco al inicio/final
        $normalizedName = strtolower(trim($request->input('name')));

        // Reemplaza el nombre original del request con el normalizado ANTES de la validación
        $request->merge(['name' => $normalizedName]);

        $request->validate([
            // Regla de unicidad avanzada: ignora el ID del equipo actual y es case-insensitive
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams')->ignore($team->id)->where(function ($query) use ($normalizedName) {
                    return $query->whereRaw('lower(name) = ?', [$normalizedName]);
                }),
            ],
            'city' => 'nullable|string|max:255',
        ]);

        $team->update($request->all());
        // event(new TeamUpdated($team)); // Descomentar si usas broadcasting
        return response()->json($team);
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
        $team->delete();
        // event(new TeamDeleted($team->id)); // Descomentar si usas broadcasting
        return response()->json(null, 204);
    }

    /**
     * Calcula y retorna la tabla de posiciones de los equipos.
     * Este método es llamado por GET /api/teams/standings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStandings()
    {
        $teams = Team::all();
        $standings = [];

        foreach ($teams as $team) {
            $pj = 0; // Partidos Jugados
            $pg = 0; // Partidos Ganados
            $pe = 0; // Partidos Empatados
            $pp = 0; // Partidos Perdidos
            $gf = 0; // Goles a Favor
            $gc = 0; // Goles en Contra
            $puntos = 0;

            // Partidos como equipo local
            $homeMatches = $team->homeMatches; // Accede a la relación definida en el modelo Team
            foreach ($homeMatches as $match) {
                $pj++;
                $gf += $match->home_team_score;
                $gc += $match->away_team_score;

                if ($match->home_team_score > $match->away_team_score) {
                    $pg++;
                    $puntos += 3;
                } elseif ($match->home_team_score < $match->away_team_score) {
                    $pp++;
                } else {
                    $pe++;
                    $puntos += 1;
                }
            }

            // Partidos como equipo visitante
            $awayMatches = $team->awayMatches; // Accede a la relación definida en el modelo Team
            foreach ($awayMatches as $match) {
                $pj++;
                $gf += $match->away_team_score;
                $gc += $match->home_team_score;

                if ($match->away_team_score > $match->home_team_score) {
                    $pg++;
                    $puntos += 3;
                } elseif ($match->away_team_score < $match->home_team_score) {
                    $pp++;
                } else {
                    $pe++;
                    $puntos += 1;
                }
            }

            $gd = $gf - $gc; // Diferencia de Goles

            $standings[] = [
                'id' => $team->id,
                'name' => $team->name,
                'PJ' => $pj,
                'PG' => $pg,
                'PE' => $pe,
                'PP' => $pp,
                'GF' => $gf,
                'GC' => $gc,
                'GD' => $gd,
                'PUNTOS' => $puntos,
            ];
        }

        // Ordenar la tabla de posiciones:
        // 1. Por PUNTOS (descendente)
        // 2. Por GD (Diferencia de Goles) (descendente)
        // 3. Por GF (Goles a Favor) (descendente)
        usort($standings, function ($a, $b) {
            if ($a['PUNTOS'] != $b['PUNTOS']) {
                return $b['PUNTOS'] <=> $a['PUNTOS'];
            }
            if ($a['GD'] != $b['GD']) {
                return $b['GD'] <=> $a['GD'];
            }
            return $b['GF'] <=> $a['GF'];
        });

        return response()->json($standings);
    }
}
