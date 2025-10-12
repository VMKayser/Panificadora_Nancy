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
        Schema::create('vendedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('codigo_vendedor')->unique()->nullable(); // Código único de vendedor
            $table->decimal('comision_porcentaje', 5, 2)->default(0); // Comisión por venta (%)
            $table->decimal('descuento_maximo_bs', 10, 2)->default(0); // Descuento máximo que puede otorgar en Bs.
            $table->boolean('puede_dar_descuentos')->default(true);
            $table->boolean('puede_cancelar_ventas')->default(false);
            $table->string('turno')->nullable(); // mañana, tarde, noche
            $table->date('fecha_ingreso')->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'suspendido'])->default('activo');
            $table->text('observaciones')->nullable();
            
            // Estadísticas del vendedor
            $table->integer('ventas_realizadas')->default(0);
            $table->decimal('total_vendido', 12, 2)->default(0);
            $table->decimal('descuentos_otorgados', 10, 2)->default(0);
            
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('codigo_vendedor');
            $table->index('estado');
            $table->index('turno');
        });

        // Agregar campos relacionados a pedidos
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('vendedor_id')->nullable()->after('user_id')->constrained('vendedores')->onDelete('set null');
            $table->decimal('descuento_bs', 10, 2)->default(0)->after('total'); // Descuento en bolivianos
            $table->text('motivo_descuento')->nullable()->after('descuento_bs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['vendedor_id']);
            $table->dropColumn(['vendedor_id', 'descuento_bs', 'motivo_descuento']);
        });
        
        Schema::dropIfExists('vendedores');
    }
};
