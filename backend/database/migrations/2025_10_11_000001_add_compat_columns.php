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
        // Compatibilidad: algunas partes del código usan 'orden' en lugar de 'order'
        if (Schema::hasTable('categorias') && !Schema::hasColumn('categorias', 'orden')) {
            Schema::table('categorias', function (Blueprint $table) {
                $table->integer('orden')->default(0)->after('esta_activo');
            });
        }

        // Compatibilidad: algunos seeders/controladores esperan 'tiene_limite_produccion'
        if (Schema::hasTable('productos') && !Schema::hasColumn('productos', 'tiene_limite_produccion')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->boolean('tiene_limite_produccion')->default(false)->after('unidad_tiempo');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('categorias') && Schema::hasColumn('categorias', 'orden')) {
            Schema::table('categorias', function (Blueprint $table) {
                $table->dropColumn('orden');
            });
        }

        if (Schema::hasTable('productos') && Schema::hasColumn('productos', 'tiene_limite_produccion')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('tiene_limite_produccion');
            });
        }
    }
};
