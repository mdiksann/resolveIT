import { useRequestFeedback } from '../../Lib/useRequestFeedback';
import { useForm } from '@inertiajs/react';
import type { CategoryOption, TicketDetail } from '../../Types';
import { FormField } from '../FormField';
import { FormErrors } from '../FormErrors';
import { Button } from '../ui/button';
export function AssignmentControl({
  ticket,
  assignees,
}: {
  ticket: TicketDetail;
  assignees: CategoryOption[];
}) {
  const assign = useForm({ assignee_id: String(ticket.assignee?.id ?? '') });
  const other = useForm({});
  const feedback = useRequestFeedback();
  const busy = assign.processing || other.processing;
  return (
    <div className="space-y-3 border-t border-border pt-4">
      <FormErrors errors={{}} failure={feedback.failure} />
      <form
        className="space-y-3"
        onSubmit={(e) => {
          e.preventDefault();
          assign.patch(`/tickets/${ticket.id}/assignment`, {
            ...feedback.options,
            preserveScroll: true,
          });
        }}
      >
        <FormErrors errors={assign.errors} />
        <FormField id="assignee_id" label="Assign to" error={assign.errors.assignee_id}>
          <select
            id="assignee_id"
            className="min-h-11 w-full rounded-control border border-control-border bg-surface px-3 text-sm"
            required
            value={assign.data.assignee_id}
            onChange={(e) => assign.setData('assignee_id', e.target.value)}
            aria-invalid={!!assign.errors.assignee_id}
            aria-describedby={assign.errors.assignee_id ? 'assignee_id-error' : undefined}
          >
            <option value="">Choose agent</option>
            {assignees.map((a) => (
              <option key={a.id} value={a.id}>
                {a.name}
              </option>
            ))}
          </select>
        </FormField>
        <Button type="submit" disabled={busy || !assign.data.assignee_id}>
          {assign.processing ? 'Assigning…' : 'Assign ticket'}
        </Button>
      </form>
      <FormErrors errors={other.errors} />
      <div className="flex flex-wrap gap-3">
        <Button
          variant="secondary"
          disabled={busy}
          onClick={() =>
            other.post(`/tickets/${ticket.id}/self-assign`, {
              ...feedback.options,
              preserveScroll: true,
            })
          }
        >
          {other.processing ? 'Updating…' : 'Assign to me'}
        </Button>
        {ticket.assignee && ['OPEN', 'ASSIGNED'].includes(ticket.status) && (
          <Button
            variant="secondary"
            disabled={busy}
            onClick={() =>
              other.delete(`/tickets/${ticket.id}/assignment`, {
                ...feedback.options,
                preserveScroll: true,
              })
            }
          >
            {other.processing ? 'Unassigning…' : 'Unassign'}
          </Button>
        )}
      </div>
    </div>
  );
}
