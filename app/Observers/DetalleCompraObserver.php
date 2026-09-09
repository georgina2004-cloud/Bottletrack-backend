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
}
