<?php

namespace Tests\Feature;

use App\Models\AlertaOperativa;
use App\Models\CasoOperativo;
use App\Models\User;
use App\Services\ProcesarAlertasOperativas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlertasOperativasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-21 12:00:00');
        config(['costybot.alertas.email_habilitado' => false]);
        Role::findOrCreate('contabilidad', 'web');
        Role::findOrCreate('administrador', 'web');
        Role::findOrCreate('superadministrador', 'web');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_genera_una_sola_alerta_vencida_y_notifica_operadores_y_escalamiento(): void
    {
        $contabilidad = $this->usuarioRol('contabilidad');
        $superadmin = $this->usuarioRol('superadministrador');
        $caso = $this->caso('alta-vencida', 'alta', now()->subHours(3));
        $procesador = app(ProcesarAlertasOperativas::class);

        $primera = $procesador->procesar();
        $segunda = $procesador->procesar();

        $this->assertSame(1, $primera['nuevas']);
        $this->assertSame(2, $primera['notificaciones']);
        $this->assertSame(0, $segunda['nuevas']);
        $this->assertDatabaseCount('alertas_operativas', 1);
        $this->assertDatabaseHas('alertas_operativas', [
            'caso_operativo_id' => $caso->id, 'tipo' => 'sla_vencido',
            'estado' => 'notificada', 'estado_email' => 'deshabilitado',
        ]);
        $this->assertSame(1, $contabilidad->fresh()->notifications()->count());
        $this->assertSame(1, $superadmin->fresh()->notifications()->count());
        $this->assertDatabaseHas('auditoria_logs', ['accion' => 'generar_alerta_operativa', 'entidad_id' => $caso->id]);
    }

    public function test_asignado_recibe_aviso_y_un_caso_cerrado_no_genera_alertas(): void
    {
        $asignado = $this->usuarioRol('contabilidad');
        $otro = $this->usuarioRol('contabilidad');
        $caso = $this->caso('media-por-vencer', 'media', now()->subHours(7));
        $caso->update(['estado' => 'en_revision', 'asignado_a' => $asignado->id, 'asignado_en' => now()->subHour()]);
        $cerrado = $this->caso('alta-cerrada', 'alta', now()->subHours(4));
        $cerrado->update(['estado' => 'resuelto', 'resuelto_en' => now(), 'resolucion' => 'Caso concluido.']);

        $resultado = app(ProcesarAlertasOperativas::class)->procesar();

        $this->assertSame(1, $resultado['nuevas']);
        $this->assertDatabaseHas('alertas_operativas', ['caso_operativo_id' => $caso->id, 'tipo' => 'por_vencer']);
        $this->assertDatabaseMissing('alertas_operativas', ['caso_operativo_id' => $cerrado->id]);
        $this->assertSame(1, $asignado->fresh()->notifications()->count());
        $this->assertSame(0, $otro->fresh()->notifications()->count());
    }

    public function test_escalamiento_se_genera_despues_de_24_horas_adicionales(): void
    {
        $this->usuarioRol('contabilidad');
        $this->usuarioRol('administrador');
        $caso = $this->caso('alta-escalada', 'alta', now()->subHours(27));

        app(ProcesarAlertasOperativas::class)->procesar();

        $this->assertDatabaseHas('alertas_operativas', ['caso_operativo_id' => $caso->id, 'tipo' => 'escalado', 'nivel' => 'critica']);
    }

    public function test_centro_muestra_solo_notificaciones_propias_y_permite_marcar_lectura(): void
    {
        $usuario = $this->usuarioRol('contabilidad');
        $otro = $this->usuarioRol('contabilidad');
        Permission::findOrCreate('interacciones.ver', 'web');
        $usuario->givePermissionTo('interacciones.ver');
        $caso = $this->caso('alta-centro', 'alta', now()->subMinutes(10));
        app(ProcesarAlertasOperativas::class)->procesar();

        $propia = $usuario->fresh()->notifications()->firstOrFail();
        $ajena = $otro->fresh()->notifications()->firstOrFail();
        $this->actingAs($usuario)->get(route('notificaciones.index'))
            ->assertOk()->assertSee('Nuevo caso de prioridad alta');
        $this->actingAs($usuario)->get(route('notificaciones.abrir', $ajena->id))->assertNotFound();
        $this->actingAs($usuario)->get(route('notificaciones.abrir', $propia->id))
            ->assertRedirect(route('pendientes.index', ['tipo' => 'casos', 'estado' => 'todos', 'caso_id' => $caso->id]));
        $this->assertNotNull($propia->fresh()->read_at);
    }

    public function test_marcar_todas_no_afecta_notificaciones_de_otro_usuario(): void
    {
        $usuario = $this->usuarioRol('contabilidad');
        $otro = $this->usuarioRol('contabilidad');
        $this->caso('alta-todas', 'alta', now()->subMinutes(10));
        app(ProcesarAlertasOperativas::class)->procesar();

        $this->actingAs($usuario)->patch(route('notificaciones.leer-todas'))->assertRedirect();

        $this->assertSame(0, $usuario->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $otro->fresh()->unreadNotifications()->count());
    }

    public function test_no_intenta_enviar_correo_mientras_el_canal_esta_deshabilitado(): void
    {
        Notification::fake();
        $this->usuarioRol('contabilidad');
        $this->caso('alta-sin-email', 'alta', now()->subMinutes(10));

        $resultado = app(ProcesarAlertasOperativas::class)->procesar();

        $this->assertSame(0, $resultado['emails']);
        $this->assertSame('deshabilitado', AlertaOperativa::firstOrFail()->estado_email);
    }

    private function usuarioRol(string $rol): User
    {
        $usuario = User::factory()->create(['activo' => true, 'bloqueado' => false]);
        $usuario->assignRole($rol);

        return $usuario;
    }

    private function caso(string $clave, string $prioridad, $detectado): CasoOperativo
    {
        return CasoOperativo::create([
            'clave' => $clave, 'tipo' => 'prueba_alerta', 'prioridad' => $prioridad,
            'estado' => 'pendiente', 'titulo' => 'Caso de alerta '.$clave,
            'detectado_en' => $detectado, 'ultima_deteccion_en' => $detectado,
        ]);
    }
}
