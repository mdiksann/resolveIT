import { Head, Link } from '@inertiajs/react';
import { DueDate } from '../Components/Tickets/DueDate';
import AppLayout from '../Layouts/AppLayout';
import { Table, TableCell, TableHead } from '../Components/ui/table';
import { StatusBadge, PriorityBadge } from '../Components/Tickets/Badges';
import { EmptyState } from '../Components/EmptyState';
import { ConfigurationPagination } from '../Components/ConfigurationPagination';
import type { DashboardProps, TicketStatus } from '../Types';

const statusSegmentColors: Record<TicketStatus, string> = {
  OPEN: 'bg-info',
  ASSIGNED: 'bg-primary',
  IN_PROGRESS: 'bg-warning',
  RESOLVED: 'bg-success',
  CLOSED: 'bg-muted-foreground/40',
};

export default function Dashboard({
  metrics,
  byStatus,
  byPriority,
  assignments,
  generatedAt,
}: DashboardProps) {
  const totalByStatus = byStatus.reduce((acc, s) => acc + s.total, 0);
  const totalByPriority = byPriority.reduce((acc, p) => acc + p.total, 0);

  const priorityRanks = [...new Set(byPriority.map((p) => p.rank))].sort((a, b) => a - b);
  const getPrioritySegmentColor = (rank: number) => {
    if (priorityRanks.length > 1 && rank === priorityRanks[0]) return 'bg-destructive';
    if (priorityRanks.length > 2 && rank === priorityRanks[1]) return 'bg-warning';
    return 'bg-primary/50';
  };

  const overviewTiles = [
    {
      label: 'Open work',
      count: metrics.open,
      note: 'Open, Assigned, In Progress',
      href: '/tickets',
    },
    {
      label: 'Unassigned',
      count: metrics.unassigned,
      note: 'Awaiting triage',
      href: '/tickets?assignee=',
    },
    {
      label: 'Overdue',
      count: metrics.overdue,
      note: 'SLA target exceeded',
      isAlert: metrics.overdue > 0,
      href: '/tickets?overdue=1',
    },
    {
      label: 'Resolved last 7 days',
      count: metrics.resolved_last_7_days,
      note: 'Recent resolutions',
      href: '/tickets?status=RESOLVED',
    },
  ];

  return (
    <AppLayout title="Dashboard">
      <Head title="Dashboard" />

      {/* Workload overview using the same soft brand accent as the landing page. */}
      <section
        aria-labelledby="overview-heading"
        className="space-y-6 rounded-panel border border-border bg-overview p-5 sm:p-6"
      >
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 id="overview-heading" className="text-xl font-bold tracking-tight text-foreground">
              Overview
            </h2>
            <p className="mt-1 text-sm text-muted-foreground">
              Keep requests moving. See what needs your team’s attention.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Link
              href="/tickets?overdue=1"
              className="inline-flex min-h-11 items-center justify-center rounded-full border border-control-border bg-surface px-5 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-muted focus-visible:outline-2"
            >
              View overdue tickets
            </Link>
            <Link
              href="/tickets"
              className="inline-flex min-h-11 items-center justify-center rounded-full bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary-hover focus-visible:outline-2"
            >
              View ticket queue
            </Link>
          </div>
        </div>

        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          {overviewTiles.map((tile) => (
            <Link
              key={tile.label}
              href={tile.href}
              className="group block rounded-panel border border-border bg-surface p-5 transition-colors hover:border-primary focus-visible:outline-2"
            >
              <dl>
                <div className="flex items-center justify-between gap-2">
                  <dt className="text-sm font-medium text-muted-foreground">{tile.label}</dt>
                  {tile.isAlert && (
                    <span className="flex size-2 rounded-full bg-destructive" aria-hidden="true" />
                  )}
                </div>
                <dd
                  className={`mt-3 text-4xl font-bold tracking-tight tabular-nums ${tile.isAlert ? 'text-destructive' : 'text-foreground group-hover:text-primary'}`}
                >
                  {tile.count}
                </dd>
              </dl>
              <p className="mt-2 text-xs text-muted-foreground">{tile.note}</p>
            </Link>
          ))}
        </div>
      </section>

      {/* Two Distribution Panels: Status and Priority */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Status Distribution Panel */}
        <section
          aria-labelledby="status-heading"
          className="space-y-4 rounded-panel border border-border bg-surface p-5"
        >
          <div className="flex flex-wrap items-center justify-between gap-3">
            <h2 id="status-heading" className="text-lg font-bold tracking-tight text-foreground">
              Tickets by status
            </h2>
            <span className="text-xs text-muted-foreground tabular-nums">
              Total: {totalByStatus} tickets
            </span>
          </div>

          {/* Segmented Distribution Bar */}
          {totalByStatus > 0 && (
            <div
              role="img"
              aria-label="Status distribution visual bar"
              className="flex h-2.5 w-full overflow-hidden rounded-full bg-muted"
            >
              {byStatus.map((s) => {
                const percent = (s.total / totalByStatus) * 100;
                if (percent <= 0) return null;
                return (
                  <div
                    key={s.value}
                    style={{ width: `${percent}%` }}
                    className={`${statusSegmentColors[s.value]} transition-all`}
                    title={`${s.value}: ${s.total} (${Math.round(percent)}%)`}
                  />
                );
              })}
            </div>
          )}

          <Table className="[&_td]:py-3 [&_th]:py-3" aria-label="Counts by status">
            <thead className="bg-muted">
              <tr>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Share</TableHead>
                <TableHead className="text-right">Tickets</TableHead>
              </tr>
            </thead>
            <tbody>
              {byStatus.map((s) => {
                const share = totalByStatus > 0 ? Math.round((s.total / totalByStatus) * 100) : 0;
                return (
                  <tr key={s.value} className="hover:bg-muted/50">
                    <TableCell>
                      <Link
                        className="inline-flex min-h-9 items-center text-primary hover:underline"
                        href={`/tickets?status=${s.value}`}
                      >
                        <StatusBadge status={s.value} />
                      </Link>
                    </TableCell>
                    <TableCell className="text-right text-xs text-muted-foreground tabular-nums">
                      {share}%
                    </TableCell>
                    <TableCell className="text-right font-medium tabular-nums text-foreground">
                      {s.total}
                    </TableCell>
                  </tr>
                );
              })}
            </tbody>
          </Table>
        </section>

        {/* Priority Distribution Panel */}
        <section
          aria-labelledby="priority-heading"
          className="space-y-4 rounded-panel border border-border bg-surface p-5"
        >
          <div className="flex flex-wrap items-center justify-between gap-3">
            <h2 id="priority-heading" className="text-lg font-bold tracking-tight text-foreground">
              Tickets by priority
            </h2>
            <span className="text-xs text-muted-foreground tabular-nums">
              Total: {totalByPriority} tickets
            </span>
          </div>

          {/* Segmented Distribution Bar */}
          {totalByPriority > 0 && (
            <div
              role="img"
              aria-label="Priority distribution visual bar"
              className="flex h-2.5 w-full overflow-hidden rounded-full bg-muted"
            >
              {byPriority.map((p) => {
                const percent = (p.total / totalByPriority) * 100;
                if (percent <= 0) return null;
                return (
                  <div
                    key={p.id}
                    style={{ width: `${percent}%` }}
                    className={`${getPrioritySegmentColor(p.rank)} transition-all`}
                    title={`${p.name}: ${p.total} (${Math.round(percent)}%)`}
                  />
                );
              })}
            </div>
          )}

          {byPriority.length ? (
            <Table className="[&_td]:py-3 [&_th]:py-3" aria-label="Counts by priority">
              <thead className="bg-muted">
                <tr>
                  <TableHead>Priority</TableHead>
                  <TableHead className="text-right">Share</TableHead>
                  <TableHead className="text-right">Tickets</TableHead>
                </tr>
              </thead>
              <tbody>
                {byPriority.map((p) => {
                  const share =
                    totalByPriority > 0 ? Math.round((p.total / totalByPriority) * 100) : 0;
                  return (
                    <tr key={p.id} className="hover:bg-muted/50">
                      <TableCell>
                        <Link
                          className="inline-flex min-h-9 items-center text-primary hover:underline"
                          href={`/tickets?priority=${p.id}`}
                        >
                          <PriorityBadge priority={p} priorities={byPriority} />
                        </Link>
                      </TableCell>
                      <TableCell className="text-right text-xs text-muted-foreground tabular-nums">
                        {share}%
                      </TableCell>
                      <TableCell className="text-right font-medium tabular-nums text-foreground">
                        {p.total}
                      </TableCell>
                    </tr>
                  );
                })}
              </tbody>
            </Table>
          ) : (
            <p className="text-sm text-muted-foreground">No priorities configured.</p>
          )}
        </section>
      </div>

      {/* Assignments Table Section */}
      <section
        aria-labelledby="assignments-heading"
        className="space-y-4 rounded-panel border border-border bg-surface p-5"
      >
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2
              id="assignments-heading"
              className="text-lg font-bold tracking-tight text-foreground"
            >
              My open assignments
            </h2>
            <p className="text-xs text-muted-foreground">
              Tickets currently assigned to you awaiting investigation or resolution.
            </p>
          </div>
          <span className="text-xs text-muted-foreground tabular-nums">
            {assignments.total} total
          </span>
        </div>

        {assignments.data.length ? (
          <div className="overflow-hidden rounded-panel border border-border bg-surface">
            <Table className="[&_td]:py-3 [&_th]:py-3" aria-label="My open assignments">
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
                  <tr key={t.id} className="hover:bg-muted/50">
                    <TableCell className="min-w-52">
                      <Link
                        href={`/tickets/${t.id}`}
                        className="inline-block min-h-10 text-primary underline"
                      >
                        <span className="font-medium">{t.number}</span>
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
          <EmptyState
            title="No open assignments"
            description="You have no open assignments on this page."
            action={{
              label: 'View ticket queue',
              href: '/tickets',
            }}
          />
        )}
        <ConfigurationPagination page={assignments} label="Assignments" />
      </section>
    </AppLayout>
  );
}
