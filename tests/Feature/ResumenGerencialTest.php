<?php

namespace Tests\Feature;

use App\Exports\ResumenGerencialExport;
use App\Models\AuditoriaLog;
use App\Models\User;
use App\Services\ResumenGerencialService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ResumenGerencialTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_la_vista_y_la_exportacion_exigen_permisos_independientes(): void
    {
        $sinPermiso = User::factory()->create();
        $this->actingAs($sinPermiso)->get(route('resumen-gerencial.index'))->assertForbidden();
        $this->actingAs($sinPermiso)->post(route('resumen-gerencial.export'))->assertForbidden();

        $soloLectura = $this->usuarioConPermisos(['auditoria.ver']);
        $this->actingAs($soloLectura)->get(route('resumen-gerencial.index'))
            ->assertOk()
            ->assertSee('Resumen gerencial')
            ->assertSee('Evolución diaria')
            ->assertSee('Cumplimiento SLA actual');
        $this->actingAs($soloLectura)->post(route('resumen-gerencial.export'))->assertForbidden();
    }

    public function test_concilia_pagos_evidencias_y_montos_sin_duplicar_comprobantes(): void
    {
        CarbonImmutable::setTestNow('2026-08-21 12:00:00');
        $this->crearSesion('resumen-1', '0990000001', 'reactivado', '2026-08-20 10:00:00');
        $this->crearSesion('resumen-2', '0990000002', 'reactivado', '2026-08-21 09:00:00');
        $this->crearSesion('resumen-3', '0990000003', 'reactivado', '2026-08-21 10:00:00');

        $comprobanteId = DB::table('comprobantes')->insertGetId([
            'sesion_id' => 'resumen-1',
            'numero_whatsapp' => '593990000001',
            'fecha_hora' => '2026-08-19 23:55:00',
            'banco' => 'Banco Pichincha',
            'monto' => 20,
            'estado' => 'reactivacion_exitosa',
            'estado_auditoria' => 'PENDIENTE',
        ]);
        DB::table('sesiones')->whereIn('sesion_id', ['resumen-1', 'resumen-2'])->update(['comprobante_id' => $comprobanteId]);
        DB::table('saldos_a_favor')->insert([
            'sesion_id' => 'resumen-1',
            'numero_whatsapp' => '593990000001',
            'cedula' => '0990000001',
            'excedente' => 5,
            'estado' => 'pendiente',
            'fecha_registro' => '2026-08-20 10:01:00',
        ]);

        $resumen = app(ResumenGerencialService::class)->generar(
            CarbonImmutable::parse('2026-08-20')->startOfDay(),
            CarbonImmutable::parse('2026-08-21')->endOfDay(),
        );

        $this->assertSame(3, $resumen['actual']['metricas']['pagos']);
        $this->assertSame(20.0, $resumen['actual']['metricas']['monto']);
        $this->assertSame(5.0, $resumen['actual']['metricas']['creditos']);
        $this->assertSame(1, $resumen['actual']['metricas']['sin_evidencia']);
        $this->assertSame(20.0, (float) $resumen['actual']['serie']->sum('monto'));
        $this->assertSame(3, $resumen['actual']['serie']->sum('pagos'));
        $this->assertSame(1, $resumen['actual']['bancos']->first()['cantidad']);
    }

    public function test_compara_con_un_periodo_anterior_de_la_misma_duracion(): void
    {
        CarbonImmutable::setTestNow('2026-08-21 12:00:00');
        $this->crearSesion('actual-1', '0990000011', null, '2026-08-21 08:00:00');
        $this->crearSesion('actual-2', '0990000012', null, '2026-08-20 08:00:00');
        $this->crearSesion('anterior-1', '0990000021', null, '2026-08-19 08:00:00');

        $resumen = app(ResumenGerencialService::class)->generar(
            CarbonImmutable::parse('2026-08-20')->startOfDay(),
            CarbonImmutable::parse('2026-08-21')->endOfDay(),
        );

        $this->assertSame(2, $resumen['dias']);
        $this->assertSame(2, $resumen['actual']['metricas']['interacciones']);
        $this->assertSame(1, $resumen['anterior']['metricas']['interacciones']);
        $this->assertSame(100.0, $resumen['variaciones']['interacciones']);
        $this->assertSame('2026-08-18', $resumen['inicio_anterior']->format('Y-m-d'));
        $this->assertSame('2026-08-19', $resumen['fin_anterior']->format('Y-m-d'));
    }

    public function test_exporta_excel_y_registra_la_auditoria(): void
    {
        CarbonImmutable::setTestNow('2026-08-21 12:00:00');
        Excel::fake();
        $usuario = $this->usuarioConPermisos(['auditoria.exportar']);

        $this->actingAs($usuario)->post(route('resumen-gerencial.export'), ['periodo' => 'hoy'])->assertOk();

        Excel::assertDownloaded(
            'resumen_gerencial_20260821_20260821.xlsx',
            fn (ResumenGerencialExport $export) => true,
        );
        $this->assertDatabaseHas('auditoria_logs', [
            'usuario_id' => $usuario->id,
            'accion' => 'exportar_resumen_gerencial',
            'modulo' => 'Resumen gerencial',
        ]);
    }

    public function test_el_resumen_programado_no_envia_nada_si_el_correo_esta_deshabilitado(): void
    {
        Notification::fake();
        config()->set('costybot.alertas.email_habilitado', false);

        $this->artisan('costy:enviar-resumen-gerencial', ['tipo' => 'diario'])
            ->expectsOutput('Correo deshabilitado: no se enviaron resúmenes.')
            ->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertSame(0, AuditoriaLog::count());
    }

    public function test_rechaza_periodos_invalidos_o_mayores_a_un_ano(): void
    {
        $usuario = $this->usuarioConPermisos(['auditoria.ver']);

        $this->actingAs($usuario)->get(route('resumen-gerencial.index', ['periodo' => 'desconocido']))
            ->assertSessionHasErrors('periodo');
        $this->actingAs($usuario)->get(route('resumen-gerencial.index', [
            'periodo' => 'personalizado',
            'desde' => '2025-01-01',
            'hasta' => '2026-08-21',
        ]))->assertStatus(422);
    }

    private function usuarioConPermisos(array $permisos): User
    {
        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }
        $usuario = User::factory()->create();
        $usuario->givePermissionTo($permisos);

        return $usuario;
    }

    private function crearSesion(string $sesionId, string $cedula, ?string $resultado, string $inicio): void
    {
        DB::table('sesiones')->insert([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593'.substr($cedula, -9),
            'bot' => 'reactivacion',
            'cedula' => $cedula,
            'estado_sesion' => 'cerrada',
            'resultado' => $resultado,
            'inicio' => $inicio,
            'fin' => $inicio,
        ]);
    }
}
