<?php

namespace Database\Seeders\TestData;

use App\Enums\RoleName;
use App\Models\Cart\Cart;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::USER->value))
            ->get()
            ->each(fn (User $buyer) => Cart::create(['user_id' => $buyer->id]));
    }
}
