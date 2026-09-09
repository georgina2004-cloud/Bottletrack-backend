<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Rones', 'descripcion' => 'Rones nacionales e importados'],
            ['nombre' => 'Cervezas', 'descripcion' => 'Cervezas nacionales e importadas'],
            ['nombre' => 'Vinos', 'descripcion' => 'Vinos tintos, blancos y rosados'],
            ['nombre' => 'Whisky', 'descripcion' => 'Whisky escocés, bourbon y otros'],
            ['nombre' => 'Vodka', 'descripcion' => 'Vodkas nacionales e importados'],
            ['nombre' => 'Snacks', 'descripcion' => 'Botanas y acompañantes'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}
