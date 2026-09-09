<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentaRequest;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $query = Venta::with(['usuario', 'detalles.producto'])->orderByDesc('fecha');

        if ($request->filled('desde')) {
            $query->whereDate('fecha', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha', '<=', $request->input('hasta'));
        }

        if (!$request->user()->tienePermiso('ventas.ver_todas')) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->paginate(15));
    }

    public function store(StoreVentaRequest $request)
    {
        $datosValidados = $request->validated();

        $venta = DB::transaction(function () use ($datosValidados, $request) {
            $subtotal = 0;
            $lineasCalculadas = [];

            foreach ($datosValidados['productos'] as $item) {
                $producto = Producto::findOrFail($item['producto_id']);
                $presentacion = \App\Models\PresentacionProducto::findOrFail($item['presentacion_id']);

                $unidadesReales = $presentacion->unidades_equivalentes * $item['cantidad'];

                if ($producto->stock_actual < $unidadesReales) {
                    throw new \Exception("Stock insuficiente para '{$producto->nombre}'. Disponible: {$producto->stock_actual} unidades.");
                }

                $precioUnitario = $presentacion->precio_venta;
                $subtotalLinea = $precioUnitario * $item['cantidad'];
                $subtotal += $subtotalLinea;

                $lineasCalculadas[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $unidadesReales,
                    'precio_unitario' => $precioUnitario / $presentacion->unidades_equivalentes,
                    'subtotal' => $subtotalLinea,
                ];
            }

            $descuento = $datosValidados['descuento'] ?? 0;
            $impuesto = round(($subtotal - $descuento) * 0.15, 2);
            $total = $subtotal - $descuento + $impuesto;

            $venta = Venta::create([
                'numero_factura' => 'FAC-' . strtoupper(uniqid()),
                'user_id' => $request->user()->id,
                'cliente_nombre' => $datosValidados['cliente_nombre'] ?? null,
                'fecha' => now()->toDateString(),
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'descuento' => $descuento,
                'total' => $total,
                'estado_activa' => true,
            ]);

            foreach ($lineasCalculadas as $linea) {
                $venta->detalles()->create($linea);
            }

            return $venta;
        });

        return response()->json([
            'message' => 'Venta registrada correctamente.',
            'venta' => $venta->load(['detalles.producto', 'usuario']),
        ], 201);
    }

    public function show(Venta $venta)
    {
        return response()->json($venta->load(['detalles.producto', 'usuario']));
    }

    public function anular(Request $request, Venta $venta)
    {
        if (!$request->user()->tienePermiso('ventas.anular')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        if (!$venta->estado_activa) {
            return response()->json(['message' => 'Esta venta ya está anulada.'], 409);
        }

        DB::transaction(function () use ($venta) {
            foreach ($venta->detalles as $detalle) {
                $detalle->delete();
            }

            $venta->update(['estado_activa' => false]);
        });

        return response()->json(['message' => 'Venta anulada correctamente. El stock fue restituido.']);
    }
}