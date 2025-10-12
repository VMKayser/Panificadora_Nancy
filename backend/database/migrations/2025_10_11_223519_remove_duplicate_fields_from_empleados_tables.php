<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Elimina campos duplicados de panaderos y vendedores que ya existen en users
     * NOTA: Esta migración es opcional si las tablas ya fueron creadas correctamente
     */
    public function up(): void
    {
        // Verificar y eliminar campos de panaderos
        $panaderosCols = Schema::getColumnListing('panaderos');
        if (in_array('nombre', $panaderosCols) || in_array('apellido', $panaderosCols) || in_array('email', $panaderosCols)) {
            Schema::table('panaderos', function (Blueprint $table) use ($panaderosCols) {
                $columnsToDrop = [];
                if (in_array('nombre', $panaderosCols)) $columnsToDrop[] = 'nombre';
                if (in_array('apellido', $panaderosCols)) $columnsToDrop[] = 'apellido';
                if (in_array('email', $panaderosCols)) $columnsToDrop[] = 'email';
                
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }

        // Verificar y eliminar campos de vendedores
        $vendedoresCols = Schema::getColumnListing('vendedores');
        if (in_array('nombre', $vendedoresCols) || in_array('apellido', $vendedoresCols) || in_array('email', $vendedoresCols)) {
            Schema::table('vendedores', function (Blueprint $table) use ($vendedoresCols) {
                $columnsToDrop = [];
                if (in_array('nombre', $vendedoresCols)) $columnsToDrop[] = 'nombre';
                if (in_array('apellido', $vendedoresCols)) $columnsToDrop[] = 'apellido';
                if (in_array('email', $vendedoresCols)) $columnsToDrop[] = 'email';
                
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     * Restaura los campos en caso de rollback
     */
    public function down(): void
    {
        // Restaurar campos en panaderos
        Schema::table('panaderos', function (Blueprint $table) {
            $table->string('nombre', 100)->after('id');
            $table->string('apellido', 100)->after('nombre');
            $table->string('email', 150)->unique()->after('apellido');
        });

        // Restaurar campos en vendedores
        Schema::table('vendedores', function (Blueprint $table) {
            $table->string('nombre', 100)->after('id');
            $table->string('apellido', 100)->after('nombre');
            $table->string('email', 150)->unique()->after('apellido');
        });
    }
};
