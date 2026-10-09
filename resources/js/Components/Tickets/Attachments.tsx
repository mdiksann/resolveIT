import { useRequestFeedback } from '../../Lib/useRequestFeedback';
import { Link, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import type { Paginated, TicketAttachment } from '../../Types';
import { Button } from '../ui/button';
import { FormField } from '../FormField';
import { FormErrors } from '../FormErrors';
export function Attachments({
  ticketId,
  attachments,
  canAttach,
}: {
  ticketId: number;
  attachments: Paginated<TicketAttachment>;
  canAttach: boolean;
}) {
  const upload = useForm<{ file: File | null }>({ file: null });
  const remove = useForm({});
  const feedback = useRequestFeedback();
  const input = useRef<HTMLInputElement>(null);
  const busy = upload.processing || remove.processing;
  return (
    <section className="border-t border-border pt-5" aria-labelledby="attachments-heading">
      <h2 id="attachments-heading" className="mb-3 font-semibold">
        Attachments
      </h2>
      <FormErrors errors={{}} failure={feedback.failure} />
      {!attachments.data.length && (
        <p className="text-sm text-muted-foreground">No attachments yet.</p>
      )}
      <ul className="space-y-3">
        {attachments.data.map((a) => (
          <li
            key={a.id}
            className="flex flex-wrap items-center justify-between gap-3 border-b border-border py-3 text-sm"
          >
            <div className="min-w-0">
              <a
                href={a.download_url}
                className="inline-flex min-h-11 items-center break-all text-primary underline"
              >
                {a.original_name}
              </a>
              <p className="text-muted-foreground">
                {Math.ceil(a.size_bytes / 1024)} KB · {a.uploader?.name ?? 'Deleted user'}
              </p>
            </div>
            {a.can_delete && (
              <Button
                variant="secondary"
                disabled={busy}
                onClick={() =>
                  remove.delete(`/tickets/${ticketId}/attachments/${a.id}`, {
                    ...feedback.options,
                    preserveScroll: true,
                  })
                }
              >
                {remove.processing ? 'Removing…' : 'Remove'}
              </Button>
            )}
          </li>
        ))}
      </ul>
      <FormErrors errors={remove.errors} />
      <nav aria-label="Attachment pagination" className="my-3 flex gap-4">
        {attachments.prev_page_url && (
          <Link
            className="inline-flex min-h-11 items-center text-primary underline"
            preserveScroll
            href={attachments.prev_page_url}
          >
            Newer attachments
          </Link>
        )}
        {attachments.next_page_url && (
          <Link
            className="inline-flex min-h-11 items-center text-primary underline"
            preserveScroll
            href={attachments.next_page_url}
          >
            Older attachments
          </Link>
        )}
      </nav>
      {canAttach ? (
        <form
          className="space-y-3"
          onSubmit={(e) => {
            e.preventDefault();
            upload.post(`/tickets/${ticketId}/attachments`, {
              forceFormData: true,
              ...feedback.options,
              preserveScroll: true,
              onSuccess: () => {
                upload.reset();
                if (input.current) input.current.value = '';
              },
            });
          }}
        >
          <FormErrors errors={upload.errors} />
          <div
            className="rounded-control border-2 border-dashed border-control-border bg-surface p-4"
            onDragOver={(e) => e.preventDefault()}
            onDrop={(e) => {
              e.preventDefault();
              if (!busy) {
                upload.setData('file', e.dataTransfer.files[0] ?? null);
                if (input.current) input.current.value = '';
              }
            }}
          >
            <FormField id="file" label="Upload attachment" error={upload.errors.file}>
              <input
                ref={input}
                id="file"
                type="file"
                className="min-h-11 max-w-full text-sm"
                accept=".png,.jpg,.jpeg,.gif,.webp,.pdf,.txt,.log,.csv,.zip"
                disabled={busy}
                onChange={(e) => upload.setData('file', e.target.files?.[0] ?? null)}
                aria-invalid={!!upload.errors.file}
                aria-describedby="file-help file-error"
              />
            </FormField>
            <p id="file-help" className="mt-2 text-sm text-muted-foreground">
              Drop one file here or choose a file. Images, PDF, TXT, LOG, CSV, ZIP; maximum 10 MB.
            </p>
            {upload.data.file && (
              <p className="mt-2 break-all text-sm">Selected: {upload.data.file.name}</p>
            )}
          </div>
          {upload.progress && (
            <p role="status" className="text-sm">
              Uploading: {upload.progress.percentage}%
            </p>
          )}
          <Button type="submit" disabled={busy || !upload.data.file}>
            {upload.processing ? 'Uploading…' : 'Upload file'}
          </Button>
        </form>
      ) : (
        <p className="text-sm text-muted-foreground">Uploads are unavailable on closed tickets.</p>
      )}
    </section>
  );
}
