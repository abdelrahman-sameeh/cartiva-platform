<?php

namespace Tests\Feature\Web;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_login_and_register_pages(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('تسجيل الدخول');

        $this->get('/register')
            ->assertOk()
            ->assertSee('إنشاء حساب جديد');

        $this->get('/home')->assertRedirect('/login');
    }

    public function test_user_can_register_from_web_form(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'email' => 'ahmed@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ahmed@example.com']);
        $this->assertTrue(User::whereEmail('ahmed@example.com')->firstOrFail()->hasRole(RoleName::USER));
    }

    public function test_user_can_login_and_logout_from_web(): void
    {
        $user = User::create([
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'email' => 'ahmed@example.com',
            'password' => 'password123',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);

        $this->get('/home')
            ->assertOk()
            ->assertSee('Ahmed');

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
