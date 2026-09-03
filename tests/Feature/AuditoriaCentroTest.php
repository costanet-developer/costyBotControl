<?php

namespace Tests\Feature;

use App\Exports\AuditoriaExport;
use App\Models\AuditoriaLog;
use App\Models\CasoOperativo;
use App\Models\User;
use App\Services\SeguimientoOperativo;
use App\Support\AuditoriaPresentador;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditoriaCentroTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditoria_exige_permisos_independientes_de_consulta_y_exportacion(): void
    {
        $sinPermiso = User::factory()->create();
        $this->actingAs($sinPermiso)->get(route('auditoria.index'))->assertForbidden();
        $this->actingAs($sinPermiso)->post(route('auditoria.export'))->assertForbidden();

        Permission::findOrCreate('auditoria.ver', 'web');
        $soloLectura = User::factory()->create();
        $soloLectura->givePermissionTo('auditoria.ver');
        $this->actingAs($soloLectura)->get(route('auditoria.index'))->assertOk();
        $this->actingAs($soloLectura)->post(route('auditoria.export'))->assertForbidden();
    }

    public function test_el_seeder_reserva_la_auditoria_global_a_administracion(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Role::findByName('superadministrador')->hasPermissionTo('auditoria.exportar'));
        $this->assertTrue(Role::findByName('administrador')->hasPermissionTo('auditoria.ver'));
        $this->assertTrue(Role::findByName('administrador')->hasPermissionTo('auditoria.exportar'));
        $this->assertFalse(Role::findByName('contabilidad')->hasPermissionTo('auditoria.ver'));
        $this->assertFalse(Role::findByName('contabilidad')->hasPermissionTo('auditoria.exportar'));
    }

    public function test_la_vista_filtra_y_oculta_secretos(): void
    {
        $usuario = $this->usuarioConPermiso('auditoria.ver');
        AuditoriaLog::create([
            'usuario_id' => $usuario->id,
            'accion' => 'actualizar_seguridad',
            'modulo' => 'Usuarios',
            'entidad' => 'User',
            'entidad_id' => $usuario->id,
            'datos_nuevos' => [
                'nombre' => 'Dato visible',
                'password' => 'ClaveSuperSecreta',
                'otp_codigo' => '654321',
                'access_token' => 'token-no-debe-salir',
            ],
            'resultado' => 'exitoso',
            'descripcion' => 'Cambio de seguridad visible en auditoría.',
        ]);
        AuditoriaLog::create([
            'accion' => 'otro_evento', 'modulo' => 'Otro', 'resultado' => 'exitoso',
            'descripcion' => 'Este registro no corresponde al filtro.',
        ]);

        $this->actingAs($usuario)->get(route('auditoria.index', ['modulo' => 'Usuarios']))
            ->assertOk()
            ->assertSee('Cambio de seguridad visible en auditoría.')
            ->assertSee('Dato visible')
            ->assertSee('[PROTEGIDO]')
            ->assertDontSee('ClaveSuperSecreta')
            ->assertDontSee('654321')
            ->assertDontSee('token-no-debe-salir')
            ->assertDontSee('Este registro no corresponde al filtro.');
    }

    public function test_la_exportacion_respeta_permiso_y_deja_auditoria(): void
    {
        Excel::fake();
        $usuario = $this->usuarioConPermiso('auditoria.exportar');
        AuditoriaLog::create([
            'accion' => 'evento_exportable', 'modulo' => 'Pruebas', 'resultado' => 'exitoso',
            'descripcion' => 'Evento para exportación.',
        ]);

        $this->actingAs($usuario)->post(route('auditoria.export'), ['modulo' => 'Pruebas'])->assertOk();
        Excel::assertDownloaded('auditoria_costy_'.now()->format('Ymd_His').'.xlsx');
        $this->assertDatabaseHas('auditoria_logs', [
            'usuario_id' => $usuario->id,
            'accion' => 'exportar_auditoria',
            'modulo' => 'Auditoría',
        ]);
    }

    public function test_presentador_y_excel_nunca_exponen_credenciales(): void
    {
        $log = new AuditoriaLog([
            'accion' => 'prueba', 'modulo' => 'Pruebas',
            'datos_nuevos' => ['password' => 'secreto', 'codigo_ingresado' => '112233', 'campo' => 'visible'],
        ]);
        $log->setRelation('usuario', null);
        $fila = (new AuditoriaExport(collect([$log])))->map($log);
        $texto = implode(' ', array_map(fn ($valor) => (string) $valor, $fila));

        $this->assertStringContainsString('visible', $texto);
        $this->assertStringContainsString('[PROTEGIDO]', $texto);
        $this->assertStringNotContainsString('secreto', $texto);
        $this->assertStringNotContainsString('112233', $texto);
        $this->assertSame('[PROTEGIDO]', AuditoriaPresentador::redactar('valor', 'authorization'));
    }

    public function test_calcula_sla_y_tiempos_por_prioridad(): void
    {
        Carbon::setTestNow('2026-08-21 12:00:00');
        $responsable = User::factory()->create();
        $this->caso('alta-vencida', 'alta', now()->subHours(3));
        $this->caso('baja-vigente', 'baja', now()->subHour());
        $resuelto = $this->caso('media-resuelta', 'media', now()->subHours(2));
        $resuelto->update([
            'estado' => 'resuelto',
            'asignado_a' => $responsable->id,
            'asignado_en' => now()->subMinutes(90),
            'resuelto_por' => $responsable->id,
            'resuelto_en' => now()->subMinutes(30),
            'resolucion' => 'Resuelto durante la prueba.',
        ]);

        $sla = app(SeguimientoOperativo::class)->resumen();

        $this->assertSame(2, $sla['abiertos']);
        $this->assertSame(2, $sla['sin_asignar']);
        $this->assertSame(1, $sla['vencidos']);
        $this->assertSame(30, $sla['promedio_toma_minutos']);
        $this->assertSame(90, $sla['promedio_resolucion_minutos']);
        $this->assertSame('alta-vencida', $sla['criticos']->first()['caso']->clave);
        Carbon::setTestNow();
    }

    public function test_los_logs_no_pueden_modificarse_ni_eliminarse_con_el_modelo(): void
    {
        $log = AuditoriaLog::create(['accion' => 'inmutable', 'modulo' => 'Pruebas', 'resultado' => 'exitoso']);

        try {
            $log->update(['descripcion' => 'Intento de alteración']);
            $this->fail('El log permitió una actualización.');
        } catch (\LogicException $e) {
            $this->assertSame('Los registros de auditoría son inmutables.', $e->getMessage());
        }

        $this->expectException(\LogicException::class);
        $log->delete();
    }

    private function usuarioConPermiso(string $permiso): User
    {
        Permission::findOrCreate($permiso, 'web');
        $usuario = User::factory()->create();
        $usuario->givePermissionTo($permiso);

        return $usuario;
    }

    private function caso(string $clave, string $prioridad, $detectado): CasoOperativo
    {
        return CasoOperativo::create([
            'clave' => $clave,
            'tipo' => 'prueba_sla',
            'prioridad' => $prioridad,
            'estado' => 'pendiente',
            'titulo' => 'Caso de SLA',
            'detectado_en' => $detectado,
            'ultima_deteccion_en' => $detectado,
        ]);
    }
}
