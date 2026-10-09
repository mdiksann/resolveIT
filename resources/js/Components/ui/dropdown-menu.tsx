import * as Primitive from '@radix-ui/react-dropdown-menu';
import type { ComponentProps } from 'react';
export const DropdownMenu = Primitive.Root;
export const DropdownMenuTrigger = Primitive.Trigger;
export function DropdownMenuContent(props: ComponentProps<typeof Primitive.Content>) {
  return (
    <Primitive.Portal>
      <Primitive.Content
        align="end"
        sideOffset={6}
        className="z-50 min-w-44 rounded-control border border-border bg-surface p-1 shadow-sm"
        {...props}
      />
    </Primitive.Portal>
  );
}
export function DropdownMenuItem(props: ComponentProps<typeof Primitive.Item>) {
  return (
    <Primitive.Item
      className="flex min-h-11 cursor-pointer items-center rounded-control px-3 text-sm outline-none focus:bg-muted"
      {...props}
    />
  );
}
