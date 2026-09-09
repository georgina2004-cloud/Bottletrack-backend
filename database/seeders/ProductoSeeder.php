<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $rones = Categoria::where('nombre', 'Rones')->first();
        $cervezas = Categoria::where('nombre', 'Cervezas')->first();
        $vinos = Categoria::where('nombre', 'Vinos')->first();
        $whisky = Categoria::where('nombre', 'Whisky')->first();
        $vodka = Categoria::where('nombre', 'Vodka')->first();

        $productos = [
            ['codigo_barras' => '7501234560001', 'nombre' => 'Flor de Caña 7 años', 'marca' => 'Flor de Caña', 'categoria_id' => $rones->id, 'precio_compra' => 250.00, 'precio_venta' => 380.00, 'stock_actual' => 45, 'stock_minimo' => 10, 'stock_maximo' => 100, 'presentacion_ml' => 750, 'ubicacion' => 'Estante A1'],
            ['codigo_barras' => '7501234560002', 'nombre' => 'Ron Plata Flor de Caña', 'marca' => 'Flor de Caña', 'categoria_id' => $rones->id, 'precio_compra' => 180.00, 'precio_venta' => 270.00, 'stock_actual' => 8, 'stock_minimo' => 10, 'stock_maximo' => 80, 'presentacion_ml' => 750, 'ubicacion' => 'Estante A1'],
            ['codigo_barras' => '7501234560003', 'nombre' => 'Toña', 'marca' => 'Toña', 'categoria_id' => $cervezas->id, 'precio_compra' => 18.00, 'precio_venta' => 28.00, 'stock_actual' => 120, 'stock_minimo' => 24, 'stock_maximo' => 300, 'presentacion_ml' => 355, 'ubicacion' => 'Refrigerador 1'],
            ['codigo_barras' => '7501234560004', 'nombre' => 'Victoria', 'marca' => 'Victoria', 'categoria_id' => $cervezas->id, 'precio_compra' => 16.00, 'precio_venta' => 25.00, 'stock_actual' => 5, 'stock_minimo' => 24, 'stock_maximo' => 300, 'presentacion_ml' => 355, 'ubicacion' => 'Refrigerador 1'],
            ['codigo_barras' => '7501234560005', 'nombre' => 'Casillero del Diablo Cabernet', 'marca' => 'Concha y Toro', 'categoria_id' => $vinos->id, 'precio_compra' => 220.00, 'precio_venta' => 340.00, 'stock_actual' => 22, 'stock_minimo' => 6, 'stock_maximo' => 50, 'presentacion_ml' => 750, 'ubicacion' => 'Estante B2'],
            ['codigo_barras' => '7501234560006', 'nombre' => 'Johnnie Walker Black Label', 'marca' => 'Johnnie Walker', 'categoria_id' => $whisky->id, 'precio_compra' => 850.00, 'precio_venta' => 1250.00, 'stock_actual' => 3, 'stock_minimo' => 5, 'stock_maximo' => 30, 'presentacion_ml' => 750, 'ubicacion' => 'Vitrina Premium'],
            ['codigo_barras' => '7501234560007', 'nombre' => 'Absolut Original', 'marca' => 'Absolut', 'categoria_id' => $vodka->id, 'precio_compra' => 320.00, 'precio_venta' => 480.00, 'stock_actual' => 18, 'stock_minimo' => 5, 'stock_maximo' => 40, 'presentacion_ml' => 750, 'ubicacion' => 'Estante C1'],
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }
    }
}
