import type { PriorityOption, TicketStatus } from '../../Types';
const labels: Record<TicketStatus, string> = {
  OPEN: 'Open',
  ASSIGNED: 'Assigned',
  IN_PROGRESS: 'In Progress',
  RESOLVED: 'Resolved',
  CLOSED: 'Closed',
};
const colors: Record<TicketStatus, string> = {
  OPEN: 'bg-info-soft text-info',
  ASSIGNED: 'bg-primary-soft text-primary',
  IN_PROGRESS: 'bg-warning-soft text-warning',
  RESOLVED: 'bg-success-soft text-success',
  CLOSED: 'bg-muted text-muted-foreground',
};
const badge =
  'inline-flex items-center rounded-badge px-2 py-1 text-xs leading-[18px] font-medium whitespace-nowrap';
export function StatusBadge({ status }: { status: TicketStatus }) {
  return <span className={`${badge} ${colors[status]}`}>{labels[status]}</span>;
}
export function PriorityBadge({
  priority,
  priorities,
}: {
  priority: PriorityOption;
  priorities: PriorityOption[];
}) {
  const ranks = [...new Set(priorities.map((p) => p.rank))].sort((a, b) => a - b);
  const color =
    ranks.length > 1 && priority.rank === ranks[0]
      ? 'bg-destructive-soft text-destructive'
      : ranks.length > 2 && priority.rank === ranks[1]
        ? 'bg-warning-soft text-warning'
        : 'bg-muted text-foreground';
  return <span className={`${badge} ${color}`}>{priority.name}</span>;
}
