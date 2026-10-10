import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { SharedProps } from '../Types';
import type { PublicLanguage } from '../Lib/usePublicLanguage';

export default function PublicLayout({
  language,
  setLanguage,
  children,
}: {
  language: PublicLanguage;
  setLanguage: (language: PublicLanguage) => void;
  children: ReactNode;
}) {
  const { appName, auth } = usePage<SharedProps>().props;
  const t = (en: string, id: string) => (language === 'en' ? en : id);
  return (
    <div className="public-site" lang={language}>
      <a className="public-skip" href="#main-content">
        {t('Skip to content', 'Lewati ke konten')}
      </a>
      <header className="public-header">
        <div className="public-header-inner">
          <Link
            href="/landing"
            className="public-brand"
            aria-label={`${appName} ${t('home', 'beranda')}`}
          >
            {appName}
          </Link>
          <nav className="public-nav" aria-label={t('Main navigation', 'Navigasi utama')}>
            <a href="/landing#why-resolveit">{t('For IT teams', 'Untuk tim IT')}</a>
            <a href="/landing#faq">FAQ</a>
          </nav>
          <div className="public-header-actions">
            <select
              aria-label={t('Language', 'Bahasa')}
              value={language}
              onChange={(event) => setLanguage(event.target.value as PublicLanguage)}
              className="public-language"
            >
              <option value="en">EN</option>
              <option value="id">ID</option>
            </select>
            {auth.user ? (
              <Link
                className="public-button public-button-small"
                href={auth.can.viewAnyTicket ? '/dashboard' : '/tickets'}
              >
                {t('Open app', 'Buka aplikasi')}
              </Link>
            ) : (
              <>
                <Link className="public-login-link" href="/login">
                  {t('Log in', 'Masuk')}
                </Link>
                <Link className="public-button public-button-small" href="/register">
                  {t('Sign up', 'Daftar')}
                </Link>
              </>
            )}
          </div>
        </div>
      </header>
      {children}
      <footer className="public-footer">
        <div className="public-container public-footer-inner">
          <div>
            <Link href="/landing" className="public-brand">
              {appName}
            </Link>
            <p>
              {t('A clearer day for your IT team.', 'Hari yang lebih teratur untuk tim IT Anda.')}
            </p>
          </div>
          <nav aria-label={t('Footer navigation', 'Navigasi footer')}>
            <Link href="/register">{t('Create an account', 'Buat akun')}</Link>
            <Link href="/login">{t('Log in', 'Masuk')}</Link>
            <a href="/landing#faq">FAQ</a>
          </nav>
          <small>
            © {new Date().getFullYear()} {appName}
          </small>
        </div>
      </footer>
    </div>
  );
}
