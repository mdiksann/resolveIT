<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [10, 30];

    public readonly string $number;

    public readonly string $title;

    public readonly string $url;

    public function __construct(Ticket $ticket)
    {
        $this->number = '#'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT);
        $this->title = $ticket->title;
        $this->url = route('tickets.show', $ticket);
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Ticket resolved: '.$this->number)
            ->line('Your ticket '.$this->number.': '.$this->title.' has been resolved.')
            ->line('You can close the ticket if the issue is fixed, or reopen it if you still need help.')
            ->action('View ticket', $this->url);
    }
}
