<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function forUser(User $user): Builder
    {
        return Address::query()->where('user_id', $user->id);
    }

    public function create(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data): Address {
            $isDefault = ! $this->forUser($user)->exists() || ($data['is_default'] ?? false);

            if ($isDefault) {
                $this->clearDefault($user);
            }

            return $user->addresses()->create([
                ...$data,
                'is_default' => $isDefault,
            ]);
        });
    }

    public function update(User $user, Address $address, array $data): Address
    {
        $address = $this->ownedBy($user, $address);

        return DB::transaction(function () use ($user, $address, $data): Address {
            if ($data['is_default'] ?? false) {
                $this->clearDefault($user);
                $data['is_default'] = true;
            } else {
                unset($data['is_default']);
            }

            $address->update($data);

            return $address->refresh();
        });
    }

    public function delete(User $user, Address $address): bool
    {
        $address = $this->ownedBy($user, $address);

        if ($address->is_default) {
            return false;
        }

        return (bool) $address->delete();
    }

    public function ownedBy(User $user, Address $address): Address
    {
        abort_unless(
            (int) $address->user_id === (int) $user->id,
            403,
            'غير مسموح لك بالوصول إلى هذا العنوان.'
        );

        return $address;
    }

    private function clearDefault(User $user): void
    {
        $this->forUser($user)->where('is_default', true)->update(['is_default' => false]);
    }
}
