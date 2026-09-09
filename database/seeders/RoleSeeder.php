<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create([
            'nombre' => 'Gerente de Bodega',
            'descripcion' => 'Control total del sistema: gestión de usuarios, inventario, reportes y configuración.',
        ]);

        Role::create([
            'nombre' => 'Encargado de Ventas',
            'descripcion' => 'Registra ventas en el punto de venta y consulta el inventario disponible.',
        ]);

        Role::create([
            'nombre' => 'Auditor',
            'descripcion' => 'Acceso de solo lectura a reportes y movimientos del sistema, sin permisos de edición.',
        ]);
    }
}
