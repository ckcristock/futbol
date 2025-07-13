<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Llama a los seeders en el orden de dependencia
        $this->call([
            TeamSeeder::class,
            PlayerSeeder::class,        // Depende de TeamSeeder
            PartidoSeeder::class,       // Depende de TeamSeeder
            MatchPlayerSeeder::class,   // Depende de PartidoSeeder y PlayerSeeder
        ]);
    }
}
