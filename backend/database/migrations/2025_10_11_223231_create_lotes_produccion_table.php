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
        Schema::create('lotes_produccion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_lote')->unique(); // Ej: LOTE-20251011-001
            $table->foreignId('produccion_id')->constrained('producciones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->integer('cantidad_producida');
            $table->date('fecha_produccion');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->integer('cantidad_disponible'); // Cantidad que aún no se ha vendido/usado
            $table->enum('estado', ['activo', 'vencido', 'agotado', 'retirado'])->default('activo');
            $table->text('notas')->nullable();
            $table->timestamps();
            
            // Índices para optimizar búsquedas
            $table->index(['producto_id', 'fecha_produccion']);
            $table->index(['estado', 'fecha_vencimiento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes_produccion');
    }
};
