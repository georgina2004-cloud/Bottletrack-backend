<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\PresentacionProductoController;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/setup/estado', [SetupController::class, 'estado']);
Route::post('/setup/inicializar', [SetupController::class, 'inicializar']);
Route::get('/configuracion/publica', [SetupController::class, 'publica']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/configuracion', [SetupController::class, 'obtener']);
    Route::post('/configuracion', [SetupController::class, 'actualizar']);

    Route::get('/productos/buscar-por-codigo/{codigo}', [ProductoController::class, 'buscarPorCodigo']);
    Route::apiResource('productos', ProductoController::class);
    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('proveedores', ProveedorController::class);
    Route::get('/dashboard/resumen-inventario', [DashboardController::class, 'resumenInventario']);


    Route::get('/ventas', [VentaController::class, 'index']);
    Route::post('/ventas', [VentaController::class, 'store']);
    Route::get('/ventas/{venta}', [VentaController::class, 'show']);
    Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular']);

    Route::get('/compras', [CompraController::class, 'index']);
    Route::post('/compras', [CompraController::class, 'store']);
    Route::get('/compras/{compra}', [CompraController::class, 'show']);

    Route::get('/dashboard/tendencia', [DashboardController::class, 'tendencia']);
    Route::get('/dashboard/resumen-ventas-compras', [DashboardController::class, 'resumenVentasCompras']);

    Route::apiResource('usuarios', UsuarioController::class);
    Route::get('/roles', [RoleController::class, 'index']); 

    Route::get('/reportes/maestro-detalle-ventas', [ReportController::class, 'maestroDetalleVentas']);
    Route::get('/reportes/inventario-actual', [ReportController::class, 'inventarioActual']);
    Route::get('/reportes/stock-bajo', [ReportController::class, 'stockBajo']);
    Route::get('/reportes/ventas-por-fechas', [ReportController::class, 'ventasPorFechas']);
    Route::get('/reportes/ventas-por-vendedor', [ReportController::class, 'ventasPorVendedor']);
    Route::get('/reportes/compras-por-proveedor', [ReportController::class, 'comprasPorProveedor']);
    Route::get('/reportes/movimientos-inventario', [ReportController::class, 'movimientosInventario']);
    Route::get('/ventas/{venta}/factura-pdf', [ReportController::class, 'facturaPdf']);

    Route::post('/productos/bulk', [ProductoController::class, 'storeBulk']);
    Route::get('/productos/{producto}/presentaciones', [PresentacionProductoController::class, 'index']);
    Route::post('/productos/{producto}/presentaciones', [PresentacionProductoController::class, 'store']);
    Route::put('/presentaciones/{presentacion}', [PresentacionProductoController::class, 'update']);
    Route::delete('/presentaciones/{presentacion}', [PresentacionProductoController::class, 'destroy']);
});