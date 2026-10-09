import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { SharedProps } from '../Types';
import { FlashMessages } from '../Components/FlashMessages';
export default function AuthLayout({ title, children }: { title: string; children: ReactNode }) {
  const { appName } = usePage<SharedProps>().props;
  return (
    <main className="mx-auto flex min-h-screen max-w-sm flex-col justify-center gap-6 px-5 py-12">
      <p className="text-sm font-medium text-muted-foreground">{appName}</p>
      <h1 className="text-2xl font-semibold">{title}</h1>
      <FlashMessages />
      {children}
    </main>
  );
}
