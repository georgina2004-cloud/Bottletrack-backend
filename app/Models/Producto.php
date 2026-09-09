<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_barras',
        'nombre',
        'marca',
        'categoria_id',
        'precio_compra',
        'precio_venta',
        'stock_actual',
        'stock_minimo',
        'stock_maximo',
        'presentacion_ml',
        'ubicacion',
        'activo',
        'imagen_path',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'presentacion_ml' => 'decimal:2',
        'activo' => 'boolean',
    ];

    protected $appends = ['imagen_url'];

    public function getImagenUrlAttribute(): ?string
    {
    return $this->imagen_path ? asset(Storage::url($this->imagen_path)) : null;
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function detalleCompras(): HasMany
    {
        return $this->hasMany(DetalleCompra::class);
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function tieneStockBajo(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    public function presentaciones(): HasMany
    {
    return $this->hasMany(PresentacionProducto::class);
    }
}
