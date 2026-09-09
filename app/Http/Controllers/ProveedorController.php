<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Proveedor::where('activo', true);

        if ($request->filled('busqueda')) {
            $texto = $request->input('busqueda');
            $query->where(function ($q) use ($texto) {
                $q->where('razon_social', 'like', "%{$texto}%")
                  ->orWhere('ruc', 'like', "%{$texto}%");
            });
        }

        $proveedores = $query->orderBy('razon_social')->paginate(15);

        return response()->json($proveedores);
    }

    public function store(Request $request)
    {
        if (!$request->user()->tienePermiso('proveedores.crear')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        $validated = $request->validate([
            'ruc' => 'required|string|max:20|unique:proveedores,ruc',
            'razon_social' => 'required|string|max:150',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'direccion' => 'nullable|string|max:255',
        ]);

        $proveedor = Proveedor::create($validated);

        return response()->json([
            'message' => 'Proveedor creado correctamente.',
            'proveedor' => $proveedor,
        ], 201);
    }

    public function show(Proveedor $proveedor)
    {
        return response()->json($proveedor);
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        if (!$request->user()->tienePermiso('proveedores.editar')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        $validated = $request->validate([
            'ruc' => ['required', 'string', 'max:20', Rule::unique('proveedores', 'ruc')->ignore($proveedor->id)],
            'razon_social' => 'required|string|max:150',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'direccion' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $proveedor->update($validated);

        return response()->json([
            'message' => 'Proveedor actualizado correctamente.',
            'proveedor' => $proveedor,
        ]);
    }

    public function destroy(Request $request, Proveedor $proveedor)
    {
        if (!$request->user()->tienePermiso('proveedores.eliminar')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        $proveedor->update(['activo' => false]);

        return response()->json([
            'message' => 'Proveedor desactivado correctamente.',
        ]);
    }
}