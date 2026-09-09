<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresentacionProducto extends Model
{
    use HasFactory;

    protected $table = 'presentaciones_producto';

    protected $fillable = [
        'producto_id', 'nombre', 'unidades_equivalentes', 'precio_venta', 'es_default',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'es_default' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
