import { Head, Link, router, usePage } from '@inertiajs/react';
import { type ReactNode, useEffect, useState } from 'react';
import type { SharedProps } from '../Types';
import { FlashMessages } from '../Components/FlashMessages';
import { Button } from '../Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '../Components/ui/dropdown-menu';

interface NavItem {
  href: string;
  label: string;
  icon: (active: boolean) => ReactNode;
}

export default function AppLayout({ title, children }: { title: string; children: ReactNode }) {
  const { appName, auth } = usePage<SharedProps>().props;
  const { url } = usePage();
  const [navigating, setNavigating] = useState(false);

  useEffect(() => {
    const removeStart = router.on('start', () => setNavigating(true));
    const removeFinish = router.on('finish', () => setNavigating(false));
    return () => {
      removeStart();
      removeFinish();
    };
  }, []);

  const currentPath = url.split('?')[0];

  const isRouteActive = (href: string) => {
    if (href === '/tickets/create') return currentPath === '/tickets/create';
    if (href === '/tickets') {
      return (
        currentPath === '/tickets' ||
        (currentPath.startsWith('/tickets/') && currentPath !== '/tickets/create')
      );
    }
    return currentPath === href || currentPath.startsWith(`${href}/`);
  };

  const primaryNavigation: NavItem[] = [
    ...(auth.can.viewAnyTicket
      ? [
          {
            href: '/dashboard',
            label: 'Dashboard',
            icon: (active: boolean) => (
              <svg
                aria-hidden="true"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth={active ? '2.25' : '1.75'}
                className="size-4 shrink-0"
              >
                <rect width="7" height="9" x="3" y="3" rx="1" />
                <rect width="7" height="5" x="14" y="3" rx="1" />
                <rect width="7" height="9" x="14" y="12" rx="1" />
                <rect width="7" height="5" x="3" y="16" rx="1" />
              </svg>
            ),
          },
        ]
      : []),
    {
      href: '/tickets',
      label: auth.can.viewAnyTicket ? 'Ticket queue' : 'My tickets',
      icon: (active: boolean) => (
        <svg
          aria-hidden="true"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth={active ? '2.25' : '1.75'}
          className="size-4 shrink-0"
        >
          <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
          <path d="M13 5v2" />
          <path d="M13 17v2" />
          <path d="M13 11v2" />
        </svg>
      ),
    },
    {
      href: '/tickets/create',
      label: 'New ticket',
      icon: (active: boolean) => (
        <svg
          aria-hidden="true"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth={active ? '2.25' : '1.75'}
          className="size-4 shrink-0"
        >
          <rect width="18" height="18" x="3" y="3" rx="2" />
          <path d="M12 8v8" />
          <path d="M8 12h8" />
        </svg>
      ),
    },
  ];

  const adminNavigation: NavItem[] = [
    ...(auth.can.manageCategories
      ? [
          {
            href: '/admin/categories',
            label: 'Categories',
            icon: (active: boolean) => (
              <svg
                aria-hidden="true"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth={active ? '2.25' : '1.75'}
                className="size-4 shrink-0"
              >
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" />
                <polyline points="9 22 9 12 15 12 15 22" />
              </svg>
            ),
          },
        ]
      : []),
    ...(auth.can.managePriorities
      ? [
          {
            href: '/admin/priorities',
            label: 'Priorities',
            icon: (active: boolean) => (
              <svg
                aria-hidden="true"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth={active ? '2.25' : '1.75'}
                className="size-4 shrink-0"
              >
                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
                <line x1="4" x2="4" y1="22" y2="15" />
              </svg>
            ),
          },
        ]
      : []),
    ...(auth.can.manageUsers
      ? [
          {
            href: '/admin/users',
            label: 'Users',
            icon: (active: boolean) => (
              <svg
                aria-hidden="true"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth={active ? '2.25' : '1.75'}
                className="size-4 shrink-0"
              >
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
              </svg>
            ),
          },
        ]
      : []),
  ];

  return (
    <div className="min-h-screen bg-background md:grid md:grid-cols-[240px_1fr]">
      <Head title={title} />
      {navigating && (
        <div
          role="progressbar"
          aria-label="Loading page"
          className="fixed top-0 right-0 left-0 z-50 h-0.5 bg-primary animate-pulse"
        />
      )}
      <div role="status" aria-live="polite" className="sr-only">
        {navigating ? 'Loading page…' : ''}
      </div>
      <a
        href="#main"
        className="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-control focus:bg-surface focus:p-3 focus:text-primary focus:shadow-md"
      >
        Skip to content
      </a>
      <aside className="border-b border-border bg-surface p-4 md:sticky md:top-0 md:h-screen md:overflow-y-auto md:border-r md:border-b-0 md:pt-6">
        <div className="mb-8 px-3">
          <Link
            href="/"
            className="inline-flex min-h-11 items-center text-[28px] leading-none font-extrabold tracking-[-0.07em] text-brand"
          >
            <span>{appName}</span>
          </Link>
        </div>
        <nav aria-label="Main navigation" className="flex flex-wrap gap-1 md:flex-col">
          {primaryNavigation.map(({ href, label, icon }) => {
            const active = isRouteActive(href);
            return (
              <Link
                key={href}
                href={href}
                aria-current={active ? 'page' : undefined}
                className={`flex min-h-11 items-center gap-3 rounded-full px-4 py-2 text-sm transition-colors ${
                  active
                    ? 'bg-primary-soft font-semibold text-primary'
                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                }`}
              >
                {icon(active)}
                <span>{label}</span>
              </Link>
            );
          })}

          {adminNavigation.length > 0 && (
            <div className="w-full pt-4 md:pt-6">
              <p className="px-3 pb-2 text-xs font-medium text-muted-foreground">Administration</p>
              <div className="flex flex-wrap gap-1 md:flex-col">
                {adminNavigation.map(({ href, label, icon }) => {
                  const active = isRouteActive(href);
                  return (
                    <Link
                      key={href}
                      href={href}
                      aria-current={active ? 'page' : undefined}
                      className={`flex min-h-11 items-center gap-3 rounded-full px-4 py-2 text-sm transition-colors ${
                        active
                          ? 'bg-primary-soft font-semibold text-primary'
                          : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                      }`}
                    >
                      {icon(active)}
                      <span>{label}</span>
                    </Link>
                  );
                })}
              </div>
            </div>
          )}
        </nav>
      </aside>

      <div className="flex min-w-0 flex-col">
        <header className="flex h-20 shrink-0 items-center justify-between gap-4 border-b border-border bg-surface px-5 md:px-8">
          <span className="min-w-0 truncate text-sm font-medium text-foreground">{title}</span>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="secondary"
                className="min-h-11 max-w-[65%] gap-2 rounded-full px-5 text-sm"
                aria-label={`User menu for ${auth.user?.name ?? 'Account'}`}
              >
                <span className="truncate">{auth.user?.name}</span>
                <svg
                  aria-hidden="true"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                  className="size-4 text-muted-foreground"
                >
                  <path
                    fillRule="evenodd"
                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                    clipRule="evenodd"
                  />
                </svg>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
              <DropdownMenuItem asChild>
                <Link href="/settings/profile" className="w-full">
                  Profile settings
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem
                onSelect={() => router.post('/logout')}
                className="mt-1 border-t border-border text-destructive focus:bg-destructive-soft focus:text-destructive"
              >
                Log out
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </header>

        <main id="main" tabIndex={-1} className="min-w-0 flex-1 space-y-6 p-4 md:p-8">
          <h1 className="break-words text-3xl font-bold tracking-[-0.04em] text-foreground">
            {title}
          </h1>
          <FlashMessages />
          {children}
        </main>
      </div>
    </div>
  );
}
