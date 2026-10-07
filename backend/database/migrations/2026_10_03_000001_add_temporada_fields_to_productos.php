<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos para productos de temporada:
 * - precio_por_confirmar: el producto se muestra pero no se vende por la web
 *   hasta fijar el precio (se consulta por WhatsApp).
 * - pedidos_hasta: último día (hora de Bolivia) en que la web acepta pedidos.
 * - etiqueta_personalizacion: si tiene texto, el cliente debe escribir ese
 *   dato al pedir (ej. "Nombre del difunto", "Para").
 * - detalle_pedidos.personalizacion: lo que escribió el cliente, ya con la
 *   etiqueta delante ("Nombre del difunto: Juan Pérez").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'precio_por_confirmar')) {
                $table->boolean('precio_por_confirmar')->default(false)->after('precio_mayorista');
            }
            if (!Schema::hasColumn('productos', 'pedidos_hasta')) {
                $table->date('pedidos_hasta')->nullable()->after('unidad_tiempo');
            }
            if (!Schema::hasColumn('productos', 'etiqueta_personalizacion')) {
                $table->string('etiqueta_personalizacion', 60)->nullable()->after('pedidos_hasta');
            }
        });

        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('detalle_pedidos', 'personalizacion')) {
                $table->string('personalizacion', 255)->nullable()->after('nombre_producto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('detalle_pedidos', 'personalizacion')) {
                $table->dropColumn('personalizacion');
            }
        });

        Schema::table('productos', function (Blueprint $table) {
            foreach (['etiqueta_personalizacion', 'pedidos_hasta', 'precio_por_confirmar'] as $columna) {
                if (Schema::hasColumn('productos', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
