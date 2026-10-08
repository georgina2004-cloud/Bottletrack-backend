<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

class Compra extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'proveedor_id',
        'user_id',
        'fecha',
        'numero_factura_proveedor',
        'total',
        'estado_activa'
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:2',
        'estado_activa' => 'boolean'
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class);
    }
}