<?php

namespace App\Observers;

use App\Models\DetalleCompra;

class DetalleCompraObserver
{
    public function created(DetalleCompra $detalleCompra): void
    {
        $producto = $detalleCompra->producto;

        $producto->increment('stock_actual', $detalleCompra->cantidad);

        $producto->update(['precio_compra' => $detalleCompra->precio_unitario]);
    }

    public function deleted(DetalleCompra $detalleCompra): void
    {
        $producto = $detalleCompra->producto;

        $producto->decrement('stock_actual', $detalleCompra->cantidad);
        $ultimoDetalle = DetalleCompra::where('producto_id', $producto->id)
        ->where('id', '!=', $detalleCompra->id)
        ->whereHas('compra', fn ($q) => $q->where('estado_activa', true))
        ->latest('id')
        ->first();

        if ($ultimoDetalle) {
            $producto->update(['precio_compra' => $ultimoDetalle->precio_unitario]);
        }
    }
}