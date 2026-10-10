import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { FormField } from '../../Components/FormField';
import { Button } from '../../Components/ui/button';
import { Alert } from '../../Components/ui/alert';
import { useRequestFeedback } from '../../Lib/useRequestFeedback';

export default function Profile({ profile }: { profile: { name: string; email: string } }) {
  const form = useForm(profile);
  const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
  const profileFeedback = useRequestFeedback();
  const passwordFeedback = useRequestFeedback();
  return (
    <AppLayout title="Profile settings">
      <Head title="Profile settings" />
      <div className="grid max-w-5xl gap-6 xl:grid-cols-2">
        <section
          aria-labelledby="profile-heading"
          className="rounded-panel border border-border bg-surface p-5 sm:p-6"
        >
          <h2 id="profile-heading" className="text-xl font-bold tracking-tight">
            Profile information
          </h2>
          <p className="mt-2 mb-6 text-sm text-muted-foreground">
            Update your name and email address.
          </p>
          {profileFeedback.failure && (
            <Alert role="alert" className="mb-4 border-destructive text-destructive">
              {profileFeedback.failure}
            </Alert>
          )}
          <form
            className="space-y-5"
            onSubmit={(event) => {
              event.preventDefault();
              form.patch('/settings/profile', { ...profileFeedback.options, preserveScroll: true });
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
            <Button type="submit" className="rounded-full px-6" disabled={form.processing}>
              {form.processing ? 'Saving…' : 'Save changes'}
            </Button>
          </form>
        </section>
        <section
          aria-labelledby="password-heading"
          className="rounded-panel border border-border bg-surface p-5 sm:p-6"
        >
          <h2 id="password-heading" className="text-xl font-bold tracking-tight">
            Change password
          </h2>
          <p className="mt-2 mb-6 text-sm text-muted-foreground">
            Confirm your current password and choose a new one with at least 12 characters.
          </p>
          {passwordFeedback.failure && (
            <Alert role="alert" className="mb-4 border-destructive text-destructive">
              {passwordFeedback.failure}
            </Alert>
          )}
          <form
            className="space-y-5"
            onSubmit={(event) => {
              event.preventDefault();
              passwordForm.put('/settings/password', {
                ...passwordFeedback.options,
                preserveScroll: true,
                onSuccess: () => passwordForm.reset(),
                onError: () =>
                  passwordForm.reset('current_password', 'password', 'password_confirmation'),
              });
            }}
          >
            <FormField
              id="current_password"
              label="Current password"
              type="password"
              autoComplete="current-password"
              required
              value={passwordForm.data.current_password}
              onChange={(event) => passwordForm.setData('current_password', event.target.value)}
              error={passwordForm.errors.current_password}
            />
            <FormField
              id="new_password"
              label="New password"
              type="password"
              autoComplete="new-password"
              minLength={12}
              required
              value={passwordForm.data.password}
              onChange={(event) => passwordForm.setData('password', event.target.value)}
              error={passwordForm.errors.password}
            />
            <FormField
              id="password_confirmation"
              label="Confirm new password"
              type="password"
              autoComplete="new-password"
              minLength={12}
              required
              value={passwordForm.data.password_confirmation}
              onChange={(event) =>
                passwordForm.setData('password_confirmation', event.target.value)
              }
              error={passwordForm.errors.password_confirmation}
            />
            <Button type="submit" className="rounded-full px-6" disabled={passwordForm.processing}>
              {passwordForm.processing ? 'Updating…' : 'Update password'}
            </Button>
          </form>
        </section>
      </div>
    </AppLayout>
  );
}
