<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            'roles.ver', 'roles.administrar',
            'interacciones.ver', 'interacciones.editar', 'interacciones.eliminar',
            'comprobantes.ver', 'comprobantes.descargar',
            'comprobantes.revisar', 'comprobantes.aprobar', 'comprobantes.rechazar',
            'documentos_identidad.ver', 'documentos_identidad.descargar',
            'observaciones.crear',
            'reportes.ver', 'reportes.exportar',
            'auditoria.ver', 'auditoria.exportar',
            'casos_operativos.gestionar',
            'configuracion.ver', 'configuracion.editar',
        ];

        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superadmin = Role::firstOrCreate(['name' => 'superadministrador', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $contabilidad = Role::firstOrCreate(['name' => 'contabilidad', 'guard_name' => 'web']);

        $superadmin->syncPermissions(Permission::all());

        $admin->syncPermissions(Permission::whereNotIn('name', [
            'usuarios.eliminar', 'roles.administrar', 'configuracion.editar',
        ])->get());

        $contabilidad->syncPermissions(Permission::whereIn('name', [
            'interacciones.ver', 'comprobantes.ver', 'comprobantes.revisar',
            'comprobantes.aprobar', 'comprobantes.rechazar', 'observaciones.crear',
            'documentos_identidad.ver',
            'reportes.ver', 'reportes.exportar',
            'casos_operativos.gestionar',
        ])->get());

        $user = User::firstOrCreate(
            ['email' => 'superadmin@costanet.ec'],
            [
                'nombre' => 'Super',
                'apellido' => 'Administrador',
                'password' => Hash::make('Admin2026*'),
                'activo' => true,
            ]
        );

        $user->assignRole('superadministrador');

        $this->command->info('Usuario superadmin listo: superadmin@costanet.ec / Admin2026*');
    }
}
