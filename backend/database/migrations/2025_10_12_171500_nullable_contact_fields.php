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
        // Make telefono/ci nullable where present to avoid insertion errors
        if (Schema::hasTable('panaderos')) {
            Schema::table('panaderos', function (Blueprint $table) {
                if (Schema::hasColumn('panaderos', 'telefono')) {
                    $table->string('telefono', 20)->nullable()->change();
                }
                if (Schema::hasColumn('panaderos', 'ci')) {
                    // keep unique if present but allow nulls
                    $table->string('ci', 20)->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('vendedores')) {
            Schema::table('vendedores', function (Blueprint $table) {
                if (Schema::hasColumn('vendedores', 'telefono')) {
                    $table->string('telefono', 20)->nullable()->change();
                }
                if (Schema::hasColumn('vendedores', 'ci')) {
                    $table->string('ci', 20)->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                if (Schema::hasColumn('clientes', 'telefono')) {
                    $table->string('telefono', 20)->nullable()->change();
                }
                if (Schema::hasColumn('clientes', 'ci')) {
                    $table->string('ci', 20)->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'phone')) {
                    $table->string('phone', 50)->nullable()->change();
                }
                if (Schema::hasColumn('users', 'ci')) {
                    $table->string('ci', 50)->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert nullable to not-null where possible (attempt only if column exists)
        if (Schema::hasTable('panaderos')) {
            Schema::table('panaderos', function (Blueprint $table) {
                if (Schema::hasColumn('panaderos', 'telefono')) {
                    $table->string('telefono', 20)->nullable(false)->change();
                }
                if (Schema::hasColumn('panaderos', 'ci')) {
                    $table->string('ci', 20)->nullable(false)->change();
                }
            });
        }

        if (Schema::hasTable('vendedores')) {
            Schema::table('vendedores', function (Blueprint $table) {
                if (Schema::hasColumn('vendedores', 'telefono')) {
                    $table->string('telefono', 20)->nullable(false)->change();
                }
                if (Schema::hasColumn('vendedores', 'ci')) {
                    $table->string('ci', 20)->nullable(false)->change();
                }
            });
        }

        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                if (Schema::hasColumn('clientes', 'telefono')) {
                    $table->string('telefono', 20)->nullable(false)->change();
                }
                if (Schema::hasColumn('clientes', 'ci')) {
                    $table->string('ci', 20)->nullable(false)->change();
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'phone')) {
                    $table->string('phone', 50)->nullable(false)->change();
                }
                if (Schema::hasColumn('users', 'ci')) {
                    $table->string('ci', 50)->nullable(false)->change();
                }
            });
        }
    }
};
