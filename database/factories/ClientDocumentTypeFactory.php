<?php

namespace Database\Factories;

use App\Models\ClientDocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientDocumentType>
 */
class ClientDocumentTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->unique()->lexify('DOC-???'),
            'description' => fake()->sentence(3),
            'is_required' => fake()->boolean(),
        ];
    }
}
