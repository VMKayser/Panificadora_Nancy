<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('empleado_pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('empleado_pagos', 'tipo_pago')) {
                $table->string('tipo_pago')->nullable()->after('empleado_id');
            }
            if (!Schema::hasColumn('empleado_pagos', 'es_sueldo_fijo')) {
                $table->boolean('es_sueldo_fijo')->nullable()->after('tipo_pago');
            }
        });
    }

    public function down()
    {
        Schema::table('empleado_pagos', function (Blueprint $table) {
            if (Schema::hasColumn('empleado_pagos', 'tipo_pago')) {
                $table->dropColumn('tipo_pago');
            }
            if (Schema::hasColumn('empleado_pagos', 'es_sueldo_fijo')) {
                $table->dropColumn('es_sueldo_fijo');
            }
        });
    }
};
