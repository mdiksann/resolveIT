import { Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { TicketForm } from '../../Components/Tickets/TicketForm';
import type { CategoryOption, PriorityOption } from '../../Types';
export default function Edit({
  ticket,
  categories,
  priorities,
}: {
  ticket: {
    id: number;
    title: string;
    description: string;
    category_id: number | null;
    priority_id: number;
  };
  categories: CategoryOption[];
  priorities: PriorityOption[];
}) {
  return (
    <AppLayout title="Edit ticket">
      <Head title="Edit ticket" />
      <TicketForm
        ticketId={ticket.id}
        categories={categories}
        priorities={priorities}
        initial={{
          title: ticket.title,
          description: ticket.description,
          category_id: String(ticket.category_id ?? ''),
          priority_id: String(ticket.priority_id),
        }}
      />
    </AppLayout>
  );
}
