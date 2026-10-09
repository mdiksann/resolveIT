import { Head } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import { Alert } from '../Components/ui/alert';
export default function Admin() {
  return (
    <AppLayout title="Administration">
      <Head title="Administration" />
      <Alert>Administrator access is enabled for your account.</Alert>
    </AppLayout>
  );
}
