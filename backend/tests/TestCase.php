<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create the application for the tests.
     *
     * Keeping this minimal avoids any temporary diagnostic instrumentation.
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    /** Usuario con el rol indicado (crea el rol si la BD de pruebas aún no lo tiene). */
    protected function crearUsuarioConRol(string $rol, array $atributos = []): \App\Models\User
    {
        \App\Models\Role::query()->updateOrInsert(['name' => $rol], ['description' => ucfirst($rol)]);
        $user = \App\Models\User::factory()->create($atributos);
        $user->roles()->attach(\App\Models\Role::where('name', $rol)->value('id'));
        return $user;
    }
}
