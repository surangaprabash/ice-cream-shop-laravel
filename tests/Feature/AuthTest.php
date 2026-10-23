<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create([
            'email' => 'owner@icecream.test',
            'password' => 'password123',
            'role' => Role::Owner,
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->makeUser();

        $this->postJson('/api/auth/login', [
            'email' => 'owner@icecream.test',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonStructure(['message', 'token_type', 'access_token', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJsonPath('user.role', 'owner')
            ->assertJsonMissingPath('user.password');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeUser();

        $this->postJson('/api/auth/login', [
            'email' => 'owner@icecream.test',
            'password' => 'wrong-password',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_fails_for_unknown_email(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'nobody@icecream.test',
            'password' => 'password123',
        ])->assertStatus(401);
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'owner@icecream.test',
                'password' => 'wrong',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'owner@icecream.test',
            'password' => 'wrong',
        ])->assertStatus(429);
    }

    public function test_protected_route_requires_token(): void
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_authenticated_user_can_get_own_profile(): void
    {
        $this->actingAsRole(Role::Manager);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.role', 'manager');
    }

    public function test_logout_revokes_the_token(): void
    {
        $this->makeUser();

        $token = $this->postJson('/api/auth/login', [
            'email' => 'owner@icecream.test',
            'password' => 'password123',
        ])->json('access_token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}