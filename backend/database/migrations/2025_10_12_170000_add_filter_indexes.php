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
        // Clientes: ci, telefono, activo, tipo_cliente
        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                if (! $this->indexExists('clientes', 'idx_clientes_ci') && Schema::hasColumn('clientes', 'ci')) {
                    $table->index('ci', 'idx_clientes_ci');
                }
                if (! $this->indexExists('clientes', 'idx_clientes_telefono') && Schema::hasColumn('clientes', 'telefono')) {
                    $table->index('telefono', 'idx_clientes_telefono');
                }
                if (! $this->indexExists('clientes', 'idx_clientes_activo') && Schema::hasColumn('clientes', 'activo')) {
                    $table->index('activo', 'idx_clientes_activo');
                }
                if (! $this->indexExists('clientes', 'idx_clientes_tipo_cliente') && Schema::hasColumn('clientes', 'tipo_cliente')) {
                    $table->index('tipo_cliente', 'idx_clientes_tipo_cliente');
                }
            });
        }

        // Panaderos: ci, turno, especialidad
        if (Schema::hasTable('panaderos')) {
            Schema::table('panaderos', function (Blueprint $table) {
                if (! $this->indexExists('panaderos', 'idx_panaderos_ci') && Schema::hasColumn('panaderos', 'ci')) {
                    $table->index('ci', 'idx_panaderos_ci');
                }
                if (! $this->indexExists('panaderos', 'idx_panaderos_turno') && Schema::hasColumn('panaderos', 'turno')) {
                    $table->index('turno', 'idx_panaderos_turno');
                }
                if (! $this->indexExists('panaderos', 'idx_panaderos_especialidad') && Schema::hasColumn('panaderos', 'especialidad')) {
                    $table->index('especialidad', 'idx_panaderos_especialidad');
                }
            });
        }

        // Vendedores: estado, turno
        if (Schema::hasTable('vendedores')) {
            Schema::table('vendedores', function (Blueprint $table) {
                if (! $this->indexExists('vendedores', 'idx_vendedores_estado') && Schema::hasColumn('vendedores', 'estado')) {
                    $table->index('estado', 'idx_vendedores_estado');
                }
                if (! $this->indexExists('vendedores', 'idx_vendedores_turno') && Schema::hasColumn('vendedores', 'turno')) {
                    $table->index('turno', 'idx_vendedores_turno');
                }
            });
        }

        // Users: ci, phone
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! $this->indexExists('users', 'idx_users_ci') && Schema::hasColumn('users', 'ci')) {
                    $table->index('ci', 'idx_users_ci');
                }
                if (! $this->indexExists('users', 'idx_users_phone') && Schema::hasColumn('users', 'phone')) {
                    $table->index('phone', 'idx_users_phone');
                }
            });
        }

        // role_user pivot: user_id + role_id composite index
        if (Schema::hasTable('role_user')) {
            Schema::table('role_user', function (Blueprint $table) {
                if (! $this->indexExists('role_user', 'idx_role_user_user_role')) {
                    $table->index(['user_id','role_id'], 'idx_role_user_user_role');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                if ($this->indexExists('clientes', 'idx_clientes_ci')) $table->dropIndex('idx_clientes_ci');
                if ($this->indexExists('clientes', 'idx_clientes_telefono')) $table->dropIndex('idx_clientes_telefono');
                if ($this->indexExists('clientes', 'idx_clientes_activo')) $table->dropIndex('idx_clientes_activo');
                if ($this->indexExists('clientes', 'idx_clientes_tipo_cliente')) $table->dropIndex('idx_clientes_tipo_cliente');
            });
        }

        if (Schema::hasTable('panaderos')) {
            Schema::table('panaderos', function (Blueprint $table) {
                if ($this->indexExists('panaderos', 'idx_panaderos_ci')) $table->dropIndex('idx_panaderos_ci');
                if ($this->indexExists('panaderos', 'idx_panaderos_turno')) $table->dropIndex('idx_panaderos_turno');
                if ($this->indexExists('panaderos', 'idx_panaderos_especialidad')) $table->dropIndex('idx_panaderos_especialidad');
            });
        }

        if (Schema::hasTable('vendedores')) {
            Schema::table('vendedores', function (Blueprint $table) {
                if ($this->indexExists('vendedores', 'idx_vendedores_estado')) $table->dropIndex('idx_vendedores_estado');
                if ($this->indexExists('vendedores', 'idx_vendedores_turno')) $table->dropIndex('idx_vendedores_turno');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->indexExists('users', 'idx_users_ci')) $table->dropIndex('idx_users_ci');
                if ($this->indexExists('users', 'idx_users_phone')) $table->dropIndex('idx_users_phone');
            });
        }

        if (Schema::hasTable('role_user')) {
            Schema::table('role_user', function (Blueprint $table) {
                if ($this->indexExists('role_user', 'idx_role_user_user_role')) $table->dropIndex('idx_role_user_user_role');
            });
        }
    }

    /**
     * Helper to check index existence (SQLite/MySQL compatible)
     * Uses driver-specific queries since information_schema is not available in SQLite
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
