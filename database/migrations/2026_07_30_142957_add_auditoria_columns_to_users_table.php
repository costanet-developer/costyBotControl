<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'creado_por')) {
                $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'actualizado_por')) {
                $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['creado_por']);
            $table->dropForeign(['actualizado_por']);
            $table->dropColumn(['creado_por', 'actualizado_por']);
        });
    }
};
