import type { LabelHTMLAttributes } from 'react';
import { cn } from '../../Lib/utils';
export function Label({ className, ...props }: LabelHTMLAttributes<HTMLLabelElement>) {
  return <label className={cn('block text-sm font-medium', className)} {...props} />;
}
