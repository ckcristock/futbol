<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Partido; // ¡Asegúrate de importar el modelo Partido!
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Events\TeamCreated;
use App\Events\TeamUpdated;
use App\Events\TeamDeleted;
use Illuminate\Support\Facades\DB; // ¡Asegúrate de importar DB!

class TeamController extends Controller
{
    // ... (tus métodos index, store, show, update, destroy existentes)

    /**
     * Calcula y retorna la tabla de posiciones de los equipos.
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
