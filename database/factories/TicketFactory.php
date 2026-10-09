<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'status' => TicketStatus::Open,
            'requester_id' => User::factory(),
            'assignee_id' => null,
            'category_id' => Category::factory(),
            'priority_id' => Priority::factory(),
            'due_at' => fn (array $attributes) => now()->addHours(Priority::findOrFail($attributes['priority_id'])->sla_hours),
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }
}
