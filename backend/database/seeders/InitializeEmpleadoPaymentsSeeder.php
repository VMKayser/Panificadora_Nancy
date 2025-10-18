<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InitializeEmpleadoPaymentsSeeder extends Seeder
{
    public function run()
    {
        // Ensure vendedores have comision_acumulada column initialized
        if (DB::getSchemaBuilder()->hasColumn('vendedores', 'comision_acumulada')) {
            DB::table('vendedores')->whereNull('comision_acumulada')->update(['comision_acumulada' => 0]);
        }

        // Ensure empleado_pagos has tipo fields set to defaults when null
        if (DB::getSchemaBuilder()->hasColumn('empleado_pagos', 'tipo_pago')) {
            DB::table('empleado_pagos')->whereNull('tipo_pago')->update(['tipo_pago' => 'otro']);
        }
        if (DB::getSchemaBuilder()->hasColumn('empleado_pagos', 'es_sueldo_fijo')) {
            DB::table('empleado_pagos')->whereNull('es_sueldo_fijo')->update(['es_sueldo_fijo' => false]);
        }
    }
}
