<?php

namespace App\Observers;

use App\Models\DetalleVenta;
use Illuminate\Support\Facades\Log;

class DetalleVentaObserver
{
    public function created(DetalleVenta $detalleVenta): void
    {
        $producto = $detalleVenta->producto;

        $producto->decrement('stock_actual', $detalleVenta->cantidad);

        if ($producto->fresh()->tieneStockBajo()) {
            Log::info("Stock bajo detectado: producto #{$producto->id} ({$producto->nombre}) quedó con {$producto->fresh()->stock_actual} unidades, mínimo {$producto->stock_minimo}.");
        }
    }

    public function deleted(DetalleVenta $detalleVenta): void
    {
        $producto = $detalleVenta->producto;

        $producto->increment('stock_actual', $detalleVenta->cantidad);
    }
}