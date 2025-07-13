<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Importa todos los controladores que vamos a usar en nuestras rutas API
use App\Http\Controllers\TeamController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PartidoController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\CleanSheetController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Rutas de EQUIPOS ---
Route::prefix('teams')->group(function () {
    // Rutas específicas deben ir PRIMERO
    Route::get('standings', [TeamController::class, 'getStandings'])->name('teams.standings');
    Route::post('upload', [FileUploadController::class, 'uploadTeams'])->name('teams.upload');

    // ¡CAMBIO CLAVE AQUÍ! Usamos Route::name() como un grupo
    Route::name('teams.')->group(function () { // Prefijo para los nombres de ruta (ej: teams.index)
        Route::apiResource('', TeamController::class)->parameters(['' => 'team']);
    });
});


// --- Rutas de JUGADORES ---
Route::prefix('players')->group(function () {
    // Rutas específicas deben ir PRIMERO
    Route::get('top-scorers', [PlayerController::class, 'getTopScorers'])->name('players.top-scorers');
    Route::post('upload', [FileUploadController::class, 'uploadPlayers'])->name('players.upload');

    // ¡CAMBIO CLAVE AQUÍ! Usamos Route::name() como un grupo
    Route::name('players.')->group(function () { // Prefijo para los nombres de ruta (ej: players.index)
        Route::apiResource('', PlayerController::class)->parameters(['' => 'player']);
    });
});


// --- Rutas de PARTIDOS (MATCHES) ---
Route::prefix('matches')->group(function () {
    // ¡CAMBIO CLAVE AQUÍ! Usamos Route::name() como un grupo
    Route::name('matches.')->group(function () { // Prefijo para los nombres de ruta (ej: matches.index)
        Route::apiResource('', PartidoController::class)->parameters(['' => 'partido']);
    });

    // Rutas específicas para MatchPlayer (sus nombres ya son específicos)
    Route::get('{partido}/players', [PartidoController::class, 'getMatchPlayers'])->name('matches.players.index');
    Route::post('{partido}/players', [PartidoController::class, 'attachPlayersToMatch'])->name('matches.players.attach');
    Route::put('{partido}/players/{player}', [PartidoController::class, 'updateMatchPlayerStats'])->name('matches.players.update_stats');
    Route::delete('{partido}/players/{player}', [PartidoController::class, 'detachPlayerFromMatch'])->name('matches.players.detach');
});


// --- Ruta para la Valla Menos Vencida (Clean Sheet) ---
Route::get('clean-sheets', [CleanSheetController::class, 'index'])->name('clean-sheets.index');
