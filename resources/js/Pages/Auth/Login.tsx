import { Head, Link, useForm } from '@inertiajs/react';
import PublicAuthLayout from '../../Layouts/PublicAuthLayout';
import { usePublicLanguage } from '../../Lib/usePublicLanguage';
import { FormField } from '../../Components/FormField';
import { Button } from '../../Components/ui/button';
import { Alert } from '../../Components/ui/alert';
export default function Login({ status }: { status?: string }) {
  const { language, setLanguage, t } = usePublicLanguage();
  const form = useForm({ email: '', password: '', remember: false });
  return (
    <PublicAuthLayout
      language={language}
      setLanguage={setLanguage}
      title={t('Welcome back.', 'Selamat datang kembali.')}
      description={t(
        'Log in to keep your requests moving.',
        'Masuk untuk melanjutkan penanganan tiket Anda.',
      )}
    >
      <Head title={t('Log in', 'Masuk')} />
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
          label={t('Password', 'Kata sandi')}
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
          {t('Remember me', 'Ingat saya')}
        </label>
        <Link href="/forgot-password" className="block py-2 text-sm text-primary underline">
          {t('Forgot password?', 'Lupa kata sandi?')}
        </Link>
        <Button type="submit" className="w-full" disabled={form.processing}>
          {form.processing ? t('Please wait…', 'Mohon tunggu…') : t('Log in', 'Masuk')}
        </Button>
      </form>
      <Link href="/register" className="py-2 text-sm text-primary underline">
        {t('Create an account', 'Buat akun baru')}
      </Link>
    </PublicAuthLayout>
  );
}
