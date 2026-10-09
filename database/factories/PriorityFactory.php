<?php

namespace Database\Factories;

use App\Models\Priority;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Priority> */
class PriorityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->bothify('Priority ????-########'),
            'rank' => fake()->numberBetween(1, 10),
            'sla_hours' => fake()->numberBetween(1, 720),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
