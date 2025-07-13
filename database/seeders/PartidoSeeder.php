<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Partido; // Importa el modelo Partido
use App\Models\Team;     // Importa el modelo Team para obtener IDs
use Carbon\Carbon;       // Para manejar fechas

class PartidoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barcelona = Team::where('name', 'FC Barcelona')->first();
        $realMadrid = Team::where('name', 'Real Madrid')->first();
        $manCity = Team::where('name', 'Manchester City')->first();
        $bayern = Team::where('name', 'Bayern Munich')->first();

        if (!$barcelona || !$realMadrid || !$manCity || !$bayern) {
            $this->command->warn('Asegúrate de que TeamSeeder se ejecute primero y cree los equipos necesarios.');
            return;
        }

        // Partido 1: FC Barcelona vs Real Madrid (2-1)
        Partido::create([
            'home_team_id' => $barcelona->id,
            'away_team_id' => $realMadrid->id,
            'match_date' => Carbon::now()->subDays(5)->startOfDay(), // Hace 5 días
            'location' => 'Camp Nou',
            'home_team_score' => 2,
            'away_team_score' => 1,
            'status' => 'finished',
        ]);

        // Partido 2: Manchester City vs Bayern Munich (3-3)
        Partido::create([
            'home_team_id' => $manCity->id,
            'away_team_id' => $bayern->id,
            'match_date' => Carbon::now()->subDays(3)->startOfDay(), // Hace 3 días
            'location' => 'Etihad Stadium',
            'home_team_score' => 3,
            'away_team_score' => 3,
            'status' => 'finished',
        ]);

        // Partido 3: Real Madrid vs Manchester City (1-0) - Partido donde el portero del Real Madrid tendrá valla invicta
        Partido::create([
            'home_team_id' => $realMadrid->id,
            'away_team_id' => $manCity->id,
            'match_date' => Carbon::now()->subDays(1)->startOfDay(), // Ayer
            'location' => 'Santiago Bernabéu',
            'home_team_score' => 1,
            'away_team_score' => 0,
            'status' => 'finished',
        ]);

        $this->command->info('Partidos creados.');
    }
}
