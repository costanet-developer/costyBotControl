<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('auditoria_logs')) {
            Schema::create('auditoria_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('usuario_id')->nullable()->constrained('users');
                $table->string('accion', 50);
                $table->string('modulo', 50);
                $table->string('entidad', 60)->nullable();
                $table->unsignedBigInteger('entidad_id')->nullable();
                $table->json('datos_anteriores')->nullable();
                $table->json('datos_nuevos')->nullable();
                $table->string('direccion_ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('resultado', 20)->default('exitoso');
                $table->text('descripcion')->nullable();
                $table->timestamp('fecha_hora')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_logs');
    }
};
