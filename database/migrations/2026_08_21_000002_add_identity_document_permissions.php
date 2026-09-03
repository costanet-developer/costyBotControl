<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        'documentos_identidad.ver',
        'documentos_identidad.descargar',
    ];

    public function up(): void
    {
        $now = now();
        foreach (self::PERMISSIONS as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissions = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id', 'name');
        $roles = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', ['superadministrador', 'administrador', 'contabilidad'])
            ->pluck('id', 'name');

        foreach (['superadministrador', 'administrador'] as $roleName) {
            foreach (self::PERMISSIONS as $permissionName) {
                $this->grant($roles[$roleName] ?? null, $permissions[$permissionName] ?? null);
            }
        }
        $this->grant($roles['contabilidad'] ?? null, $permissions['documentos_identidad.ver'] ?? null);
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    private function grant(?int $roleId, ?int $permissionId): void
    {
        if ($roleId === null || $permissionId === null) {
            return;
        }

        DB::table('role_has_permissions')->insertOrIgnore([
            'permission_id' => $permissionId,
            'role_id' => $roleId,
        ]);
    }
};
