<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Policies\TicketPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TicketPolicyTest extends TestCase
{
    #[DataProvider('accessCases')]
    public function test_record_access_by_role_ownership_and_status(Role $role, bool $own, TicketStatus $status, array $allowed): void
    {
        [$user, $ticket] = $this->context($role, $own, $status);
        $policy = new TicketPolicy;
        foreach (['view', 'update', 'assign', 'comment', 'viewInternalNotes', 'addInternalNote', 'attach', 'viewActivity'] as $method) {
            $this->assertSame(in_array($method, $allowed, true), $policy->$method($user, $ticket), $method);
        }
        $this->assertTrue($policy->create($user));
        $this->assertSame($role !== Role::Employee, $policy->viewAny($user));
    }

    public static function accessCases(): array
    {
        $cases = [];
        foreach (Role::cases() as $role) {
            foreach ([true, false] as $own) {
                foreach (TicketStatus::cases() as $status) {
                    $allowed = [];
                    if ($role !== Role::Employee) {
                        $allowed = $status === TicketStatus::Closed
                            ? ['view', 'viewActivity', 'viewInternalNotes']
                            : ['view', 'viewActivity', 'viewInternalNotes', 'update', 'assign', 'comment', 'attach', 'addInternalNote'];
                    } elseif ($own) {
                        $allowed = match ($status) {
                            TicketStatus::Open, TicketStatus::Assigned => ['view', 'viewActivity', 'update', 'comment', 'attach'],
                            TicketStatus::InProgress, TicketStatus::Resolved => ['view', 'viewActivity', 'comment', 'attach'],
                            TicketStatus::Closed => ['view', 'viewActivity'],
                        };
                    }
                    $cases[$role->value.'-'.($own ? 'own' : 'other').'-'.$status->value] = [$role, $own, $status, $allowed];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('transitionCases')]
    public function test_all_status_transition_pairs(Role $role, bool $own, TicketStatus $from, TicketStatus $to, bool $allowed): void
    {
        [$user, $ticket] = $this->context($role, $own, $from);
        $this->assertSame($allowed, (new TicketPolicy)->transitionStatus($user, $ticket, $to));
    }

    public static function transitionCases(): array
    {
        $staffPairs = ['OPEN:ASSIGNED', 'OPEN:IN_PROGRESS', 'ASSIGNED:IN_PROGRESS', 'IN_PROGRESS:RESOLVED', 'RESOLVED:IN_PROGRESS', 'RESOLVED:CLOSED'];
        $employeePairs = ['RESOLVED:IN_PROGRESS', 'RESOLVED:CLOSED'];
        $cases = [];
        foreach (Role::cases() as $role) {
            foreach ([true, false] as $own) {
                foreach (TicketStatus::cases() as $from) {
                    foreach (TicketStatus::cases() as $to) {
                        $pair = $from->value.':'.$to->value;
                        $allowed = $role === Role::Employee ? $own && in_array($pair, $employeePairs, true) : in_array($pair, $staffPairs, true);
                        $cases[] = [$role, $own, $from, $to, $allowed];
                    }
                }
            }
        }

        return $cases;
    }

    #[DataProvider('attachmentCases')]
    public function test_attachment_removal_requires_ticket_access_and_uploader_or_admin(Role $role, bool $own, TicketStatus $status, bool $uploaded, bool $allowed): void
    {
        [$user, $ticket] = $this->context($role, $own, $status);
        $attachment = new TicketAttachment(['ticket_id' => $ticket->id, 'uploader_id' => $uploaded ? $user->id : 3]);
        $policy = new TicketPolicy;
        $this->assertSame($allowed, $policy->deleteAttachment($user, $ticket, $attachment));
        $attachment->ticket_id = 999;
        $this->assertFalse($policy->deleteAttachment($user, $ticket, $attachment));
    }

    public static function attachmentCases(): array
    {
        $cases = [];
        foreach (Role::cases() as $role) {
            foreach ([true, false] as $own) {
                foreach (TicketStatus::cases() as $status) {
                    foreach ([true, false] as $uploaded) {
                        $allowed = $status !== TicketStatus::Closed && ($role !== Role::Employee || $own) && ($role === Role::Admin || $uploaded);
                        $cases[] = [$role, $own, $status, $uploaded, $allowed];
                    }
                }
            }
        }

        return $cases;
    }

    private function context(Role $role, bool $own, TicketStatus $status): array
    {
        $user = new User;
        $user->id = 1;
        $user->role = $role;
        $ticket = new Ticket(['requester_id' => $own ? 1 : 2, 'status' => $status]);
        $ticket->id = 10;

        return [$user, $ticket];
    }
}
