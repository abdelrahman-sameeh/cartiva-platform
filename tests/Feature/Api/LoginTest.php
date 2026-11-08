<?php

namespace Tests\Feature\Api;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private string $loginUrl = '/api/auth/login';

    private function createUser(array $overrides = [], RoleName ...$roles): User
    {
        $user = User::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.com',
            'password' => 'Ec1234sasa@#',
        ], $overrides));

        if ($roles !== []) {
            $user->syncRoles(...$roles);
        }

        return $user;
    }

    // ─── Success ──────────────────────────────────────────────────────────────

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->createUser();

        $response = $this->postJson($this->loginUrl, [
            'email' => 'test@test.com',
            'password' => 'Ec1234sasa@#',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'first_name', 'last_name', 'email', 'roles'],
            ])
            ->assertJson(['token_type' => 'Bearer']);
    }

    public function test_login_returns_correct_user_data(): void
    {
        $this->createUser([
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'email' => 'ahmed@test.com',
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email' => 'ahmed@test.com',
            'password' => 'Ec1234sasa@#',
        ]);

        $response->assertOk()
            ->assertJson([
                'user' => [
                    'first_name' => 'Ahmed',
                    'last_name' => 'Ali',
                    'email' => 'ahmed@test.com',
                ],
            ]);
    }

    public function test_token_is_created_in_database_after_login(): void
    {
        $user = $this->createUser();

        $this->postJson($this->loginUrl, [
            'email' => 'test@test.com',
            'password' => 'Ec1234sasa@#',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    // ─── Wrong Credentials ────────────────────────────────────────────────────

    public function test_login_fails_with_wrong_password(): void
    {
        $this->createUser();

        $response = $this->postJson($this->loginUrl, [
            'email' => 'test@test.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Email or password is incorrect']);
    }

    public function test_login_fails_with_non_existing_email(): void
    {
        $response = $this->postJson($this->loginUrl, [
            'email' => 'notexist@test.com',
            'password' => 'Ec1234sasa@#',
        ]);

        $response->assertStatus(401);
    }

    // ─── Validation ───────────────────────────────────────────────────────────

    public function test_login_fails_without_email(): void
    {
        $response = $this->postJson($this->loginUrl, [
            'password' => 'Ec1234sasa@#',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_login_fails_without_password(): void
    {
        $response = $this->postJson($this->loginUrl, [
            'email' => 'test@test.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_login_fails_with_invalid_email_format(): void
    {
        $response = $this->postJson($this->loginUrl, [
            'email' => 'not-an-email',
            'password' => 'Ec1234sasa@#',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_login_fails_with_short_password(): void
    {
        $response = $this->postJson($this->loginUrl, [
            'email' => 'test@test.com',
            'password' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_login_fails_with_empty_body(): void
    {
        $response = $this->postJson($this->loginUrl, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // ─── All Roles ────────────────────────────────────────────────────────────

    public function test_seller_can_login(): void
    {
        $this->createUser(['email' => 'seller@test.com'], RoleName::SELLER);

        $response = $this->postJson($this->loginUrl, [
            'email' => 'seller@test.com',
            'password' => 'Ec1234sasa@#',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token']);
    }

    public function test_admin_can_login(): void
    {
        $this->createUser(['email' => 'admin@test.com'], RoleName::ADMIN);

        $response = $this->postJson($this->loginUrl, [
            'email' => 'admin@test.com',
            'password' => 'Ec1234sasa@#',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token']);
    }
}
