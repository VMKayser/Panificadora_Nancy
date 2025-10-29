<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pedidos: estado y fecha_entrega
        if (Schema::hasTable('pedidos')) {
            Schema::table('pedidos', function (Blueprint $table) {
                if (! $this->indexExists('pedidos','idx_pedidos_estado')) {
                    $table->index('estado', 'idx_pedidos_estado');
                }
                if (! $this->indexExists('pedidos','idx_pedidos_fecha_entrega')) {
                    $table->index('fecha_entrega', 'idx_pedidos_fecha_entrega');
                }
            });
        }

        // Productos: categorias_id, esta_activo
        if (Schema::hasTable('productos')) {
            Schema::table('productos', function (Blueprint $table) {
                if (! $this->indexExists('productos','idx_productos_categoria')) {
                    $table->index('categorias_id', 'idx_productos_categoria');
                }
                if (! $this->indexExists('productos','idx_productos_esta_activo')) {
                    $table->index('esta_activo', 'idx_productos_esta_activo');
                }
            });
        }

        // Panaderos/Vendedores: user_id
        if (Schema::hasTable('panaderos')) {
            Schema::table('panaderos', function (Blueprint $table) {
                if (! $this->indexExists('panaderos','idx_panaderos_user')) {
                    $table->index('user_id', 'idx_panaderos_user');
                }
            });
        }

        if (Schema::hasTable('vendedores')) {
            Schema::table('vendedores', function (Blueprint $table) {
                if (! $this->indexExists('vendedores','idx_vendedores_user')) {
                    $table->index('user_id', 'idx_vendedores_user');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pedidos')) {
            Schema::table('pedidos', function (Blueprint $table) {
                if ($this->indexExists('pedidos','idx_pedidos_estado')) {
                    $table->dropIndex('idx_pedidos_estado');
                }
                if ($this->indexExists('pedidos','idx_pedidos_fecha_entrega')) {
                    $table->dropIndex('idx_pedidos_fecha_entrega');
                }
            });
        }

        if (Schema::hasTable('productos')) {
            Schema::table('productos', function (Blueprint $table) {
                if ($this->indexExists('productos','idx_productos_categoria')) {
                    $table->dropIndex('idx_productos_categoria');
                }
                if ($this->indexExists('productos','idx_productos_esta_activo')) {
                    $table->dropIndex('idx_productos_esta_activo');
                }
            });
        }

        if (Schema::hasTable('panaderos')) {
            Schema::table('panaderos', function (Blueprint $table) {
                if ($this->indexExists('panaderos','idx_panaderos_user')) {
                    $table->dropIndex('idx_panaderos_user');
                }
            });
        }

        if (Schema::hasTable('vendedores')) {
            Schema::table('vendedores', function (Blueprint $table) {
                if ($this->indexExists('vendedores','idx_vendedores_user')) {
                    $table->dropIndex('idx_vendedores_user');
                }
            });
        }
    }

    /**
     * Helper to check index existence (SQLite/MySQL compatible)
     * Uses try/catch approach since information_schema is not available in SQLite
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $driver = Schema::getConnection()->getDriverName();
            
            if ($driver === 'sqlite') {
                // SQLite: query sqlite_master table
                $result = Schema::getConnection()->selectOne(
                    "SELECT COUNT(1) as cnt FROM sqlite_master WHERE type = 'index' AND name = ?",
                    [$indexName]
                );
                return isset($result->cnt) && $result->cnt > 0;
            } else {
                // MySQL/PostgreSQL: use information_schema
                $db = Schema::getConnection()->getDatabaseName();
                $result = Schema::getConnection()->selectOne(
                    "SELECT COUNT(1) as cnt FROM information_schema.STATISTICS WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$db, $table, $indexName]
                );
                return isset($result->cnt) && $result->cnt > 0;
            }
        } catch (\Throwable $e) {
            // If detection fails, assume index doesn't exist
            return false;
        }
    }
};
