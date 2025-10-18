<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class SyncRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza la columna users.role a partir del pivot role_user (admin>vendedor>panadero>cliente)';

    public function handle()
    {
        $this->info('Iniciando sincronización de roles...');
        $users = User::with('roles')->get();
        foreach ($users as $u) {
            $roles = $u->roles->pluck('name')->toArray();
            if (count($roles) > 0) {
                if (in_array('admin', $roles)) {
                    $u->role = 'admin';
                } elseif (in_array('vendedor', $roles)) {
                    $u->role = 'vendedor';
                } elseif (in_array('panadero', $roles)) {
                    $u->role = 'panadero';
                } else {
                    $u->role = 'cliente';
                }
                $u->saveQuietly();
            }
        }
        $this->info('Sincronización completa.');
        return 0;
    }
}
