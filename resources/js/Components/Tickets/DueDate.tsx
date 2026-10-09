import { usePage } from '@inertiajs/react';
import { durationFrom, formatAbsoluteDate, formatRelativeDue } from '../../Lib/dateFormatting';
import type { SharedProps } from '../../Types';

type Props = { dueAt: string; referenceTime: string; overdue: boolean };

export function OverdueBadge({ dueAt, referenceTime, overdue }: Props) {
  if (!overdue) return null;
  return (
    <span className="inline-flex items-center gap-1 rounded-md bg-destructive-soft px-2 py-1 text-sm font-medium text-destructive">
      <svg
        aria-hidden="true"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        className="size-4 shrink-0"
      >
        <path d="M12 3 2 21h20L12 3Z" />
        <path d="M12 9v5m0 3v1" />
      </svg>
      Overdue by {durationFrom(dueAt, referenceTime)}
    </span>
  );
}

export function DueDate({
  dueAt,
  referenceTime,
  overdue,
  showOverdue = true,
}: Props & { showOverdue?: boolean }) {
  const { timeZone } = usePage<SharedProps>().props;
  return (
    <span className="inline-flex flex-col items-start gap-1">
      <time dateTime={dueAt} title={formatAbsoluteDate(dueAt, timeZone)}>
        {formatRelativeDue(dueAt, referenceTime)}
      </time>
      {showOverdue && (
        <OverdueBadge dueAt={dueAt} referenceTime={referenceTime} overdue={overdue} />
      )}
    </span>
  );
}
