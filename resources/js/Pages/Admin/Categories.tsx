import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { FormField } from '../../Components/FormField';
import { FormErrors } from '../../Components/FormErrors';
import { Button } from '../../Components/ui/button';
import { Table, TableHead, TableCell } from '../../Components/ui/table';
import { ConfigurationPagination } from '../../Components/ConfigurationPagination';
import { useRequestFeedback } from '../../Lib/useRequestFeedback';
import type { AdminCategory, Paginated } from '../../Types';
function CategoryRow({ category }: { category: AdminCategory }) {
  const rename = useForm({ name: category.name });
  const deactivate = useForm({});
  const feedback = useRequestFeedback();
  const busy = rename.processing || deactivate.processing;
  const id = `category-${category.id}`;
  return (
    <tr>
      <TableCell>
        <form
          className="min-w-64 space-y-3"
          aria-label={`Rename ${category.name}`}
          aria-busy={busy}
          onSubmit={(e) => {
            e.preventDefault();
            rename.patch(`/admin/categories/${category.id}`, {
              ...feedback.options,
              preserveScroll: true,
            });
          }}
        >
          <FormErrors errors={rename.errors} failure={feedback.failure} fieldIds={{ name: id }} />
          <div className="flex items-end gap-3">
            <FormField
              id={id}
              label="Name"
              required
              minLength={2}
              maxLength={60}
              value={rename.data.name}
              disabled={busy}
              onChange={(e) => rename.setData('name', e.target.value)}
              error={rename.errors.name}
            />
            <Button
              type="submit"
              className="min-w-24"
              disabled={busy || rename.data.name === category.name}
            >
              {rename.processing ? 'Saving…' : 'Save'}
            </Button>
          </div>
        </form>
      </TableCell>
      <TableCell>{category.is_active ? 'Active' : 'Inactive'}</TableCell>
      <TableCell>
        <FormErrors errors={deactivate.errors} />
        {category.is_active && (
          <Button
            variant="secondary"
            disabled={busy}
            onClick={() =>
              deactivate.patch(`/admin/categories/${category.id}/deactivate`, {
                ...feedback.options,
                preserveScroll: true,
              })
            }
          >
            {deactivate.processing ? 'Deactivating…' : 'Deactivate'}
          </Button>
        )}
      </TableCell>
    </tr>
  );
}
export default function Categories({ categories }: { categories: Paginated<AdminCategory> }) {
  const form = useForm({ name: '' });
  const feedback = useRequestFeedback();
  return (
    <AppLayout title="Categories">
      <Head title="Categories" />
      <p className="text-sm text-muted-foreground">
        Retired categories remain visible on existing tickets.
      </p>
      <form
        className="max-w-md space-y-4"
        aria-busy={form.processing}
        onSubmit={(e) => {
          e.preventDefault();
          form.post('/admin/categories', { ...feedback.options, onSuccess: () => form.reset() });
        }}
      >
        <FormErrors errors={form.errors} failure={feedback.failure} />
        <FormField
          id="name"
          label="New category name"
          required
          minLength={2}
          maxLength={60}
          value={form.data.name}
          onChange={(e) => form.setData('name', e.target.value)}
          error={form.errors.name}
        />
        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Creating…' : 'Create category'}
        </Button>
      </form>
      {categories.data.length ? (
        <div className="overflow-hidden rounded-panel border border-border bg-surface">
          <Table aria-label="Categories">
            <thead className="bg-muted">
              <tr>
                <TableHead>Name</TableHead>
                <TableHead>Availability</TableHead>
                <TableHead>Actions</TableHead>
              </tr>
            </thead>
            <tbody>
              {categories.data.map((c) => (
                <CategoryRow key={`${c.id}-${c.name}-${c.is_active}`} category={c} />
              ))}
            </tbody>
          </Table>
        </div>
      ) : (
        <p className="text-sm text-muted-foreground">
          No categories on this page. Create one above or return to the first page.
        </p>
      )}
      <ConfigurationPagination page={categories} label="Categories" />
    </AppLayout>
  );
}
