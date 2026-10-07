<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las líneas de extras (ej. "Panetón - Jugo") se guardan con el producto
     * principal en productos_id, pero no deben descontar su stock.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('detalle_pedidos', 'es_extra')) {
            Schema::table('detalle_pedidos', function (Blueprint $table) {
                $table->boolean('es_extra')->default(false)->after('subtotal');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('detalle_pedidos', 'es_extra')) {
            Schema::table('detalle_pedidos', function (Blueprint $table) {
                $table->dropColumn('es_extra');
            });
        }
    }
};
