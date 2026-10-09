import type { HTMLAttributes } from 'react';
import { cn } from '../../Lib/utils';
export function Skeleton({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      aria-hidden="true"
      className={cn('rounded-control bg-muted motion-safe:animate-pulse', className)}
      {...props}
    />
  );
}
