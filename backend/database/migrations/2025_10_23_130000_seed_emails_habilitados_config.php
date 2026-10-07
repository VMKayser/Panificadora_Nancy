<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class SeedEmailsHabilitadosConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * Insert a default configuration key 'emails_habilitados' = false
     */
    public function up()
    {
        DB::table('configuracion_sistema')->updateOrInsert(
            ['clave' => 'emails_habilitados'],
            ['valor' => 'false', 'tipo' => 'boolean', 'descripcion' => 'Habilitar envíos de correo desde la aplicación (excepto confirmación de cuenta)', 'grupo' => 'notificaciones', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::table('configuracion_sistema')->where('clave', 'emails_habilitados')->delete();
    }
}
