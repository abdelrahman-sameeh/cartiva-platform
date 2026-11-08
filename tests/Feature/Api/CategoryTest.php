<?php

namespace Tests\Feature\Api;

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_categories(): void
    {
        // ─── 1. جهّز: اعمل 3 فئات ──────────────────────
        Category::create(['title' => 'Electronics']);
        Category::create(['title' => 'Fashion']);
        Category::create(['title' => 'Sports']);

        // ─── 2. نفّذ: ابعت GET request ──────────────────
        $response = $this->get('/api/categories');

        // ─── 3. اتأكد ──────────────────────────────────
        $response->assertOk()
            ->assertJsonCount(3);
    }

    public function test_can_create_category()
    {
        // الـ endpoint ده محتاج أدمن متسجل
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin@test.com',
            'password' => 'Ec1234sasa@#',
        ]);
        $admin->syncRoles(RoleName::ADMIN);

        // actingAs = ابعت الـ request كأنك اليوزر ده
        $response = $this->actingAs($admin)->postJson('/api/categories', [
            'title' => 'Books',
        ]);

        $response->assertOk();
    }
}
