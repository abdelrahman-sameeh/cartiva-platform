<?php

namespace Tests\Feature\Api;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AddressApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_their_addresses_through_the_api(): void
    {
        $user = $this->createUser('api-address@example.com');
        Sanctum::actingAs($user);

        $firstAddressId = $this->postJson('/api/addresses', $this->addressData())
            ->assertCreated()
            ->assertJsonPath('is_default', true)
            ->json('id');

        $secondAddressId = $this->postJson('/api/addresses', $this->addressData([
            'city' => 'الإسكندرية',
            'street' => 'شارع الكورنيش رقم 20',
        ]))
            ->assertCreated()
            ->assertJsonPath('is_default', false)
            ->json('id');

        $this->putJson("/api/addresses/{$secondAddressId}", ['is_default' => true])
            ->assertOk()
            ->assertJsonPath('is_default', true);

        $this->assertDatabaseHas('addresses', ['id' => $firstAddressId, 'is_default' => false]);
        $this->assertDatabaseHas('addresses', ['id' => $secondAddressId, 'is_default' => true]);

        $this->getJson('/api/addresses')
            ->assertOk()
            ->assertJsonCount(2);

        $this->deleteJson("/api/addresses/{$firstAddressId}")->assertNoContent();
        $this->deleteJson("/api/addresses/{$secondAddressId}")
            ->assertStatus(400)
            ->assertJsonPath('message', "can't delete default address");
    }

    public function test_user_cannot_access_another_users_address_through_the_api(): void
    {
        $owner = $this->createUser('owner@example.com');
        $otherUser = $this->createUser('other@example.com');
        $address = $this->createAddress($owner);
        Sanctum::actingAs($otherUser);

        $this->getJson("/api/addresses/{$address->id}")->assertForbidden();
        $this->putJson("/api/addresses/{$address->id}", ['city' => 'الجيزة'])->assertForbidden();
        $this->deleteJson("/api/addresses/{$address->id}")->assertForbidden();
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

    private function createAddress(User $user): Address
    {
        return $user->addresses()->create([
            ...$this->addressData(),
            'is_default' => true,
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
