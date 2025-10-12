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
        Schema::create('configuracion_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique()->comment('Clave única de configuración');
            $table->text('valor')->comment('Valor de la configuración');
            $table->enum('tipo', ['texto', 'numero', 'boolean', 'json'])->default('texto')->comment('Tipo de dato');
            $table->text('descripcion')->nullable()->comment('Descripción de la configuración');
            $table->string('grupo', 50)->nullable()->comment('Grupo al que pertenece (produccion, ventas, sistema, etc.)');
            $table->timestamps();

            // Índices
            $table->index('clave');
            $table->index('grupo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_sistema');
    }
};
