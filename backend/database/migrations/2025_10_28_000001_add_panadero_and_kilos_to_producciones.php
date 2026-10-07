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
        Schema::table('producciones', function (Blueprint $table) {
            if (!Schema::hasColumn('producciones', 'panadero_id')) {
                $table->foreignId('panadero_id')->nullable()->constrained('panaderos')->onDelete('set null');
            }
            if (!Schema::hasColumn('producciones', 'cantidad_kg')) {
                $table->decimal('cantidad_kg', 10, 3)->nullable()->default(null);
            }
            if (!Schema::hasColumn('producciones', 'cantidad_unidades')) {
                $table->decimal('cantidad_unidades', 10, 3)->nullable()->default(null);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producciones', function (Blueprint $table) {
            if (Schema::hasColumn('producciones', 'panadero_id')) {
                $table->dropConstrainedForeignId('panadero_id');
            }
            if (Schema::hasColumn('producciones', 'cantidad_kg')) {
                $table->dropColumn('cantidad_kg');
            }
            if (Schema::hasColumn('producciones', 'cantidad_unidades')) {
                $table->dropColumn('cantidad_unidades');
            }
        });
    }
};
