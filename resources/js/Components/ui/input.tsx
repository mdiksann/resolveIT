import type { InputHTMLAttributes } from 'react';
import { cn } from '../../Lib/utils';
export function Input({ className, ...props }: InputHTMLAttributes<HTMLInputElement>) {
  return (
    <input
      className={cn(
        'min-h-11 w-full rounded-control border border-control-border bg-surface px-3 text-base disabled:bg-muted disabled:text-muted-foreground aria-invalid:border-destructive',
        className,
      )}
      {...props}
    />
  );
}
