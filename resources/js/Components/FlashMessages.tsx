import { usePage } from '@inertiajs/react';
import type { SharedProps } from '../Types';
import { Alert } from './ui/alert';
export function FlashMessages() {
  const { flash } = usePage<SharedProps>().props;
  return (
    <div className="space-y-2">
      {Object.entries(flash ?? {}).map(
        ([kind, message]) =>
          message && (
            <Alert
              key={kind}
              role={kind === 'error' || kind === 'warning' ? 'alert' : 'status'}
              className={kind === 'error' ? 'border-destructive text-destructive' : ''}
            >
              <span className="font-medium capitalize">{kind}: </span>
              {message}
            </Alert>
          ),
      )}
    </div>
  );
}
