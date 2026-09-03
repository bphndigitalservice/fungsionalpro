<?php

namespace Database\Factories;

use App\Enums\UkomApplicationStatus;
use App\Models\CRole;
use App\Models\UkomApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UkomApplication>
 */
class UkomApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'target_c_role_id' => fn () => CRole::create([
                'role_name' => fake()->unique()->words(2, true),
                'active' => true,
            ])->id,
            'status' => UkomApplicationStatus::Draft,
            'nip' => fake()->unique()->numerify('##################'),
            'nama' => fake()->name(),
            'current_jabatan' => fake()->randomElement(['Arsiparis', 'Pustakawan', 'Pranata Humas']),
            'type' => null,
            'agency_type' => null,
            'agency_id' => null,
            'claims_new_degree' => false,
        ];
    }

    public function pendingInstansi(): static
    {
        return $this->state(['status' => UkomApplicationStatus::PendingInstansi]);
    }

    public function pendingAdmin(): static
    {
        return $this->state(['status' => UkomApplicationStatus::PendingAdmin]);
    }

    public function accepted(): static
    {
        return $this->state(['status' => UkomApplicationStatus::Accepted]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => UkomApplicationStatus::Rejected]);
    }
}
