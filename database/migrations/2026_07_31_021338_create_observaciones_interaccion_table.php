<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observaciones_interaccion', function (Blueprint $table) {
            $table->id();
            $table->string('sesion_id', 80)->nullable();
            $table->foreignId('comprobante_id')->nullable()->constrained('comprobantes');
            $table->foreignId('usuario_id')->constrained('users');
            $table->text('observacion');
            $table->timestamps();
            $table->softDeletes();

            $table->index('sesion_id');
            $table->index('comprobante_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones_interaccion');
    }
};
