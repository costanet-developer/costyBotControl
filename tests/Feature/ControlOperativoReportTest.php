<?php

namespace Tests\Feature;

use App\Exports\InteraccionesExport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ControlOperativoReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_tablero_muestra_pagos_creditos_y_excepciones(): void
    {
        $usuario = User::factory()->create();
        Permission::findOrCreate('interacciones.ver', 'web');
        $usuario->givePermissionTo('interacciones.ver');
        $this->crearSesion('dashboard_pago', '0990000001', 'reactivado');
        DB::table('saldos_a_favor')->insert([
            'sesion_id' => 'dashboard_pago',
            'numero_whatsapp' => '593990000001',
            'cedula' => '0990000001',
            'excedente' => 5,
            'estado' => 'pendiente',
        ]);

        $this->actingAs($usuario)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pagos hoy')
            ->assertSee('Total procesados')
            ->assertSee('Créditos pendientes')
            ->assertSee('$5.00')
            ->assertSee('Pagos sin evidencia');
    }

    public function test_el_reporte_incluye_procesados_sin_evidencia_y_comprobante_principal(): void
    {
        $usuario = $this->usuarioConPermisos(['reportes.ver']);
        $this->crearSesion('reporte_sin_evidencia', '0990000011', 'reactivado');
        $this->crearSesion('reporte_principal', '0990000012', null);

        $comprobanteId = DB::table('comprobantes')->insertGetId([
            'sesion_id' => 'sesion_historica',
            'numero_whatsapp' => '593990000012',
            'banco' => 'Banco Pichincha',
            'monto' => 25,
            'numero_transaccion' => 'CONTROL-REPORT',
            'numero_documento' => 'DOCUMENTO-REPORT',
            'titular_origen' => 'TITULAR ORIGEN',
            'cuenta_origen' => '****9911',
            'estado' => 'reactivacion_exitosa',
            'estado_auditoria' => 'PENDIENTE',
        ]);
        DB::table('sesiones')->where('sesion_id', 'reporte_principal')->update(['comprobante_id' => $comprobanteId]);
        DB::table('otp_verificaciones')->insert([
            'sesion_id' => 'reporte_principal',
            'numero_whatsapp' => '593990000012',
            'correo' => 'seguro@example.com',
            'codigo_enviado' => '647538',
            'codigo_ingresado' => '647538',
            'resultado' => 'validado',
        ]);

        $this->actingAs($usuario)->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('reporte_sin_evidencia')
            ->assertSee('Sin evidencia enlazada')
            ->assertSee('CONTROL-REPORT')
            ->assertSee('DOCUMENTO-REPORT')
            ->assertSee('TITULAR ORIGEN')
            ->assertSee('****9911')
            ->assertDontSee('647538');
    }

    public function test_el_excel_contiene_nuevos_campos_sin_codigos_otp(): void
    {
        $this->crearSesion('exportacion_segura', '0990000021', 'reactivado');
        $comprobanteId = DB::table('comprobantes')->insertGetId([
            'sesion_id' => 'exportacion_segura',
            'numero_whatsapp' => '593990000021',
            'monto' => 20,
            'numero_transaccion' => 'CONTROL-EXCEL',
            'numero_documento' => 'DOCUMENTO-EXCEL',
            'titular_origen' => 'ORIGEN EXCEL',
            'cuenta_origen' => '****0021',
            'estado' => 'reactivacion_exitosa',
            'estado_auditoria' => 'PENDIENTE',
        ]);
        DB::table('sesiones')->where('sesion_id', 'exportacion_segura')->update(['comprobante_id' => $comprobanteId]);
        DB::table('otp_verificaciones')->insert([
            'sesion_id' => 'exportacion_segura',
            'numero_whatsapp' => '593990000021',
            'correo' => 'excel@example.com',
            'codigo_enviado' => '123456',
            'codigo_ingresado' => '123456',
            'resultado' => 'validado',
        ]);

        $export = new InteraccionesExport(Request::create('/reportes', 'GET', ['tipo' => 'procesado']));
        $sesion = $export->query()->where('sesion_id', 'exportacion_segura')->firstOrFail();
        $fila = $export->map($export->fila($sesion));

        $this->assertCount(20, $export->headings());
        $this->assertCount(20, $fila);
        $this->assertContains('CONTROL-EXCEL', $fila);
        $this->assertContains('DOCUMENTO-EXCEL', $fila);
        $this->assertContains('ORIGEN EXCEL', $fila);
        $this->assertContains('Validado', $fila);
        $this->assertNotContains('123456', $fila);
    }

    public function test_la_descarga_exige_permiso_y_conserva_los_filtros(): void
    {
        $sinPermiso = User::factory()->create();
        $this->actingAs($sinPermiso)->post(route('reportes.export'), ['tipo' => 'procesado'])->assertForbidden();

        $conPermiso = $this->usuarioConPermisos(['reportes.exportar']);
        Excel::fake();
        Carbon::setTestNow('2026-08-21 12:00:00');

        $this->actingAs($conPermiso)->post(route('reportes.export'), [
            'tipo' => 'procesado_sin_comprobante',
            'desde' => now()->format('Y-m-d'),
        ])->assertOk();

        Excel::assertDownloaded(
            'control_operativo_procesado_sin_comprobante_20260821_120000.xlsx',
            fn (InteraccionesExport $export) => $export->tipoReporte() === 'procesado_sin_comprobante'
        );
    }

    private function usuarioConPermisos(array $permisos): User
    {
        $usuario = User::factory()->create();
        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }
        $usuario->givePermissionTo($permisos);

        return $usuario;
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
