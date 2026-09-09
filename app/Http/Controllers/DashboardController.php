<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Venta;
use App\Models\Compra;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class DashboardController extends Controller
{
    public function resumenInventario()
    {
    $totalProductos = Producto::where('activo', true)->count();

    $productosStockBajo = Producto::where('activo', true)
        ->whereColumn('stock_actual', '<=', 'stock_minimo')
        ->orderBy('stock_actual')
        ->get(['id', 'nombre', 'stock_actual', 'stock_minimo']);

    $porCategoria = Producto::where('activo', true)
        ->selectRaw('categoria_id, count(*) as total')
        ->groupBy('categoria_id')
        ->with('categoria:id,nombre')
        ->get()
        ->map(function ($item) {
            return [
                'categoria' => $item->categoria->nombre ?? 'Sin categoría',
                'total' => $item->total,
            ];
        });

    return response()->json([
        'total_productos' => $totalProductos,
        'productos_stock_bajo' => $productosStockBajo->count(),
        'productos_stock_bajo_detalle' => $productosStockBajo,
        'productos_por_categoria' => $porCategoria,
    ]);
    }

    public function resumenVentasCompras()
    {
    $hoy = now()->toDateString();
    $inicioMes = now()->startOfMonth()->toDateString();

    $ventasHoy = Venta::where('estado_activa', true)
        ->whereDate('fecha', $hoy)
        ->sum('total');

    $ventasMes = Venta::where('estado_activa', true)
        ->whereDate('fecha', '>=', $inicioMes)
        ->sum('total');

    $comprasMes = Compra::whereDate('fecha', '>=', $inicioMes)->sum('total');

    $costoVentasMes = Venta::where('estado_activa', true)
        ->whereDate('fecha', '>=', $inicioMes)
        ->with('detalles.producto')
        ->get()
        ->flatMap(fn ($venta) => $venta->detalles)
        ->sum(fn ($detalle) => $detalle->cantidad * ($detalle->producto->precio_compra ?? 0));

    $utilidadEstimada = $ventasMes - $costoVentasMes;

    $topProductos = DB::table('detalle_ventas')
        ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
        ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
        ->where('ventas.estado_activa', true)
        ->whereDate('ventas.fecha', '>=', $inicioMes)
        ->selectRaw('productos.nombre, SUM(detalle_ventas.cantidad) as total_vendido')
        ->groupBy('productos.id', 'productos.nombre')
        ->orderByDesc('total_vendido')
        ->limit(5)
        ->get();

    $ultimasVentas = Venta::where('estado_activa', true)
        ->with('usuario')
        ->orderByDesc('fecha')
        ->limit(5)
        ->get(['id', 'numero_factura', 'cliente_nombre', 'fecha', 'total', 'user_id']);

    return response()->json([
        'ventas_hoy' => round($ventasHoy, 2),
        'ventas_mes' => round($ventasMes, 2),
        'compras_mes' => round($comprasMes, 2),
        'utilidad_estimada' => round($utilidadEstimada, 2),
        'top_productos' => $topProductos,
        'ultimas_ventas' => $ultimasVentas,
    ]); 
    }

    public function tendencia(Request $request)
    {
    $periodo = $request->input('periodo', '14d');

    $dias = match ($periodo) {
        '7d' => 7,
        '14d' => 14,
        'mes' => now()->diffInDays(now()->startOfMonth()) + 1,
        default => 14,
    };

    $fechaInicio = now()->subDays($dias - 1)->startOfDay();

    $ventasPorDia = Venta::where('estado_activa', true)
        ->where('fecha', '>=', $fechaInicio->toDateString())
        ->selectRaw('fecha, SUM(total) as total')
        ->groupBy('fecha')
        ->pluck('total', 'fecha');

    $comprasPorDia = Compra::where('fecha', '>=', $fechaInicio->toDateString())
        ->selectRaw('fecha, SUM(total) as total')
        ->groupBy('fecha')
        ->pluck('total', 'fecha');

    $serie = collect();
    for ($i = 0; $i < $dias; $i++) {
        $fecha = $fechaInicio->copy()->addDays($i)->toDateString();

        $serie->push([
            'fecha' => $fecha,
            'ventas' => round($ventasPorDia->get($fecha, 0), 2),
            'compras' => round($comprasPorDia->get($fecha, 0), 2),
        ]);
    }

    return response()->json($serie);
    }
}