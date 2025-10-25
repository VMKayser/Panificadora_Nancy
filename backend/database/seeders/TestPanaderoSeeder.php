<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestPanaderoSeeder extends Seeder
{
    public function run()
    {
        \App\Models\User::query()->updateOrInsert([
            'email' => 'panadero-test@mailinator.com'
        ], [
            'name' => 'Panadero Test',
            'password' => Hash::make('prueba1234'),
            'is_active' => true,
        ]);
        $user = User::where('email', 'panadero-test@mailinator.com')->first();

        // Assign role directly if roles table exists (observer will handle creating panadero row)
        if (method_exists($user, 'roles')) {
            $role = \App\Models\Role::where('name', 'panadero')->first();
            if ($role && !$user->hasRole('panadero')) {
                $user->roles()->attach($role->id);
            }
        }

        $this->command->info('Test panadero user ensured: ' . $user->email);
    }
}
