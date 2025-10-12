<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('panaderos', function (Blueprint $table) {
            if (!Schema::hasColumn('panaderos', 'salario_por_kilo')) {
                $table->decimal('salario_por_kilo', 10, 2)->default(0)->after('salario_base');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('panaderos', function (Blueprint $table) {
            if (Schema::hasColumn('panaderos', 'salario_por_kilo')) {
                $table->dropColumn('salario_por_kilo');
            }
        });
    }
};
