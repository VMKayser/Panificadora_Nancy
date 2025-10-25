<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agregar solo índices que sabemos que existen y son seguros
     */
    public function up(): void
    {
        // Índices en tabla pedidos (solo nuevos)
        Schema::table('pedidos', function (Blueprint $table) {
            if (!$this->hasIndex('pedidos', 'pedidos_created_at_index')) {
                $table->index('created_at', 'pedidos_created_at_index');
            }
        });

        // Índices en tabla productos
        Schema::table('productos', function (Blueprint $table) {
            if (!$this->hasIndex('productos', 'productos_esta_activo_index')) {
                $table->index('esta_activo', 'productos_esta_activo_index');
            }
        });

        // Índices en tabla clientes
        Schema::table('clientes', function (Blueprint $table) {
            if (!$this->hasIndex('clientes', 'clientes_email_index')) {
                $table->index('email', 'clientes_email_index');
            }
            if (!$this->hasIndex('clientes', 'clientes_activo_index')) {
                $table->index('activo', 'clientes_activo_index');
            }
            if (!$this->hasIndex('clientes', 'clientes_fecha_ultimo_pedido_index')) {
                $table->index('fecha_ultimo_pedido', 'clientes_fecha_ultimo_pedido_index');
            }
        });

        // Índices en tabla users
        Schema::table('users', function (Blueprint $table) {
            if (!$this->hasIndex('users', 'users_is_active_index')) {
                $table->index('is_active', 'users_is_active_index');
            }
            if (!$this->hasIndex('users', 'users_email_verified_at_index')) {
                $table->index('email_verified_at', 'users_email_verified_at_index');
            }
        });
    }

    /**
     * Helper para verificar si existe un índice
     */
    private function hasIndex($table, $indexName)
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            // SQLite: use PRAGMA index_list
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $idx) {
                // result object may have 'name' property
                if ((isset($idx->name) && $idx->name === $indexName) || (isset($idx->idx_name) && $idx->idx_name === $indexName)) {
                    return true;
                }
            }
            return false;
        }

        // Default: MySQL-compatible
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");
        return !empty($indexes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices creados (solo si existen)
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_is_active_index');
            $table->dropIndex('users_email_verified_at_index');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex('clientes_email_index');
            $table->dropIndex('clientes_activo_index');
            $table->dropIndex('clientes_fecha_ultimo_pedido_index');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('productos_esta_activo_index');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex('pedidos_created_at_index');
        });
    }
};
