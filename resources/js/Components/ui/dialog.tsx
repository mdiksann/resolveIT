import * as Primitive from '@radix-ui/react-dialog';
import type { ComponentProps } from 'react';
export const Dialog = Primitive.Root;
export const DialogTrigger = Primitive.Trigger;
export const DialogTitle = Primitive.Title;
export const DialogDescription = Primitive.Description;
export const DialogClose = Primitive.Close;
export function DialogContent({
  children,
  closeDisabled,
  ...props
}: ComponentProps<typeof Primitive.Content> & { closeDisabled?: boolean }) {
  return (
    <Primitive.Portal>
      <Primitive.Overlay className="fixed inset-0 z-40 bg-overlay" />
      <Primitive.Content
        className="fixed top-1/2 left-1/2 z-50 max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-[440px] overflow-y-auto -translate-x-1/2 -translate-y-1/2 rounded-panel border border-border bg-surface p-6 shadow-dialog"
        {...props}
      >
        {children}
        <Primitive.Close
          aria-label="Close dialog"
          disabled={closeDisabled}
          className="absolute top-2 right-2 min-h-11 min-w-11"
        >
          ×
        </Primitive.Close>
      </Primitive.Content>
    </Primitive.Portal>
  );
}
