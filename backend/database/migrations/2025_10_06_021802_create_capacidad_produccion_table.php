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
        Schema::create('capacidad_produccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
            ->constrained('productos')
            ->onDelete('cascade');

            $table->integer('limite_semanal');
            $table->date('semana_inicio');
            $table->date('semana_fin');
            $table->integer('cantidad_reservada')->default(0);

            $table->index(['producto_id', 'semana_inicio']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capacidad_produccion');
    }
};
