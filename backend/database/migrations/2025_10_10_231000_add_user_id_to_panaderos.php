<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar user_id y codigo_panadero a tabla panaderos
     */
    public function up(): void
    {
        Schema::table('panaderos', function (Blueprint $table) {
            // Agregar relación con users
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            
            // Agregar código de panadero
            $table->string('codigo_panadero')->nullable()->unique()->after('user_id');
            
            // Hacer campos opcionales ya que ahora vienen del user
            $table->string('nombre', 100)->nullable()->change();
            $table->string('apellido', 100)->nullable()->change();
            $table->string('email', 150)->nullable()->change();
            
            // Índice para búsquedas
            $table->index('codigo_panadero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('panaderos', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['codigo_panadero']);
            $table->dropColumn(['user_id', 'codigo_panadero']);
        });
    }
};
