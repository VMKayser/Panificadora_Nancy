<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\TestUsersSeeder;

class AuthRoleTest extends TestCase
{
    // If your environment supports in-memory DB for tests, you can enable RefreshDatabase
    // use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure a fresh database schema for tests and seed required data
        // Use migrate:fresh to ensure tables exist in the testing connection
        $this->artisan('migrate:fresh', ['--seed' => true]);
    // Some test seeders (TestUsersSeeder) are not included in DatabaseSeeder; run it explicitly
    $this->artisan('db:seed', ['--class' => TestUsersSeeder::class]);
    }

    public function test_admin_can_login_and_has_admin_role()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@panificadoranancy.com',
            'password' => 'admin123'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'user', 'access_token']);

        $user = $response->json('user');
        $this->assertArrayHasKey('roles', $user);

        $roleNames = array_map(function ($r) { return $r['name']; }, $user['roles']);
        $this->assertContains('admin', $roleNames);
    }

    public function test_vendedor_can_login_and_has_vendedor_role()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'vendedor@panificadoranancy.com',
            'password' => 'vendedor123'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'user', 'access_token']);

        $user = $response->json('user');
        $this->assertArrayHasKey('roles', $user);
        $roleNames = array_map(function ($r) { return $r['name']; }, $user['roles']);
        $this->assertContains('vendedor', $roleNames);
    }

    public function test_cliente_can_login_and_has_cliente_role()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'maria@cliente.com',
            'password' => 'cliente123'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'user', 'access_token']);

        $user = $response->json('user');
        $this->assertArrayHasKey('roles', $user);
        $roleNames = array_map(function ($r) { return $r['name']; }, $user['roles']);
        $this->assertContains('cliente', $roleNames);
    }
}
