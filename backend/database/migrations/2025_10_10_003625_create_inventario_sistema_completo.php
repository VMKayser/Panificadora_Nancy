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
        // ============================================
        // 1. MATERIAS PRIMAS (Ingredientes)
        // ============================================
        Schema::create('materias_primas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->string('codigo_interno', 50)->unique()->nullable();
            $table->enum('unidad_medida', ['kg', 'g', 'L', 'ml', 'unidades']);
            $table->decimal('stock_actual', 10, 3)->default(0);
            $table->decimal('stock_minimo', 10, 3)->default(0);
            $table->decimal('costo_unitario', 10, 2)->default(0);
            $table->string('proveedor', 200)->nullable();
            $table->date('ultima_compra')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices para mejorar performance
            $table->index('nombre');
            $table->index('activo');
            $table->index(['activo', 'stock_actual']); // Para alertas
        });

        // ============================================
        // 2. RECETAS (Fórmulas de productos)
        // ============================================
        Schema::create('recetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->string('nombre_receta', 200);
            $table->text('descripcion')->nullable();
            $table->decimal('rendimiento', 10, 3); // Cuántas unidades produce
            $table->enum('unidad_rendimiento', ['unidades', 'kg', 'docenas'])->default('unidades');
            $table->decimal('costo_total_calculado', 10, 2)->default(0);
            $table->decimal('costo_unitario_calculado', 10, 2)->default(0);
            $table->boolean('activa')->default(true);
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['producto_id', 'activa']);
            $table->index('version');
        });

        // ============================================
        // 3. INGREDIENTES DE RECETA (Detalle)
        // ============================================
        Schema::create('ingredientes_receta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receta_id')->constrained('recetas')->onDelete('cascade');
            $table->foreignId('materia_prima_id')->constrained('materias_primas')->onDelete('cascade');
            $table->decimal('cantidad', 10, 3);
            $table->enum('unidad', ['kg', 'g', 'L', 'ml', 'unidades']);
            $table->decimal('costo_calculado', 10, 2)->default(0);
            $table->integer('orden')->default(0);
            $table->timestamps();

            // Índices y constraints
            $table->index(['receta_id', 'orden']);
            $table->unique(['receta_id', 'materia_prima_id'], 'unique_ingrediente_receta');
        });

        // ============================================
        // 4. PRODUCCIONES (Registro diario)
        // ============================================
        Schema::create('producciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('receta_id')->constrained('recetas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('fecha_produccion');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->decimal('cantidad_producida', 10, 3);
            $table->enum('unidad', ['unidades', 'kg', 'docenas'])->default('unidades');
            
            // Ajuste manual de harina
            $table->decimal('harina_real_usada', 10, 3)->nullable();
            $table->decimal('harina_teorica', 10, 3)->nullable();
            $table->decimal('diferencia_harina', 10, 3)->nullable();
            $table->enum('tipo_diferencia', ['normal', 'merma', 'exceso'])->nullable();
            
            $table->decimal('costo_produccion', 10, 2)->default(0);
            $table->decimal('costo_unitario', 10, 2)->default(0);
            $table->enum('estado', ['en_proceso', 'completado', 'cancelado'])->default('en_proceso');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('fecha_produccion');
            $table->index(['user_id', 'fecha_produccion']);
            $table->index('estado');
        });

        // ============================================
        // 5. MOVIMIENTOS MATERIA PRIMA (Historial)
        // ============================================
        Schema::create('movimientos_materia_prima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_prima_id')->constrained('materias_primas')->onDelete('cascade');
            $table->enum('tipo_movimiento', [
                'entrada_compra',
                'entrada_devolucion',
                'entrada_ajuste',
                'salida_produccion',
                'salida_merma',
                'salida_ajuste'
            ]);
            $table->decimal('cantidad', 10, 3);
            $table->decimal('costo_unitario', 10, 2)->nullable();
            $table->decimal('stock_anterior', 10, 3);
            $table->decimal('stock_nuevo', 10, 3);
            $table->foreignId('produccion_id')->nullable()->constrained('producciones')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('observaciones')->nullable();
            $table->string('numero_factura', 100)->nullable();
            $table->timestamps();

            // Índices
            $table->index('materia_prima_id');
            $table->index('tipo_movimiento');
            $table->index('created_at');
            $table->index(['materia_prima_id', 'created_at']);
        });

        // ============================================
        // 6. INVENTARIO PRODUCTOS FINALES
        // ============================================
        Schema::create('inventario_productos_finales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->unique()->constrained('productos')->onDelete('cascade');
            $table->decimal('stock_actual', 10, 3)->default(0);
            $table->decimal('stock_minimo', 10, 3)->default(0);
            $table->date('fecha_elaboracion')->nullable();
            $table->integer('dias_vida_util')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('costo_promedio', 10, 2)->default(0);
            $table->timestamps();

            // Índices
            $table->index('stock_actual');
            $table->index('fecha_vencimiento');
        });

        // ============================================
        // 7. MOVIMIENTOS PRODUCTOS FINALES
        // ============================================
        Schema::create('movimientos_productos_finales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->enum('tipo_movimiento', [
                'entrada_produccion',
                'salida_venta',
                'salida_merma',
                'salida_degustacion',
                'ajuste'
            ]);
            $table->decimal('cantidad', 10, 3);
            $table->decimal('stock_anterior', 10, 3);
            $table->decimal('stock_nuevo', 10, 3);
            $table->foreignId('produccion_id')->nullable()->constrained('producciones')->onDelete('set null');
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices
            $table->index('producto_id');
            $table->index('tipo_movimiento');
            $table->index('created_at');
            $table->index(['producto_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_productos_finales');
        Schema::dropIfExists('inventario_productos_finales');
        Schema::dropIfExists('movimientos_materia_prima');
        Schema::dropIfExists('producciones');
        Schema::dropIfExists('ingredientes_receta');
        Schema::dropIfExists('recetas');
        Schema::dropIfExists('materias_primas');
    }
};
