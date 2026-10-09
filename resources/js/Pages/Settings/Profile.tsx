import { Head, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { FormField } from '../../Components/FormField';
import { Button } from '../../Components/ui/button';
import type { SharedProps } from '../../Types';
export default function Profile() {
  const { auth } = usePage<SharedProps>().props;
  const form = useForm({ name: auth.user?.name ?? '', email: auth.user?.email ?? '' });
  return (
    <AppLayout title="Profile">
      <Head title="Profile" />
      <form
        className="max-w-md space-y-5"
        onSubmit={(event) => {
          event.preventDefault();
          form.patch('/settings/profile', { preserveScroll: true });
        }}
      >
        <FormField
          id="name"
          label="Name"
          autoComplete="name"
          required
          value={form.data.name}
          onChange={(event) => form.setData('name', event.target.value)}
          error={form.errors.name}
        />
        <FormField
          id="email"
          label="Email"
          type="email"
          autoComplete="email"
          required
          value={form.data.email}
          onChange={(event) => form.setData('email', event.target.value)}
          error={form.errors.email}
        />
        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Saving…' : 'Save changes'}
        </Button>
      </form>
    </AppLayout>
  );
}
