import type { HTMLAttributes } from 'react';
import { cn } from '../../Lib/utils';
export function Badge({ className, ...props }: HTMLAttributes<HTMLSpanElement>) {
  return (
    <span
      className={cn(
        'inline-flex rounded-control border border-border px-2 py-0.5 text-xs font-medium',
        className,
      )}
      {...props}
    />
  );
}
