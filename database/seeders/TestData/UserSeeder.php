<?php

namespace Database\Seeders\TestData;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [['أحمد', 'منصور', 'admin@gmail.com', RoleName::ADMIN]];
        $sellerNames = [
            ['كريم', 'الشاذلي'], ['سلمى', 'النجار'], ['محمود', 'فوزي'], ['ندى', 'حسن'],
            ['يوسف', 'عبد الرحمن'], ['دينا', 'صبري'], ['عمر', 'مراد'], ['مريم', 'سالم'],
            ['طارق', 'إبراهيم'], ['نهى', 'كمال'],
        ];

        foreach ($sellerNames as $index => [$firstName, $lastName]) {
            $users[] = [
                $firstName,
                $lastName,
                ProductCatalog::sellerEmails()[$index],
                RoleName::SELLER,
            ];
        }

        $buyerNames = [
            ['محمد', 'علي'], ['هنا', 'سامي'], ['أحمد', 'محمود'], ['آية', 'عادل'], ['مصطفى', 'حسين'],
            ['إسراء', 'خالد'], ['عبد الله', 'طارق'], ['نور', 'محمد'], ['مروان', 'أشرف'], ['مي', 'وليد'],
            ['زياد', 'هاني'], ['رحمة', 'مجدي'], ['علي', 'صلاح'], ['بسمة', 'رجب'], ['حسام', 'عصام'],
            ['شيماء', 'جمال'], ['رامي', 'سمير'], ['ياسمين', 'حمدي'], ['عمرو', 'سعيد'], ['ملك', 'وائل'],
            ['خالد', 'رمضان'], ['منة', 'شريف'], ['إسلام', 'فتحي'], ['أسماء', 'نادر'], ['أيمن', 'صبحي'],
            ['رانيا', 'ماهر'], ['حازم', 'جابر'], ['سارة', 'منير'], ['وليد', 'خليل'], ['داليا', 'رضا'],
            ['شادي', 'عاطف'], ['ريهام', 'سليمان'], ['مينا', 'فؤاد'], ['هدى', 'صبري'], ['باسم', 'أنور'],
            ['عبير', 'حمدان'], ['تامر', 'لطفي'], ['حنان', 'شاكر'], ['معتز', 'إمام'],
        ];

        foreach ($buyerNames as $index => [$firstName, $lastName]) {
            $email = match ($index) {
                0 => 'buyer1@gmail.com',
                1 => 'buyer2@gmail.com',
                default => sprintf('buyer%02d@cartiva.test', $index + 1),
            };
            $users[] = [$firstName, $lastName, $email, RoleName::USER];
        }

        foreach ($users as $userIndex => [$firstName, $lastName, $email, $role]) {
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => '010'.str_pad((string) $userIndex, 8, '0', STR_PAD_LEFT),
                'password' => Hash::make('123456'),
            ]);

            $user->syncRoles($role);
        }
    }
}
