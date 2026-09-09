<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Categoria::orderBy('nombre');

        if ($request->filled('busqueda')) {
            $texto = $request->input('busqueda');
            $query->where(function ($q) use ($texto) {
                $q->where('nombre', 'like', "%{$texto}%")
                  ->orWhere('descripcion', 'like', "%{$texto}%");
            });
        }

        $categorias = $query->paginate(15);

        return response()->json($categorias);
    }

    public function store(Request $request)
    {
        if (!$request->user()->tienePermiso('categorias.crear')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias,nombre',
            'descripcion' => 'nullable|string',
        ]);

        $categoria = Categoria::create($validated);

        return response()->json([
            'message' => 'Categoría creada correctamente.',
            'categoria' => $categoria,
        ], 201);
    }

    public function show(Categoria $categoria)
    {
        return response()->json($categoria);
    }

    public function update(Request $request, Categoria $categoria)
    {
        if (!$request->user()->tienePermiso('categorias.editar')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('categorias', 'nombre')->ignore($categoria->id)],
            'descripcion' => 'nullable|string',
        ]);

        $categoria->update($validated);

        return response()->json([
            'message' => 'Categoría actualizada correctamente.',
            'categoria' => $categoria,
        ]);
    }

    public function destroy(Request $request, Categoria $categoria)
    {
        if (!$request->user()->tienePermiso('categorias.eliminar')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        if ($categoria->productos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: esta categoría tiene productos asociados.',
            ], 409);
        }

        $categoria->delete();

        return response()->json([
            'message' => 'Categoría eliminada correctamente.',
        ]);
    }
}