import { Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { TicketForm } from '../../Components/Tickets/TicketForm';
import type { CategoryOption, PriorityOption } from '../../Types';
export default function Create(props: {
  categories: CategoryOption[];
  priorities: PriorityOption[];
}) {
  return (
    <AppLayout title="New ticket">
      <Head title="New ticket" />
      <TicketForm {...props} />
    </AppLayout>
  );
}
