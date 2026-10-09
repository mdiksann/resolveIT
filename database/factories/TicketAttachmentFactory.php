<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<TicketAttachment> */
class TicketAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'comment_id' => null,
            'uploader_id' => fn (array $attributes) => Ticket::findOrFail($attributes['ticket_id'])->requester_id,
            'disk' => 'local',
            // Metadata only: file creation/upload belongs to TASK-018.
            'path' => fn (array $attributes) => 'tickets/'.$attributes['ticket_id'].'/'.Str::uuid().'.txt',
            'original_name' => 'diagnostic-log.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => fake()->numberBetween(1, 10485760),
        ];
    }
}
