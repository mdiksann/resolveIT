import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { FormField } from '../../Components/FormField';
import { FormErrors } from '../../Components/FormErrors';
import { Button } from '../../Components/ui/button';
import { Table, TableHead, TableCell } from '../../Components/ui/table';
import { ConfigurationPagination } from '../../Components/ConfigurationPagination';
import { EmptyState } from '../../Components/EmptyState';
import { useRequestFeedback } from '../../Lib/useRequestFeedback';
import type { AdminPriority, Paginated } from '../../Types';
function PriorityRow({ priority }: { priority: AdminPriority }) {
  const form = useForm({
    name: priority.name,
    rank: String(priority.rank),
    sla_hours: String(priority.sla_hours),
  });
  const action = useForm({});
  const feedback = useRequestFeedback();
  const busy = form.processing || action.processing;
  const prefix = `priority-${priority.id}`;
  return (
    <tr>
      <TableCell>
        <form
          className="min-w-80 space-y-3"
          aria-label={`Edit ${priority.name}`}
          aria-busy={busy}
          onSubmit={(e) => {
            e.preventDefault();
            form.patch(`/admin/priorities/${priority.id}`, {
              ...feedback.options,
              preserveScroll: true,
            });
          }}
        >
          <FormErrors
            errors={form.errors}
            failure={feedback.failure}
            fieldIds={{
              name: `${prefix}-name`,
              rank: `${prefix}-rank`,
              sla_hours: `${prefix}-sla`,
            }}
          />
          <FormField
            id={`${prefix}-name`}
            label="Name"
            required
            minLength={2}
            maxLength={60}
            value={form.data.name}
            disabled={busy}
            onChange={(e) => form.setData('name', e.target.value)}
            error={form.errors.name}
          />
          <div className="flex items-end gap-3">
            <FormField
              id={`${prefix}-rank`}
              label="Rank (1 = most urgent)"
              type="number"
              required
              min={1}
              max={2147483647}
              value={form.data.rank}
              disabled={busy}
              onChange={(e) => form.setData('rank', e.target.value)}
              error={form.errors.rank}
            />
            <FormField
              id={`${prefix}-sla`}
              label="SLA hours"
              type="number"
              required
              min={1}
              max={720}
              value={form.data.sla_hours}
              disabled={busy}
              onChange={(e) => form.setData('sla_hours', e.target.value)}
              error={form.errors.sla_hours}
            />
            <Button type="submit" disabled={busy}>
              {' '}
              {form.processing ? 'Saving…' : 'Save'}
            </Button>
          </div>
        </form>
      </TableCell>
      <TableCell>
        {priority.is_active ? 'Active' : 'Inactive'}
        {priority.is_default && <p className="mt-1 font-medium">Default</p>}
      </TableCell>
      <TableCell>
        <div className="space-y-3">
          <FormErrors errors={action.errors} />
          {priority.is_active && !priority.is_default && (
            <>
              <Button
                variant="secondary"
                disabled={busy}
                onClick={() =>
                  action.patch(`/admin/priorities/${priority.id}/default`, {
                    ...feedback.options,
                    preserveScroll: true,
                  })
                }
              >
                {action.processing ? 'Updating…' : 'Set default'}
              </Button>
              <Button
                variant="secondary"
                disabled={busy}
                onClick={() =>
                  action.patch(`/admin/priorities/${priority.id}/deactivate`, {
                    ...feedback.options,
                    preserveScroll: true,
                  })
                }
              >
                {action.processing ? 'Deactivating…' : 'Deactivate'}
              </Button>
            </>
          )}
          {priority.is_default && (
            <p className="max-w-48 text-xs text-muted-foreground">
              Set another default before deactivating this priority.
            </p>
          )}
        </div>
      </TableCell>
    </tr>
  );
}
export default function Priorities({ priorities }: { priorities: Paginated<AdminPriority> }) {
  const form = useForm({ name: '', rank: '1', sla_hours: '24', is_default: false });
  const feedback = useRequestFeedback();
  return (
    <AppLayout title="Priorities">
      <Head title="Priorities" />
      <p className="text-sm text-muted-foreground">
        SLA changes apply to new tickets. Existing due dates stay fixed. The first priority becomes
        the default.
      </p>
      <form
        className="max-w-[640px] space-y-4"
        aria-busy={form.processing}
        onSubmit={(e) => {
          e.preventDefault();
          form.post('/admin/priorities', { ...feedback.options, onSuccess: () => form.reset() });
        }}
      >
        <FormErrors errors={form.errors} failure={feedback.failure} />
        <FormField
          id="name"
          label="New priority name"
          required
          minLength={2}
          maxLength={60}
          value={form.data.name}
          onChange={(e) => form.setData('name', e.target.value)}
          error={form.errors.name}
        />
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField
            id="rank"
            label="Rank (1 = most urgent)"
            type="number"
            required
            min={1}
            max={2147483647}
            value={form.data.rank}
            onChange={(e) => form.setData('rank', e.target.value)}
            error={form.errors.rank}
          />
          <FormField
            id="sla_hours"
            label="SLA hours"
            type="number"
            required
            min={1}
            max={720}
            value={form.data.sla_hours}
            onChange={(e) => form.setData('sla_hours', e.target.value)}
            error={form.errors.sla_hours}
          />
        </div>
        <FormField id="is_default" label="Make default" error={form.errors.is_default}>
          <input
            id="is_default"
            type="checkbox"
            className="h-5 w-5 accent-primary"
            checked={form.data.is_default}
            onChange={(e) => form.setData('is_default', e.target.checked)}
            aria-invalid={!!form.errors.is_default}
            aria-describedby={form.errors.is_default ? 'is_default-error' : undefined}
          />
        </FormField>
        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Creating…' : 'Create priority'}
        </Button>
      </form>
      {priorities.data.length ? (
        <div className="overflow-hidden rounded-panel border border-border bg-surface">
          <Table aria-label="Priorities">
            <thead className="bg-muted">
              <tr>
                <TableHead>Priority and SLA</TableHead>
                <TableHead>Availability</TableHead>
                <TableHead>Actions</TableHead>
              </tr>
            </thead>
            <tbody>
              {priorities.data.map((p) => (
                <PriorityRow
                  key={`${p.id}-${p.name}-${p.rank}-${p.sla_hours}-${p.is_default}-${p.is_active}`}
                  priority={p}
                />
              ))}
            </tbody>
          </Table>
        </div>
      ) : (
        <EmptyState
          title="No priorities on this page"
          description="Create a priority above or return to the first page to view configured priorities."
          action={{
            label: 'View first page',
            href: '/admin/priorities',
          }}
        />
      )}
      <ConfigurationPagination page={priorities} label="Priorities" />
    </AppLayout>
  );
}
