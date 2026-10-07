<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El pedido web solo pide nombre y celular: el correo pasa a ser opcional
     * en el pedido y en la ficha del cliente.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('cliente_email')->nullable()->change();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('email', 150)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('pedidos')->whereNull('cliente_email')->update(['cliente_email' => '']);
        // clientes.email es único: cada cliente sin correo recibe uno de relleno distinto
        DB::table('clientes')->whereNull('email')->orderBy('id')->each(function ($cliente) {
            DB::table('clientes')->where('id', $cliente->id)->update(['email' => "sin-correo-{$cliente->id}@invalid"]);
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('cliente_email')->nullable(false)->change();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('email', 150)->nullable(false)->change();
        });
    }
};
