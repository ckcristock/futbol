<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MatchPlayer; // Importa el modelo MatchPlayer
use App\Models\Partido;     // Importa el modelo Partido
use App\Models\Player;      // Importa el modelo Player
use App\Models\Team;        // ¡Importa el modelo Team para obtener sus objetos!

class MatchPlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- Obtención de Partidos ---
        $partido1 = Partido::where('location', 'Camp Nou')->first(); // FCB vs RMA (2-1)
        $partido2 = Partido::where('location', 'Etihad Stadium')->first(); // MCI vs BAY (3-3)
        $partido3 = Partido::where('location', 'Santiago Bernabéu')->first(); // RMA vs MCI (1-0)

        // --- Obtención de Jugadores ---
        $messi = Player::where('name', 'Lionel Messi')->first();
        $ronaldo = Player::where('name', 'Cristiano Ronaldo')->first();
        $mbappe = Player::where('name', 'Kylian Mbappé')->first();
        $haaland = Player::where('name', 'Erling Haaland')->first();
        $neuer = Player::where('name', 'Manuel Neuer')->first();
        $terStegen = Player::where('name', 'Marc-André ter Stegen')->first();
        $deBruyne = Player::where('name', 'Kevin De Bruyne')->first();
        $courtois = Player::where('name', 'Thibaut Courtois')->first(); // ¡Ahora obtenemos a Courtois!

        // --- Obtención de Equipos (Asegurar que estén definidos para su uso) ---
        $barcelona = Team::where('name', 'FC Barcelona')->first();
        $realMadrid = Team::where('name', 'Real Madrid')->first();
        $manCity = Team::where('name', 'Manchester City')->first();
        $bayern = Team::where('name', 'Bayern Munich')->first();


        // --- Verificación de Existencia de todos los datos necesarios ---
        if (
            !$partido1 || !$partido2 || !$partido3 ||
            !$messi || !$ronaldo || !$mbappe || !$haaland || !$neuer || !$terStegen || !$deBruyne || !$courtois || // Añade Courtois a la verificación
            !$barcelona || !$realMadrid || !$manCity || !$bayern
        ) {
            $this->command->warn('ADVERTENCIA: Faltan datos esenciales. Asegúrate de que TeamSeeder, PlayerSeeder y PartidoSeeder se ejecuten primero y creen todos los datos necesarios. Seeder de MatchPlayer omitido.');
            return;
        }

        // Partido 1: FC Barcelona (2) vs Real Madrid (1)
        MatchPlayer::create([
            'match_id' => $partido1->id,
            'player_id' => $messi->id,
            'goals' => 2,
            'assists' => 0,
            'played_full_match' => true
        ]);
        MatchPlayer::create([
            'match_id' => $partido1->id,
            'player_id' => $ronaldo->id,
            'goals' => 1,
            'assists' => 0,
            'played_full_match' => true
        ]);
        MatchPlayer::create([
            'match_id' => $partido1->id,
            'player_id' => $terStegen->id,
            'goals' => 0,
            'assists' => 0,
            'played_full_match' => true
        ]);

        // Partido 2: Manchester City (3) vs Bayern Munich (3)
        MatchPlayer::create([
            'match_id' => $partido2->id,
            'player_id' => $haaland->id,
            'goals' => 2,
            'assists' => 0,
            'played_full_match' => true
        ]);
        MatchPlayer::create([
            'match_id' => $partido2->id,
            'player_id' => $deBruyne->id,
            'goals' => 1,
            'assists' => 1,
            'played_full_match' => true
        ]);
        MatchPlayer::create([
            'match_id' => $partido2->id,
            'player_id' => $neuer->id,
            'goals' => 0,
            'assists' => 0,
            'played_full_match' => true
        ]);

        // Partido 3: Real Madrid (1) vs Manchester City (0)
        MatchPlayer::create([
            'match_id' => $partido3->id,
            'player_id' => $ronaldo->id,
            'goals' => 1,
            'assists' => 0,
            'played_full_match' => true
        ]);
        MatchPlayer::create([
            'match_id' => $partido3->id,
            'player_id' => $haaland->id,
            'goals' => 0,
            'assists' => 0,
            'played_full_match' => true
        ]);
        // ¡Aquí usamos el portero Courtois que sembramos para el Real Madrid!
        MatchPlayer::create([
            'match_id' => $partido3->id,
            'player_id' => $courtois->id, // Usamos el ID de Courtois
            'goals' => 0,
            'assists' => 0,
            'played_full_match' => true
        ]);


        $this->command->info('Estadísticas de jugadores en partidos creadas.');
    }
}
