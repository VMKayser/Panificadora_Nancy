<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedDashboardTtlConfig extends Migration
{
    public function up()
    {
        DB::table('configuracion_sistema')->updateOrInsert(
            ['clave' => 'dashboard_ttl_seconds'],
            ['valor' => '60', 'tipo' => 'numero', 'descripcion' => 'TTL en segundos para cache del dashboard de inventario', 'grupo' => 'cache', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down()
    {
        DB::table('configuracion_sistema')->where('clave', 'dashboard_ttl_seconds')->delete();
    }
}
