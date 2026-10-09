import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import { FormField } from '../../Components/FormField';
import { Button } from '../../Components/ui/button';

export default function ResetPassword({ email, token }: { email: string; token: string }) {
  const form = useForm({ email: email, password: '', password_confirmation: '', token });
  return (
    <AuthLayout title="Choose a new password">
      <Head title="Choose a new password" />
      <form
        className="space-y-5"
        onSubmit={(event) => {
          event.preventDefault();
          form.post('/reset-password', {
            onFinish: () => {
              form.reset('password', 'password_confirmation');
            },
          });
        }}
      >
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
          {form.processing ? 'Please wait…' : 'Choose a new password'}
        </Button>
      </form>
      <Link href="/login" className="py-2 text-sm text-primary underline">
        Back to login
      </Link>
    </AuthLayout>
  );
}
