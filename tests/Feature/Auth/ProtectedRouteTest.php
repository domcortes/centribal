<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ProtectedRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_protected_route_rejects_requests_without_a_token(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_a_protected_route_rejects_an_invalid_token(): void
    {
        $this->getJson('/api/me', [
            'Authorization' => 'Bearer invalid-token',
        ])->assertUnauthorized();
    }

    public function test_a_protected_route_accepts_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->getJson('/api/me', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()->assertJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function test_logout_invalidates_the_token(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->postJson('/api/logout', [], $headers)->assertOk();
        $this->getJson('/api/me', $headers)->assertUnauthorized();
    }

    public function test_refresh_returns_a_new_valid_token(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->postJson('/api/refresh', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
        $this->assertNotSame($token, $response->json('access_token'));
    }
}
