<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class UserRoleChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_user_role()
    {
        // create an admin and a normal user
    \App\Models\Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
    \App\Models\Role::query()->updateOrInsert(['name' => 'cliente'], ['description' => 'Cliente']);
    $roleAdmin = \App\Models\Role::where('name', 'admin')->first();
    $roleCliente = \App\Models\Role::where('name', 'cliente')->first();

        $admin = User::factory()->create();
        $admin->roles()->attach($roleAdmin->id);

        $user = User::factory()->create();
        $user->roles()->attach($roleCliente->id);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/usuarios/'.$user->id.'/cambiar-rol', ['role' => 'vendedor'])
            ->assertStatus(200)
            ->assertJsonFragment(['role' => 'vendedor']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'vendedor',
        ]);
    }

    public function test_non_admin_cannot_change_user_role()
    {
    \App\Models\Role::query()->updateOrInsert(['name' => 'cliente'], ['description' => 'Cliente']);
    $role = \App\Models\Role::where('name', 'cliente')->first();

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $other = User::factory()->create();
        $other->roles()->attach($role->id);

        $this->actingAs($other, 'sanctum')
            ->postJson('/api/admin/usuarios/'.$user->id.'/cambiar-rol', ['role' => 'vendedor'])
            ->assertStatus(403);
    }
}
