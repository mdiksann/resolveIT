import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

export interface ErrorLayoutProps {
  status: number;
  title: string;
  description: string;
  children?: ReactNode;
}

export function ErrorLayout({ status, title, description, children }: ErrorLayoutProps) {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-background p-4 text-foreground">
      <Head title={`${status} · ${title}`} />
      <div className="w-full max-w-md space-y-6 rounded-panel border border-border bg-surface p-6 sm:p-8">
        <div className="flex items-center justify-between border-b border-border pb-4">
          <Link
            href="/"
            className="flex items-center gap-2 text-base font-semibold text-foreground"
          >
            <span className="flex size-7 items-center justify-center rounded-control bg-primary text-xs font-bold text-primary-foreground">
              R
            </span>
            <span>ResolveIT</span>
          </Link>
          <span className="rounded-badge bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
            {status}
          </span>
        </div>

        <div className="space-y-2">
          <h1 className="text-xl font-semibold text-foreground sm:text-2xl">{title}</h1>
          <p className="text-sm text-muted-foreground">{description}</p>
        </div>

        {children}

        <div className="border-t border-border pt-4">
          <Link
            href="/"
            className="inline-flex min-h-11 items-center rounded-control bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary-hover focus-visible:outline-2"
          >
            Return to application
          </Link>
        </div>
      </div>
    </div>
  );
}
