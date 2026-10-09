import { useRequestFeedback } from '../../Lib/useRequestFeedback';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { StatusOption, TicketStatus } from '../../Types';
import { FormField } from '../FormField';
import { FormErrors } from '../FormErrors';
import { Button } from '../ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '../ui/dialog';
export function TransitionControl({
  ticketId,
  transitions,
}: {
  ticketId: number;
  transitions: StatusOption[];
}) {
  const form = useForm<{ status: TicketStatus | '' }>({ status: transitions[0]?.value ?? '' });
  const feedback = useRequestFeedback();
  const [open, setOpen] = useState(false);
  const target = transitions.find((t) => t.value === form.data.status);
  if (!transitions.length)
    return <p className="text-sm text-muted-foreground">No status transitions available.</p>;
  return (
    <>
      <form
        className="space-y-3"
        onSubmit={(e) => {
          e.preventDefault();
          setOpen(true);
        }}
      >
        {!open && <FormErrors errors={form.errors} failure={feedback.failure} />}
        <FormField id="status" label="Next status" error={form.errors.status}>
          <select
            id="status"
            className="min-h-11 w-full rounded-control border border-control-border bg-surface px-3 text-sm"
            value={target?.value ?? ''}
            onChange={(e) => form.setData('status', e.target.value as TicketStatus)}
            aria-invalid={!!form.errors.status}
            aria-describedby={form.errors.status ? 'status-error' : undefined}
          >
            <option value="" disabled>
              Choose status
            </option>
            {transitions.map((t) => (
              <option key={t.value} value={t.value}>
                {t.label}
              </option>
            ))}
          </select>
        </FormField>
        <Button id={`transition-${ticketId}`} type="submit" disabled={form.processing || !target}>
          Change status
        </Button>
      </form>
      <Dialog
        open={open}
        onOpenChange={(value) => {
          if (!form.processing) setOpen(value);
        }}
      >
        <DialogContent
          closeDisabled={form.processing}
          onCloseAutoFocus={(event) => {
            event.preventDefault();
            document.getElementById(`transition-${ticketId}`)?.focus();
          }}
        >
          <FormErrors errors={form.errors} failure={feedback.failure} />
          <DialogTitle className="font-semibold">Change ticket status?</DialogTitle>
          <DialogDescription className="mt-3 text-sm text-muted-foreground">
            This will move the ticket to {target?.label}. Closed tickets cannot be reopened.
          </DialogDescription>
          <div className="mt-6 flex flex-wrap justify-end gap-3">
            <Button variant="secondary" disabled={form.processing} onClick={() => setOpen(false)}>
              Cancel
            </Button>
            <Button
              disabled={form.processing || !target}
              onClick={() =>
                form.patch(`/tickets/${ticketId}/status`, {
                  ...feedback.options,
                  preserveScroll: true,
                  onSuccess: () => setOpen(false),
                  onError: () => setOpen(false),
                })
              }
            >
              {form.processing ? 'Updating…' : `Move to ${target?.label ?? 'status'}`}
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
}
