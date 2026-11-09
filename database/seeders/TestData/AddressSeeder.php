<?php

namespace Database\Seeders\TestData;

use App\Enums\RoleName;
use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        $cities = ['القاهرة', 'الجيزة', 'الإسكندرية', 'المنصورة', 'طنطا', 'الزقازيق', 'أسيوط', 'سوهاج'];
        $streets = ['شارع التحرير', 'شارع النصر', 'شارع الجمهورية', 'شارع البحر', 'شارع الجامعة'];

        User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::USER->value))
            ->orderBy('id')
            ->get()
            ->each(function (User $buyer, int $index) use ($cities, $streets): void {
                Address::create([
                    'user_id' => $buyer->id,
                    'country' => 'EG',
                    'city' => $cities[$index % count($cities)],
                    'street' => (10 + $index).' '.$streets[$index % count($streets)],
                    'is_default' => true,
                    'note' => $index % 3 === 0 ? 'يرجى الاتصال قبل الوصول' : null,
                ]);
            });
    }
}
