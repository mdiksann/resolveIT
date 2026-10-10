import { ErrorLayout } from './ErrorLayout';

export default function ServerError({ incidentId }: { incidentId?: string }) {
  return (
    <ErrorLayout
      status={500}
      title="Something went wrong"
      description="An internal server error occurred while processing your request. Please try again later."
    >
      {incidentId && (
        <div className="rounded-control border border-border bg-muted p-3">
          <p className="text-xs font-medium text-muted-foreground">Incident reference:</p>
          <p className="font-mono text-xs text-foreground select-all">{incidentId}</p>
        </div>
      )}
    </ErrorLayout>
  );
}
