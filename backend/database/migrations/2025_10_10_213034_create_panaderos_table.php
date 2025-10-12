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
        Schema::create('panaderos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('email', 150)->unique();
            $table->string('telefono', 20);
            $table->string('ci', 20)->unique();
            $table->text('direccion')->nullable();
            
            // Información laboral
            $table->date('fecha_ingreso');
            $table->enum('turno', ['mañana', 'tarde', 'noche', 'rotativo'])->default('mañana');
            $table->enum('especialidad', ['pan', 'reposteria', 'ambos'])->default('ambos');
            $table->decimal('salario_base', 10, 2);
            
            // Estadísticas de producción
            $table->integer('total_kilos_producidos')->default(0);
            $table->integer('total_unidades_producidas')->default(0);
            $table->date('ultima_produccion')->nullable();
            
            // Estado y observaciones
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('activo');
            $table->index('turno');
            $table->index('especialidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panaderos');
    }
};
