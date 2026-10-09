import { useRequestFeedback } from '../../Lib/useRequestFeedback';
import { Link, useForm } from '@inertiajs/react';
import type { CategoryOption, PriorityOption, TicketFields } from '../../Types';
import { FormField } from '../FormField';
import { FormErrors } from '../FormErrors';
import { Button } from '../ui/button';
const control =
  'min-h-11 w-full rounded-control border border-control-border bg-surface px-3 py-2 text-sm';
export function TicketForm({
  categories,
  priorities,
  initial,
  ticketId,
}: {
  categories: CategoryOption[];
  priorities: PriorityOption[];
  initial?: TicketFields;
  ticketId?: number;
}) {
  const form = useForm<TicketFields>(
    initial ?? {
      title: '',
      description: '',
      category_id: '',
      priority_id: String(priorities.find((p) => p.is_default)?.id ?? ''),
    },
  );
  const feedback = useRequestFeedback();
  return (
    <form
      className="max-w-[640px] space-y-5"
      onSubmit={(event) => {
        event.preventDefault();
        if (ticketId) form.patch(`/tickets/${ticketId}`, feedback.options);
        else form.post('/tickets', feedback.options);
      }}
    >
      <FormErrors errors={form.errors} failure={feedback.failure} />
      {!priorities.length && (
        <p role="alert">
          No active priorities are available. Ask an administrator to configure one.
        </p>
      )}
      <FormField
        id="title"
        label="Title"
        required
        minLength={3}
        maxLength={120}
        value={form.data.title}
        onChange={(e) => form.setData('title', e.target.value)}
        error={form.errors.title}
      />
      <FormField id="description" label="Description" error={form.errors.description}>
        <textarea
          id="description"
          className={control}
          rows={7}
          required
          minLength={10}
          maxLength={5000}
          value={form.data.description}
          onChange={(e) => form.setData('description', e.target.value)}
          aria-invalid={!!form.errors.description}
          aria-describedby={form.errors.description ? 'description-error' : undefined}
        />
      </FormField>
      <FormField id="category_id" label="Category (optional)" error={form.errors.category_id}>
        <select
          id="category_id"
          className={control}
          value={form.data.category_id}
          onChange={(e) => form.setData('category_id', e.target.value)}
          aria-invalid={!!form.errors.category_id}
          aria-describedby={form.errors.category_id ? 'category_id-error' : undefined}
        >
          <option value="">No category</option>
          {categories.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </select>
      </FormField>
      <FormField id="priority_id" label="Priority" error={form.errors.priority_id}>
        <select
          id="priority_id"
          className={control}
          value={form.data.priority_id}
          onChange={(e) => form.setData('priority_id', e.target.value)}
          aria-invalid={!!form.errors.priority_id}
          aria-describedby={form.errors.priority_id ? 'priority_id-error' : undefined}
        >
          <option value="">Use default priority</option>
          {priorities.map((p) => (
            <option key={p.id} value={p.id}>
              {p.name}
            </option>
          ))}
        </select>
      </FormField>
      <div className="flex flex-wrap gap-3">
        <Button type="submit" disabled={form.processing || !priorities.length}>
          {form.processing
            ? ticketId
              ? 'Saving…'
              : 'Creating ticket…'
            : ticketId
              ? 'Save changes'
              : 'Create ticket'}
        </Button>
        <Link
          className="inline-flex min-h-11 items-center text-primary underline"
          href={ticketId ? `/tickets/${ticketId}` : '/tickets'}
        >
          Cancel
        </Link>
      </div>
    </form>
  );
}
