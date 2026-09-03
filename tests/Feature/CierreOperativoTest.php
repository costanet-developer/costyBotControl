<?php

namespace Tests\Feature;

use App\Models\Comprobante;
use App\Models\User;
use App\Services\SecureLocalFile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CierreOperativoTest extends TestCase
{
    use RefreshDatabase;

    public function test_interacciones_y_pendientes_exigen_permiso_explicito(): void
    {
        $sinPermiso = User::factory()->create();
        $this->actingAs($sinPermiso)->get(route('interacciones.index'))->assertForbidden();
        $this->actingAs($sinPermiso)->get(route('pendientes.index'))->assertForbidden();

        $conPermiso = User::factory()->create();
        Permission::findOrCreate('interacciones.ver', 'web');
        $conPermiso->givePermissionTo('interacciones.ver');

        $this->actingAs($conPermiso)->get(route('interacciones.index'))->assertOk();
        $this->actingAs($conPermiso)->get(route('pendientes.index'))->assertOk();
    }

    public function test_el_seeder_conserva_la_matriz_de_minimo_privilegio(): void
    {
        $this->seed(DatabaseSeeder::class);

        $contabilidad = Role::findByName('contabilidad');
        $administrador = Role::findByName('administrador');
        $superadministrador = Role::findByName('superadministrador');

        $this->assertTrue($contabilidad->hasPermissionTo('reportes.ver'));
        $this->assertTrue($contabilidad->hasPermissionTo('reportes.exportar'));
        $this->assertTrue($contabilidad->hasPermissionTo('documentos_identidad.ver'));
        $this->assertTrue($contabilidad->hasPermissionTo('casos_operativos.gestionar'));
        $this->assertFalse($contabilidad->hasPermissionTo('documentos_identidad.descargar'));
        $this->assertFalse($contabilidad->hasPermissionTo('comprobantes.descargar'));
        $this->assertFalse($contabilidad->hasPermissionTo('usuarios.ver'));
        $this->assertTrue($administrador->hasPermissionTo('comprobantes.descargar'));
        $this->assertTrue($superadministrador->hasPermissionTo('roles.administrar'));
    }

    public function test_un_administrador_no_puede_escalar_a_superadministrador(): void
    {
        $this->seed(DatabaseSeeder::class);
        $administrador = User::factory()->create();
        $administrador->assignRole('administrador');
        $superadministrador = User::where('email', 'superadmin@costanet.ec')->firstOrFail();

        $this->actingAs($administrador)->get(route('usuarios.index'))
            ->assertOk()
            ->assertDontSee('<option value="superadministrador"', false);

        $this->actingAs($administrador)->post(route('usuarios.store'), [
            'nombre' => 'Intento',
            'apellido' => 'Escalacion',
            'email' => 'intento@example.com',
            'password' => 'Password2026*',
            'rol' => 'superadministrador',
        ])->assertForbidden();

        $this->actingAs($administrador)->patch(route('usuarios.update', $superadministrador), [
            'nombre' => 'Modificado',
            'apellido' => 'Sin permiso',
            'email' => $superadministrador->email,
        ])->assertForbidden();
    }

    public function test_la_bandeja_reune_todas_las_categorias_operativas(): void
    {
        $usuario = User::factory()->create();
        Permission::findOrCreate('interacciones.ver', 'web');
        $usuario->givePermissionTo('interacciones.ver');

        $this->crearSesion('caso_sin_evidencia', '0991000001', 'reactivado');
        $this->crearSesion('caso_operativo', '0991000002', null);
        $comprobanteId = DB::table('comprobantes')->insertGetId([
            'sesion_id' => 'caso_operativo',
            'numero_whatsapp' => '593991000002',
            'monto' => 20,
            'numero_transaccion' => 'CTRL-PENDIENTE',
            'estado' => 'recibida',
            'estado_auditoria' => 'PENDIENTE',
        ]);
        DB::table('saldos_a_favor')->insert([
            'sesion_id' => 'caso_operativo',
            'numero_whatsapp' => '593991000002',
            'cedula' => '0991000002',
            'comprobante_id' => $comprobanteId,
            'monto_pagado' => 25,
            'monto_factura' => 20,
            'excedente' => 5,
            'numero_transaccion' => 'CTRL-PENDIENTE',
            'estado' => 'pendiente',
        ]);
        DB::table('validaciones_identidad')->insert([
            'sesion_id' => 'caso_operativo',
            'numero_whatsapp' => '593991000002',
            'cedula' => '0991000002',
            'correo' => 'control@example.com',
            'estado_kyc' => 'revision_manual',
            'derivado_revision' => true,
        ]);

        $this->actingAs($usuario)->get(route('pendientes.index'))
            ->assertOk()->assertSee('caso_sin_evidencia');
        $this->actingAs($usuario)->get(route('pendientes.index', ['tipo' => 'auditoria_pendiente']))
            ->assertOk()->assertSee('CTRL-PENDIENTE');
        $this->actingAs($usuario)->get(route('pendientes.index', ['tipo' => 'creditos']))
            ->assertOk()->assertSee('$5.00');
        $this->actingAs($usuario)->get(route('pendientes.index', ['tipo' => 'kyc']))
            ->assertOk()->assertSee('co•••••@example.com')->assertDontSee('control@example.com');
    }

    public function test_visualizar_un_comprobante_deja_auditoria(): void
    {
        $usuario = User::factory()->create();
        Permission::findOrCreate('comprobantes.ver', 'web');
        $usuario->givePermissionTo('comprobantes.ver');
        $comprobanteId = DB::table('comprobantes')->insertGetId([
            'numero_whatsapp' => '593991000003',
            'ruta_imagen' => '/home/Tlsg_n8n/whatsapp_imagenes/prueba.jpg',
            'estado' => 'recibida',
            'estado_auditoria' => 'PENDIENTE',
        ]);

        $archivo = tempnam(sys_get_temp_dir(), 'comprobante-');
        file_put_contents($archivo, 'imagen-de-prueba');
        $files = Mockery::mock(SecureLocalFile::class);
        $files->shouldReceive('resolve')->once()->andReturn($archivo);
        $this->app->instance(SecureLocalFile::class, $files);

        $this->actingAs($usuario)->get(route('comprobantes.imagen', Comprobante::findOrFail($comprobanteId)))->assertOk();

        $this->assertDatabaseHas('auditoria_logs', [
            'usuario_id' => $usuario->id,
            'accion' => 'visualizar_comprobante',
            'entidad' => 'Comprobante',
            'entidad_id' => $comprobanteId,
            'resultado' => 'exitoso',
        ]);

        @unlink($archivo);
    }

    private function crearSesion(string $sesionId, string $cedula, ?string $resultado): void
    {
        DB::table('sesiones')->insert([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593'.substr($cedula, -9),
            'bot' => 'reactivacion',
            'cedula' => $cedula,
            'estado_sesion' => 'cerrada',
            'resultado' => $resultado,
            'inicio' => now(),
            'fin' => now(),
        ]);
    }
}
