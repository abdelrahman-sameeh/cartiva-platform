<?php

namespace Tests\Feature\Web;

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_manage_categories(): void
    {
        $seller = User::create([
            'first_name' => 'Seller',
            'last_name' => 'User',
            'email' => 'seller-category@example.com',
            'password' => 'password123',
        ]);
        $seller->syncRoles(RoleName::SELLER);

        $this->actingAs($seller)
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_categories_and_sub_categories(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin-category@example.com',
            'password' => 'password123',
        ]);
        $admin->syncRoles(RoleName::ADMIN);

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('إدارة التصنيفات');

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), ['title' => 'Electronics'])
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.sub-categories.store'), [
                'title' => 'Phones',
                'category_id' => $category->id,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('sub_category', [
            'title' => 'Phones',
            'category_id' => $category->id,
        ]);
    }

    public function test_admin_can_delete_a_sub_category_and_category(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Delete',
            'email' => 'admin-delete@example.com',
            'password' => 'password123',
        ]);
        $admin->syncRoles(RoleName::ADMIN);
        $category = Category::create(['title' => 'Fashion']);
        $subCategory = $category->sub_categories()->create(['title' => 'Shoes']);

        $this->actingAs($admin)
            ->delete(route('admin.sub-categories.destroy', $subCategory))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('sub_category', ['id' => $subCategory->id]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
