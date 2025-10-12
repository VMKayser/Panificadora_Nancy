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
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // Código de la promoción (ej: VERANO2025)
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('tipo_descuento', ['porcentaje', 'monto_fijo']); // % o Bs
            $table->decimal('valor_descuento', 10, 2); // 10% o 50 Bs
            $table->decimal('monto_minimo_compra', 10, 2)->nullable(); // Compra mínima para aplicar
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('usos_maximos')->nullable(); // Límite total de usos
            $table->integer('usos_por_cliente')->default(1); // Usos por cliente
            $table->integer('usos_actuales')->default(0); // Contador de usos
            $table->boolean('activo')->default(true);
            $table->json('productos_aplicables')->nullable(); // IDs de productos, null = todos
            $table->json('categorias_aplicables')->nullable(); // IDs de categorías
            $table->timestamps();
            
            $table->index(['codigo', 'activo']);
            $table->index(['fecha_inicio', 'fecha_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};
