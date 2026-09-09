<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(Role::orderBy('nombre')->get(['id', 'nombre', 'descripcion']));
    }

    public function permisos(Request $request)
    {
        return response()->json(Permiso::orderBy('id')->get());
    }

    public function rolesConPermisos(Request $request)
    {
        $roles = Role::with('permisos')->orderBy('id')->get();
        return response()->json($roles);
    }

    public function actualizarPermisos(Request $request, Role $role)
    {
        if (!$request->user()->tienePermiso('roles.editar')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        $request->validate([
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permisos,id',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:permisos,id',
        ]);

        $ids = $request->input('permission_ids', $request->input('permisos', []));
        $role->permisos()->sync($ids ?? []);

        return response()->json([
            'message' => 'Permisos actualizados correctamente.',
            'role' => $role->load('permisos'),
        ]);
    }
}