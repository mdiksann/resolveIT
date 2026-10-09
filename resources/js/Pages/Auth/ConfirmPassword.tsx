import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import { FormField } from '../../Components/FormField';
import { Button } from '../../Components/ui/button';
export default function ConfirmPassword() {
  const form = useForm({ password: '' });
  return (
    <AuthLayout title="Confirm password">
      <Head title="Confirm password" />
      <p className="text-sm text-muted-foreground">Enter your password to continue.</p>
      <form
        className="space-y-5"
        onSubmit={(event) => {
          event.preventDefault();
          form.post('/user/confirm-password', { onFinish: () => form.reset('password') });
        }}
      >
        <FormField
          id="password"
          label="Password"
          type="password"
          autoComplete="current-password"
          required
          value={form.data.password}
          onChange={(event) => form.setData('password', event.target.value)}
          error={form.errors.password}
        />
        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Please wait…' : 'Confirm password'}
        </Button>
      </form>
      <Link href="/dashboard" className="min-h-11 py-3 text-sm text-primary underline">
        Return to application
      </Link>
    </AuthLayout>
  );
}
