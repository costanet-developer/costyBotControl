<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class IndicadoresKpiDashboardTest extends TestCase
{
    use RefreshDatabase;

    private int $telefono = 0;

    public function test_muestra_fcr_y_ces_separados_por_opcion(): void
    {
        Carbon::setTestNow('2026-08-22 12:00:00');
        $usuario = $this->usuarioDashboard();

        $this->crearSesion('reactiva-ok', 'reactivado');
        $this->crearEvento('reactiva-ok', 'menu_reactivar_seleccionado');
        $this->crearEvento('reactiva-ok', 'encuesta_ces_respondida', ['opcion' => 'reactivacion', 'puntuacion' => 6]);
        $this->crearEncuesta('reactiva-ok', 'reactivacion', 'respondida', 6);

        $this->crearSesion('reactiva-no', 'cerrado_sin_comprobante');
        $this->crearEvento('reactiva-no', 'menu_reactivar_seleccionado');
        $this->crearEvento('reactiva-no', 'encuesta_ces_respondida', ['opcion' => 'reactivacion', 'puntuacion' => 4]);
        $this->crearEncuesta('reactiva-no', 'reactivacion', 'respondida', 4);

        $this->crearSesion('saldo-ok', 'cuenta_al_dia');
        $this->crearEvento('saldo-ok', 'menu_consultar_seleccionado');
        $this->crearEvento('saldo-ok', 'encuesta_ces_respondida', ['opcion' => 'saldo_pagar', 'puntuacion' => 7]);
        $this->crearEncuesta('saldo-ok', 'consulta_valores', 'respondida', 7);

        $this->crearSesion('saldo-manual', 'transferido_pagos');
        $this->crearEvento('saldo-manual', 'menu_consultar_seleccionado');

        $this->actingAs($usuario)->get(route('dashboard', ['periodo_kpi' => 30]))
            ->assertOk()
            ->assertSee('KPI de experiencia y resolución')
            ->assertSee('Reactivación')
            ->assertSee('Saldo a pagar')
            ->assertSee('50.0%', false)
            ->assertSee('5.0', false)
            ->assertSee('7.0', false)
            ->assertSee('2 respuestas')
            ->assertSee('1 respuesta')
            ->assertSee('100.0% tasa de respuesta');
    }

    public function test_muestra_sin_respuestas_y_respeta_el_periodo(): void
    {
        Carbon::setTestNow('2026-08-22 12:00:00');
        $usuario = $this->usuarioDashboard();

        $this->crearSesion('reciente', 'reactivado');
        $this->crearEvento('reciente', 'menu_reactivar_seleccionado');
        $this->crearSesion('antigua', 'cerrado_sin_comprobante', now()->subDays(10));
        $this->crearEvento('antigua', 'menu_reactivar_seleccionado', null, now()->subDays(10));

        $this->actingAs($usuario)->get(route('dashboard', ['periodo_kpi' => 7]))
            ->assertOk()
            ->assertSee('100.0%', false)
            ->assertSee('1 de 1 resueltas')
            ->assertSee('Sin respuestas')
            ->assertSee('0 enviadas; esperando respuesta');
    }

    private function usuarioDashboard(): User
    {
        $usuario = User::factory()->create();
        Permission::findOrCreate('interacciones.ver', 'web');
        $usuario->givePermissionTo('interacciones.ver');

        return $usuario;
    }

    private function crearSesion(string $sesionId, ?string $resultado, ?Carbon $inicio = null): void
    {
        $this->telefono++;

        DB::table('sesiones')->insert([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593990'.str_pad((string) $this->telefono, 6, '0', STR_PAD_LEFT),
            'bot' => 'reactivacion',
            'estado_sesion' => 'cerrada',
            'resultado' => $resultado,
            'inicio' => $inicio ?? now(),
            'fin' => $inicio ?? now(),
        ]);
    }

    private function crearEvento(string $sesionId, string $paso, ?array $datos = null, ?Carbon $fecha = null): void
    {
        DB::table('eventos_interaccion')->insert([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593990000000',
            'fecha_evento' => $fecha ?? now(),
            'paso' => $paso,
            'datos_adicionales' => $datos === null ? null : json_encode($datos),
        ]);
    }

    private function crearEncuesta(
        string $sesionId,
        string $tipoGestion,
        string $estado,
        ?int $puntuacion = null,
    ): void {
        DB::table('encuestas_ces')->insert([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593990000000',
            'tipo_gestion' => $tipoGestion,
            'estado' => $estado,
            'programada_para' => now(),
            'enviada_en' => in_array($estado, ['enviada', 'respondida', 'vencida'], true) ? now() : null,
            'respondida_en' => $estado === 'respondida' ? now() : null,
            'puntuacion' => $puntuacion,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
    }
}
