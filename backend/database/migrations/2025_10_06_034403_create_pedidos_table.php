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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pedido')->unique(); 
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); 
            

            $table->string('cliente_nombre');
            $table->string('cliente_apellido');
            $table->string('cliente_email');
            $table->string('cliente_telefono');

            $table->enum('tipo_entrega', ['delivery', 'recoger'])->default('recoger');
            $table->text('direccion_entrega')->nullable();
            $table->text('indicaciones_especiales')->nullable();
            

            $table->decimal('subtotal', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            

            $table->foreignId('metodos_pago_id')->constrained('metodos_pago');
            $table->string('codigo_promocional')->nullable();
            

            $table->enum('estado', ['pendiente', 'confirmado', 'en_preparacion', 'listo', 'entregado', 'cancelado'])->default('pendiente');
            $table->enum('estado_pago', ['pendiente', 'pagado', 'rechazado'])->default('pendiente');
            
            $table->text('qr_pago')->nullable(); 
            $table->string('referencia_pago')->nullable();
            

            $table->timestamp('fecha_entrega')->nullable();
            $table->timestamp('fecha_pago')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
