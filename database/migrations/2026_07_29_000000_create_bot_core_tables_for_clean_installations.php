<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estas tablas ya existen en Producción porque las creó el bot.
     * Solo se crean en instalaciones limpias y bases aisladas de pruebas.
     */
    public function up(): void
    {
        if (! Schema::hasTable('clientes')) {
            Schema::create('clientes', function (Blueprint $table) {
                $table->string('numero_whatsapp', 20)->primary();
                $table->string('cedula', 15)->nullable();
                $table->string('nombre', 100)->nullable();
                $table->integer('idcliente_costanet')->nullable();
                $table->timestamp('primera_interaccion')->nullable();
                $table->timestamp('ultima_interaccion')->nullable();
                $table->string('correo_registrado', 150)->nullable();
            });
        }

        if (! Schema::hasTable('sesiones')) {
            Schema::create('sesiones', function (Blueprint $table) {
                $table->id();
                $table->string('sesion_id', 80)->unique();
                $table->string('numero_whatsapp', 20);
                $table->string('bot', 30)->nullable();
                $table->string('intencion', 30)->nullable();
                $table->string('cedula', 15)->nullable();
                $table->string('estado_sesion', 20)->default('activa');
                $table->string('resultado', 40)->nullable();
                $table->integer('intentos_comprobante')->default(0);
                $table->timestamp('inicio')->nullable();
                $table->timestamp('fin')->nullable();
                $table->string('media_id', 100)->nullable();
                $table->string('cedula_media_id', 100)->nullable();
                $table->json('mensajes_procesados')->nullable();
                $table->boolean('es_multiples_servicios')->default(false);
                $table->json('servicios_disponibles')->nullable();
                $table->string('codigo_servicio_elegido', 30)->nullable();
                $table->unsignedBigInteger('comprobante_id')->nullable();
                $table->bigInteger('menu_generado_en')->nullable();
                $table->bigInteger('ultima_actividad')->nullable();
            });
        }

        if (! Schema::hasTable('eventos_interaccion')) {
            Schema::create('eventos_interaccion', function (Blueprint $table) {
                $table->id();
                $table->string('sesion_id', 80)->index();
                $table->string('numero_whatsapp', 20);
                $table->timestamp('fecha_evento')->nullable();
                $table->string('paso', 60);
                $table->string('estado_conversacion', 50)->nullable();
                $table->integer('intentos_comprobante')->nullable();
                $table->string('cedula', 15)->nullable();
                $table->string('tipo_comprobante', 50)->nullable();
                $table->boolean('duplicado')->nullable();
                $table->integer('opcion_ocr')->nullable();
                $table->decimal('monto_esperado', 12, 2)->nullable();
                $table->decimal('deuda_total', 12, 2)->nullable();
                $table->json('datos_adicionales')->nullable();
            });
        }

        if (! Schema::hasTable('comprobantes')) {
            Schema::create('comprobantes', function (Blueprint $table) {
                $table->id();
                $table->string('sesion_id', 80)->nullable()->index();
                $table->string('numero_whatsapp', 20)->nullable();
                $table->string('origen', 20)->nullable();
                $table->timestamp('fecha_hora')->nullable();
                $table->string('nombre_archivo', 200)->nullable();
                $table->string('ruta_imagen', 300)->nullable();
                $table->string('media_id', 100)->nullable();
                $table->string('cedula', 15)->nullable();
                $table->string('banco', 100)->nullable();
                $table->decimal('monto', 12, 2)->nullable();
                $table->string('fecha_comprobante', 20)->nullable();
                $table->string('numero_transaccion', 100)->nullable();
                $table->string('titular', 150)->nullable();
                $table->string('cuenta_destino', 50)->nullable();
                $table->string('estado', 30)->nullable();
                $table->boolean('banco_valido')->nullable();
                $table->boolean('cuenta_valida')->nullable();
                $table->boolean('titular_valido')->nullable();
                $table->string('riesgo_visual', 20)->nullable();
                $table->json('alertas')->nullable();
                $table->integer('probabilidad_ia_generativa')->nullable();
                $table->string('riesgo_ia_generativa', 20)->nullable();
                $table->json('alertas_ia_generativa')->nullable();
                $table->integer('score_confianza')->nullable();
                $table->string('accion_recomendada', 30)->nullable();
            });
        }

        if (! Schema::hasTable('documentos_identidad')) {
            Schema::create('documentos_identidad', function (Blueprint $table) {
                $table->id();
                $table->string('sesion_id', 80)->index();
                $table->string('numero_whatsapp', 20);
                $table->timestamp('fecha_hora')->nullable();
                $table->string('nombre_archivo', 200)->nullable();
                $table->string('ruta_imagen', 300)->nullable();
                $table->string('media_id', 100)->nullable();
                $table->string('cedula_ingresada', 15)->nullable();
                $table->string('cedula_ocr', 15)->nullable();
                $table->string('tipo_documento', 30)->nullable();
                $table->string('nombres', 150)->nullable();
                $table->string('apellidos', 150)->nullable();
                $table->string('fecha_nacimiento', 20)->nullable();
                $table->string('fecha_expiracion', 20)->nullable();
                $table->string('nacionalidad', 50)->nullable();
                $table->string('calidad_lectura', 20)->nullable();
                $table->boolean('coincide')->nullable();
                $table->boolean('ocr_valido')->nullable();
                $table->text('observaciones')->nullable();
                $table->string('lado', 10)->nullable();
                $table->json('ocr_json')->nullable();
                $table->decimal('ocr_confianza', 5, 2)->nullable();
                $table->string('sexo', 30)->nullable();
                $table->string('estado_civil', 50)->nullable();
                $table->string('codigo_dactilar', 10)->nullable();
                $table->string('emisor_documento', 50)->nullable();
            });
        }

        if (! Schema::hasTable('validaciones_identidad')) {
            Schema::create('validaciones_identidad', function (Blueprint $table) {
                $table->id();
                $table->string('sesion_id', 80)->index();
                $table->string('numero_whatsapp', 20);
                $table->string('cedula', 15);
                $table->timestamp('cedula_ingresada_en')->nullable();
                $table->timestamp('anverso_recibido_en')->nullable();
                $table->timestamp('reverso_recibido_en')->nullable();
                $table->string('ocr_vs_sistema_resultado', 20)->nullable();
                $table->json('ocr_vs_sistema_detalle')->nullable();
                $table->boolean('codigo_dactilar_validado')->nullable();
                $table->string('correo', 150)->nullable();
                $table->boolean('correo_verificado')->default(false);
                $table->string('otp_codigo', 6)->nullable();
                $table->timestamp('otp_expira_en')->nullable();
                $table->integer('otp_intentos')->default(0);
                $table->string('estado_kyc', 30)->nullable();
                $table->integer('intentos_fallidos_comparacion')->default(0);
                $table->boolean('derivado_revision')->default(false);
                $table->timestamp('actualizado_en')->nullable();
            });
        }

        if (! Schema::hasTable('otp_verificaciones')) {
            Schema::create('otp_verificaciones', function (Blueprint $table) {
                $table->id();
                $table->string('sesion_id', 80)->index();
                $table->string('numero_whatsapp', 20);
                $table->string('correo', 150);
                $table->string('codigo_enviado', 64)->nullable();
                $table->string('codigo_ingresado', 64)->nullable();
                $table->string('resultado', 30)->nullable();
                $table->timestamp('creado_en')->nullable();
                $table->string('cedula', 15)->nullable();
                $table->timestamp('expira_en')->nullable();
                $table->integer('intentos')->default(0);
                $table->integer('max_intentos')->default(3);
            });
        }

        if (! Schema::hasTable('saldos_a_favor')) {
            Schema::create('saldos_a_favor', function (Blueprint $table) {
                $table->id();
                $table->string('sesion_id', 100)->index();
                $table->string('numero_whatsapp', 20);
                $table->string('cedula', 20)->nullable();
                $table->integer('idcliente')->nullable();
                $table->integer('idfactura')->nullable();
                $table->decimal('monto_pagado', 12, 2)->nullable();
                $table->decimal('monto_factura', 12, 2)->nullable();
                $table->decimal('excedente', 12, 2)->nullable();
                $table->string('numero_transaccion', 50)->nullable();
                $table->unsignedBigInteger('comprobante_id')->nullable();
                $table->string('estado', 20)->nullable();
                $table->string('origen', 30)->nullable();
                $table->timestamp('fecha_registro')->nullable();
            });
        }

        if (! Schema::hasTable('encuestas_ces')) {
            Schema::create('encuestas_ces', function (Blueprint $table) {
                $table->id();
                $table->string('sesion_id', 80);
                $table->string('numero_whatsapp', 20);
                $table->string('tipo_gestion', 30);
                $table->string('estado', 20)->default('pendiente');
                $table->timestamp('programada_para')->nullable();
                $table->timestamp('enviada_en')->nullable();
                $table->timestamp('respondida_en')->nullable();
                $table->timestamp('vencida_en')->nullable();
                $table->smallInteger('puntuacion')->nullable();
                $table->text('comentario')->nullable();
                $table->string('motivo_dificultad')->nullable();
                $table->string('whatsapp_message_id')->nullable();
                $table->string('wa_respuesta_id')->nullable();
                $table->smallInteger('intentos_envio')->default(0);
                $table->text('error_envio')->nullable();
                $table->timestamp('creado_en')->nullable();
                $table->timestamp('actualizado_en')->nullable();
                $table->unique(['sesion_id', 'tipo_gestion']);
                $table->index(['estado', 'programada_para']);
            });
        }
    }

    public function down(): void
    {
        // Intencionalmente vacío: en Producción estas tablas pertenecen a n8n
        // y nunca deben eliminarse por un rollback de Laravel.
    }
};
