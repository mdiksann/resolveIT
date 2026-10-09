import { Head } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
export default function Admin() {
  return (
    <AppLayout title="Administration">
      <Head title="Administration" />
      <p className="text-sm text-muted-foreground">Helpdesk configuration is not available yet.</p>
    </AppLayout>
  );
}
