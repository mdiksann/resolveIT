import { Head, Link } from '@inertiajs/react';
import { DueDate } from '../Components/Tickets/DueDate';
import AppLayout from '../Layouts/AppLayout';
import { Table, TableCell, TableHead } from '../Components/ui/table';
import { StatusBadge, PriorityBadge } from '../Components/Tickets/Badges';
import { ConfigurationPagination } from '../Components/ConfigurationPagination';
import type { DashboardProps } from '../Types';
export default function Dashboard({
  metrics,
  byStatus,
  byPriority,
  assignments,
  generatedAt,
}: DashboardProps) {
  const counters = [
    ['Open work', metrics.open],
    ['Unassigned', metrics.unassigned],
    ['Overdue', metrics.overdue],
    ['Resolved in last 7 days', metrics.resolved_last_7_days],
  ] as const;
  return (
    <AppLayout title="Dashboard">
      <Head title="Dashboard" />
      <p className="text-sm text-muted-foreground">
        Open work includes Open, Assigned and In Progress. Recent resolutions include tickets later
        closed.
      </p>
      <dl className="grid gap-4 rounded-panel border border-border bg-surface p-5 sm:grid-cols-2 lg:grid-cols-4">
        {counters.map(([label, count]) => (
          <div key={label}>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="mt-1 text-2xl font-semibold tabular-nums">{count}</dd>
          </div>
        ))}
      </dl>
      <div className="flex flex-wrap gap-4">
        <Link
          className="inline-flex min-h-11 items-center text-primary underline"
          href="/tickets?overdue=1"
        >
          View overdue tickets
        </Link>
        <Link className="inline-flex min-h-11 items-center text-primary underline" href="/tickets">
          View ticket queue
        </Link>
      </div>
      <div className="grid gap-6 lg:grid-cols-2">
        <section aria-labelledby="status-heading">
          <h2 id="status-heading" className="mb-3 text-lg font-semibold">
            Tickets by status
          </h2>
          <Table aria-label="Counts by status">
            <thead className="bg-muted">
              <tr>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Tickets</TableHead>
              </tr>
            </thead>
            <tbody>
              {byStatus.map((s) => (
                <tr key={s.value}>
                  <TableCell>
                    <Link
                      className="inline-flex min-h-11 items-center text-primary underline"
                      href={`/tickets?status=${s.value}`}
                    >
                      <StatusBadge status={s.value} />
                    </Link>
                  </TableCell>
                  <TableCell className="text-right tabular-nums">{s.total}</TableCell>
                </tr>
              ))}
            </tbody>
          </Table>
        </section>
        <section aria-labelledby="priority-heading">
          <h2 id="priority-heading" className="mb-3 text-lg font-semibold">
            Tickets by priority
          </h2>
          {byPriority.length ? (
            <Table aria-label="Counts by priority">
              <thead className="bg-muted">
                <tr>
                  <TableHead>Priority</TableHead>
                  <TableHead className="text-right">Tickets</TableHead>
                </tr>
              </thead>
              <tbody>
                {byPriority.map((p) => (
                  <tr key={p.id}>
                    <TableCell>
                      <Link
                        className="inline-flex min-h-11 items-center text-primary underline"
                        href={`/tickets?priority=${p.id}`}
                      >
                        <PriorityBadge priority={p} priorities={byPriority} />
                      </Link>
                    </TableCell>
                    <TableCell className="text-right tabular-nums">{p.total}</TableCell>
                  </tr>
                ))}
              </tbody>
            </Table>
          ) : (
            <p className="text-sm text-muted-foreground">No priorities configured.</p>
          )}
        </section>
      </div>
      <section aria-labelledby="assignments-heading">
        <h2 id="assignments-heading" className="mb-3 text-lg font-semibold">
          My open assignments
        </h2>
        {assignments.data.length ? (
          <div className="overflow-hidden rounded-panel border border-border bg-surface">
            <Table aria-label="My open assignments">
              <thead className="bg-muted">
                <tr>
                  <TableHead>Ticket</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Priority</TableHead>
                  <TableHead>Due</TableHead>
                </tr>
              </thead>
              <tbody>
                {assignments.data.map((t) => (
                  <tr key={t.id}>
                    <TableCell className="min-w-52">
                      <Link
                        href={`/tickets/${t.id}`}
                        className="inline-block min-h-11 text-primary underline"
                      >
                        {t.number}
                        <span className="block break-words text-foreground">{t.title}</span>
                      </Link>
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={t.status} />
                    </TableCell>
                    <TableCell>
                      <PriorityBadge priority={t.priority} priorities={byPriority} />
                    </TableCell>
                    <TableCell>
                      <DueDate dueAt={t.due_at} referenceTime={generatedAt} overdue={t.overdue} />
                    </TableCell>
                  </tr>
                ))}
              </tbody>
            </Table>
          </div>
        ) : (
          <p className="py-4 text-sm text-muted-foreground">
            You have no open assignments on this page.
          </p>
        )}
        <ConfigurationPagination page={assignments} label="Assignments" />
      </section>
    </AppLayout>
  );
}
