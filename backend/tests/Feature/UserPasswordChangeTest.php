<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;

class UserPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_own_password_with_current_password()
    {
        $user = User::factory()->create(["password" => Hash::make('old-secret')]);
        $payload = [
            'current_password' => 'old-secret',
            'new_password' => 'new-secret',
            'new_password_confirmation' => 'new-secret',
        ];

        // The profile endpoint in the app accepts password changes via PUT /api/profile
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile', $payload)
            ->assertStatus(200);

        $this->assertTrue(Hash::check('new-secret', $user->fresh()->password));
    }

    public function test_admin_can_reset_user_password()
    {
    \App\Models\Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
    $role = \App\Models\Role::where('name', 'admin')->first();
    $admin = User::factory()->create();
    $admin->roles()->attach($role->id);

        $user = User::factory()->create(['role' => 'cliente']);

        // Admin update endpoint supports updating password via PUT
        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/usuarios/'.$user->id, ['password' => 'reset-123'])
            ->assertStatus(200);

        $this->assertTrue(Hash::check('reset-123', $user->fresh()->password));
    }
}
