import { Button } from '../../Components/ui/button';
import { ErrorLayout } from './ErrorLayout';

export default function PageExpired() {
  return (
    <ErrorLayout
      status={419}
      title="Page expired"
      description="Your session has expired or the form submission token is stale. Please reload the page to try again."
    >
      <div className="flex flex-wrap gap-3">
        <Button type="button" onClick={() => window.location.reload()} className="min-h-11">
          Reload and retry
        </Button>
      </div>
    </ErrorLayout>
  );
}
