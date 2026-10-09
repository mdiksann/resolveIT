import { Head, Link, usePage } from '@inertiajs/react';
import { AssignmentControl } from '../../Components/Tickets/AssignmentControl';
import { TransitionControl } from '../../Components/Tickets/TransitionControl';
import { Comments } from '../../Components/Tickets/Comments';
import { Attachments } from '../../Components/Tickets/Attachments';
import { ActivityTimeline } from '../../Components/Tickets/ActivityTimeline';
import { DueDate, OverdueBadge } from '../../Components/Tickets/DueDate';
import { formatAbsoluteDate } from '../../Lib/dateFormatting';
import type { SharedProps } from '../../Types';
import AppLayout from '../../Layouts/AppLayout';
import { StatusBadge, PriorityBadge } from '../../Components/Tickets/Badges';
import type {
  PriorityOption,
  TicketCapabilities,
  TicketDetail,
  StatusOption,
  CategoryOption,
} from '../../Types';
export default function Show({
  ticket,
  generatedAt,
  priorities,
  transitions,
  can,
  assignees,
  comments,
  attachments,
  activities,
}: {
  ticket: TicketDetail;
  generatedAt: string;
  priorities: PriorityOption[];
  can: TicketCapabilities;
  transitions: StatusOption[];
  assignees: CategoryOption[];
  comments: import('../../Types').Paginated<import('../../Types').TicketComment>;
  attachments: import('../../Types').Paginated<import('../../Types').TicketAttachment>;
  activities: import('../../Types').Paginated<import('../../Types').TicketActivity>;
}) {
  const { timeZone } = usePage<SharedProps>().props;
  return (
    <AppLayout title={`${ticket.number} · ${ticket.title}`}>
      <Head title={`${ticket.number} ${ticket.title}`} />
      <Link href="/tickets" className="inline-flex min-h-11 items-center text-primary underline">
        Back to tickets
      </Link>
      {can.update && (
        <Link
          href={`/tickets/${ticket.id}/edit`}
          className="inline-flex min-h-11 items-center text-primary underline"
        >
          Edit ticket
        </Link>
      )}
      <div className="flex flex-wrap gap-2">
        <StatusBadge status={ticket.status} />
        <PriorityBadge priority={ticket.priority} priorities={priorities} />
        <OverdueBadge dueAt={ticket.due_at} referenceTime={generatedAt} overdue={ticket.overdue} />
      </div>
      <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_288px]">
        <aside className="space-y-5 lg:col-start-2 lg:row-start-1">
          <section aria-labelledby="details-heading">
            <h2 id="details-heading" className="mb-3 font-semibold">
              Ticket details
            </h2>
            <dl className="space-y-3 text-sm">
              {[
                ['Category', ticket.category?.name ?? 'No category'],
                ['Requester', ticket.requester?.name ?? 'Deleted user'],
                ['Assignee', ticket.assignee?.name ?? 'Unassigned'],
              ].map(([label, value]) => (
                <div key={label}>
                  <dt className="text-muted-foreground">{label}</dt>
                  <dd className="break-words">{value}</dd>
                </div>
              ))}
              <div>
                <dt className="text-muted-foreground">Created</dt>
                <dd>
                  <time dateTime={ticket.created_at}>
                    {formatAbsoluteDate(ticket.created_at, timeZone)}
                  </time>
                </dd>
              </div>
              <div>
                <dt className="text-muted-foreground">Due</dt>
                <dd>
                  <DueDate
                    dueAt={ticket.due_at}
                    referenceTime={generatedAt}
                    overdue={ticket.overdue}
                    showOverdue={false}
                  />
                </dd>
              </div>
            </dl>
          </section>
          <section aria-labelledby="actions-heading">
            <h2 id="actions-heading" className="font-semibold">
              Actions
            </h2>
            <TransitionControl key={ticket.status} ticketId={ticket.id} transitions={transitions} />
            {can.assign && (
              <AssignmentControl
                key={ticket.assignee?.id ?? 0}
                ticket={ticket}
                assignees={assignees}
              />
            )}
          </section>
        </aside>
        <div className="min-w-0 space-y-6 lg:col-start-1 lg:row-start-1">
          <section aria-labelledby="description-heading">
            <h2 id="description-heading" className="mb-3 font-semibold">
              Description
            </h2>
            <p className="whitespace-pre-wrap break-words text-sm leading-6">
              {ticket.description}
            </p>
          </section>
          <Comments
            ticketId={ticket.id}
            comments={comments}
            canComment={can.comment}
            canInternal={can.internal}
          />
          <Attachments ticketId={ticket.id} attachments={attachments} canAttach={can.attach} />
          <ActivityTimeline activities={activities} />
        </div>
      </div>
    </AppLayout>
  );
}
