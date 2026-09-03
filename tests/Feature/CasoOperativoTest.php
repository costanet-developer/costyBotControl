<?php

namespace Tests\Feature;

use App\Models\CasoOperativo;
use App\Models\Sesion;
use App\Models\User;
use App\Services\CasoOperativoDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CasoOperativoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_detector_es_idempotente_y_conserva_un_caso_resuelto(): void
    {
        $this->sesion('detector-1', null, 'activa', now()->subHour());
        $this->assertSame(1, Sesion::whereBetween('inicio', [now()->subDay(), now()->subMinutes(30)])->count());
        $detector = app(CasoOperativoDetector::class);

        $primera = $detector->detectar();
        $this->assertSame(1, $primera['nuevos']);
        $this->assertDatabaseCount('casos_operativos', 1);

        $caso = CasoOperativo::firstOrFail();
        $caso->update(['estado' => 'resuelto', 'resolucion' => 'Validado manualmente']);
        $segunda = $detector->detectar();

        $this->assertSame(0, $segunda['nuevos']);
        $this->assertSame(1, $segunda['actualizados']);
        $this->assertDatabaseCount('casos_operativos', 1);
        $this->assertSame('resuelto', $caso->fresh()->estado);
    }

    public function test_detecta_reglas_sin_exponer_codigos_otp(): void
    {
        $this->sesion('duplicado-a', null, 'cerrada', now()->subHours(2));
        $this->sesion('duplicado-b', null, 'cerrada', now()->subHours(2));
        DB::table('comprobantes')->insert([
            ['sesion_id' => 'duplicado-a', 'numero_transaccion' => '0001-23', 'monto' => 20],
            ['sesion_id' => 'duplicado-b', 'numero_transaccion' => '123', 'monto' => 20],
        ]);
        $comprobanteId = DB::table('comprobantes')->insertGetId([
            'sesion_id' => 'duplicado-a', 'numero_transaccion' => 'CREDITO-1', 'monto' => 25,
        ]);
        DB::table('saldos_a_favor')->insert([
            'sesion_id' => 'duplicado-a', 'numero_whatsapp' => '593991000001',
            'monto_pagado' => 25, 'monto_factura' => 20, 'excedente' => 2,
            'comprobante_id' => $comprobanteId,
        ]);
        DB::table('validaciones_identidad')->insert([
            'sesion_id' => 'duplicado-a', 'numero_whatsapp' => '593991000001',
            'cedula' => '0991000001', 'estado_kyc' => 'revision_manual', 'derivado_revision' => true,
        ]);
        DB::table('otp_verificaciones')->insert([
            'sesion_id' => 'duplicado-a', 'numero_whatsapp' => '593991000001',
            'correo' => 'cliente@example.com', 'codigo_enviado' => 'secreto-hash',
            'codigo_ingresado' => '654321', 'resultado' => 'agotado', 'intentos' => 3, 'max_intentos' => 3,
        ]);

        app(CasoOperativoDetector::class)->detectar();

        $this->assertDatabaseHas('casos_operativos', ['tipo' => 'transaccion_duplicada']);
        $this->assertDatabaseHas('casos_operativos', ['tipo' => 'credito_inconsistente']);
        $this->assertDatabaseHas('casos_operativos', ['tipo' => 'kyc_revision']);
        $this->assertDatabaseHas('casos_operativos', ['tipo' => 'otp_agotado']);
        $detalleOtp = json_encode(CasoOperativo::where('tipo', 'otp_agotado')->firstOrFail()->detalle);
        $this->assertStringNotContainsString('654321', $detalleOtp);
        $this->assertStringNotContainsString('secreto-hash', $detalleOtp);
    }

    public function test_solo_detecta_como_estancadas_las_sesiones_recientes(): void
    {
        $this->sesion('estancada-reciente', null, 'activa', now()->subHour());
        $this->sesion('estancada-antigua', null, 'activa', now()->subDays(3));

        app(CasoOperativoDetector::class)->detectar();

        $this->assertDatabaseHas('casos_operativos', ['clave' => 'sesion_estancada:estancada-reciente']);
        $this->assertDatabaseMissing('casos_operativos', ['clave' => 'sesion_estancada:estancada-antigua']);
    }

    public function test_usuario_autorizado_puede_tomar_y_resolver_con_auditoria(): void
    {
        $usuario = User::factory()->create();
        Permission::findOrCreate('casos_operativos.gestionar', 'web');
        $usuario->givePermissionTo('casos_operativos.gestionar');
        $caso = $this->caso('prueba:gestion');

        $this->actingAs($usuario)->patch(route('casos-operativos.tomar', $caso))->assertRedirect();
        $this->assertDatabaseHas('casos_operativos', ['id' => $caso->id, 'estado' => 'en_revision', 'asignado_a' => $usuario->id]);

        $this->actingAs($usuario)->patch(route('casos-operativos.cerrar', $caso), [
            'estado' => 'resuelto', 'resolucion' => 'Se verificó la evidencia con contabilidad.',
        ])->assertRedirect();
        $this->assertDatabaseHas('casos_operativos', ['id' => $caso->id, 'estado' => 'resuelto', 'resuelto_por' => $usuario->id]);
        $this->assertDatabaseHas('auditoria_logs', ['accion' => 'tomar_caso_operativo', 'entidad_id' => $caso->id]);
        $this->assertDatabaseHas('auditoria_logs', ['accion' => 'cerrar_caso_operativo', 'entidad_id' => $caso->id]);
    }

    public function test_usuario_sin_permiso_no_puede_gestionar_casos(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario)->patch(route('casos-operativos.tomar', $this->caso('prueba:prohibido')))->assertForbidden();
    }

    public function test_la_bandeja_muestra_los_casos_y_sus_controles_segun_permiso(): void
    {
        $usuario = User::factory()->create();
        Permission::findOrCreate('interacciones.ver', 'web');
        Permission::findOrCreate('casos_operativos.gestionar', 'web');
        $usuario->givePermissionTo(['interacciones.ver', 'casos_operativos.gestionar']);
        $this->caso('prueba:bandeja');

        $this->actingAs($usuario)->get(route('pendientes.index', ['tipo' => 'casos']))
            ->assertOk()
            ->assertSee('Caso de prueba')
            ->assertSee('Tomar caso')
            ->assertSee('Resolver');
    }

    private function sesion(string $id, ?string $resultado, string $estado, $inicio): void
    {
        DB::table('sesiones')->insert([
            'sesion_id' => $id, 'numero_whatsapp' => '593991000001', 'bot' => 'reactivacion',
            'estado_sesion' => $estado, 'resultado' => $resultado, 'inicio' => $inicio,
        ]);
    }

    private function caso(string $clave): CasoOperativo
    {
        return CasoOperativo::create([
            'clave' => $clave, 'tipo' => 'sesion_estancada', 'prioridad' => 'baja',
            'estado' => 'pendiente', 'titulo' => 'Caso de prueba',
            'detectado_en' => now(), 'ultima_deteccion_en' => now(),
        ]);
    }
}
