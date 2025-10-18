<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('empleado_pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('empleado_pagos', 'empleadoable_type')) {
                $table->string('empleadoable_type')->nullable()->after('empleado_id');
            }
            if (!Schema::hasColumn('empleado_pagos', 'empleadoable_id')) {
                $table->unsignedBigInteger('empleadoable_id')->nullable()->after('empleadoable_type');
            }
        });

        // Copy data from empleado_tipo/empleado_id into empleadoable_* using simple mapping
        // Note: do not require exact class names in DB migration; map common types
        $map = [
            'panadero' => '\\App\\Models\\Panadero',
            'vendedor' => '\\App\\Models\\Vendedor',
        ];

        foreach ($map as $tipo => $class) {
            // Use DB direct update
            \Illuminate\Support\Facades\DB::table('empleado_pagos')
                ->where('empleado_tipo', $tipo)
                ->update(['empleadoable_type' => $class, 'empleadoable_id' => \Illuminate\Support\Facades\DB::raw('empleado_id')]);
        }
    }

    public function down()
    {
        Schema::table('empleado_pagos', function (Blueprint $table) {
            if (Schema::hasColumn('empleado_pagos', 'empleadoable_id')) {
                $table->dropColumn('empleadoable_id');
            }
            if (Schema::hasColumn('empleado_pagos', 'empleadoable_type')) {
                $table->dropColumn('empleadoable_type');
            }
        });
    }
};
