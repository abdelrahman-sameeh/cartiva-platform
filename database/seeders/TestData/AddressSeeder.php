<?php

namespace Database\Seeders\TestData;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        $buyer1 = User::query()->where('email', 'buyer1@gmail.com')->firstOrFail();
        $buyer2 = User::query()->where('email', 'buyer2@gmail.com')->firstOrFail();

        Address::create([
            'user_id' => $buyer1->id,
            'country' => 'EG',
            'city' => 'القاهرة',
            'street' => '١٥ ميدان التحرير، وسط البلد',
            'is_default' => true,
            'note' => 'بجوار محطة المترو',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        Address::create([
            'user_id' => $buyer1->id,
            'country' => 'EG',
            'city' => 'الجيزة',
            'street' => '٢٢ شارع الهرم',
            'is_default' => false,
            'note' => 'بالقرب من المركز التجاري',
        ]);

        Address::create([
            'user_id' => $buyer2->id,
            'country' => 'EG',
            'city' => 'الإسكندرية',
            'street' => '٥ طريق الكورنيش',
            'is_default' => true,
        ]);
    }
}
