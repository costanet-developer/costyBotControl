<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            if (! Schema::hasColumn('comprobantes', 'numero_documento')) {
                $table->string('numero_documento', 100)->nullable();
            }
            if (! Schema::hasColumn('comprobantes', 'titular_origen')) {
                $table->string('titular_origen', 150)->nullable();
            }
            if (! Schema::hasColumn('comprobantes', 'cuenta_origen')) {
                $table->string('cuenta_origen', 80)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $columns = collect(['numero_documento', 'titular_origen', 'cuenta_origen'])
                ->filter(fn (string $column) => Schema::hasColumn('comprobantes', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
