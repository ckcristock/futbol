<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('match_players', function (Blueprint $table) {
            $table->id(); // ID único para cada entrada en la tabla pivote

            // Claves foráneas que referencian a las tablas 'matches' y 'players'
            // 'constrained()' asume el nombre de la tabla por convención (plural del nombre del modelo)
            // 'onDelete('cascade')' significa que si un partido o un jugador es eliminado, las entradas relacionadas aquí también se eliminan.
            $table->foreignId('match_id')->constrained('matches')->onDelete('cascade'); // ID del partido
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade'); // ID del jugador

            // Estadísticas específicas del jugador en ese partido
            $table->integer('goals')->default(0); // Goles anotados por el jugador en ese partido
            $table->integer('assists')->default(0); // Asistencias realizadas por el jugador
            $table->boolean('played_full_match')->default(false); // Indica si el jugador jugó todo el partido (útil para porteros)

            $table->timestamps(); // `created_at` y `updated_at` para el control de tiempo

            // Restricción para asegurar que un jugador solo aparezca una vez por partido
            $table->unique(['match_id', 'player_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_players'); // Elimina la tabla 'match_players' si se revierte la migración
    }
};
