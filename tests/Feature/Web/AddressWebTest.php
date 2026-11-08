<?php

namespace Tests\Feature\Web;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_address_management(): void
    {
        $this->get(route('addresses.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_create_and_view_an_address(): void
    {
        $user = $this->createUser('address@example.com');

        $this->actingAs($user)
            ->post(route('addresses.store'), $this->addressData())
            ->assertRedirect(route('addresses.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'country' => 'EG',
            'city' => 'القاهرة',
            'is_default' => true,
        ]);

        $this->actingAs($user)
            ->get(route('addresses.index'))
            ->assertOk()
            ->assertSee('القاهرة')
            ->assertSee('مصر')
            ->assertSee('الافتراضي');
    }

    public function test_user_can_change_the_default_address_and_delete_another_address(): void
    {
        $user = $this->createUser('manage-address@example.com');
        $firstAddress = $this->createAddress($user, true);
        $secondAddress = $this->createAddress($user, false, [
            'city' => 'الإسكندرية',
            'street' => 'شارع الكورنيش رقم 20',
        ]);

        $this->actingAs($user)
            ->put(route('addresses.update', $secondAddress), [
                ...$this->addressData([
                    'city' => 'الإسكندرية',
                    'street' => 'شارع الكورنيش رقم 25',
                ]),
                'is_default' => true,
            ])
            ->assertRedirect(route('addresses.index'));

        $this->assertDatabaseHas('addresses', ['id' => $firstAddress->id, 'is_default' => false]);
        $this->assertDatabaseHas('addresses', [
            'id' => $secondAddress->id,
            'street' => 'شارع الكورنيش رقم 25',
            'is_default' => true,
        ]);

        $this->actingAs($user)
            ->delete(route('addresses.destroy', $firstAddress))
            ->assertRedirect(route('addresses.index'));

        $this->assertDatabaseMissing('addresses', ['id' => $firstAddress->id]);

        $this->actingAs($user)
            ->delete(route('addresses.destroy', $secondAddress))
            ->assertSessionHasErrors('address');

        $this->assertDatabaseHas('addresses', ['id' => $secondAddress->id]);
    }

    public function test_user_cannot_manage_another_users_address(): void
    {
        $owner = $this->createUser('owner@example.com');
        $otherUser = $this->createUser('other@example.com');
        $address = $this->createAddress($owner, true);

        $this->actingAs($otherUser)
            ->get(route('addresses.edit', $address))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('addresses.destroy', $address))
            ->assertForbidden();
    }

    private function createUser(string $email): User
    {
        return User::create([
            'first_name' => 'مستخدم',
            'last_name' => 'تجريبي',
            'email' => $email,
            'password' => 'password123',
        ]);
    }

    private function createAddress(User $user, bool $isDefault, array $overrides = []): Address
    {
        return $user->addresses()->create([
            ...$this->addressData($overrides),
            'is_default' => $isDefault,
        ]);
    }

    private function addressData(array $overrides = []): array
    {
        return [
            'country' => 'EG',
            'city' => 'القاهرة',
            'street' => 'شارع التحرير رقم 10',
            'note' => 'بجوار محطة المترو',
            ...$overrides,
        ];
    }
}
