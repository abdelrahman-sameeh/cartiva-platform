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
        $users = [
            ['Ahmed', 'Admin', 'admin@gmail.com', RoleName::ADMIN],
            ['Printore', 'Shop', 'printore@gmail.com', RoleName::SELLER],
            ['Salman', 'Store', 'salman@gmail.com', RoleName::SELLER],
            ['Mohamed', 'Ali', 'buyer1@gmail.com', RoleName::USER],
            ['Hana', 'Sami', 'buyer2@gmail.com', RoleName::USER],
        ];

        foreach ($users as [$firstName, $lastName, $email, $role]) {
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => Hash::make('123456'),
            ]);

            $user->syncRoles($role);
        }
    }
}
