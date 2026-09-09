<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $gerente = Role::where('nombre', 'Gerente de Bodega')->first();
        $vendedor = Role::where('nombre', 'Encargado de Ventas')->first();
        $auditor = Role::where('nombre', 'Auditor')->first();

        User::create([
            'name' => 'Georgina Blanco',
            'email' => 'admin@bottletrack.com',
            'password' => 'password123',
            'role_id' => $gerente->id,
            'estado' => true,
        ]);

        User::create([
            'name' => 'Usuario Ventas',
            'email' => 'ventas@bottletrack.com',
            'password' => 'password123',
            'role_id' => $vendedor->id,
            'estado' => true,
        ]);

        User::create([
            'name' => 'Usuario Auditor',
            'email' => 'auditor@bottletrack.com',
            'password' => 'password123',
            'role_id' => $auditor->id,
            'estado' => true,
        ]);
    }
}
