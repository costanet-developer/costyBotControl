<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InteraccionDetalleFaseDosTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_la_trazabilidad_enriquecida_sin_exponer_el_otp(): void
    {
        $usuario = User::factory()->create();
        Permission::findOrCreate('interacciones.ver', 'web');
        $usuario->givePermissionTo('interacciones.ver');
        $sesionId = '593999999999_fase2';
        $servicios = [[
            'codigo' => 'abc123',
            'nombre' => 'CLIENTE DEMOSTRACION',
            'estado' => 'SUSPENDIDO',
            'direccion_principal' => 'Dirección de prueba',
            'facturacion' => ['total_facturas' => '20.00', 'facturas_nopagadas' => 1],
            'servicios' => [[
                'perfil' => 'PLAN 200MB $20',
                'tiposervicio' => 'internet',
                'costo' => '20.00',
                'status_user' => 'OFFLINE',
            ]],
            'otros_servicios' => ['recurrentes' => [[
                'nombre' => 'TVCABLE SATELITAL',
                'tipo' => 'television',
                'monto' => '5.00',
                'state' => 'pendiente',
            ]]],
        ]];

        DB::table('sesiones')->insert([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593999999999',
            'bot' => 'reactivacion',
            'intencion' => 'valores_a_pagar',
            'cedula' => '0999999999',
            'estado_sesion' => 'cerrada',
            'resultado' => 'consulta_valores',
            'servicios_disponibles' => json_encode($servicios),
            'codigo_servicio_elegido' => 'abc123',
            'inicio' => now(),
            'fin' => now(),
        ]);

        $comprobanteId = DB::table('comprobantes')->insertGetId([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593999999999',
            'banco' => 'Banco Pichincha',
            'monto' => 25,
            'numero_transaccion' => 'CONTROL-001',
            'numero_documento' => 'DOC-002',
            'titular_origen' => 'CLIENTE ORIGEN',
            'cuenta_origen' => '****7788',
            'titular' => 'TELECOM&NET S.A.',
            'cuenta_destino' => '2100239012',
            'estado' => 'reactivacion_exitosa',
            'estado_auditoria' => 'PENDIENTE',
        ]);
        DB::table('sesiones')->where('sesion_id', $sesionId)->update(['comprobante_id' => $comprobanteId]);

        DB::table('eventos_interaccion')->insert([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593999999999',
            'fecha_evento' => now(),
            'paso' => 'mensaje_seguro_kyc',
            'datos_adicionales' => json_encode([
                'correo' => 'prueba@example.com',
                'otp_codigo' => '647538',
                'token' => 'secreto-token',
            ]),
        ]);

        DB::table('documentos_identidad')->insert([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593999999999',
            'lado' => 'anverso',
            'cedula_ingresada' => '0999999999',
            'cedula_ocr' => '0999999999',
            'nombres' => 'PERSONA',
            'apellidos' => 'DE PRUEBA',
            'sexo' => 'MUJER',
            'estado_civil' => 'SOLTERA',
            'codigo_dactilar' => 'E3343V2244',
            'emisor_documento' => 'registro_civil_gobierno',
            'ocr_valido' => true,
            'coincide' => true,
        ]);

        DB::table('validaciones_identidad')->insert([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593999999999',
            'cedula' => '0999999999',
            'ocr_vs_sistema_resultado' => 'coincide',
            'codigo_dactilar_validado' => true,
            'correo' => 'prueba@example.com',
            'correo_verificado' => true,
            'estado_kyc' => 'validada',
            'actualizado_en' => now(),
        ]);

        DB::table('otp_verificaciones')->insert([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593999999999',
            'cedula' => '0999999999',
            'correo' => 'prueba@example.com',
            'codigo_enviado' => '647538',
            'codigo_ingresado' => '647538',
            'resultado' => 'validado',
            'intentos' => 1,
            'max_intentos' => 3,
            'creado_en' => now(),
        ]);

        DB::table('saldos_a_favor')->insert([
            'sesion_id' => $sesionId,
            'numero_whatsapp' => '593999999999',
            'cedula' => '0999999999',
            'monto_pagado' => 25,
            'monto_factura' => 20,
            'excedente' => 5,
            'comprobante_id' => $comprobanteId,
            'estado' => 'aplicado',
        ]);

        $respuesta = $this->actingAs($usuario)->get(route('interacciones.detalle', $sesionId));

        $respuesta->assertOk()
            ->assertSee('PLAN 200MB $20')
            ->assertSee('TVCABLE SATELITAL')
            ->assertSee('DOC-002')
            ->assertSee('CLIENTE ORIGEN')
            ->assertSee('****7788')
            ->assertSee('E3343V2244')
            ->assertSee('Registro Civil (Gobierno)')
            ->assertSee('Crédito generado')
            ->assertSee('pr••••@example.com')
            ->assertDontSee('prueba@example.com')
            ->assertDontSee('647538')
            ->assertDontSee('secreto-token');
    }
}
