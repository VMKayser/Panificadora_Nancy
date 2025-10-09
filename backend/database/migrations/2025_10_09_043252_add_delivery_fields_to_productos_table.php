<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agregar campos para controlar tipos de entrega disponibles
     * 
     * Lógica:
     * - Recojo en sucursal: SIEMPRE disponible si el producto está activo
     * - Delivery: Solo si permite_delivery = true
     * - Envío nacional: Solo si permite_envio_nacional = true
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('permite_delivery')->default(true)->after('esta_activo');
            $table->boolean('permite_envio_nacional')->default(false)->after('permite_delivery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['permite_delivery', 'permite_envio_nacional']);
        });
    }
};
