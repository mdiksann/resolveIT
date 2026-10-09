import { Link } from '@inertiajs/react';
import type { Paginated, TicketActivity } from '../../Types';
export function ActivityTimeline({ activities }: { activities: Paginated<TicketActivity> }) {
  return (
    <section className="border-t border-border pt-5" aria-labelledby="activity-heading">
      <h2 id="activity-heading" className="mb-3 font-semibold">
        Activity
      </h2>
      {!activities.data.length && <p className="text-sm text-muted-foreground">No activity yet.</p>}
      <ol className="space-y-4">
        {activities.data.map((a) => (
          <li key={a.id} className="border-l-2 border-border pl-4 text-sm">
            <p className="break-words">
              <span className="font-medium">{a.actor?.name ?? 'System'}</span> ·{' '}
              {a.summary || 'Ticket activity'}
            </p>
            <time
              className="text-muted-foreground"
              dateTime={a.created_at}
              title={new Date(a.created_at).toLocaleString()}
            >
              {a.relative_time} · {new Date(a.created_at).toLocaleString()}
            </time>
          </li>
        ))}
      </ol>
      <nav aria-label="Activity pagination" className="mt-3 flex gap-4">
        {activities.prev_page_url && (
          <Link
            className="inline-flex min-h-11 items-center text-primary underline"
            preserveScroll
            href={activities.prev_page_url}
          >
            Newer activity
          </Link>
        )}
        {activities.next_page_url && (
          <Link
            className="inline-flex min-h-11 items-center text-primary underline"
            preserveScroll
            href={activities.next_page_url}
          >
            Older activity
          </Link>
        )}
      </nav>
    </section>
  );
}
