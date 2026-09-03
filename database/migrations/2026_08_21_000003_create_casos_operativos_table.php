<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casos_operativos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 190)->unique();
            $table->string('tipo', 50)->index();
            $table->string('prioridad', 15)->default('media')->index();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->string('sesion_id', 80)->nullable()->index();
            $table->unsignedBigInteger('comprobante_id')->nullable()->index();
            $table->unsignedBigInteger('saldo_favor_id')->nullable()->index();
            $table->unsignedBigInteger('validacion_identidad_id')->nullable()->index();
            $table->unsignedBigInteger('otp_verificacion_id')->nullable()->index();
            $table->string('titulo', 180);
            $table->json('detalle')->nullable();
            $table->timestamp('detectado_en');
            $table->timestamp('ultima_deteccion_en');
            $table->foreignId('asignado_a')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('asignado_en')->nullable();
            $table->foreignId('resuelto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resuelto_en')->nullable();
            $table->text('resolucion')->nullable();
            $table->timestamps();

            $table->index(['estado', 'prioridad', 'detectado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casos_operativos');
    }
};
