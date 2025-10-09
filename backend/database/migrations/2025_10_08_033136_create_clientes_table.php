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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('email', 150)->unique();
            $table->string('telefono', 20);
            $table->text('direccion')->nullable();
            $table->string('ci', 20)->nullable();
            $table->enum('tipo_cliente', ['regular', 'mayorista', 'vip'])->default('regular');
            $table->integer('total_pedidos')->default(0);
            $table->decimal('total_gastado', 10, 2)->default(0);
            $table->date('fecha_ultimo_pedido')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
