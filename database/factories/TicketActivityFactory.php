<?php

namespace Database\Factories;

use App\Enums\TicketEvent;
use App\Models\Ticket;
use App\Models\TicketActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TicketActivity> */
class TicketActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'actor_id' => fn (array $attributes) => Ticket::findOrFail($attributes['ticket_id'])->requester_id,
            'event' => TicketEvent::Created,
            'properties' => [],
        ];
    }
}
