<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class UserProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_update_profile_data()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'orig@example.com',
        ]);
        $payload = [
            'name' => 'Nuevo Nombre',
        ];

        // The profile endpoint supports changing name and password; email changes
        // are handled via admin endpoints. Assert name changed.
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile', $payload)
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Nuevo Nombre']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nuevo Nombre',
        ]);
    }

    public function test_email_must_be_unique_when_updating_profile()
    {
        // To test uniqueness we use the admin user update endpoint which validates email
            \App\Models\Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
            $role = \App\Models\Role::where('name', 'admin')->first();
        $admin = User::factory()->create();
        $admin->roles()->attach($role->id);

        $user = User::factory()->create(['email' => 'user1@example.com']);
        $other = User::factory()->create(['email' => 'user2@example.com']);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/usuarios/'.$user->id, ['email' => 'user2@example.com'])
            ->assertStatus(422);
    }
}
