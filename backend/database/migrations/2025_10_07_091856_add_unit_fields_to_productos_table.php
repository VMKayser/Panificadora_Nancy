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
        Schema::table('productos', function (Blueprint $table) {
            // Unidad de medida del producto
            $table->enum('unidad_medida', [
                'unidad',      // Para productos individuales (pan, empanada)
                'cm',          // Para TantaWawas por tamaño
                'docena',      // Para paquetes de 12
                'paquete',     // Para sets especiales
                'gramos',      // Para productos por peso
                'kilogramos',  // Para productos por peso
                'arroba',      // Para urpu
                'porcion'      // Para porciones
            ])->default('unidad')->after('descripcion_corta');
            
            // Cantidad/Tamaño del producto (ej: 60 para 60cm, 1 para 1 unidad)
            $table->decimal('cantidad', 8, 2)->nullable()->after('unidad_medida');
            
            // Presentación visual para mostrar al cliente (ej: "60 cm aprox", "1 Unidad", "200 und.")
            $table->string('presentacion', 100)->nullable()->after('cantidad');
            
            // Para productos con múltiples variantes de tamaño/precio
            $table->boolean('tiene_variantes')->default(false)->after('presentacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['unidad_medida', 'cantidad', 'presentacion', 'tiene_variantes']);
        });
    }
};
