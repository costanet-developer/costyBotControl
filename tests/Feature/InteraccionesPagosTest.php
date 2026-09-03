<?php

namespace Tests\Feature;

use App\Models\Sesion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InteraccionesPagosTest extends TestCase
{
    use RefreshDatabase;

    public function test_clasifica_y_filtra_todas_las_formas_de_pago(): void
    {
        $usuario = User::factory()->create();
        Permission::findOrCreate('interacciones.ver', 'web');
        $usuario->givePermissionTo('interacciones.ver');
        $this->crearSesion('sesion_procesada_sin_evidencia', '0100000001', 'reactivado');
        $this->crearSesion('sesion_recibida', '0100000002', 'no_reconocido');
        $this->crearSesion('sesion_sin_pago', '0100000003', null);

        DB::table('comprobantes')->insert([
            'sesion_id' => 'sesion_recibida',
            'numero_whatsapp' => '593000000002',
            'numero_transaccion' => 'RECIBIDO-001',
            'estado' => 'recibida',
            'estado_auditoria' => 'PENDIENTE',
        ]);

        $this->assertSame(1, Sesion::pagoProcesado()->count());
        $this->assertSame(1, Sesion::pagoProcesado()->sinComprobanteRelacionado()->count());
        $this->assertSame(1, Sesion::recibidoSinProcesar()->count());
        $this->assertSame(1, Sesion::sinPagoNiComprobante()->count());

        $this->actingAs($usuario)
            ->get(route('interacciones.index', ['pago' => 'procesado']))
            ->assertOk()
            ->assertSee('sesion_procesada_sin_evidencia')
            ->assertDontSee('sesion_recibida')
            ->assertDontSee('sesion_sin_pago');

        $this->actingAs($usuario)
            ->get(route('interacciones.index', ['pago' => 'recibido_no_procesado']))
            ->assertOk()
            ->assertSee('sesion_recibida')
            ->assertDontSee('sesion_procesada_sin_evidencia');

        $this->actingAs($usuario)
            ->get(route('interacciones.index', ['pago' => 'sin_comprobante']))
            ->assertOk()
            ->assertSee('sesion_sin_pago')
            ->assertDontSee('sesion_procesada_sin_evidencia');
    }

    public function test_muestra_el_comprobante_principal_aunque_su_sesion_historica_no_coincida(): void
    {
        $usuario = User::factory()->create();
        Permission::findOrCreate('interacciones.ver', 'web');
        $usuario->givePermissionTo('interacciones.ver');
        $this->crearSesion('sesion_principal', '0100000010', null);

        $comprobanteId = DB::table('comprobantes')->insertGetId([
            'sesion_id' => 'sesion_historica_distinta',
            'numero_whatsapp' => '593000000010',
            'numero_transaccion' => 'PRINCIPAL-999',
            'numero_documento' => 'DOCUMENTO-999',
            'monto' => 20,
            'estado' => 'reactivacion_exitosa',
            'estado_auditoria' => 'PENDIENTE',
        ]);
        DB::table('sesiones')->where('sesion_id', 'sesion_principal')->update([
            'comprobante_id' => $comprobanteId,
        ]);

        $this->actingAs($usuario)
            ->get(route('interacciones.detalle', 'sesion_principal'))
            ->assertOk()
            ->assertSee('Pago procesado')
            ->assertSee('PRINCIPAL-999')
            ->assertSee('DOCUMENTO-999')
            ->assertSee('Usado en la sesión');
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
