import { Head } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';

export default function Dashboard() {
  return (
    <AppLayout title="Dashboard">
      <Head title="Dashboard" />
      <p className="text-sm text-muted-foreground">Operational metrics are not available yet.</p>
    </AppLayout>
  );
}
