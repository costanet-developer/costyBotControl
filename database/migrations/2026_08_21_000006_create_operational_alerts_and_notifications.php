<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->json('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::create('alertas_operativas', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 190)->unique();
            $table->foreignId('caso_operativo_id')->constrained('casos_operativos')->cascadeOnDelete();
            $table->string('tipo', 40)->index();
            $table->string('nivel', 15)->index();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->json('destinatarios')->nullable();
            $table->timestamp('notificada_en')->nullable();
            $table->string('estado_email', 20)->default('deshabilitado');
            $table->timestamp('email_enviado_en')->nullable();
            $table->text('ultimo_error')->nullable();
            $table->timestamps();

            $table->index(['caso_operativo_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_operativas');
        Schema::dropIfExists('notifications');
    }
};
