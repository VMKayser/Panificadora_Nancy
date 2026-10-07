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
        Schema::table('pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos', 'descuento_bs')) {
                $table->decimal('descuento_bs', 10, 2)->default(0)->after('descuento')->comment('Descuento en bolivianos aplicado por el vendedor');
            }
            if (!Schema::hasColumn('pedidos', 'motivo_descuento')) {
                $table->string('motivo_descuento')->nullable()->after('descuento_bs')->comment('Motivo del descuento aplicado');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['descuento_bs', 'motivo_descuento']);
        });
    }
};
