<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\User;
use App\Models\Panadero;
use App\Models\Vendedor;

class FixRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder is idempotent: it can be run multiple times without
     * duplicating data. It ensures required roles exist, assigns pivot
     * entries for users that have role-specific rows, and synchronizes
     * the `users.role` column to match pivot membership.
     */
    public function run()
    {
        DB::transaction(function () {
            // Ensure core roles exist
            $roles = ['admin', 'vendedor', 'cliente', 'panadero'];
            $roleIds = [];
            foreach ($roles as $name) {
                $role = Role::firstOrCreate(['name' => $name], ['display_name' => ucfirst($name)]);
                $roleIds[$name] = $role->id;
            }

            // Assign pivot role_user for panaderos
            $panaderos = Panadero::with('user')->get();
            foreach ($panaderos as $p) {
                if ($p->user) {
                    $user = $p->user;
                    // sync add panadero role without removing other roles
                    if (! $user->roles->contains('name', 'panadero')) {
                        $user->roles()->attach($roleIds['panadero']);
                        // keep users.role in sync preferring admin > panadero > vendedor > cliente
                    }
                }
            }

            // Assign pivot role_user for vendedores
            $vendedores = Vendedor::with('user')->get();
            foreach ($vendedores as $v) {
                if ($v->user) {
                    $user = $v->user;
                    if (! $user->roles->contains('name', 'vendedor')) {
                        $user->roles()->attach($roleIds['vendedor']);
                    }
                }
            }

            // Ensure users that are clearly clientes have the cliente pivot
            // Here we assume that users without panadero/vendedor roles are clientes
            $allUsers = User::with('roles')->get();
            foreach ($allUsers as $user) {
                $has_pan = $user->roles->contains('name', 'panadero');
                $has_ven = $user->roles->contains('name', 'vendedor');
                $has_admin = $user->roles->contains('name', 'admin');

                if (! $has_pan && ! $has_ven && ! $has_admin) {
                    if (! $user->roles->contains('name', 'cliente')) {
                        $user->roles()->attach($roleIds['cliente']);
                    }
                }

                // Now synchronize users.role column according to priority
                $newRole = null;
                if ($has_admin) {
                    $newRole = 'admin';
                } elseif ($has_pan) {
                    $newRole = 'panadero';
                } elseif ($has_ven) {
                    $newRole = 'vendedor';
                } else {
                    $newRole = 'cliente';
                }

                if ($user->role !== $newRole) {
                    // update without triggering observers
                    $user->saveQuietly();
                    $user->role = $newRole;
                    $user->saveQuietly();
                }
            }
        });
    }
}
