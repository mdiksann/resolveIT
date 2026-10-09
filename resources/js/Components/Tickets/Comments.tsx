import { useRequestFeedback } from '../../Lib/useRequestFeedback';
import { Link, useForm } from '@inertiajs/react';
import type { Paginated, TicketComment } from '../../Types';
import { FormField } from '../FormField';
import { FormErrors } from '../FormErrors';
import { Button } from '../ui/button';
export function Comments({
  ticketId,
  comments,
  canComment,
  canInternal,
}: {
  ticketId: number;
  comments: Paginated<TicketComment>;
  canComment: boolean;
  canInternal: boolean;
}) {
  const form = useForm({ body: '', is_internal: false });
  const feedback = useRequestFeedback();
  return (
    <section className="border-t border-border pt-5" aria-labelledby="comments-heading">
      <h2 id="comments-heading" className="mb-3 font-semibold">
        Comments
      </h2>
      <div className="space-y-4">
        {!comments.data.length && <p className="text-sm text-muted-foreground">No comments yet.</p>}
        {comments.data.map((c) => (
          <article
            key={c.id}
            className={`rounded-control border border-border p-4 ${c.is_internal ? 'bg-muted' : 'bg-surface'}`}
          >
            <p className="mb-2 text-sm">
              <span className="font-medium">{c.author?.name ?? 'Deleted user'}</span> ·{' '}
              <time dateTime={c.created_at}>{new Date(c.created_at).toLocaleString()}</time>
              {c.is_internal && <span className="ml-2 font-medium">Internal note</span>}
            </p>
            <p className="whitespace-pre-wrap break-words text-sm">{c.body}</p>
          </article>
        ))}
      </div>
      <nav aria-label="Comment pagination" className="my-3 flex gap-4">
        {comments.prev_page_url && (
          <Link
            className="inline-flex min-h-11 items-center text-primary underline"
            preserveScroll
            href={comments.prev_page_url}
          >
            Newer comments
          </Link>
        )}
        {comments.next_page_url && (
          <Link
            className="inline-flex min-h-11 items-center text-primary underline"
            preserveScroll
            href={comments.next_page_url}
          >
            Older comments
          </Link>
        )}
      </nav>
      {canComment ? (
        <form
          className="space-y-3"
          onSubmit={(e) => {
            e.preventDefault();
            form.post(`/tickets/${ticketId}/comments`, {
              ...feedback.options,
              preserveScroll: true,
              onSuccess: () => form.reset('body'),
            });
          }}
        >
          <FormErrors errors={form.errors} failure={feedback.failure} />
          <FormField
            id="body"
            label={form.data.is_internal ? 'Internal note' : 'Public comment'}
            error={form.errors.body}
          >
            <textarea
              id="body"
              rows={4}
              required
              maxLength={2000}
              className="min-h-11 w-full rounded-control border border-control-border bg-surface px-3 py-2 text-sm"
              value={form.data.body}
              onChange={(e) => form.setData('body', e.target.value)}
              aria-invalid={!!form.errors.body}
              aria-describedby={form.errors.body ? 'body-error' : undefined}
            />
          </FormField>
          {canInternal && (
            <FormField id="is_internal" label="Visibility" error={form.errors.is_internal}>
              <select
                id="is_internal"
                className="min-h-11 rounded-control border border-control-border bg-surface px-3 text-sm"
                value={form.data.is_internal ? 'internal' : 'public'}
                onChange={(e) => form.setData('is_internal', e.target.value === 'internal')}
              >
                <option value="public">Public comment</option>
                <option value="internal">Internal note (staff only)</option>
              </select>
            </FormField>
          )}
          <Button type="submit" disabled={form.processing}>
            {form.processing
              ? 'Posting…'
              : form.data.is_internal
                ? 'Add internal note'
                : 'Add comment'}
          </Button>
        </form>
      ) : (
        <p className="text-sm text-muted-foreground">Comments are unavailable on closed tickets.</p>
      )}
    </section>
  );
}
