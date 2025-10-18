<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('empleado_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('empleado_tipo'); // 'panadero' | 'vendedor' | ...
            $table->unsignedBigInteger('empleado_id');
            $table->decimal('monto', 12, 2);
            $table->decimal('kilos_pagados', 10, 2)->nullable();
            $table->unsignedBigInteger('metodos_pago_id')->nullable();
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['empleado_tipo', 'empleado_id']);
            $table->foreign('metodos_pago_id')->references('id')->on('metodos_pago')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('empleado_pagos');
    }
};
