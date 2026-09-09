<?php

namespace App\Http\Controllers;

use App\Models\PresentacionProducto;
use App\Models\Producto;
use Illuminate\Http\Request;

class PresentacionProductoController extends Controller
{
    public function index(Producto $producto)
    {
        return response()->json($producto->presentaciones);
    }

    public function store(Request $request, Producto $producto)
    {
        $validado = $request->validate([
            'nombre' => 'required|string|max:50',
            'unidades_equivalentes' => 'required|integer|min:1',
            'precio_venta' => 'required|numeric|min:0',
            'es_default' => 'nullable|boolean',
        ]);

        if (!empty($validado['es_default'])) {
            $producto->presentaciones()->update(['es_default' => false]);
        }

        $presentacion = $producto->presentaciones()->create($validado);

        return response()->json([
            'message' => 'Presentación agregada correctamente.',
            'presentacion' => $presentacion,
        ], 201);
    }

    public function update(Request $request, PresentacionProducto $presentacion)
    {
        $validado = $request->validate([
            'nombre' => 'required|string|max:50',
            'unidades_equivalentes' => 'required|integer|min:1',
            'precio_venta' => 'required|numeric|min:0',
            'es_default' => 'nullable|boolean',
        ]);

        if (!empty($validado['es_default'])) {
            $presentacion->producto->presentaciones()->update(['es_default' => false]);
        }

        $presentacion->update($validado);

        return response()->json(['message' => 'Presentación actualizada.', 'presentacion' => $presentacion]);
    }

    public function destroy(PresentacionProducto $presentacion)
    {
        if ($presentacion->producto->presentaciones()->count() <= 1) {
            return response()->json([
                'message' => 'Un producto debe tener al menos una presentación.',
            ], 409);
        }

        $presentacion->delete();

        return response()->json(['message' => 'Presentación eliminada.']);
    }
}
