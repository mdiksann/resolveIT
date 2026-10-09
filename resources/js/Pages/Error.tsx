import { Head } from '@inertiajs/react';
const messages: Record<number, [string, string]> = {
  403: ['Access denied', 'You do not have permission to view this page.'],
  404: ['Page not found', 'Check the address or return to the application.'],
  419: ['Session expired', 'Reload the page and try again. You may need to log in.'],
  500: ['Something went wrong', 'Please try again later.'],
  503: ['Temporarily unavailable', 'Please try again shortly.'],
};
export default function ErrorPage({ status }: { status: number }) {
  const [title, message] = messages[status] ?? messages[500];
  return (
    <main className="mx-auto flex min-h-screen max-w-md flex-col justify-center gap-4 px-5">
      <Head title={title} />
      <p className="text-sm text-muted-foreground">{status}</p>
      <h1 className="text-2xl font-semibold">{title}</h1>
      <p>{message}</p>
      <a className="min-h-11 py-3 text-primary underline" href="/dashboard">
        Return to application
      </a>
      {status === 419 && (
        <button
          className="min-h-11 text-left text-primary underline"
          onClick={() => window.location.reload()}
        >
          Reload page
        </button>
      )}
    </main>
  );
}
