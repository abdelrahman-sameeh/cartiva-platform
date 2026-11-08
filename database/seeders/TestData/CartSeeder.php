<?php

namespace Database\Seeders\TestData;

use App\Models\Cart\Cart;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        $buyers = User::query()
            ->whereIn('email', ['buyer1@gmail.com', 'buyer2@gmail.com'])
            ->get();

        foreach ($buyers as $buyer) {
            Cart::create(['user_id' => $buyer->id]);
        }
    }
}
