<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetupInicialRequest;
use App\Models\ConfiguracionEmpresa;
use App\Models\Role;
use App\Models\User;
use App\Services\ConfiguracionSingleton;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SetupController extends Controller
{
    public function estado()
    {
        $configurado = ConfiguracionEmpresa::exists();

        return response()->json(['configurado' => $configurado]);
    }

    public function inicializar(SetupInicialRequest $request)
    {
        if (ConfiguracionEmpresa::exists()) {
            return response()->json([
                'message' => 'El sistema ya fue configurado previamente.',
            ], 409);
        }

        $datos = $request->validated();

        $resultado = DB::transaction(function () use ($datos, $request) {
            $rolAdmin = Role::firstOrCreate(
                ['nombre' => 'Gerente de Bodega'],
                ['descripcion' => 'Control total del sistema.']
            );
            Role::firstOrCreate(['nombre' => 'Encargado de Ventas'], ['descripcion' => 'Registra ventas.']);
            Role::firstOrCreate(['nombre' => 'Auditor'], ['descripcion' => 'Acceso de solo lectura.']);

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
            }

            $configuracion = ConfiguracionEmpresa::create([
                'nombre_licoreria' => $datos['nombre_licoreria'],
                'eslogan' => $datos['eslogan'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'email' => $datos['email_empresa'] ?? null,
                'direccion' => $datos['direccion'] ?? null,
                'moneda' => $datos['moneda'],
                'color_primario' => $datos['color_primario'],
                'logo_path' => $logoPath,
            ]);

            $admin = User::create([
                'name' => $datos['admin_name'],
                'email' => $datos['admin_email'],
                'password' => $datos['admin_password'],
                'role_id' => $rolAdmin->id,
                'estado' => true,
            ]);

            // Retornamos también $rolAdmin para que esté disponible afuera
            return [$configuracion, $admin, $rolAdmin];
        });

        [$configuracion, $admin, $rolAdmin] = $resultado;

        ConfiguracionSingleton::obtenerInstancia()->refrescar();

        $token = $admin->createToken('bottletrack_token')->plainTextToken;

        return response()->json([
            'message' => 'Sistema inicializado correctamente.',
            'token' => $token,
            'user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $rolAdmin->nombre,
            ],
        ], 201);
    }

    public function publica()
    {
        $config = ConfiguracionSingleton::obtenerInstancia()->obtenerConfiguracion();

        if (!$config) {
            return response()->json(['configurado' => false]);
        }

        return response()->json([
            'configurado' => true,
            'nombre_licoreria' => $config->nombre_licoreria,
            'logo_url' => $config->logo_url,
            'color_primario' => $config->color_primario,
        ]);
    }

    public function obtener()
    {
        $config = ConfiguracionSingleton::obtenerInstancia()->obtenerConfiguracion();

        return response()->json($config);
    }

    public function actualizar(Request $request)
{
    $config = ConfiguracionEmpresa::first();

    if (!$config) {
        return response()->json(['message' => 'Configuración no encontrada.'], 404);
    }

    $validados = $request->validate([
        'nombre_licoreria' => 'required|string|max:255',
        'eslogan'          => 'nullable|string|max:255',
        'telefono'         => 'nullable|string|max:50',
        'email'            => 'nullable|email|max:255',
        'direccion'        => 'nullable|string|max:255',
        'moneda'           => 'required|string|max:10',
        'color_primario'   => 'required|string|max:20',
        'logo'             => 'nullable|image|max:2048',
    ]);

    if ($request->hasFile('logo')) {
        // Eliminar logo anterior si existe
        if ($config->logo_path && Storage::disk('public')->exists($config->logo_path)) {
            Storage::disk('public')->delete($config->logo_path);
        }
        $validados['logo_path'] = $request->file('logo')->store('logos', 'public');
    }

    $config->update($validados);

    // Refrescar singleton
    ConfiguracionSingleton::obtenerInstancia()->refrescar();

    return response()->json([
        'message' => 'Configuración actualizada exitosamente.',
        'configuracion' => $config->fresh(),
    ]);
}
}
