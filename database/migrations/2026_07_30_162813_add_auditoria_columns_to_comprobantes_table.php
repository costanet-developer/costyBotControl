<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->string('estado_auditoria', 20)->default('PENDIENTE');
            $table->foreignId('revisado_por')->nullable()->constrained('users');
            $table->timestamp('revisado_en')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->timestamp('aprobado_en')->nullable();
            $table->foreignId('rechazado_por')->nullable()->constrained('users');
            $table->timestamp('rechazado_en')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->boolean('tiene_observaciones')->default(false);
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->index('estado_auditoria');
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropIndex(['estado_auditoria']);
            $table->dropColumn([
                'estado_auditoria', 'revisado_por', 'revisado_en',
                'aprobado_por', 'aprobado_en', 'rechazado_por', 'rechazado_en',
                'motivo_rechazo', 'tiene_observaciones', 'deleted_at', 'deleted_by',
                'created_at', 'updated_at', 'updated_by',
            ]);
        });
    }
};
