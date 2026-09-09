<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\Reports\ReportFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ConfiguracionEmpresa;


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
                'vendedor' => $venta->usuario->name,
                'cliente' => $venta->cliente_nombre ?? 'Consumidor final',
                'producto' => $d->producto->nombre,
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
            'vendedor' => $v->usuario->name,
            'fecha' => $v->fecha,
            'total' => number_format($v->total, 2),
            'estado' => $v->estado_activa ? 'Activa' : 'Anulada',
        ]);

        return $this->responder($request, 'Ventas_Por_Fechas', ['Factura', 'Cliente', 'Vendedor', 'Fecha', 'Total', 'Estado'], $datos);
    }

    public function ventasPorVendedor(Request $request)
    {
        $query = Venta::where('estado_activa', true)
            ->join('users', 'ventas.user_id', '=', 'users.id')
            ->selectRaw('users.name as vendedor, COUNT(ventas.id) as total_ventas, SUM(ventas.total) as monto_total')
            ->groupBy('users.id', 'users.name');

        if ($request->filled('desde')) $query->whereDate('ventas.fecha', '>=', $request->input('desde'));
        if ($request->filled('hasta')) $query->whereDate('ventas.fecha', '<=', $request->input('hasta'));

        $datos = $query->orderByDesc('monto_total')->get()->map(fn ($f) => [
            'vendedor' => $f->vendedor,
            'total_ventas' => $f->total_ventas,
            'monto_total' => number_format($f->monto_total, 2),
        ]);

        return $this->responder($request, 'Ventas_Por_Vendedor', ['Vendedor', 'Total Ventas', 'Monto Total'], $datos);
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
        $entradas = DB::table('detalle_compras')
            ->join('compras', 'detalle_compras.compra_id', '=', 'compras.id')
            ->join('productos', 'detalle_compras.producto_id', '=', 'productos.id')
            ->selectRaw("compras.fecha, productos.nombre as producto, 'Entrada' as tipo, detalle_compras.cantidad, detalle_compras.precio_unitario")
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('compras.fecha', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('compras.fecha', '<=', $request->input('hasta')));

        $salidas = DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->where('ventas.estado_activa', true)
            ->selectRaw("ventas.fecha, productos.nombre as producto, 'Salida' as tipo, detalle_ventas.cantidad, detalle_ventas.precio_unitario")
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('ventas.fecha', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('ventas.fecha', '<=', $request->input('hasta')));

        $datos = $entradas->unionAll($salidas)->orderByDesc('fecha')->get()->map(fn ($m) => [
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

    $empresa = \App\Services\ConfiguracionSingleton::obtenerInstancia()->obtenerConfiguracion();

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
    
    $alturaProductos = $venta->detalles->count() * $alturaPorLinea;

    $alturaTotal = $alturaBase + $alturaProductos;

    return $alturaTotal * 1.25;
    }
}