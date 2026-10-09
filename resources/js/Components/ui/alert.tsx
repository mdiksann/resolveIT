import type { HTMLAttributes } from 'react';
import { cn } from '../../Lib/utils';
export function Alert({ className, role = 'status', ...props }: HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      role={role}
      className={cn('rounded-control border border-border bg-surface p-3 text-sm', className)}
      {...props}
    />
  );
}
