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
        Schema::create('matches', function (Blueprint $table) { // La tabla en la DB se llamará 'matches'
            $table->id(); // ID único para cada partido

            // Claves foráneas para los equipos (local y visitante)
            $table->foreignId('home_team_id')->constrained('teams')->onDelete('cascade'); // ID del equipo local
            $table->foreignId('away_team_id')->constrained('teams')->onDelete('cascade'); // ID del equipo visitante

            $table->dateTime('match_date'); // Fecha y hora del partido
            $table->string('location')->nullable(); // Ubicación o estadio del partido (opcional)

            $table->integer('home_team_score')->default(0); // Marcador del equipo local, por defecto 0
            $table->integer('away_team_score')->default(0); // Marcador del equipo visitante, por defecto 0

            $table->string('status')->default('scheduled'); // Estado del partido: 'scheduled' (programado), 'in_progress' (en curso), 'finished' (finalizado), 'cancelled' (cancelado)

            $table->timestamps(); // `created_at` y `updated_at` para el control de tiempo

            // Restricción para asegurar que un equipo no juegue contra sí mismo y no haya partidos duplicados en la misma fecha
            $table->unique(['home_team_id', 'away_team_id', 'match_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches'); // Elimina la tabla 'matches' si se revierte la migración
    }
};
