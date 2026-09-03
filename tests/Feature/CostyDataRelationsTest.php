<?php

namespace Tests\Feature;

use App\Models\Sesion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CostyDataRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_carga_la_trazabilidad_nueva_sin_exponer_codigos_otp(): void
    {
        $sessionId = '593999999999_123456';

        DB::table('sesiones')->insert([
            'sesion_id' => $sessionId,
            'numero_whatsapp' => '593999999999',
            'bot' => 'reactivacion',
            'estado_sesion' => 'cerrada',
            'resultado' => 'consulta_valores',
            'inicio' => now(),
            'fin' => now(),
        ]);

        $comprobanteId = DB::table('comprobantes')->insertGetId([
            'sesion_id' => $sessionId,
            'numero_whatsapp' => '593999999999',
            'estado' => 'reactivacion_exitosa',
            'estado_auditoria' => 'PENDIENTE',
            'numero_documento' => 'DOC-123',
            'titular_origen' => 'CLIENTE PRUEBA',
            'cuenta_origen' => '****1234',
        ]);
        DB::table('sesiones')->where('sesion_id', $sessionId)->update(['comprobante_id' => $comprobanteId]);

        DB::table('documentos_identidad')->insert([
            'sesion_id' => $sessionId,
            'numero_whatsapp' => '593999999999',
            'lado' => 'anverso',
            'ocr_json' => json_encode(['cedula' => '0999999999']),
        ]);
        DB::table('validaciones_identidad')->insert([
            'sesion_id' => $sessionId,
            'numero_whatsapp' => '593999999999',
            'cedula' => '0999999999',
            'estado_kyc' => 'validada',
            'correo_verificado' => true,
            'actualizado_en' => now(),
        ]);
        DB::table('otp_verificaciones')->insert([
            'sesion_id' => $sessionId,
            'numero_whatsapp' => '593999999999',
            'correo' => 'prueba@example.com',
            'codigo_enviado' => hash('sha256', '123456'),
            'codigo_ingresado' => hash('sha256', '123456'),
            'resultado' => 'validado',
            'creado_en' => now(),
        ]);
        DB::table('saldos_a_favor')->insert([
            'sesion_id' => $sessionId,
            'numero_whatsapp' => '593999999999',
            'comprobante_id' => $comprobanteId,
            'monto_pagado' => 20,
            'monto_factura' => 17.50,
            'excedente' => 2.50,
            'estado' => 'pendiente',
        ]);

        $session = Sesion::with([
            'comprobantePrincipal',
            'documentosIdentidad',
            'ultimaValidacionIdentidad',
            'otpVerificaciones',
            'saldosFavor',
        ])->where('sesion_id', $sessionId)->firstOrFail();

        $this->assertSame('DOC-123', $session->comprobantePrincipal->numero_documento);
        $this->assertCount(1, $session->documentosIdentidad);
        $this->assertSame('validada', $session->ultimaValidacionIdentidad->estado_kyc);
        $this->assertSame('2.50', $session->saldosFavor->first()->excedente);

        $serializedOtp = $session->otpVerificaciones->first()->toArray();
        $this->assertArrayNotHasKey('codigo_enviado', $serializedOtp);
        $this->assertArrayNotHasKey('codigo_ingresado', $serializedOtp);
        $this->assertSame('validado', $serializedOtp['resultado']);
    }
}
