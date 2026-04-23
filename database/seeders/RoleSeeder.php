<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'pre-client', 'guard_name' => 'web']);
        Role::create(['name' => SystemRole::Client->value, 'guard_name' => 'web']);
        Role::create(['name' => SystemRole::Admin->value, 'guard_name' => 'web']);
        Role::create(['name' => SystemRole::Verifier->value, 'guard_name' => 'web']);
        Role::create(['name' => SystemRole::AdminRegional->value, 'guard_name' => 'web']);
    }
}
