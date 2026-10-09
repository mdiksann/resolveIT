import type { ButtonHTMLAttributes } from 'react';
import { cn } from '../../Lib/utils';
export function Button({
  className,
  variant = 'primary',
  type = 'button',
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: 'primary' | 'secondary' }) {
  return (
    <button
      type={type}
      className={cn(
        'inline-flex min-h-11 items-center justify-center rounded-control px-4 text-sm font-medium disabled:cursor-not-allowed disabled:bg-muted disabled:text-muted-foreground',
        variant === 'primary'
          ? 'bg-primary text-primary-foreground hover:bg-primary-hover'
          : 'border border-control-border bg-surface hover:bg-muted',
        className,
      )}
      {...props}
    />
  );
}
