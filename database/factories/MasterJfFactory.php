<?php

namespace Database\Factories;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Models\MasterJf;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MasterJf> */
class MasterJfFactory extends Factory
{
    protected $model = MasterJf::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'nip' => fake()->unique()->numerify('##################'),
            'gol_ruang' => fake()->randomElement(['III/a', 'III/b', 'IV/a', null]),
            'jabatan' => fake()->jobTitle(),
            'unit_kerja' => fake()->company(),
            'instansi' => fake()->company(),
            'pengangkatan' => fake()->randomElement(array_keys(MasterJf::pengangkatanOptions())),
            'status' => fake()->randomElement(ClientStatus::cases())->value,
            'type' => fake()->randomElement([...array_column(ClientCluster::cases(), 'value'), null]),
            'status_kepegawaian' => fake()->randomElement([...array_column(JenisKepegawaian::cases(), 'value'), null]),
        ];
    }
}
