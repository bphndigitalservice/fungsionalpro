<?php

namespace Database\Factories;

use App\Enums\UkomDocumentPack;
use App\Models\UkomDocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UkomDocumentType>
 */
class UkomDocumentTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pack' => UkomDocumentPack::CalonJf,
            'slug' => fake()->unique()->slug(2),
            'label' => fake()->sentence(4),
            'is_required' => true,
            'required_if_claims_new_degree' => false,
            'sort' => fake()->numberBetween(1, 20),
        ];
    }
}
