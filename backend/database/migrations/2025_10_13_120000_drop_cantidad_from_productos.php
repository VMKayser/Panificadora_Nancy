<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Before dropping the column, ensure inventory exists for all products
        // Create missing inventory rows using current productos.cantidad as initial stock
        // Note: avoid wrapping schema operations in DB transactions because some drivers do not support it.
        // Insert inventory rows for products that lack them, using productos.cantidad as seed value.
        try {
            // Use CURRENT_TIMESTAMP for SQLite compatibility instead of NOW()
            $driver = DB::connection()->getDriverName();
            $timestamp = $driver === 'sqlite' ? "datetime('now')" : 'NOW()';
            
            DB::statement(
                "INSERT INTO inventario_productos_finales (producto_id, stock_actual, stock_minimo, costo_promedio, created_at, updated_at)
                 SELECT p.id, COALESCE(p.cantidad, 0), 0, p.precio_minorista, {$timestamp}, {$timestamp}
                 FROM productos p
                 LEFT JOIN inventario_productos_finales i ON i.producto_id = p.id
                 WHERE i.producto_id IS NULL"
            );
        } catch (\Throwable $e) {
            // If insert fails (e.g., table doesn't exist yet), log and continue to allow dropping the column if present.
            // We avoid failing the migration here to keep it idempotent across environments.
            // Use the DB facade to log via error_log to ensure visibility in container logs.
            error_log('Warning: could not insert missing inventory rows: ' . $e->getMessage());
        }

        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'cantidad')) {
                $table->dropColumn('cantidad');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Avoid wrapping schema changes in a DB transaction to keep compatibility with MySQL and
        // other drivers that may not support transactional DDL. Make operations tolerant so the
        // migration can be applied idempotently across environments.
        try {
            Schema::table('productos', function (Blueprint $table) {
                if (!Schema::hasColumn('productos', 'cantidad')) {
                    $table->decimal('cantidad', 8, 2)->nullable()->after('unidad_medida');
                }
            });

            // Populate productos.cantidad from inventory stock_actual where available
            try {
                DB::statement(
                    "UPDATE productos p
                     JOIN inventario_productos_finales i ON i.producto_id = p.id
                     SET p.cantidad = i.stock_actual"
                );
            } catch (\Throwable $inner) {
                // If the update fails (e.g., missing table/columns), log and continue to avoid
                // making the rollback non-idempotent.
                error_log('Warning: could not populate productos.cantidad from inventario: ' . $inner->getMessage());
            }
        } catch (\Throwable $e) {
            throw $e;
        }
    }
};
