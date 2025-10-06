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
        Schema::create('imagen_producto', function (Blueprint $table) {
            $table->id();
            //campos para la imagen de producto 
            $table->foreignId('producto_id')
            ->constrained()
            ->onDelete('cascade');

            $table->string('url_imagen');
            $table->string('texto_alternativo')->nullable();
            $table->boolean('es_imagen_principal')->default(false);
            $table->integer('order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagen_producto');
    }
};
