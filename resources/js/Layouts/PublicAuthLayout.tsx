import type { ReactNode } from 'react';
import PublicLayout from './PublicLayout';
import type { PublicLanguage } from '../Lib/usePublicLanguage';
import { FlashMessages } from '../Components/FlashMessages';

export default function PublicAuthLayout({
  title,
  description,
  language,
  setLanguage,
  children,
}: {
  title: string;
  description: string;
  language: PublicLanguage;
  setLanguage: (language: PublicLanguage) => void;
  children: ReactNode;
}) {
  const t = (en: string, id: string) => (language === 'en' ? en : id);
  return (
    <PublicLayout language={language} setLanguage={setLanguage}>
      <main id="main-content" className="public-auth" tabIndex={-1}>
        <div className="public-container public-auth-grid">
          <section className="public-auth-form">
            <h1>{title}</h1>
            <p className="public-auth-description">{description}</p>
            <FlashMessages />
            {children}
          </section>
          <aside className="public-auth-visual">
            <img
              src="/images/it-team.jpg"
              alt={t(
                'Colleagues collaborating around a shared table',
                'Rekan kerja berkolaborasi di meja bersama',
              )}
              width="1600"
              height="2399"
            />
            <div>
              <h2>{t('Good work starts with a team.', 'Kerja yang baik dimulai bersama.')}</h2>
              <p>
                {t(
                  'Keep every request, conversation, and next step together.',
                  'Satukan setiap permintaan, percakapan, dan langkah berikutnya.',
                )}
              </p>
            </div>
          </aside>
        </div>
      </main>
    </PublicLayout>
  );
}
