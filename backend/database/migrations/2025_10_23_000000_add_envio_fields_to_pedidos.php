<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new columns and try to extend enum for tipo_entrega
        Schema::table('pedidos', function (Blueprint $table) {
            // Add columns
            $table->boolean('envio_por_pagar')->default(false)->after('estado_pago');
            $table->string('empresa_transporte')->nullable()->after('envio_por_pagar');
        });

        // Alter enum to include envio_nacional (MySQL/MariaDB)
        try {
            DB::statement("ALTER TABLE pedidos MODIFY tipo_entrega ENUM('delivery','recoger','envio_nacional') NOT NULL DEFAULT 'recoger'");
        } catch (\Exception $e) {
            // If the DB doesn't support MODIFY this way, log and continue. The app can still store envio info in the new columns.
            // We avoid failing migrations in case of stricter DB engines.
            // Optionally, you could implement a more robust enum migration per your DB.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['envio_por_pagar', 'empresa_transporte']);
        });

        // Attempt to revert enum (best-effort)
        try {
            DB::statement("ALTER TABLE pedidos MODIFY tipo_entrega ENUM('delivery','recoger') NOT NULL DEFAULT 'recoger'");
        } catch (\Exception $e) {
            // ignore
        }
    }
};
