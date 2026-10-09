import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import { FormField } from '../../Components/FormField';
import { Button } from '../../Components/ui/button';
import { Alert } from '../../Components/ui/alert';
export default function ForgotPassword({ status }: { status?: string }) {
  const form = useForm({ email: '' });
  return (
    <AuthLayout title="Reset password">
      <Head title="Reset password" />
      {status && <Alert>{status}</Alert>}
      <form
        className="space-y-5"
        onSubmit={(event) => {
          event.preventDefault();
          form.post('/forgot-password', { onFinish: () => {} });
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
        <Button type="submit" className="w-full" disabled={form.processing}>
          {form.processing ? 'Please wait…' : 'Send reset link'}
        </Button>
      </form>
      <Link href="/login" className="py-2 text-sm text-primary underline">
        Back to login
      </Link>
    </AuthLayout>
  );
}
