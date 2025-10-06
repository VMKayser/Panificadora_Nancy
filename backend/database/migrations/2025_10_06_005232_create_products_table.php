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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            //estos son los campos para los productos 
            $table->foreignId('categorias_id')
            ->constrained()
            ->onDelete('cascade');
            
            $table->string('nombre',150);
            $table->string('url', 150)->unique();
            $table->text('descripcion')->nullable();
            $table->text('descripcion_corta')->nullable();

            $table->decimal('precio_minorista',10,2);
            $table->decimal('precio_mayorista',10,2)->nullable();
            $table->integer('cantidad_minima_mayoreo')->default(10);
        
            $table->boolean('es_de_temporada')->default(false);
            $table->boolean('esta_activo')->default(true);
            $table->boolean('requiere_tiempo_anticipacion')->default(false);
            $table->integer('tiempo_anticipacion')->nullable();
            $table->enum('unidad_tiempo', ['horas', 'dias', 'semanas'])->nullable();
            $table->boolean('limite_produccion')->default(false);


            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
