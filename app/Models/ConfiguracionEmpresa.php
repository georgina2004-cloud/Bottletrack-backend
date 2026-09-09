<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ConfiguracionEmpresa extends Model
{
    protected $table = 'configuracion_empresa';

    protected $fillable = [
        'nombre_licoreria', 'eslogan', 'telefono', 'email',
        'direccion', 'moneda', 'color_primario', 'logo_path',
    ];

    
    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        
        return asset(Storage::url($this->logo_path));
    }
}
