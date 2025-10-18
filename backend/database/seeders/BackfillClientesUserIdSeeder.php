<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackfillClientesUserIdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Backfilling clientes.user_id from users.email...');

        $rows = DB::table('clientes')
            ->leftJoin('users', 'clientes.email', '=', 'users.email')
            ->select('clientes.id as cliente_id', 'users.id as user_id', 'clientes.email')
            ->get();

        $count = 0;
        foreach ($rows as $r) {
            if ($r->user_id && DB::table('clientes')->where('id', $r->cliente_id)->whereNull('user_id')->exists()) {
                DB::table('clientes')->where('id', $r->cliente_id)->update(['user_id' => $r->user_id]);
                $count++;
            }
        }

        $this->command->info("Backfilled user_id for {$count} clientes");
    }
}
