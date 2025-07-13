<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Player; // Importa el modelo Player
use App\Models\Team;   // Importa el modelo Team para obtener IDs

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = Team::all(); // Obtener todos los equipos existentes

        if ($teams->isEmpty()) {
            $this->command->warn('No hay equipos para asignar a jugadores. Ejecuta TeamSeeder primero.');
            return;
        }

        Player::create([
            'name' => 'Lionel Messi',
            'position' => 'Delantero',
            'team_id' => $teams->where('name', 'FC Barcelona')->first()->id ?? null,
            'birth_date' => '1987-06-24',
        ]);
        Player::create([
            'name' => 'Cristiano Ronaldo',
            'position' => 'Delantero',
            'team_id' => $teams->where('name', 'Juventus FC')->first()->id ?? null,
            'birth_date' => '1985-02-05',
        ]);
        Player::create([
            'name' => 'Kylian Mbappé',
            'position' => 'Delantero',
            'team_id' => $teams->where('name', 'Paris Saint-Germain')->first()->id ?? null,
            'birth_date' => '1998-12-20',
        ]);
        Player::create([
            'name' => 'Erling Haaland',
            'position' => 'Delantero',
            'team_id' => $teams->where('name', 'Manchester City')->first()->id ?? null,
            'birth_date' => '2000-07-21',
        ]);
        Player::create([
            'name' => 'Manuel Neuer',
            'position' => 'Portero',
            'team_id' => $teams->where('name', 'Bayern Munich')->first()->id ?? null,
            'birth_date' => '1986-03-27',
        ]);
        Player::create([
            'name' => 'Marc-André ter Stegen',
            'position' => 'Portero',
            'team_id' => $teams->where('name', 'FC Barcelona')->first()->id ?? null,
            'birth_date' => '1992-04-30',
        ]);
        // ¡Este es el jugador clave para el ejemplo de valla invicta del Real Madrid!
        Player::create([
            'name' => 'Thibaut Courtois',
            'position' => 'Portero',
            'team_id' => $teams->where('name', 'Real Madrid')->first()->id ?? null,
            'birth_date' => '1992-05-11',
        ]);
        Player::create([
            'name' => 'Kevin De Bruyne',
            'position' => 'Medio',
            'team_id' => $teams->where('name', 'Manchester City')->first()->id ?? null,
            'birth_date' => '1991-06-28',
        ]);

        $this->command->info('Jugadores creados.');
    }
}
