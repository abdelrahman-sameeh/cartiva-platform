<?php

namespace Database\Seeders\TestData;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleName::cases() as $roleName) {
            Role::query()->updateOrCreate(
                ['name' => $roleName->value],
                ['label' => $roleName->label()],
            );
        }
    }
}
