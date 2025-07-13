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

// --- Rutas ESPECÍFICAS para Equipos (Teams) - ¡COLOCADAS PRIMERO! ---
// Ruta para la Tabla de Posiciones (más específica que el recurso 'teams/{id}')
Route::get('teams/standings', [TeamController::class, 'getStandings']);

// Ruta específica para la subida masiva de equipos desde archivos .xls o .csv
Route::post('teams/upload', [FileUploadController::class, 'uploadTeams']);

// --- Rutas genéricas apiResource para Equipos (Teams) ---
// apiResource crea rutas para: GET /teams, POST /teams, GET /teams/{id}, PUT /teams/{id}, DELETE /teams/{id}
Route::apiResource('teams', TeamController::class);


// --- Rutas ESPECÍFICAS para Jugadores (Players) - ¡COLOCADAS PRIMERO! ---
// Ruta para obtener la lista de los máximos goleadores
Route::get('players/top-scorers', [PlayerController::class, 'getTopScorers']);

// Ruta específica para la subida masiva de jugadores desde archivos .xls o .csv
Route::post('players/upload', [FileUploadController::class, 'uploadPlayers']);

// --- Rutas genéricas apiResource para Jugadores (Players) ---
// apiResource crea rutas para: GET /players, POST /players, GET /players/{id}, PUT /players/{id}, DELETE /players/{id}
Route::apiResource('players', PlayerController::class);


// --- Rutas para la gestión de Partidos (Matches) ---
// apiResource crea rutas para: GET /matches, POST /matches, GET /matches/{id}, PUT /matches/{id}, DELETE /matches/{id}
// Usamos 'matches' como prefijo de URL, pero el controlador es PartidoController
Route::apiResource('matches', PartidoController::class);

// --- Rutas específicas para MatchPlayer (Estadísticas de Jugadores en Partidos) ---
// Estas rutas manejan la relación entre partidos y jugadores, y sus estadísticas.
// Nota: 'partido' en el segmento de la URL corresponde al parámetro de ruta del controlador.
Route::get('matches/{partido}/players', [PartidoController::class, 'getMatchPlayers']);
Route::post('matches/{partido}/players', [PartidoController::class, 'attachPlayersToMatch']);
Route::put('matches/{partido}/players/{player}', [PartidoController::class, 'updateMatchPlayerStats']);
Route::delete('matches/{partido}/players/{player}', [PartidoController::class, 'detachPlayerFromMatch']);


// --- Ruta para la Valla Menos Vencida (Clean Sheet) ---
// Esta ruta no colisiona con las apiResource existentes, por lo que su posición es flexible.
Route::get('clean-sheets', [CleanSheetController::class, 'index']);
