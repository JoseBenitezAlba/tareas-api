<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_gets_token(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'secreto123',
        ])->assertCreated()->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);
    }

    public function test_register_validates_input(): void
    {
        $this->postJson('/api/register', ['email' => 'no-es-email', 'password' => '123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'secreto123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => 'secreto123']);

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'secreto123'])
            ->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => 'secreto123']);

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'mala'])
            ->assertUnprocessable();
    }

    public function test_protected_routes_require_token(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_me_and_logout_with_real_token(): void
    {
        $token = $this->postJson('/api/register', [
            'name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'secreto123',
        ])->json('token');

        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJson(['email' => 'ana@example.com']);
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
