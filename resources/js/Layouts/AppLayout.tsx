import { Link, router, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { SharedProps } from '../Types';
import { FlashMessages } from '../Components/FlashMessages';
import { Button } from '../Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '../Components/ui/dropdown-menu';
export default function AppLayout({ title, children }: { title: string; children: ReactNode }) {
  const { appName, auth } = usePage<SharedProps>().props;
  const { url } = usePage();
  const navigation = [
    { href: '/tickets', label: auth.can.viewAnyTicket ? 'Ticket queue' : 'My tickets' },
    { href: '/tickets/create', label: 'New ticket' },
    ...(auth.can.viewAnyTicket ? [{ href: '/dashboard', label: 'Dashboard' }] : []),
    { href: '/settings/profile', label: 'Profile' },
    ...(auth.can.manageCategories ? [{ href: '/admin/categories', label: 'Categories' }] : []),
    ...(auth.can.managePriorities ? [{ href: '/admin/priorities', label: 'Priorities' }] : []),
    ...(auth.can.manageUsers ? [{ href: '/admin/users', label: 'Users' }] : []),
  ];
  return (
    <div className="min-h-screen md:grid md:grid-cols-[220px_1fr]">
      <a
        href="#main"
        className="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:bg-surface focus:p-3"
      >
        Skip to content
      </a>
      <aside className="border-b border-border bg-surface p-4 md:border-r md:border-b-0">
        <p className="mb-5 px-2 text-sm font-semibold">{appName}</p>
        <nav aria-label="Main navigation" className="flex flex-wrap gap-1 md:flex-col">
          {navigation.map(({ href, label }) => (
            <Link
              key={href}
              href={href}
              aria-current={url.split('?')[0] === href ? 'page' : undefined}
              className={`rounded-control px-3 py-3 text-sm ${url.split('?')[0] === href ? 'bg-muted font-medium text-primary' : 'hover:bg-muted'}`}
            >
              {label}
            </Link>
          ))}
        </nav>
      </aside>
      <div className="min-w-0">
        <header className="flex min-h-16 items-center justify-between border-b border-border bg-surface px-5">
          <span className="min-w-0 truncate text-sm text-muted-foreground">{title}</span>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="secondary">{auth.user?.name}</Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent>
              <DropdownMenuItem asChild>
                <Link href="/settings/profile">Profile settings</Link>
              </DropdownMenuItem>
              <DropdownMenuItem onSelect={() => router.post('/logout')}>Log out</DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </header>
        <main id="main" tabIndex={-1} className="mx-auto max-w-5xl space-y-6 p-5 md:p-8">
          <h1 className="break-words text-2xl font-semibold">{title}</h1>
          <FlashMessages />
          {children}
        </main>
      </div>
    </div>
  );
}
