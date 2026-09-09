<?php

namespace Database\Factories;

use App\Models\CalonJf;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalonJf>
 */
class CalonJfFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nip' => fake()->unique()->numerify('##################'),
            'nama' => fake()->name(),
        ];
    }
}
