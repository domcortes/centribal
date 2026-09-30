<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_login_with_valid_credentials_returns_a_jwt(): void
    {
        $user = User::factory()->create([
            'email' => 'agente@centribal.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
        ]);

        $this->assertSame('bearer', $response->json('token_type'));
        $this->assertSame(3600, $response->json('expires_in'));
    }

    public function test_login_with_invalid_credentials_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'agente@centribal.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'agente@centribal.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_throttled_after_five_attempts_per_minute(): void
    {
        $payload = ['email' => 'nadie@centribal.com', 'password' => 'wrong-password'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', $payload)->assertUnprocessable();
        }

        $this->postJson('/api/login', $payload)->assertTooManyRequests();
    }
}
