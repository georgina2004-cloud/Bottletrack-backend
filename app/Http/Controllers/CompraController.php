<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompraRequest;
use App\Models\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::with(['proveedor', 'usuario', 'detalles.producto'])->orderByDesc('fecha');

        if ($request->filled('desde')) {
            $query->whereDate('fecha', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha', '<=', $request->input('hasta'));
        }

        return response()->json($query->paginate(15));
    }

    public function store(StoreCompraRequest $request)
    {
        $datosValidados = $request->validated();

        $compra = DB::transaction(function () use ($datosValidados, $request) {
            $total = 0;
            $lineasCalculadas = [];

            foreach ($datosValidados['productos'] as $item) {
                $subtotalLinea = $item['precio_unitario'] * $item['cantidad'];
                $total += $subtotalLinea;

                $lineasCalculadas[] = [
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $subtotalLinea,
                ];
            }

            $compra = Compra::create([
                'proveedor_id' => $datosValidados['proveedor_id'],
                'user_id' => $request->user()->id,
                'fecha' => now()->toDateString(),
                'numero_factura_proveedor' => $datosValidados['numero_factura_proveedor'] ?? null,
                'total' => $total,
                'estado_activa' => true,
            ]);

            foreach ($lineasCalculadas as $linea) {
                $compra->detalles()->create($linea);
            }

            return $compra;
        });

        return response()->json([
            'message' => 'Compra registrada correctamente.',
            'compra' => $compra->load(['detalles.producto', 'proveedor', 'usuario']),
        ], 201);
    }

    public function show(Compra $compra)
    {
        return response()->json($compra->load(['detalles.producto', 'proveedor', 'usuario']));
    }

    public function anular(Request $request, Compra $compra)
{
    if (!$request->user()->tienePermiso('compras.anular')) {
        return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
    }

    if (!$compra->estado_activa) {
        return response()->json(['message' => 'Esta compra ya está anulada.'], 409);
    }

    DB::transaction(function () use ($compra) {
        // Anular una compra resta stock: validar antes de borrar los detalles
        $porProducto = $compra->detalles->groupBy('producto_id')
            ->map(fn ($lineas) => $lineas->sum('cantidad'));

        foreach ($porProducto as $productoId => $cantidad) {
            $producto = Producto::lockForUpdate()->findOrFail($productoId);

            if ($producto->stock_actual < $cantidad) {
                abort(422, "No se puede anular: el stock de '{$producto->nombre}' quedaría negativo (disponible: {$producto->stock_actual}, a descontar: {$cantidad}).");
            }
        }

        foreach ($compra->detalles as $detalle) {
            $detalle->delete();
        }

        $compra->update(['estado_activa' => false]);
    });

    return response()->json(['message' => 'Compra anulada correctamente. El stock fue descontado.']);
}
}
