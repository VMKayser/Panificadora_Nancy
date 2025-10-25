<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            ['clave' => 'whatsapp_provider', 'valor' => 'facebook', 'tipo' => 'texto', 'descripcion' => 'Proveedor para integración WhatsApp (facebook|otro)', 'grupo' => 'whatsapp'],
            ['clave' => 'whatsapp_api_token', 'valor' => '', 'tipo' => 'texto', 'descripcion' => 'Token para WhatsApp Cloud API o proveedor', 'grupo' => 'whatsapp'],
            ['clave' => 'whatsapp_phone_number_id', 'valor' => '', 'tipo' => 'texto', 'descripcion' => 'Phone Number ID para WhatsApp Cloud API', 'grupo' => 'whatsapp'],
            ['clave' => 'whatsapp_api_url', 'valor' => '', 'tipo' => 'texto', 'descripcion' => 'URL del endpoint para proveedor alternativo', 'grupo' => 'whatsapp'],
        ];

        foreach ($defaults as $cfg) {
            $exists = DB::table('configuracion_sistema')->where('clave', $cfg['clave'])->first();
            if (!$exists) {
                DB::table('configuracion_sistema')->insert(array_merge($cfg, ['created_at' => now(), 'updated_at' => now()]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('configuracion_sistema')->whereIn('clave', [
            'whatsapp_provider', 'whatsapp_api_token', 'whatsapp_phone_number_id', 'whatsapp_api_url'
        ])->delete();
    }
};
