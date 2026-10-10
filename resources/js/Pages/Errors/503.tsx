import { Button } from '../../Components/ui/button';
import { ErrorLayout } from './ErrorLayout';

export default function ServiceUnavailable() {
  return (
    <ErrorLayout
      status={503}
      title="Service temporarily unavailable"
      description="ResolveIT is undergoing maintenance or experiencing temporary load. Please try again in a few moments."
    >
      <div className="flex flex-wrap gap-3">
        <Button type="button" onClick={() => window.location.reload()} className="min-h-11">
          Check again
        </Button>
      </div>
    </ErrorLayout>
  );
}
