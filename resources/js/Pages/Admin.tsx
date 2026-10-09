import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import AppLayout from '../Layouts/AppLayout';
import { FormField } from '../Components/FormField';
import { Button } from '../Components/ui/button';
import { Table, TableCell, TableHead } from '../Components/ui/table';
import type { AdminUser, Paginated, RoleOption, SharedProps } from '../Types';

type Props = { users: Paginated<AdminUser>; roles: RoleOption[] };

function RoleEditor({ user, roles }: { user: AdminUser; roles: RoleOption[] }) {
  const form = useForm({ role: user.role });
  const errorSummary = useRef<HTMLDivElement>(null);
  const fieldId = `role-${user.id}`;
  const focusError = () => requestAnimationFrame(() => errorSummary.current?.focus());
  return (
    <form
      aria-label={`Change role for ${user.name}`}
      onSubmit={(event) => {
        event.preventDefault();
        form.patch(`/admin/users/${user.id}/role`, {
          preserveScroll: true,
          onError: focusError,
          onNetworkError: () => {
            form.setError(
              'role',
              'Could not confirm the change. Reload to check the saved role before retrying.',
            );
            focusError();
            return false;
          },
          onHttpException: () => {
            form.setError(
              'role',
              'Could not confirm the change. Reload to check the saved role before retrying.',
            );
            focusError();
            return false;
          },
        });
      }}
      aria-busy={form.processing}
      className="min-w-64 space-y-2"
    >
      {form.errors.role && (
        <div ref={errorSummary} tabIndex={-1} className="text-sm text-destructive">
          <a href={`#${fieldId}`} className="underline">
            Check the role selection
          </a>
        </div>
      )}
      <div className="flex items-end gap-2">
        <div className="min-w-0 flex-1">
          <FormField id={fieldId} label="Role" error={form.errors.role}>
            <select
              id={fieldId}
              aria-label={`Role for ${user.name}`}
              aria-invalid={!!form.errors.role}
              aria-describedby={form.errors.role ? `${fieldId}-error` : undefined}
              value={form.data.role}
              disabled={form.processing}
              onChange={(event) => {
                const option = roles.find((role) => role.value === event.target.value);
                if (option) {
                  form.setData('role', option.value);
                  form.clearErrors('role');
                }
              }}
              className="min-h-11 w-full rounded-control border border-control-border bg-surface px-3 text-base disabled:bg-muted disabled:text-muted-foreground aria-invalid:border-destructive md:text-sm"
            >
              {roles.map((role) => (
                <option key={role.value} value={role.value}>
                  {role.label}
                </option>
              ))}
            </select>
          </FormField>
        </div>
        <Button
          type="submit"
          aria-label={`Save role for ${user.name}`}
          disabled={form.processing || form.data.role === user.role}
          className="min-w-24"
        >
          {form.processing ? 'Saving…' : 'Save role'}
        </Button>
      </div>
    </form>
  );
}

export default function Admin({ users, roles }: Props) {
  const { auth } = usePage<SharedProps>().props;
  const [loading, setLoading] = useState(false);
  const [loadError, setLoadError] = useState('');
  const paginationOptions = {
    preserveScroll: true,
    onBefore: () => !loading,
    onStart: () => {
      setLoadError('');
      setLoading(true);
    },
    onFinish: () => setLoading(false),
    onNetworkError: () => {
      setLoadError('Could not load the requested page. Check your connection and try again.');
      return false;
    },
    onHttpException: () => {
      setLoadError('Could not load the requested page. Try again.');
      return false;
    },
  };
  const paginationClass =
    'inline-flex min-h-11 items-center rounded-control border border-control-border bg-surface px-3 text-sm hover:bg-muted';
  return (
    <AppLayout title="Users">
      <Head title="Users" />
      <p className="text-sm text-muted-foreground">
        Manage access to ResolveIT. Role changes apply on the user’s next request.
      </p>
      {loadError && (
        <p role="alert" className="text-sm text-destructive">
          {loadError}
        </p>
      )}
      {loading && (
        <p role="status" className="text-sm text-muted-foreground">
          Loading users…
        </p>
      )}
      <section aria-label="Users" aria-busy={loading}>
        {users.data.length === 0 ? (
          <div className="space-y-2 py-6">
            <h2 className="text-base font-semibold">No users on this page</h2>
            <p className="text-sm text-muted-foreground">
              Return to the first page to view accounts.
            </p>
            <Link href="/admin/users" className="text-sm text-primary underline">
              View first page
            </Link>
          </div>
        ) : (
          <div className="overflow-hidden rounded-panel border border-border bg-surface">
            <Table aria-label="User accounts" className="min-w-[700px]">
              <caption className="sr-only">User accounts, ticket counts, and role controls</caption>
              <thead className="bg-muted">
                <tr>
                  <TableHead>Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead className="text-right">Submitted</TableHead>
                  <TableHead className="text-right">Assigned</TableHead>
                  <TableHead>Role</TableHead>
                </tr>
              </thead>
              <tbody>
                {users.data.map((user) => (
                  <tr key={user.id} className="hover:bg-muted">
                    <TableCell className="max-w-48 break-words">{user.name}</TableCell>
                    <TableCell className="max-w-64 break-all text-muted-foreground">
                      {user.email}
                    </TableCell>
                    <TableCell className="text-right tabular-nums">
                      {user.requested_tickets_count}
                    </TableCell>
                    <TableCell className="text-right tabular-nums">
                      {user.assigned_tickets_count}
                    </TableCell>
                    <TableCell>
                      {user.id === auth.user?.id ? (
                        <div className="space-y-1">
                          <span>
                            {roles.find((role) => role.value === user.role)?.label ??
                              'Unknown role'}
                          </span>
                          <p className="text-xs text-muted-foreground">
                            You cannot remove your own administrator role.
                          </p>
                        </div>
                      ) : (
                        <RoleEditor key={`${user.id}-${user.role}`} user={user} roles={roles} />
                      )}
                    </TableCell>
                  </tr>
                ))}
              </tbody>
            </Table>
          </div>
        )}
      </section>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <p className="text-sm text-muted-foreground">
          {users.from ?? 0}–{users.to ?? 0} of {users.total} users
        </p>
        <nav aria-label="Users pagination" className="flex items-center gap-2">
          {users.prev_page_url ? (
            <Link
              href={users.prev_page_url}
              {...paginationOptions}
              className={paginationClass}
              aria-disabled={loading}
            >
              Previous
            </Link>
          ) : (
            <span aria-disabled="true" className="px-3 text-sm text-muted-foreground">
              Previous
            </span>
          )}
          <span className="text-sm tabular-nums">
            Page {users.current_page} of {users.last_page}
          </span>
          {users.next_page_url ? (
            <Link
              href={users.next_page_url}
              {...paginationOptions}
              className={paginationClass}
              aria-disabled={loading}
            >
              Next
            </Link>
          ) : (
            <span aria-disabled="true" className="px-3 text-sm text-muted-foreground">
              Next
            </span>
          )}
        </nav>
      </div>
    </AppLayout>
  );
}
