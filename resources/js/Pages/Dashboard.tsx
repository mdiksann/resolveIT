import { Head, usePage } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import { Card, CardTitle } from '../Components/ui/card';
import type { SharedProps } from '../Types';
export default function Dashboard({
  environment,
  database,
}: {
  environment: string;
  database: string;
}) {
  const { auth } = usePage<SharedProps>().props;
  return (
    <AppLayout title="Dashboard">
      <Head title="Dashboard" />
      <p>Welcome, {auth.user?.name}.</p>
      <Card className="max-w-xl">
        <CardTitle>System status</CardTitle>
        <dl className="space-y-3 text-sm">
          {Object.entries({
            Authentication: 'OK',
            Database: database,
            Environment: environment,
          }).map(([label, value]) => (
            <div key={label} className="flex justify-between gap-4">
              <dt className="text-muted-foreground">{label}</dt>
              <dd>{value}</dd>
            </div>
          ))}
        </dl>
      </Card>
    </AppLayout>
  );
}
