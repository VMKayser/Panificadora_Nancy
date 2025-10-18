<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

    // In this app unauthenticated users are redirected; accept either /login or /app
    $response->assertStatus(302);
    $location = $response->headers->get('Location');
    $this->assertNotNull($location, 'Response did not include a Location header');
    $path = parse_url($location, PHP_URL_PATH);
    $this->assertMatchesRegularExpression('/^\/(login|app)$/', $path, "Redirected to unexpected path: $path");
    }
}
