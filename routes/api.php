<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Importa todos los controladores que vamos a usar en nuestras rutas API
use App\Http\Controllers\TeamController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PartidoController; // Nuestro controlador para los partidos
use App\Http\Controllers\FileUploadController; // Para la subida de archivos
use App\Http\Controllers\CleanSheetController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí es donde puedes registrar las rutas API para tu aplicación. Estas
| rutas son cargadas por el RouteServiceProvider y todas ellas estarán
| prefijadas con '/api' y se les aplicará el grupo de middleware 'api'.
|
*/

// --- Rutas para la gestión de Equipos (Teams) ---
// apiResource crea rutas para: GET /teams, POST /teams, GET /teams/{id}, PUT /teams/{id}, DELETE /teams/{id}
Route::apiResource('teams', TeamController::class);

// Ruta específica para la subida masiva de equipos desde archivos .xls o .csv
Route::post('teams/upload', [FileUploadController::class, 'uploadTeams']);

// --- Rutas para la gestión de Jugadores (Players) ---
// apiResource crea rutas para: GET /players, POST /players, GET /players/{id}, PUT /players/{id}, DELETE /players/{id}
Route::apiResource('players', PlayerController::class);

// Ruta específica para la subida masiva de jugadores desde archivos .xls o .csv
Route::post('players/upload', [FileUploadController::class, 'uploadPlayers']);

// --- Rutas para la gestión de Partidos (Matches) ---
// apiResource crea rutas para: GET /matches, POST /matches, GET /matches/{id}, PUT /matches/{id}, DELETE /matches/{id}
// Usamos 'matches' como prefijo de URL, pero el controlador es PartidoController
Route::apiResource('matches', PartidoController::class);

// --- Rutas específicas para MatchPlayer (Estadísticas de Jugadores en Partidos) ---
// Estas rutas manejan la relación entre partidos y jugadores, y sus estadísticas.
// Nota: 'partido' en el segmento de la URL corresponde al parámetro de ruta del controlador.

// Obtener todos los jugadores y sus estadísticas para un partido específico
Route::get('matches/{partido}/players', [PartidoController::class, 'getMatchPlayers']);

// Adjuntar jugadores a un partido y establecer/actualizar sus estadísticas iniciales
Route::post('matches/{partido}/players', [PartidoController::class, 'attachPlayersToMatch']);

// Actualizar las estadísticas de un jugador específico en un partido específico
Route::put('matches/{partido}/players/{player}', [PartidoController::class, 'updateMatchPlayerStats']);

// Desvincular (eliminar) un jugador de un partido (elimina su entrada en la tabla pivote)
Route::delete('matches/{partido}/players/{player}', [PartidoController::class, 'detachPlayerFromMatch']);

// --- Ruta para la Valla Menos Vencida (Clean Sheet) ---
Route::get('clean-sheets', [CleanSheetController::class, 'index']); // Descomentar cuando se cree el controlador