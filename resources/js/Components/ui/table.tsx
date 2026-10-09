import type { ComponentProps } from 'react';
import { cn } from '../../Lib/utils';
export function Table({ className, ...props }: ComponentProps<'table'>) {
  return (
    <div className="overflow-x-auto">
      <table className={cn('w-full text-left text-sm', className)} {...props} />
    </div>
  );
}
export function TableHead({ className, ...props }: ComponentProps<'th'>) {
  return (
    <th
      scope="col"
      className={cn('border-b border-border px-3 py-2 font-medium', className)}
      {...props}
    />
  );
}
export function TableCell({ className, ...props }: ComponentProps<'td'>) {
  return <td className={cn('border-b border-border px-3 py-2', className)} {...props} />;
}
