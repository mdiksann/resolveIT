import { Button } from '../Components/ui/button';
import { ErrorLayout } from './Errors/ErrorLayout';

const errorDetails: Record<number, { title: string; description: string }> = {
  403: {
    title: 'Access denied',
    description: 'You do not have permission to access this resource or perform this action.',
  },
  404: {
    title: 'Page not found',
    description: 'The page or resource you are looking for does not exist or has been moved.',
  },
  419: {
    title: 'Page expired',
    description:
      'Your session has expired or the form submission token is stale. Please reload the page to try again.',
  },
  500: {
    title: 'Something went wrong',
    description:
      'An internal server error occurred while processing your request. Please try again later.',
  },
  503: {
    title: 'Service temporarily unavailable',
    description:
      'ResolveIT is undergoing maintenance or experiencing temporary load. Please try again in a few moments.',
  },
};

export default function ErrorPage({ status, incidentId }: { status: number; incidentId?: string }) {
  const details = errorDetails[status] ?? errorDetails[500];

  return (
    <ErrorLayout status={status} title={details.title} description={details.description}>
      {status === 500 && incidentId && (
        <div className="rounded-control border border-border bg-muted p-3">
          <p className="text-xs font-medium text-muted-foreground">Incident reference:</p>
          <p className="font-mono text-xs text-foreground select-all">{incidentId}</p>
        </div>
      )}
      {(status === 419 || status === 503) && (
        <div className="flex flex-wrap gap-3">
          <Button type="button" onClick={() => window.location.reload()} className="min-h-11">
            {status === 419 ? 'Reload and retry' : 'Check again'}
          </Button>
        </div>
      )}
    </ErrorLayout>
  );
}
