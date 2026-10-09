import type { HTMLAttributes } from 'react';
import { cn } from '../../Lib/utils';
export function Card({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn('rounded-control border border-border bg-surface p-5', className)}
      {...props}
    />
  );
}
export function CardTitle({ className, ...props }: HTMLAttributes<HTMLHeadingElement>) {
  return <h2 className={cn('mb-4 text-base font-semibold', className)} {...props} />;
}
