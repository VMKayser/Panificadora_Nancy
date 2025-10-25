<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_only_endpoint_blocked_for_vendedor()
    {
        // Ensure role exists and attach via pivot so middleware sees it
    \App\Models\Role::query()->updateOrInsert(['name' => 'vendedor'], ['description' => 'Vendedor']);
    $role = \App\Models\Role::where('name', 'vendedor')->first();
        $vendedor = User::factory()->create();
        $vendedor->roles()->attach($role->id);

        $this->actingAs($vendedor, 'sanctum')
            ->getJson('/api/admin/dashboard-stats')
            ->assertStatus(403);
    }

    public function test_admin_can_access_admin_endpoint()
    {
    \App\Models\Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
    $role = \App\Models\Role::where('name', 'admin')->first();
        $admin = User::factory()->create();
        $admin->roles()->attach($role->id);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/dashboard-stats')
            ->assertStatus(200);
    }
}
