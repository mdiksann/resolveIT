import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { Button } from './ui/button';

export interface EmptyStateAction {
  label: string;
  href?: string;
  onClick?: () => void;
  variant?: 'primary' | 'secondary';
  disabled?: boolean;
}

export interface EmptyStateProps {
  title: string;
  description: string;
  action?: EmptyStateAction;
  icon?: ReactNode;
  className?: string;
}

export function EmptyState({ title, description, action, icon, className = '' }: EmptyStateProps) {
  return (
    <div
      role="status"
      className={`rounded-panel border border-dashed border-border bg-surface p-6 ${className}`}
    >
      <div className="flex flex-col items-start gap-2">
        {icon && <div className="text-muted-foreground">{icon}</div>}
        <h2 className="text-base font-semibold text-foreground">{title}</h2>
        <p className="text-sm text-muted-foreground">{description}</p>
        {action && (
          <div className="pt-2">
            {action.href ? (
              <Link
                href={action.href}
                className="inline-flex min-h-11 items-center rounded-control bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary-hover focus-visible:outline-2"
              >
                {action.label}
              </Link>
            ) : (
              <Button
                type="button"
                variant={action.variant ?? 'secondary'}
                onClick={action.onClick}
                disabled={action.disabled}
              >
                {action.label}
              </Button>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

