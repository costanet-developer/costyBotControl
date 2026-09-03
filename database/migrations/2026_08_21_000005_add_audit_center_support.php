<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSION = 'auditoria.exportar';

    public function up(): void
    {
        Schema::table('auditoria_logs', function (Blueprint $table) {
            $table->index('fecha_hora', 'auditoria_logs_fecha_hora_idx');
            $table->index(['modulo', 'accion'], 'auditoria_logs_modulo_accion_idx');
            $table->index(['resultado', 'fecha_hora'], 'auditoria_logs_resultado_fecha_idx');
            $table->index(['entidad', 'entidad_id'], 'auditoria_logs_entidad_idx');
        });

        DB::table('permissions')->insertOrIgnore([
            'name' => self::PERMISSION,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permissionId = DB::table('permissions')->where(['name' => self::PERMISSION, 'guard_name' => 'web'])->value('id');
        $roleIds = DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['superadministrador', 'administrador'])->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
        }
    }

    public function down(): void
    {
        Schema::table('auditoria_logs', function (Blueprint $table) {
            $table->dropIndex('auditoria_logs_fecha_hora_idx');
            $table->dropIndex('auditoria_logs_modulo_accion_idx');
            $table->dropIndex('auditoria_logs_resultado_fecha_idx');
            $table->dropIndex('auditoria_logs_entidad_idx');
        });

        $permissionId = DB::table('permissions')->where(['name' => self::PERMISSION, 'guard_name' => 'web'])->value('id');
        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
