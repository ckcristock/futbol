<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Team; // Importa el modelo Team

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Team::create(['name' => 'Real Madrid', 'city' => 'Madrid']);
        Team::create(['name' => 'FC Barcelona', 'city' => 'Barcelona']);
        Team::create(['name' => 'Manchester City', 'city' => 'Manchester']);
        Team::create(['name' => 'Bayern Munich', 'city' => 'Munich']);
        Team::create(['name' => 'Paris Saint-Germain', 'city' => 'Paris']);
        Team::create(['name' => 'Liverpool FC', 'city' => 'Liverpool']);
        Team::create(['name' => 'Juventus FC', 'city' => 'Turin']);
        Team::create(['name' => 'Borussia Dortmund', 'city' => 'Dortmund']);

        $this->command->info('Equipos creados.');
    }
}
