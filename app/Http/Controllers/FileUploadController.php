<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Para gestionar el almacenamiento de archivos
use PhpOffice\PhpSpreadsheet\IOFactory; // Para leer archivos de hojas de cálculo
use App\Models\Team; // Necesitamos el modelo Team
use App\Models\Player; // Necesitamos el modelo Player
use Illuminate\Support\Facades\DB; // Para transacciones de base de datos
use Illuminate\Validation\ValidationException; // Para manejar errores de validación
use App\Events\TeamCreated; // Importa los eventos para disparar actualizaciones
use App\Events\PlayerCreated;

class FileUploadController extends Controller
{
    /**
     * Sube y procesa un archivo para importar equipos.
     * Soporta .xlsx, .xls, .csv
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadTeams(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048', // Archivo requerido, tipos permitidos, max 2MB
        ]);

        $file = $request->file('file');
        $filePath = $file->storeAs('uploads', 'teams_import_' . time() . '.' . $file->getClientOriginalExtension());

        try {
            $spreadsheet = IOFactory::load(Storage::path($filePath));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Asumiendo que la primera fila son los encabezados: 'name', 'city'
            $headers = array_map('strtolower', array_shift($rows)); // Convierte encabezados a minúsculas
            $nameIndex = array_search('name', $headers);
            $cityIndex = array_search('city', $headers);

            if ($nameIndex === false) {
                throw new \Exception("La columna 'name' es requerida en el archivo.");
            }

            $importedTeamsCount = 0;
            $errors = [];

            DB::beginTransaction(); // Inicia una transacción para asegurar la atomicidad

            foreach ($rows as $row) {
                if (empty(array_filter($row))) { // Salta filas completamente vacías
                    continue;
                }

                $teamData = [];
                if (isset($row[$nameIndex])) {
                    $teamData['name'] = trim($row[$nameIndex]);
                }
                if ($cityIndex !== false && isset($row[$cityIndex])) {
                    $teamData['city'] = trim($row[$cityIndex]);
                } else {
                    $teamData['city'] = null; // Asegura que 'city' sea null si no está presente
                }

                // Valida que el nombre no esté vacío
                if (empty($teamData['name'])) {
                    $errors[] = "Fila con nombre de equipo vacío omitida.";
                    continue;
                }

                try {
                    // Busca un equipo por nombre, si existe lo actualiza, si no lo crea
                    $team = Team::updateOrCreate(
                        ['name' => $teamData['name']],
                        $teamData
                    );
                    $importedTeamsCount++;
                    event(new TeamCreated($team)); // Dispara evento de WebSocket (TeamCreated sirve para creado o actualizado)
                } catch (\Illuminate\Database\QueryException $e) {
                    // Manejo de errores específicos de la base de datos, como nombres duplicados
                    if ($e->getCode() === '23000') { // Código para violación de unicidad
                        $errors[] = "Error al importar el equipo '{$teamData['name']}': Nombre duplicado.";
                    } else {
                        $errors[] = "Error desconocido al importar el equipo '{$teamData['name']}': " . $e->getMessage();
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error al importar el equipo '{$teamData['name']}': " . $e->getMessage();
                }
            }

            DB::commit(); // Confirma la transacción si todo fue bien

            Storage::delete($filePath); // Elimina el archivo después de procesar

            return response()->json([
                'message' => "Importación de equipos completada. {$importedTeamsCount} equipos procesados.",
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte la transacción si hay un error
            Storage::delete($filePath); // Elimina el archivo
            return response()->json(['error' => 'Error al procesar el archivo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sube y procesa un archivo para importar jugadores.
     * Soporta .xlsx, .xls, .csv
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadPlayers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048', // Archivo requerido, tipos permitidos, max 2MB
        ]);

        $file = $request->file('file');
        $filePath = $file->storeAs('uploads', 'players_import_' . time() . '.' . $file->getClientOriginalExtension());

        try {
            $spreadsheet = IOFactory::load(Storage::path($filePath));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Asumiendo la primera fila son los encabezados: 'name', 'position', 'team_name'
            $headers = array_map('strtolower', array_shift($rows));
            $nameIndex = array_search('name', $headers);
            $positionIndex = array_search('position', $headers);
            $teamNameIndex = array_search('team_name', $headers); // Nombre del equipo, no el ID

            if ($nameIndex === false) {
                throw new \Exception("La columna 'name' es requerida en el archivo.");
            }

            $importedPlayersCount = 0;
            $errors = [];

            DB::beginTransaction(); // Inicia una transacción

            foreach ($rows as $row) {
                if (empty(array_filter($row))) { // Salta filas completamente vacías
                    continue;
                }

                $playerData = [];
                if (isset($row[$nameIndex])) {
                    $playerData['name'] = trim($row[$nameIndex]);
                }
                if ($positionIndex !== false && isset($row[$positionIndex])) {
                    $playerData['position'] = trim($row[$positionIndex]);
                } else {
                    $playerData['position'] = null;
                }

                $team_id = null;
                $teamName = null;
                if ($teamNameIndex !== false && isset($row[$teamNameIndex])) {
                    $teamName = trim($row[$teamNameIndex]);
                    if (!empty($teamName)) {
                        $team = Team::where('name', $teamName)->first();
                        if ($team) {
                            $team_id = $team->id;
                        } else {
                            $errors[] = "Fila con jugador '{$playerData['name']}' omitida: Equipo '{$teamName}' no encontrado.";
                            continue; // Salta esta fila si el equipo no existe
                        }
                    }
                }
                $playerData['team_id'] = $team_id;

                // Valida que el nombre del jugador no esté vacío
                if (empty($playerData['name'])) {
                    $errors[] = "Fila con nombre de jugador vacío omitida.";
                    continue;
                }

                try {
                    // Busca un jugador por nombre, si existe lo actualiza, si no lo crea
                    // Consideración: ¿Los nombres de jugadores son únicos? Si no, se podría actualizar un jugador incorrecto.
                    // Para mayor precisión, se podría buscar por nombre y team_id, o requerir un ID único.
                    $player = Player::updateOrCreate(
                        ['name' => $playerData['name']], // Esto podría actualizar un jugador con el mismo nombre pero de otro equipo
                        $playerData
                    );
                    $importedPlayersCount++;
                    event(new PlayerCreated($player)); // Dispara evento de WebSocket
                } catch (\Exception $e) {
                    $errors[] = "Error al importar el jugador '{$playerData['name']}': " . $e->getMessage();
                }
            }

            DB::commit(); // Confirma la transacción

            Storage::delete($filePath); // Elimina el archivo después de procesar

            return response()->json([
                'message' => "Importación de jugadores completada. {$importedPlayersCount} jugadores procesados.",
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte la transacción
            Storage::delete($filePath); // Elimina el archivo
            return response()->json(['error' => 'Error al procesar el archivo: ' . $e->getMessage()], 500);
        }
    }
}
