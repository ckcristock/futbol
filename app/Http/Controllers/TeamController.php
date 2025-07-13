<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Partido; // Asegúrate de importar el modelo Partido!
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Events\TeamCreated;
use App\Events\TeamUpdated;
use App\Events\TeamDeleted;
use Illuminate\Support\Facades\DB; // Asegúrate de importar DB!

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     * Obtiene y lista todos los equipos.
     * Este método es llamado por GET /api/teams
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
     * Este método es llamado por POST /api/teams
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            'city' => 'nullable|string|max:255',
        ]);

        $team = Team::create($request->all());
        event(new TeamCreated($team)); // Dispara el evento WebSocket
        return response()->json($team, 201);
    }

    /**
     * Display the specified resource.
     * Muestra un equipo específico por su ID.
     * Este método es llamado por GET /api/teams/{id}
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
     * Este método es llamado por PUT /api/teams/{id}
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Team $team)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id,
            'city' => 'nullable|string|max:255',
        ]);

        $team->update($request->all());
        event(new TeamUpdated($team)); // Dispara el evento WebSocket
        return response()->json($team);
    }

    /**
     * Remove the specified resource from storage.
     * Elimina un equipo de la base de datos.
     * Este método es llamado por DELETE /api/teams/{id}
     *
     * @param  \App\Models\Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Team $team)
    {
        $team->delete();
        event(new TeamDeleted($team->id)); // Dispara el evento WebSocket
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
