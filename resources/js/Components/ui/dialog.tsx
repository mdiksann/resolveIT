import * as Primitive from '@radix-ui/react-dialog';
import type { ComponentProps } from 'react';
export const Dialog = Primitive.Root;
export const DialogTrigger = Primitive.Trigger;
export const DialogTitle = Primitive.Title;
export const DialogDescription = Primitive.Description;
export const DialogClose = Primitive.Close;
export function DialogContent({ children, ...props }: ComponentProps<typeof Primitive.Content>) {
  return (
    <Primitive.Portal>
      <Primitive.Overlay className="fixed inset-0 z-40 bg-black/40" />
      <Primitive.Content
        className="fixed top-1/2 left-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-control border border-border bg-surface p-6"
        {...props}
      >
        {children}
        <Primitive.Close
          aria-label="Close dialog"
          className="absolute top-2 right-2 min-h-11 min-w-11"
        >
          ×
        </Primitive.Close>
      </Primitive.Content>
    </Primitive.Portal>
  );
}
