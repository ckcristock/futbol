<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Partido;
use Illuminate\Http\Request; // Importante: para poder usar $request en el método index
use Illuminate\Validation\ValidationException;
// Aunque no estemos usando WebSockets activos, Laravel sigue intentando emitir.
// Puedes comentar o eliminar estas líneas si quieres evitar cualquier referencia a eventos.
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
     * Ahora puede incluir los jugadores asociados si se pide con 'withPlayers=true'.
     *
     * @param  \Illuminate\Http\Request  $request // Importante para leer query parameters
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Verifica si la solicitud incluye el parámetro 'withPlayers=true'
        if ($request->query('withPlayers')) {
            // Si el parámetro está presente, carga los equipos EAGER LOADING con sus jugadores
            return response()->json(Team::with('players')->get()); // Carga equipos con sus jugadores
        }

        // Por defecto, si 'withPlayers' no está, retorna solo los equipos sin sus jugadores
        return response()->json(Team::all());
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
        // Opcional: Si quieres emitir eventos (ahora con driver 'log'), puedes descomentar
        // event(new TeamCreated($team));
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
        // Opcional: Si quieres emitir eventos (ahora con driver 'log'), puedes descomentar
        // event(new TeamUpdated($team));
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
        // Opcional: Si quieres emitir eventos (ahora con driver 'log'), puedes descomentar
        // event(new TeamDeleted($team->id));
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
