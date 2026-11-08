<?php

namespace Database\Seeders;

use Database\Seeders\TestData\AddressSeeder;
use Database\Seeders\TestData\CartCouponSeeder;
use Database\Seeders\TestData\CartItemSeeder;
use Database\Seeders\TestData\CartSeeder;
use Database\Seeders\TestData\CategorySeeder;
use Database\Seeders\TestData\CouponSeeder;
use Database\Seeders\TestData\OrderItemSeeder;
use Database\Seeders\TestData\OrderSeeder;
use Database\Seeders\TestData\ProductAttributeSeeder;
use Database\Seeders\TestData\ProductSeeder;
use Database\Seeders\TestData\ProductSubCategorySeeder;
use Database\Seeders\TestData\ProductVariantSeeder;
use Database\Seeders\TestData\RoleSeeder;
use Database\Seeders\TestData\SubCategorySeeder;
use Database\Seeders\TestData\SubOrderSeeder;
use Database\Seeders\TestData\UserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            AddressSeeder::class,
            CategorySeeder::class,
            SubCategorySeeder::class,
            ProductSeeder::class,
            ProductVariantSeeder::class,
            ProductSubCategorySeeder::class,
            ProductAttributeSeeder::class,
            CouponSeeder::class,
            CartSeeder::class,
            CartItemSeeder::class,
            CartCouponSeeder::class,
            OrderSeeder::class,
            SubOrderSeeder::class,
            OrderItemSeeder::class,
        ]);
    }
}
