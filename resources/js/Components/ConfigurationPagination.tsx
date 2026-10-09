import { Link } from '@inertiajs/react';
import { useState } from 'react';
import type { Paginated } from '../Types';
export function ConfigurationPagination({
  page,
  label,
}: {
  page: Omit<Paginated<never>, 'data'>;
  label: string;
}) {
  const [busy, setBusy] = useState(false);
  const [failure, setFailure] = useState('');
  const fail = () => {
    setFailure(`Could not load ${label}. Try the page link again.`);
    return false;
  };
  const options = {
    preserveScroll: true,
    onBefore: () => !busy,
    onStart: () => {
      setFailure('');
      setBusy(true);
    },
    onFinish: () => setBusy(false),
    onHttpException: fail,
    onNetworkError: fail,
  };
  return (
    <div className="space-y-3">
      {failure && (
        <p role="alert" className="text-sm text-destructive">
          {failure}
        </p>
      )}
      <p role="status" className="text-sm text-muted-foreground">
        {busy ? `Loading ${label}…` : `${page.from ?? 0}–${page.to ?? 0} of ${page.total}`}
      </p>
      <nav aria-label={`${label} pagination`} aria-busy={busy} className="flex flex-wrap gap-4">
        {page.prev_page_url && (
          <Link
            className="inline-flex min-h-11 items-center text-primary underline"
            href={page.prev_page_url}
            {...options}
          >
            Previous
          </Link>
        )}
        {page.next_page_url && (
          <Link
            className="inline-flex min-h-11 items-center text-primary underline"
            href={page.next_page_url}
            {...options}
          >
            Next
          </Link>
        )}
      </nav>
    </div>
  );
}
