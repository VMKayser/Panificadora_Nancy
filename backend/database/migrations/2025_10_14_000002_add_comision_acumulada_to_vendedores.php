<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vendedores', function (Blueprint $table) {
            if (!Schema::hasColumn('vendedores', 'comision_acumulada')) {
                $table->decimal('comision_acumulada', 12, 2)->default(0)->after('total_vendido');
            }
        });
    }

    public function down()
    {
        Schema::table('vendedores', function (Blueprint $table) {
            if (Schema::hasColumn('vendedores', 'comision_acumulada')) {
                $table->dropColumn('comision_acumulada');
            }
        });
    }
};
