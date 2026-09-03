<?php

namespace Tests\Unit;

use App\Enums\EstadoAuditoria;
use PHPUnit\Framework\TestCase;

class EstadoAuditoriaTest extends TestCase
{
    public function test_pendiente_puede_ir_a_en_revision(): void
    {
        $this->assertTrue(EstadoAuditoria::PENDIENTE->puedeTransicionarA(EstadoAuditoria::EN_REVISION));
    }

    public function test_pendiente_no_puede_ir_a_aprobado(): void
    {
        $this->assertFalse(EstadoAuditoria::PENDIENTE->puedeTransicionarA(EstadoAuditoria::APROBADO));
    }

    public function test_en_revision_puede_ir_a_aprobado(): void
    {
        $this->assertTrue(EstadoAuditoria::EN_REVISION->puedeTransicionarA(EstadoAuditoria::APROBADO));
    }

    public function test_en_revision_puede_ir_a_rechazado(): void
    {
        $this->assertTrue(EstadoAuditoria::EN_REVISION->puedeTransicionarA(EstadoAuditoria::RECHAZADO));
    }

    public function test_aprobado_no_puede_volver_a_en_revision(): void
    {
        $this->assertFalse(EstadoAuditoria::APROBADO->puedeTransicionarA(EstadoAuditoria::EN_REVISION));
    }

    public function test_anulado_no_puede_ir_a_ningun_lado(): void
    {
        $this->assertEmpty(EstadoAuditoria::ANULADO->transicionesPermitidas());
    }

    public function test_permiso_requerido_para_aprobar(): void
    {
        $this->assertEquals('comprobantes.aprobar', EstadoAuditoria::EN_REVISION->permisoRequeridoPara(EstadoAuditoria::APROBADO));
    }

    public function test_permiso_requerido_para_anular(): void
    {
        $this->assertEquals('configuracion.editar', EstadoAuditoria::PENDIENTE->permisoRequeridoPara(EstadoAuditoria::ANULADO));
    }

    public function test_labels_no_estan_vacios(): void
    {
        foreach (EstadoAuditoria::cases() as $estado) {
            $this->assertNotEmpty($estado->label());
        }
    }
}
