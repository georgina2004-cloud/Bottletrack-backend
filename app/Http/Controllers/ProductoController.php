<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::with('categoria')->where('activo', true);

        if ($request->filled('busqueda')) {
            $texto = $request->input('busqueda');
            $query->where(function ($q) use ($texto) {
                $q->where('nombre', 'like', "%{$texto}%")
                  ->orWhere('codigo_barras', 'like', "%{$texto}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->input('categoria_id'));
        }

        $productos = $query->orderBy('nombre')->paginate(15);

        return response()->json($productos);
    }

    public function show(Producto $producto)
    {
        return response()->json($producto->load('categoria'));
    }

    public function store(StoreProductoRequest $request)
    {
        $datos = $request->validated();
        unset($datos['imagen']);

        if ($request->hasFile('imagen')) {
            $datos['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto = Producto::create($datos);

        return response()->json([
            'message' => 'Producto creado correctamente.',
            'producto' => $producto->load('categoria'),
        ], 201);
    }

    public function storeBulk(Request $request)
    {
        if ($request->user()->role->nombre !== 'Gerente de Bodega') {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        $request->validate([
            'productos' => 'required|array|min:1',
        ]);

        $creados = [];
        $errores = [];

        foreach ($request->input('productos') as $indice => $item) {
            $validador = validator($item, [
                'codigo_barras' => 'nullable|string|max:50|unique:productos,codigo_barras',
                'nombre' => 'required|string|max:150',
                'marca' => 'nullable|string|max:100',
                'categoria_id' => 'required|exists:categorias,id',
                'precio_compra' => 'required|numeric|min:0',
                'precio_venta' => 'required|numeric|min:0',
                'stock_actual' => 'nullable|integer|min:0',
                'stock_minimo' => 'nullable|integer|min:0',
                'stock_maximo' => 'nullable|integer|min:0',
                'presentacion_ml' => 'nullable|numeric|min:0',
                'ubicacion' => 'nullable|string|max:100',
            ], [
                'nombre.required' => 'El nombre del producto es obligatorio.',
                'categoria_id.required' => 'Debes seleccionar una categoría.',
                'categoria_id.exists' => 'La categoría seleccionada no existe.',
                'precio_compra.required' => 'El precio de compra es obligatorio.',
                'precio_venta.required' => 'El precio de venta es obligatorio.',
                'codigo_barras.unique' => 'Ya existe un producto con ese código de barras.',
            ]);

            if ($validador->fails()) {
                $errores[] = [
                    'fila' => $indice,
                    'nombre' => $item['nombre'] ?? '(sin nombre)',
                    'errores' => $validador->errors()->all(),
                ];
                continue;
            }

            $producto = Producto::create($validador->validated());
            $creados[] = $producto->load('categoria');
        }

        return response()->json([
            'message' => count($creados) . ' de ' . count($request->input('productos')) . ' productos creados correctamente.',
            'creados' => $creados,
            'errores' => $errores,
        ], 201);
    }

    public function update(UpdateProductoRequest $request, Producto $producto)
    {
        $datos = $request->validated();
        unset($datos['imagen']);

        if ($request->hasFile('imagen')) {
            if ($producto->imagen_path) {
                Storage::disk('public')->delete($producto->imagen_path);
            }
            $datos['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto->update($datos);

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'producto' => $producto->load('categoria'),
        ]);
    }

    public function destroy(Request $request, Producto $producto)
    {
        if ($request->user()->role->nombre !== 'Gerente de Bodega') {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        $producto->update(['activo' => false]);

        return response()->json([
            'message' => 'Producto desactivado correctamente.',
        ]);
    }

    public function buscarPorCodigo(string $codigo)
    {
        $producto = Producto::with('categoria')
            ->where('codigo_barras', $codigo)
            ->where('activo', true)
            ->first();

        if (!$producto) {
            return response()->json([
                'existe' => false,
            ], 404);
        }

        return response()->json([
            'existe' => true,
            'producto' => $producto,
        ]);
    }
}