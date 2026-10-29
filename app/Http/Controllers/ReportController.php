<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\ConfiguracionEmpresa;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\ConfiguracionSingleton;
use App\Services\Reports\ReportFactory;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function responder(Request $request, string $titulo, array $columnas, Collection $datos)
    {
        $formato = $request->input('formato');

        if (in_array($formato, ['pdf', 'excel'])) {
            return ReportFactory::exportar($formato, $titulo, $columnas, $datos);
        }

        return response()->json($datos);
    }

    public function maestroDetalleVentas(Request $request)
    {
        $query = Venta::where('estado_activa', true)
            ->with(['usuario', 'detalles.producto'])
            ->orderByDesc('fecha');

        if ($request->filled('desde')) $query->whereDate('fecha', '>=', $request->input('desde'));
        if ($request->filled('hasta')) $query->whereDate('fecha', '<=', $request->input('hasta'));

        $datos = $query->get()->flatMap(function ($venta) {
            return $venta->detalles->map(fn ($d) => [
                'factura' => $venta->numero_factura,
                'fecha' => $venta->fecha,
                'vendedor' => $venta->usuario->name ?? 'N/A',
                'cliente' => $venta->cliente_nombre ?? 'Consumidor final',
                'producto' => $d->producto->nombre ?? 'N/A',
                'cantidad' => $d->cantidad,
                'precio_unitario' => number_format($d->precio_unitario, 2),
                'subtotal_linea' => number_format($d->subtotal, 2),
                'total_venta' => number_format($venta->total, 2),
            ]);
        });

        return $this->responder($request, 'Maestro_Detalle_Ventas', ['Factura', 'Fecha', 'Vendedor', 'Cliente', 'Producto', 'Cantidad', 'Precio Unit.', 'Subtotal', 'Total Venta'], $datos);
    }

    public function inventarioActual(Request $request)
    {
        $datos = Producto::where('activo', true)->with('categoria')->orderBy('nombre')->get()
            ->map(fn ($p) => [
                'codigo' => $p->codigo_barras,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria->nombre ?? '-',
                'precio_compra' => number_format($p->precio_compra, 2),
                'precio_venta' => number_format($p->precio_venta, 2),
                'stock' => $p->stock_actual,
            ]);

        return $this->responder($request, 'Inventario_Actual', ['Código', 'Nombre', 'Categoría', 'Precio Compra', 'Precio Venta', 'Stock'], $datos);
    }

    public function stockBajo(Request $request)
    {
        $datos = Producto::where('activo', true)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->with('categoria')->orderBy('stock_actual')->get()
            ->map(fn ($p) => [
                'nombre' => $p->nombre,
                'categoria' => $p->categoria->nombre ?? '-',
                'stock_actual' => $p->stock_actual,
                'stock_minimo' => $p->stock_minimo,
            ]);

        return $this->responder($request, 'Stock_Bajo', ['Producto', 'Categoría', 'Stock Actual', 'Stock Mínimo'], $datos);
    }

    public function ventasPorFechas(Request $request)
    {
        $query = Venta::with('usuario')->orderByDesc('fecha');
        if ($request->filled('desde')) $query->whereDate('fecha', '>=', $request->input('desde'));
        if ($request->filled('hasta')) $query->whereDate('fecha', '<=', $request->input('hasta'));

        $datos = $query->get()->map(fn ($v) => [
            'factura' => $v->numero_factura,
            'cliente' => $v->cliente_nombre ?? 'Consumidor final',
            'vendedor' => $v->usuario->name ?? 'N/A',
            'fecha' => $v->fecha,
            'total' => number_format($v->total, 2),
            'estado' => $v->estado_activa ? 'Activa' : 'Anulada',
        ]);

        return $this->responder($request, 'Ventas_Por_Fechas', ['Factura', 'Cliente', 'Vendedor', 'Fecha', 'Total', 'Estado'], $datos);
    }

    public function ventasPorVendedor(Request $request)
    {
        $desde = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());
        $vendedorId = $request->input('vendedor_id');

        if ($desde && str_contains($desde, '/')) {
            $desde = Carbon::createFromFormat('d/m/Y', $desde)->format('Y-m-d');
        }
        if ($hasta && str_contains($hasta, '/')) {
            $hasta = Carbon::createFromFormat('d/m/Y', $hasta)->format('Y-m-d');
        }

        $query = User::query()
            ->withCount(['ventas as transacciones' => function ($q) use ($desde, $hasta) {
                $q->where('estado_activa', true)
                  ->whereDate('fecha', '>=', $desde)
                  ->whereDate('fecha', '<=', $hasta);
            }])
            ->withSum(['ventas as total_facturado' => function ($q) use ($desde, $hasta) {
                $q->where('estado_activa', true)
                  ->whereDate('fecha', '>=', $desde)
                  ->whereDate('fecha', '<=', $hasta);
            }], 'total');

        if ($request->filled('vendedor_id') && $vendedorId !== 'todos') {
            $query->where('id', $vendedorId);
        }

        $vendedores = $query->orderBy('name')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'vendedor' => $u->name,
                'transacciones' => (int) ($u->transacciones ?? 0),
                'total_facturado' => (float) ($u->total_facturado ?? 0),
            ];
        });

        if (in_array($request->input('formato'), ['pdf', 'excel'])) {
            $datosExportar = $vendedores->map(fn ($v) => [
                'vendedor' => $v['vendedor'],
                'transacciones' => $v['transacciones'] . ' transacciones',
                'total_facturado' => 'C$ ' . number_format($v['total_facturado'], 2),
            ]);

            return $this->responder(
                $request,
                'Ventas_Por_Vendedor',
                ['Vendedor / Cajero', 'Transacciones Realizadas', 'Total Facturado'],
                $datosExportar
            );
        }

        return response()->json([
            'data' => $vendedores,
            'total_general_transacciones' => $vendedores->sum('transacciones'),
            'total_general_facturado' => $vendedores->sum('total_facturado'),
        ]);
    }

    public function gananciasPorPeriodo(Request $request)
    {
    $desde = $request->input('desde', now()->startOfMonth()->toDateString());
    $hasta = $request->input('hasta', now()->toDateString());
    $agrupar = $request->input('agrupar') === 'mes' ? 'mes' : 'dia';

    if ($desde && str_contains($desde, '/')) {
        $desde = Carbon::createFromFormat('d/m/Y', $desde)->format('Y-m-d');
    }
    if ($hasta && str_contains($hasta, '/')) {
        $hasta = Carbon::createFromFormat('d/m/Y', $hasta)->format('Y-m-d');
    }

    $formato = $agrupar === 'mes' ? '%Y-%m' : '%Y-%m-%d';

    $filas = DB::table('detalle_ventas')
        ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
        ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
        ->where('ventas.estado_activa', true) // las ventas anuladas no cuentan
        ->whereDate('ventas.fecha', '>=', $desde)
        ->whereDate('ventas.fecha', '<=', $hasta)
        ->selectRaw('DATE_FORMAT(ventas.fecha, ?) as periodo', [$formato])
        ->selectRaw('SUM(detalle_ventas.subtotal) as ingresos')
        ->selectRaw('SUM(detalle_ventas.cantidad * productos.precio_compra) as costo')
        ->groupBy('periodo')
        ->orderBy('periodo')
        ->get()
        ->map(fn ($f) => [
            'periodo' => $f->periodo,
            'ingresos' => (float) $f->ingresos,
            'costo' => (float) $f->costo,
            'ganancia' => (float) $f->ingresos - (float) $f->costo,
        ]);

    if (in_array($request->input('formato'), ['pdf', 'excel'])) {
        $datosExportar = $filas->map(fn ($f) => [
            'periodo' => $f['periodo'],
            'ingresos' => 'C$ ' . number_format($f['ingresos'], 2),
            'costo' => 'C$ ' . number_format($f['costo'], 2),
            'ganancia' => 'C$ ' . number_format($f['ganancia'], 2),
        ]);

        return $this->responder(
            $request,
            'Ganancias_Por_Periodo',
            ['Periodo', 'Ingresos', 'Costo', 'Ganancia'],
            $datosExportar
        );
    }

    return response()->json([
        'data' => $filas,
        'total_ingresos' => $filas->sum('ingresos'),
        'total_costo' => $filas->sum('costo'),
        'total_ganancia' => $filas->sum('ganancia'),
    ]);
    }

    public function comprasPorProveedor(Request $request)
    {
        $query = Compra::join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->selectRaw('proveedores.razon_social as proveedor, COUNT(compras.id) as total_compras, SUM(compras.total) as monto_total')
            ->groupBy('proveedores.id', 'proveedores.razon_social');

        if ($request->filled('desde')) $query->whereDate('compras.fecha', '>=', $request->input('desde'));
        if ($request->filled('hasta')) $query->whereDate('compras.fecha', '<=', $request->input('hasta'));

        $datos = $query->orderByDesc('monto_total')->get()->map(fn ($f) => [
            'proveedor' => $f->proveedor,
            'total_compras' => $f->total_compras,
            'monto_total' => number_format($f->monto_total, 2),
        ]);

        return $this->responder($request, 'Compras_Por_Proveedor', ['Proveedor', 'Total Compras', 'Monto Total'], $datos);
    }

    public function movimientosInventario(Request $request)
    {
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        // Normalizar en caso de que el frontend envíe DD/MM/YYYY
        if ($desde && str_contains($desde, '/')) {
            $desde = Carbon::createFromFormat('d/m/Y', $desde)->format('Y-m-d');
        }
        if ($hasta && str_contains($hasta, '/')) {
            $hasta = Carbon::createFromFormat('d/m/Y', $hasta)->format('Y-m-d');
        }

        $desdeTimestamp = $desde ? "{$desde} 00:00:00" : null;
        $hastaTimestamp = $hasta ? "{$hasta} 23:59:59" : null;

        // 1. Entradas por Compras Activas
        $entradas = DB::table('detalle_compras')
            ->join('compras', 'detalle_compras.compra_id', '=', 'compras.id')
            ->join('productos', 'detalle_compras.producto_id', '=', 'productos.id')
            ->selectRaw("compras.fecha, productos.nombre as producto, 'Entrada' as tipo, detalle_compras.cantidad, detalle_compras.precio_unitario")
            ->when($desdeTimestamp, fn ($q) => $q->where('compras.fecha', '>=', $desdeTimestamp))
            ->when($hastaTimestamp, fn ($q) => $q->where('compras.fecha', '<=', $hastaTimestamp));

        // 2. Salidas por Ventas Activas
        $salidas = DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->where('ventas.estado_activa', true)
            ->selectRaw("ventas.fecha, productos.nombre as producto, 'Salida' as tipo, detalle_ventas.cantidad, detalle_ventas.precio_unitario")
            ->when($desdeTimestamp, fn ($q) => $q->where('ventas.fecha', '>=', $desdeTimestamp))
            ->when($hastaTimestamp, fn ($q) => $q->where('ventas.fecha', '<=', $hastaTimestamp));

        // 3. Entradas/Reingresos por Ventas Anuladas
        $anulaciones = DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->where('ventas.estado_activa', false)
            ->selectRaw("ventas.updated_at as fecha, productos.nombre as producto, 'Anulación' as tipo, detalle_ventas.cantidad, detalle_ventas.precio_unitario")
            ->when($desdeTimestamp, fn ($q) => $q->where('ventas.updated_at', '>=', $desdeTimestamp))
            ->when($hastaTimestamp, fn ($q) => $q->where('ventas.updated_at', '<=', $hastaTimestamp));

        $datos = $entradas->unionAll($salidas)->unionAll($anulaciones)->orderByDesc('fecha')->get()->map(fn ($m) => [
            'fecha' => $m->fecha,
            'producto' => $m->producto,
            'tipo' => $m->tipo,
            'cantidad' => $m->cantidad,
            'precio_unitario' => number_format($m->precio_unitario, 2),
        ]);

        return $this->responder($request, 'Movimientos_Inventario', ['Fecha', 'Producto', 'Tipo', 'Cantidad', 'Precio Unit.'], collect($datos));
    }

    public function facturaPdf(Venta $venta)
    {
        $venta->load(['detalles.producto', 'usuario']);

        $empresa = ConfiguracionSingleton::obtenerInstancia()->obtenerConfiguracion();

        $pdf = Pdf::loadView('reportes.factura-ticket', [
            'venta' => $venta,
            'empresa' => $empresa,
        ]);

        $alturaCalculada = $this->calcularAlturaFactura($venta, $empresa);
        $pdf->setPaper([0, 0, 226.77, $alturaCalculada]);

        return $pdf->stream("Factura_{$venta->numero_factura}.pdf");
    }

    private function calcularAlturaFactura(Venta $venta, $empresa): float
    {
        $alturaBase = 300; 
        $alturaPorLinea = 24;
        $alturaProductos = $venta->detalles->count() * $alturaPorLinea;

        if ($empresa && $empresa->eslogan) {
            $alturaBase += 12; 
        }
        if ($empresa && $empresa->logo_path) {
            $alturaBase += 4; 
        }

        $alturaTotal = $alturaBase + $alturaProductos;

        return $alturaTotal * 1.25;
    }
}