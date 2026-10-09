import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import { FormField } from '../../Components/FormField';
import { Button } from '../../Components/ui/button';
import { Alert } from '../../Components/ui/alert';
export default function Register({ status }: { status?: string }) {
  const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
  return (
    <AuthLayout title="Create account">
      <Head title="Create account" />
      {status && <Alert>{status}</Alert>}
      <form
        className="space-y-5"
        onSubmit={(event) => {
          event.preventDefault();
          form.post('/register', {
            onFinish: () => {
              form.reset('password', 'password_confirmation');
            },
          });
        }}
      >
        <FormField
          id="name"
          label="Name"
          type="text"
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
        <FormField
          id="password"
          label="Password (12 characters minimum)"
          type="password"
          autoComplete="new-password"
          required
          value={form.data.password}
          onChange={(event) => form.setData('password', event.target.value)}
          error={form.errors.password}
        />
        <FormField
          id="password_confirmation"
          label="Confirm password"
          type="password"
          autoComplete="new-password"
          required
          value={form.data.password_confirmation}
          onChange={(event) => form.setData('password_confirmation', event.target.value)}
          error={form.errors.password_confirmation}
        />
        <Button type="submit" className="w-full" disabled={form.processing}>
          {form.processing ? 'Please wait…' : 'Create account'}
        </Button>
      </form>
      <Link href="/login" className="py-2 text-sm text-primary underline">
        Back to login
      </Link>
    </AuthLayout>
  );
}
