<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_verification_notification()
    {
        Notification::fake();

        $payload = [
            'name' => 'Test User',
            'email' => 'test+register@example.test',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
        ]);

        $user = User::where('email', $payload['email'])->first();
        $this->assertNotNull($user);

        // Ensure our custom VerifyEmailNotification was sent
        Notification::assertSentTo($user, VerifyEmailNotification::class);

        // Email should not be verified yet
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_unverified_user_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'test+login@example.test',
            'password' => bcrypt('Password1'),
            'email_verified_at' => null,
        ]);

        $payload = [
            'email' => $user->email,
            'password' => 'Password1',
        ];

        $response = $this->postJson('/api/login', $payload);

        // ValidationException returns 422 with message about verification
        $response->assertStatus(422);
        $response->assertJsonFragment(['Por favor verifica tu correo antes de iniciar sesión.']);
    }
}
