<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisiones_comprobante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_id')->constrained('comprobantes');
            $table->foreignId('usuario_id')->constrained('users');
            $table->string('estado_anterior', 20)->nullable();
            $table->string('estado_nuevo', 20);
            $table->text('observacion')->nullable();
            $table->timestamp('fecha_revision')->useCurrent();

            $table->index('comprobante_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisiones_comprobante');
    }
};
