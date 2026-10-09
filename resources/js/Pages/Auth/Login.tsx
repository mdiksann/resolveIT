import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import { FormField } from '../../Components/FormField';
import { Button } from '../../Components/ui/button';
import { Alert } from '../../Components/ui/alert';
export default function Login({ status }: { status?: string }) {
  const form = useForm({ email: '', password: '', remember: false });
  return (
    <AuthLayout title="Log in">
      <Head title="Log in" />
      {status && <Alert>{status}</Alert>}
      <form
        className="space-y-5"
        onSubmit={(event) => {
          event.preventDefault();
          form.post('/login', {
            onFinish: () => {
              form.reset('password');
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
          label="Password"
          type="password"
          autoComplete="current-password"
          required
          value={form.data.password}
          onChange={(event) => form.setData('password', event.target.value)}
          error={form.errors.password}
        />
        <label className="flex min-h-11 items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={form.data.remember}
            onChange={(event) => form.setData('remember', event.target.checked)}
          />
          Remember me
        </label>
        <Link href="/forgot-password" className="block py-2 text-sm text-primary underline">
          Forgot password?
        </Link>
        <Button type="submit" className="w-full" disabled={form.processing}>
          {form.processing ? 'Please wait…' : 'Log in'}
        </Button>
      </form>
      <Link href="/register" className="py-2 text-sm text-primary underline">
        Create an account
      </Link>
    </AuthLayout>
  );
}
