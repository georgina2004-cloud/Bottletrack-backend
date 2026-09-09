<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupController extends Controller
{
    public function generar(Request $request)
    {
        if (!$request->user()->tienePermiso('respaldos.generar')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        $nombreArchivo = 'bottletrack_backup_' . now()->format('Y-m-d_His') . '.sql';
        $rutaTemporal = storage_path('app/backups/' . $nombreArchivo);

        if (!file_exists(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        $proceso = new Process([
            'mysqldump',
            '-h', config('database.connections.mysql.host'),
            '-P', config('database.connections.mysql.port'),
            '-u', config('database.connections.mysql.username'),
            '--password=' . config('database.connections.mysql.password'),
            '--protocol=TCP',
            config('database.connections.mysql.database'),
            '--result-file=' . $rutaTemporal,
        ]);

        $proceso->run(null, [
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'PATH' => getenv('PATH'),
        ]);


        if (!$proceso->isSuccessful()) {
            return response()->json([
                'message' => 'Error al generar el respaldo: ' . $proceso->getErrorOutput(),
            ], 500);
        }

        return response()->download($rutaTemporal, $nombreArchivo)->deleteFileAfterSend(true);
    }

    public function restaurar(Request $request)
    {   
        
    if (!$request->user()->tienePermiso('respaldos.restaurar')) {
        return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
    }

    $request->validate([
        'archivo' => ['required', 'file', 'max:51200'],
    ]);

    if (strtolower($request->file('archivo')->getClientOriginalExtension()) !== 'sql') {
        return response()->json([
            'message' => 'El archivo debe tener extensión .sql',
        ], 422);
    }

    if (!file_exists(storage_path('app/backups_restaurar'))) {
        mkdir(storage_path('app/backups_restaurar'), 0755, true);
    }

    $nombreOriginal = $request->file('archivo')->getClientOriginalName();
    $rutaCompleta = storage_path('app/backups_restaurar/' . $nombreOriginal);
    $request->file('archivo')->move(storage_path('app/backups_restaurar'), $nombreOriginal);

    $proceso = new Process([
        'mysql',
        '-h', config('database.connections.mysql.host'),
        '-P', config('database.connections.mysql.port'),
        '-u', config('database.connections.mysql.username'),
        '--password=' . config('database.connections.mysql.password'),
        '--protocol=TCP',
        config('database.connections.mysql.database'),
    ]);

    $proceso->setInput(fopen($rutaCompleta, 'r'));
    $proceso->setTimeout(300);
    $proceso->run(null, [
        'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
        'PATH' => getenv('PATH'),
    ]);

    if (file_exists($rutaCompleta)) {
        unlink($rutaCompleta);
    }

    if (!$proceso->isSuccessful()) {
        return response()->json([
            'message' => 'Error al restaurar el respaldo: ' . $proceso->getErrorOutput(),
        ], 500);
    }

    return response()->json(['message' => 'Base de datos restaurada correctamente.']);
    }
}