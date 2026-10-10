import { Head, Link, useForm } from '@inertiajs/react';
import PublicAuthLayout from '../../Layouts/PublicAuthLayout';
import { usePublicLanguage } from '../../Lib/usePublicLanguage';
import { FormField } from '../../Components/FormField';
import { Button } from '../../Components/ui/button';
import { Alert } from '../../Components/ui/alert';
export default function Register({ status }: { status?: string }) {
  const { language, setLanguage, t } = usePublicLanguage();
  const defaultEmail =
    typeof window !== 'undefined'
      ? new URLSearchParams(window.location.search).get('email') || ''
      : '';
  const form = useForm({
    name: '',
    email: defaultEmail,
    password: '',
    password_confirmation: '',
  });
  return (
    <PublicAuthLayout
      language={language}
      setLanguage={setLanguage}
      title={t('Your next fix starts here.', 'Mulai langkah menuju solusi.')}
      description={t(
        'Create your ResolveIT account to submit and track requests.',
        'Buat akun ResolveIT untuk mengajukan dan memantau tiket.',
      )}
    >
      <Head title={t('Create account', 'Buat akun')} />
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
          label={t('Full name', 'Nama lengkap')}
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
          label={t('Password (12 characters minimum)', 'Kata sandi (minimal 12 karakter)')}
          type="password"
          autoComplete="new-password"
          required
          value={form.data.password}
          onChange={(event) => form.setData('password', event.target.value)}
          error={form.errors.password}
        />
        <FormField
          id="password_confirmation"
          label={t('Confirm password', 'Konfirmasi kata sandi')}
          type="password"
          autoComplete="new-password"
          required
          value={form.data.password_confirmation}
          onChange={(event) => form.setData('password_confirmation', event.target.value)}
          error={form.errors.password_confirmation}
        />
        <Button type="submit" className="w-full" disabled={form.processing}>
          {form.processing ? t('Please wait…', 'Mohon tunggu…') : t('Create account', 'Buat akun')}
        </Button>
      </form>
      <p className="public-form-note">
        {t(
          'Your account starts as an Employee. An administrator can grant IT Agent access.',
          'Akun Anda dimulai sebagai Employee. Administrator dapat memberikan akses Agent.',
        )}
      </p>
      <Link href="/login" className="py-2 text-sm text-primary underline">
        {t('Already have an account? Log in', 'Sudah punya akun? Masuk')}
      </Link>
    </PublicAuthLayout>
  );
}
