import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { DueDate } from '../../Components/Tickets/DueDate';
import AppLayout from '../../Layouts/AppLayout';
import { FormField } from '../../Components/FormField';
import { FormErrors } from '../../Components/FormErrors';
import { EmptyState } from '../../Components/EmptyState';
import { Button } from '../../Components/ui/button';
import { StatusBadge, PriorityBadge } from '../../Components/Tickets/Badges';
import type {
  CategoryOption,
  Paginated,
  PriorityOption,
  StatusOption,
  TicketFilters,
  TicketSummary,
} from '../../Types';
type Props = {
  generatedAt: string;
  tickets: Paginated<TicketSummary>;
  filters: TicketFilters;
  priorities: PriorityOption[];
  categories: CategoryOption[];
  statuses: StatusOption[];
  assignees: CategoryOption[];
  errors: Partial<Record<string, string>>;
};
export default function Index({
  tickets,
  generatedAt,
  filters,
  priorities,
  categories,
  statuses,
  assignees,
  errors,
}: Props) {
  const [data, setData] = useState<TicketFilters>(filters);
  const [busy, setBusy] = useState(false);
  const [failure, setFailure] = useState('');
  function visit(clear = false) {
    const next = clear ? {} : data;
    if (clear) setData({});
    setFailure('');
    router.get(
      '/tickets',
      { ...next, mine: next.mine ? 1 : 0, overdue: next.overdue ? 1 : 0 },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => setBusy(true),
        onFinish: () => setBusy(false),
        onError: () => setFailure('Filters could not be applied. Check the fields and try again.'),
        onHttpException: () => {
          setFailure('Tickets could not be loaded. Try applying the filters again.');
          return false;
        },
        onNetworkError: () => {
          setFailure('Connection failed. Try applying the filters again.');
          return false;
        },
      },
    );
  }
  const selectClass =
    'min-h-11 w-full rounded-control border border-control-border bg-surface px-3 text-sm';
  return (
    <AppLayout title="Tickets">
      <Head title="Tickets" />
      <Link
        href="/tickets/create"
        className="inline-flex min-h-11 items-center rounded-control bg-primary px-4 text-sm font-medium text-primary-foreground"
      >
        New ticket
      </Link>
      <form
        className="space-y-4"
        onSubmit={(e) => {
          e.preventDefault();
          visit();
        }}
      >
        <FormErrors errors={errors} />
        {failure && (
          <p role="alert" className="text-destructive">
            {failure}
          </p>
        )}
        <FormField
          id="search"
          label="Search titles"
          maxLength={100}
          value={data.search ?? ''}
          onChange={(e) => setData({ ...data, search: e.target.value })}
          error={errors.search}
        />
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
          {(['status', 'priority', 'category', 'assignee', 'sort'] as const).map((key) => {
            const options =
              key === 'status'
                ? statuses
                : key === 'sort'
                  ? [
                      { value: 'newest', label: 'Newest first' },
                      { value: 'oldest', label: 'Oldest first' },
                      { value: 'due', label: 'Due date' },
                    ]
                  : (key === 'priority'
                      ? priorities
                      : key === 'category'
                        ? categories
                        : assignees
                    ).map((o) => ({ value: String(o.id), label: o.name }));
            return (
              <FormField
                key={key}
                id={key}
                label={key[0].toUpperCase() + key.slice(1)}
                error={errors[key]}
              >
                <select
                  id={key}
                  className={selectClass}
                  value={data[key] ?? (key === 'sort' ? 'newest' : '')}
                  onChange={(e) => setData({ ...data, [key]: e.target.value })}
                  aria-invalid={!!errors[key]}
                  aria-describedby={errors[key] ? `${key}-error` : undefined}
                >
                  {key !== 'sort' && <option value="">All</option>}
                  {options.map((o) => (
                    <option key={o.value} value={o.value}>
                      {o.label}
                    </option>
                  ))}
                </select>
              </FormField>
            );
          })}
        </div>
        <div className="flex flex-wrap items-center gap-4">
          {(['mine', 'overdue'] as const).map((key) => (
            <label key={key} className="flex min-h-11 items-center gap-2">
              <input
                type="checkbox"
                checked={!!data[key]}
                onChange={(e) => setData({ ...data, [key]: e.target.checked })}
              />
              {key === 'mine' ? 'Mine' : 'Overdue'}
            </label>
          ))}
          <Button type="submit" disabled={busy}>
            {busy ? 'Loading tickets…' : 'Apply filters'}
          </Button>
          <Button type="button" variant="secondary" disabled={busy} onClick={() => visit(true)}>
            Clear filters
          </Button>
        </div>
      </form>
      <p role="status" className="sr-only">
        {busy ? 'Loading tickets' : ''}
      </p>
      <div aria-busy={busy}>
        {!tickets.data.length ? (
          <EmptyState
            title={
              Object.values(filters).some(Boolean)
                ? 'No tickets match these filters'
                : 'No tickets yet'
            }
            description={
              Object.values(filters).some(Boolean)
                ? 'Try adjusting or clearing your filters to see more tickets.'
                : 'Submitted IT requests will appear here.'
            }
            action={
              Object.values(filters).some(Boolean)
                ? {
                    label: 'Clear filters',
                    onClick: () => visit(true),
                    disabled: busy,
                  }
                : {
                    label: 'New ticket',
                    href: '/tickets/create',
                  }
            }
          />
        ) : (
          <div
            role="region"
            aria-label="Ticket queue"
            tabIndex={0}
            className="overflow-x-auto rounded-panel border border-border bg-surface"
          >
            <table className="w-full text-left text-sm">
              <caption className="sr-only">Tickets</caption>
              <thead className="border-b border-border bg-muted">
                <tr>
                  {[
                    'Reference / title',
                    'Status',
                    'Priority',
                    'Requester',
                    'Assignee',
                    'Due date',
                  ].map((h) => (
                    <th key={h} scope="col" className="px-4 py-3">
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {tickets.data.map((ticket) => (
                  <tr key={ticket.id} className="border-b border-border">
                    <td className="min-w-52 px-4 py-3">
                      <Link
                        href={`/tickets/${ticket.id}`}
                        className="inline-block min-h-11 text-primary underline"
                      >
                        {ticket.number}
                        <span className="block break-words text-foreground">{ticket.title}</span>
                      </Link>
                    </td>
                    <td className="px-4 py-3">
                      <StatusBadge status={ticket.status} />
                    </td>
                    <td className="px-4 py-3">
                      <PriorityBadge priority={ticket.priority} priorities={priorities} />
                    </td>
                    <td className="px-4 py-3">{ticket.requester?.name ?? 'Deleted user'}</td>
                    <td className="px-4 py-3">{ticket.assignee?.name ?? 'Unassigned'}</td>
                    <td className="px-4 py-3">
                      <DueDate
                        dueAt={ticket.due_at}
                        referenceTime={generatedAt}
                        overdue={ticket.overdue}
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <nav aria-label="Ticket pagination" className="mt-4 flex flex-wrap items-center gap-4">
          <span className="text-sm text-muted-foreground">
            {tickets.from ?? 0}–{tickets.to ?? 0} of {tickets.total}
          </span>
          {tickets.prev_page_url && (
            <Link
              className="inline-flex min-h-11 items-center text-primary underline"
              href={tickets.prev_page_url}
            >
              Previous
            </Link>
          )}
          {tickets.next_page_url && (
            <Link
              className="inline-flex min-h-11 items-center text-primary underline"
              href={tickets.next_page_url}
            >
              Next
            </Link>
          )}
        </nav>
      </div>
    </AppLayout>
  );
}
