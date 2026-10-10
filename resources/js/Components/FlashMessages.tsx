import { usePage } from '@inertiajs/react';
import type { SharedProps } from '../Types';
import { Alert } from './ui/alert';

const variantClasses: Record<string, string> = {
  error: 'border-destructive bg-destructive-soft text-destructive',
  warning: 'border-warning bg-warning-soft text-warning',
  success: 'border-success bg-success-soft text-success',
  info: 'border-info bg-info-soft text-info',
};

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
              aria-live={kind === 'error' || kind === 'warning' ? 'assertive' : 'polite'}
              aria-atomic="true"
              className={variantClasses[kind] ?? 'border-border bg-surface text-foreground'}
            >
              <span className="font-semibold capitalize">{kind}: </span>
              <span>{message}</span>
            </Alert>
          ),
      )}
    </div>
  );
}
