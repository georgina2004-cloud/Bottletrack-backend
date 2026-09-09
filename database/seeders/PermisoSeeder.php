<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermisoSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // Productos
            ['clave' => 'productos.ver', 'descripcion' => 'Consultar lista, fichas técnicas y stock'],
            ['clave' => 'productos.crear', 'descripcion' => 'Registrar nuevos productos y licores'],
            ['clave' => 'productos.editar', 'descripcion' => 'Modificar precios, datos y presentaciones'],
            ['clave' => 'productos.eliminar', 'descripcion' => 'Dar de baja productos del inventario'],

            // Categorías
            ['clave' => 'categorias.ver', 'descripcion' => 'Consultar lista de categorías'],
            ['clave' => 'categorias.crear', 'descripcion' => 'Registrar nuevas categorías de licores'],
            ['clave' => 'categorias.editar', 'descripcion' => 'Modificar nombres y descripciones'],
            ['clave' => 'categorias.eliminar', 'descripcion' => 'Eliminar categorías sin productos asociados'],

            // Proveedores
            ['clave' => 'proveedores.ver', 'descripcion' => 'Consultar directorio de distribuidores'],
            ['clave' => 'proveedores.crear', 'descripcion' => 'Registrar nuevos distribuidores'],
            ['clave' => 'proveedores.editar', 'descripcion' => 'Modificar datos de contacto y fiscales'],
            ['clave' => 'proveedores.eliminar', 'descripcion' => 'Eliminar proveedores del registro'],

            // Compras
            ['clave' => 'compras.ver', 'descripcion' => 'Consultar historial de abastecimiento'],
            ['clave' => 'compras.crear', 'descripcion' => 'Ingresar facturas y lotes al stock'],
            ['clave' => 'compras.anular', 'descripcion' => 'Revertir ingresos de mercadería erróneos'],

            // Ventas
            ['clave' => 'ventas.ver', 'descripcion' => 'Consultar tickets y reportes de caja'],
            ['clave' => 'ventas.ver_todas', 'descripcion' => 'Consultar historial y ventas de todos los vendedores'],
            ['clave' => 'ventas.crear', 'descripcion' => 'Cobrar y emitir comprobantes de venta (POS)'],
            ['clave' => 'ventas.anular', 'descripcion' => 'Revertir transacciones y reponer stock'],

            // Usuarios
            ['clave' => 'usuarios.ver', 'descripcion' => 'Listar colaboradores del sistema'],
            ['clave' => 'usuarios.crear', 'descripcion' => 'Registrar cuentas para cajeros y personal'],
            ['clave' => 'usuarios.editar', 'descripcion' => 'Modificar roles y datos de acceso'],
            ['clave' => 'usuarios.eliminar', 'descripcion' => 'Revocar acceso a colaboradores'],

            // Roles
            ['clave' => 'roles.ver', 'descripcion' => 'Consultar configuración de permisos'],
            ['clave' => 'roles.editar', 'descripcion' => 'Asignar o revocar capacidades por rol'],

            // Reportes
            ['clave' => 'reportes.ver', 'descripcion' => 'Visualizar gráficos y balances financieros'],
            ['clave' => 'reportes.exportar', 'descripcion' => 'Descargar auditorías y listados (PDF / Excel)'],

            // Configuración
            ['clave' => 'configuracion.ver', 'descripcion' => 'Consultar datos de empresa e impuestos'],
            ['clave' => 'configuracion.editar', 'descripcion' => 'Ajustar branding, datos fiscales y alertas'],

            // Respaldos
            ['clave' => 'respaldos.generar', 'descripcion' => 'Generar y descargar copias de seguridad de la base de datos'],
            ['clave' => 'respaldos.restaurar', 'descripcion' => 'Restaurar la base de datos desde un archivo SQL'],

            // Dashboard
            ['clave' => 'dashboard.ver', 'descripcion' => 'Visualizar el panel principal con métricas clave'],
        ];

        foreach ($permisos as $p) {
            Permiso::firstOrCreate(['clave' => $p['clave']], ['descripcion' => $p['descripcion']]);
        }

        // Asignación a roles
        $todosPermisosIds = Permiso::pluck('id')->toArray();

        // 1. Gerente de Bodega / Administrador -> Todos los permisos
        $gerente = Role::where('nombre', 'Gerente de Bodega')->first();
        if ($gerente) {
            $gerente->permisos()->sync($todosPermisosIds);
        }

        $admin = Role::where('nombre', 'Administrador')->first();
        if ($admin) {
            $admin->permisos()->sync($todosPermisosIds);
        }

        // 2. Encargado de Ventas -> Ventas, productos.ver, dashboard.ver (NO ventas.ver_todas)
        $vendedor = Role::where('nombre', 'Encargado de Ventas')->first();
        if ($vendedor) {
            $permisosVentas = Permiso::whereIn('clave', [
                'dashboard.ver',
                'productos.ver',
                'ventas.ver',
                'ventas.crear',
                'ventas.anular',
            ])->pluck('id')->toArray();
            $vendedor->permisos()->sync($permisosVentas);
        }

        // 3. Auditor -> Lectura general + reportes + ventas.ver_todas
        $auditor = Role::where('nombre', 'Auditor')->first();
        if ($auditor) {
            $permisosAuditor = Permiso::whereIn('clave', [
                'dashboard.ver',
                'productos.ver',
                'categorias.ver',
                'proveedores.ver',
                'compras.ver',
                'ventas.ver',
                'ventas.ver_todas',
                'reportes.ver',
                'reportes.exportar',
                'roles.ver',
                'configuracion.ver',
            ])->pluck('id')->toArray();
            $auditor->permisos()->sync($permisosAuditor);
        }
    }
}