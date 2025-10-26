<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * - Add `ci` column to users if missing
     * - Copy telefono/ci from panaderos -> users for rows with user_id
     * - Drop telefono and ci from panaderos when present
     */
    public function up(): void
    {
        // Add ci column to users if not exists
        if (! Schema::hasColumn('users', 'ci')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('ci', 50)->nullable()->after('phone');
            });
        }

        // Copy data from panaderos to users when linked by user_id
        if (Schema::hasTable('panaderos') && Schema::hasColumn('panaderos', 'user_id')) {
            // Use a join update on drivers that support it; fallback to subquery update for SQLite
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                DB::statement(<<<'SQL'
                    UPDATE users
                    SET phone = COALESCE(phone, (
                        SELECT p.telefono FROM panaderos p WHERE p.user_id = users.id LIMIT 1
                    )),
                        ci = COALESCE(ci, (
                        SELECT p.ci FROM panaderos p WHERE p.user_id = users.id LIMIT 1
                    ))
                    WHERE EXISTS (SELECT 1 FROM panaderos p WHERE p.user_id = users.id);
                SQL
                );
            } else {
                DB::statement(<<<'SQL'
                    UPDATE users u
                    JOIN panaderos p ON p.user_id = u.id
                    SET u.phone = COALESCE(u.phone, p.telefono),
                        u.ci = COALESCE(u.ci, p.ci)
                    WHERE p.user_id IS NOT NULL;
                SQL
                );
            }

            // Drop columns from panaderos if they exist
            $cols = Schema::getColumnListing('panaderos');
            $toDrop = [];
            if (in_array('telefono', $cols)) $toDrop[] = 'telefono';
            if (in_array('ci', $cols)) $toDrop[] = 'ci';

            if (!empty($toDrop)) {
                // Drop indexes before dropping columns (SQLite-safe approach)
                // Try raw DROP INDEX IF EXISTS first (works on SQLite and MySQL)
                if (in_array('ci', $cols)) {
                    try {
                        DB::statement('DROP INDEX IF EXISTS panaderos_ci_unique');
                    } catch (\Throwable $e) {
                        // Some DBs might not support IF EXISTS; try without it
                        try {
                            Schema::table('panaderos', function (Blueprint $table) {
                                $table->dropUnique('panaderos_ci_unique');
                            });
                        } catch (\Throwable $e2) {
                            // ignore if index doesn't exist
                        }
                    }
                }
                
                if (in_array('telefono', $cols)) {
                    try {
                        DB::statement('DROP INDEX IF EXISTS panaderos_telefono_unique');
                    } catch (\Throwable $e) {
                        try {
                            Schema::table('panaderos', function (Blueprint $table) {
                                $table->dropUnique('panaderos_telefono_unique');
                            });
                        } catch (\Throwable $e2) {
                            // ignore if index doesn't exist
                        }
                    }
                }

                // Now drop the columns
                Schema::table('panaderos', function (Blueprint $table) use ($toDrop) {
                    foreach ($toDrop as $col) {
                        if (Schema::hasColumn('panaderos', $col)) {
                            $table->dropColumn($col);
                        }
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     * Attempt to restore columns on panaderos (empty values) and drop ci from users.
     */
    public function down(): void
    {
        // Restore telefono and ci columns on panaderos if missing
        if (Schema::hasTable('panaderos')) {
            $cols = Schema::getColumnListing('panaderos');
            Schema::table('panaderos', function (Blueprint $table) use ($cols) {
                if (! in_array('telefono', $cols)) {
                    $table->string('telefono', 20)->nullable()->after('email');
                }
                if (! in_array('ci', $cols)) {
                    $table->string('ci', 20)->nullable()->unique()->after('telefono');
                }
            });

            // Try to copy back data from users -> panaderos when linked
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                DB::statement(<<<'SQL'
                    UPDATE panaderos
                    SET telefono = COALESCE(telefono, (
                        SELECT u.phone FROM users u WHERE u.id = panaderos.user_id LIMIT 1
                    )),
                        ci = COALESCE(ci, (
                        SELECT u.ci FROM users u WHERE u.id = panaderos.user_id LIMIT 1
                    ))
                    WHERE EXISTS (SELECT 1 FROM users u WHERE u.id = panaderos.user_id);
                SQL
                );
            } else {
                DB::statement(<<<'SQL'
                    UPDATE panaderos p
                    JOIN users u ON p.user_id = u.id
                    SET p.telefono = COALESCE(p.telefono, u.phone),
                        p.ci = COALESCE(p.ci, u.ci)
                    WHERE p.user_id IS NOT NULL;
                SQL
                );
            }
        }

        // Remove ci column from users if exists
        if (Schema::hasColumn('users', 'ci')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('ci');
            });
        }
    }
};
