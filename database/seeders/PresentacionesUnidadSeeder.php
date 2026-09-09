<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class PresentacionesUnidadSeeder extends Seeder
{
    public function run(): void
    {
        Producto::whereDoesntHave('presentaciones')->each(function ($producto) {
            $producto->presentaciones()->create([
                'nombre' => 'Unidad',
                'unidades_equivalentes' => 1,
                'precio_venta' => $producto->precio_venta,
                'es_default' => true,
            ]);
        });
    }
}
