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
        Schema::create('teams', function (Blueprint $table) {
            $table->id(); // Columna autoincremental para la clave primaria
            $table->string('name')->unique(); // Nombre del equipo, debe ser único
            $table->string('city')->nullable(); // Ciudad del equipo (opcional)
            $table->timestamps(); // Columnas `created_at` y `updated_at`
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
