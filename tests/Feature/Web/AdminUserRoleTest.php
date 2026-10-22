<?php

namespace Tests\Feature\Web;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_multiple_roles_including_admin(): void
    {
        $admin = $this->createUser('admin@example.com', RoleName::ADMIN);
        $user = $this->createUser('user@example.com', RoleName::USER);
        $roleIds = Role::query()
            ->whereIn('name', [RoleName::USER->value, RoleName::SELLER->value, RoleName::ADMIN->value])
            ->pluck('id')
            ->all();

        $this->actingAs($admin)
            ->get(route('admin.users.roles.index'))
            ->assertOk()
            ->assertSee('إدارة صلاحيات المستخدمين')
            ->assertSee('user@example.com');

        $this->actingAs($admin)
            ->put(route('admin.users.roles.update', $user), ['role_ids' => $roleIds])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->hasRole(RoleName::USER));
        $this->assertTrue($user->hasRole(RoleName::SELLER));
        $this->assertTrue($user->hasRole(RoleName::ADMIN));
        $this->assertCount(3, $user->roles);

        $this->actingAs($user)
            ->get(route('seller.products.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('seller.products.index'), false)
            ->assertSee(route('admin.users.roles.index'), false);
    }

    public function test_non_admin_cannot_manage_roles(): void
    {
        $seller = $this->createUser('seller@example.com', RoleName::SELLER);
        $user = $this->createUser('target@example.com', RoleName::USER);
        $adminRoleId = Role::query()->where('name', RoleName::ADMIN->value)->value('id');

        $this->actingAs($seller)
            ->get(route('admin.users.roles.index'))
            ->assertForbidden();

        $this->actingAs($seller)
            ->put(route('admin.users.roles.update', $user), ['role_ids' => [$adminRoleId]])
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasRole(RoleName::ADMIN));
    }

    public function test_last_admin_cannot_remove_their_admin_role(): void
    {
        $admin = $this->createUser('only-admin@example.com', RoleName::ADMIN);
        $userRoleId = Role::query()->where('name', RoleName::USER->value)->value('id');

        $this->actingAs($admin)
            ->from(route('admin.users.roles.index'))
            ->put(route('admin.users.roles.update', $admin), ['role_ids' => [$userRoleId]])
            ->assertRedirect(route('admin.users.roles.index'))
            ->assertSessionHasErrors("roles.{$admin->id}");

        $this->assertTrue($admin->fresh()->hasRole(RoleName::ADMIN));
    }

    public function test_user_must_keep_at_least_one_role(): void
    {
        $admin = $this->createUser('validation-admin@example.com', RoleName::ADMIN);
        $user = $this->createUser('validation-user@example.com', RoleName::USER);

        $this->actingAs($admin)
            ->put(route('admin.users.roles.update', $user), ['role_ids' => []])
            ->assertSessionHasErrors('role_ids');

        $this->assertTrue($user->fresh()->hasRole(RoleName::USER));
    }

    private function createUser(string $email, RoleName ...$roles): User
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'password123',
        ]);

        return $user->syncRoles(...$roles);
    }
}
