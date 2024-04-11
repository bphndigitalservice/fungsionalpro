<?php

namespace Database\Seeders;

use App\Models\CRole;
use App\Models\CRoleLevel;
use Illuminate\Database\Seeder;

class CRoleSeeder extends Seeder
{
    public array $levels = [
        'Ahli Pertama',
        'Ahli Muda',
        'Ahli Madya',
        'Ahli Utama',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cRole = CRole::create([
            'role_name' => 'Analis Hukum',
            'active' => true,
        ]);

        foreach ($this->levels as $level) {
            CRoleLevel::create([
                'c_role_id' => $cRole->id,
                'level' => $level,
            ]);
        }

    }
}
