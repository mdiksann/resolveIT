import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import PublicLayout from '../Layouts/PublicLayout';
import { usePublicLanguage } from '../Lib/usePublicLanguage';
import type { SharedProps } from '../Types';

export default function Landing() {
  const { language, setLanguage, t } = usePublicLanguage();
  const { auth } = usePage<SharedProps>().props;
  const [email, setEmail] = useState('');
  const mainRef = useRef<HTMLElement>(null);
  useEffect(() => {
    const main = mainRef.current;
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (!main || motion.matches || !('IntersectionObserver' in window)) return;

    const targets = main.querySelectorAll<HTMLElement>('[data-scroll-reveal]');
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach(({ target, isIntersecting }) => {
          if (!isIntersecting) return;
          target.classList.remove('public-reveal-pending');
          observer.unobserve(target);
        });
      },
      { threshold: 0.08, rootMargin: '0px 0px -10% 0px' },
    );
    targets.forEach((target) => {
      if (target.getBoundingClientRect().top < window.innerHeight * 0.8) return;
      target.classList.add('public-reveal-pending');
      observer.observe(target);
    });
    const showAll = () => {
      observer.disconnect();
      targets.forEach((target) => target.classList.remove('public-reveal-pending'));
    };
    const revealFocused = (event: FocusEvent) => {
      if (!(event.target instanceof Element)) return;
      const target = event.target.closest('[data-scroll-reveal]');
      if (!target) return;
      target.classList.remove('public-reveal-pending');
      observer.unobserve(target);
    };
    motion.addEventListener('change', showAll);
    main.addEventListener('focusin', revealFocused);
    return () => {
      showAll();
      motion.removeEventListener('change', showAll);
      main.removeEventListener('focusin', revealFocused);
    };
  }, []);
  const appHref = auth.can.viewAnyTicket ? '/dashboard' : '/tickets';
  const faqs = [
    {
      question: t(
        'What can my IT team do with ResolveIT?',
        'Apa yang bisa dilakukan tim IT dengan ResolveIT?',
      ),
      answer: t(
        'Keep requests in one queue, assign tickets, set priorities, and follow each issue through to resolution. Comments, attachments, and activity history stay with the ticket.',
        'Kelola permintaan dalam satu antrean, tugaskan tiket, tentukan prioritas, dan pantau setiap masalah hingga selesai. Komentar, lampiran, dan riwayat aktivitas tersimpan bersama tiket.',
      ),
    },
    {
      question: t('How do SLA deadlines work?', 'Bagaimana tenggat SLA ditentukan?'),
      answer: t(
        'An administrator configures the SLA hours for each priority. ResolveIT calculates a deadline when a ticket is created and marks overdue tickets so your team can identify what needs attention.',
        'Administrator mengatur jam SLA untuk setiap prioritas. ResolveIT menghitung tenggat saat tiket dibuat dan menandai tiket yang terlambat agar tim tahu mana yang perlu ditangani.',
      ),
    },
    {
      question: t('Can we discuss a ticket privately?', 'Bisakah tim berdiskusi secara internal?'),
      answer: t(
        'Yes. Agents and administrators can add internal notes that employees cannot see. Public comments keep the employee informed about their request.',
        'Ya. Agent dan administrator dapat menambahkan catatan internal yang tidak terlihat oleh karyawan. Komentar publik digunakan untuk memberi kabar kepada pelapor.',
      ),
    },
    {
      question: t(
        'Does signing up give me IT agent access?',
        'Apakah akun baru langsung mendapat akses Agent?',
      ),
      answer: t(
        'New accounts start with the Employee role and can submit and track their own tickets. An administrator must assign the Agent role before you can manage the IT queue.',
        'Akun baru memiliki peran Employee untuk membuat dan memantau tiket sendiri. Administrator perlu memberikan peran Agent sebelum Anda dapat mengelola antrean tim IT.',
      ),
    },
    {
      question: t('What happens if an issue comes back?', 'Bagaimana jika masalah muncul kembali?'),
      answer: t(
        'The employee can reopen their resolved ticket if the issue persists, or close it once the fix is confirmed. The ticket history stays available for the next investigation.',
        'Karyawan dapat membuka kembali tiket berstatus Resolved jika masalah belum tuntas, atau menutupnya setelah solusi dikonfirmasi. Riwayat tiket tetap tersedia untuk investigasi berikutnya.',
      ),
    },
  ];

  return (
    <PublicLayout language={language} setLanguage={setLanguage}>
      <Head title={t('IT support, all in one place', 'Layanan IT dalam satu tempat')}>
        <meta
          name="description"
          content={t(
            'Give your IT team one place to manage requests, priorities, and SLA deadlines with ResolveIT.',
            'Kelola permintaan, prioritas, dan tenggat SLA tim IT dalam satu tempat dengan ResolveIT.',
          )}
        />
      </Head>
      <main id="main-content" tabIndex={-1} ref={mainRef}>
        <div className="public-announcement">
          {t(
            'Built for the people who keep work moving.',
            'Untuk tim yang menjaga pekerjaan tetap berjalan.',
          )}
          <a href="#why-resolveit">
            {t('Meet ResolveIT', 'Kenali ResolveIT')} <span aria-hidden="true">↗</span>
          </a>
        </div>
        <section className="public-hero">
          <div className="public-container public-hero-grid">
            <div className="public-hero-copy">
              <p className="public-kicker">{t('For IT teams', 'Untuk tim IT')}</p>
              <h1>
                {t('Make every request a resolved one.', 'Bantu tim kerja. Tuntaskan kendala.')}
              </h1>
              <p className="public-lead">
                {t(
                  'Give every ticket an owner. Keep priorities clear and your team focused on the next fix.',
                  'Pastikan setiap tiket punya penanggung jawab. Perjelas prioritas agar tim fokus menyelesaikan kendala berikutnya.',
                )}
              </p>
              {auth.user ? (
                <Link href={appHref} className="public-button">
                  {t('Open your workspace', 'Buka ruang kerja')}
                </Link>
              ) : (
                <form
                  className="public-signup-form"
                  onSubmit={(event) => {
                    event.preventDefault();
                    router.get('/register', { email: email.trim() });
                  }}
                >
                  <label htmlFor="signup-email">
                    {t('Your email address', 'Alamat email Anda')}
                  </label>
                  <input
                    id="signup-email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    placeholder="you@company.com"
                    required
                    value={email}
                    onChange={(event) => setEmail(event.target.value)}
                  />
                  <button type="submit" className="public-button">
                    {t('Create your account', 'Buat akun Anda')}
                  </button>
                  <p className="public-form-note">
                    {t('Already have an account?', 'Sudah punya akun?')}{' '}
                    <Link href="/login">{t('Log in', 'Masuk')}</Link>
                  </p>
                </form>
              )}
            </div>
            <div className="public-hero-photo">
              <img
                src="/images/it-team.jpg"
                alt={t(
                  'Colleagues working together with laptops around a shared table',
                  'Rekan kerja berkolaborasi menggunakan laptop di meja bersama',
                )}
                width="1600"
                height="2399"
                fetchPriority="high"
              />
              <div className="public-photo-caption">
                {t('Behind every fix, there’s a team.', 'Di balik setiap solusi, ada tim Anda.')}
              </div>
            </div>
          </div>
        </section>

        <section id="why-resolveit" className="public-benefits public-container" data-scroll-reveal>
          <div className="public-benefits-intro">
            <h2>{t('Less chasing. More resolving.', 'Lebih terarah. Lebih cepat ditangani.')}</h2>
            <p>
              {t(
                'A shared place for the requests that keep your team busy.',
                'Satu tempat untuk permintaan yang ditangani tim Anda setiap hari.',
              )}
            </p>
          </div>
          <div className="public-benefit-columns">
            {[
              [
                t('One queue. Clear ownership.', 'Satu antrean. Tanggung jawab jelas.'),
                t(
                  'Assign each request to an agent so everyone knows who is handling it and what comes next.',
                  'Tugaskan setiap permintaan kepada Agent agar semua tahu siapa yang menangani dan apa langkah berikutnya.',
                ),
              ],
              [
                t('Put urgent work first.', 'Dahulukan yang mendesak.'),
                t(
                  'Use priorities and SLA deadlines to spot overdue tickets and decide what needs attention.',
                  'Gunakan prioritas dan tenggat SLA untuk melihat tiket terlambat dan menentukan mana yang perlu perhatian.',
                ),
              ],
              [
                t('Keep the whole story.', 'Simpan konteks lengkapnya.'),
                t(
                  'Find conversations, attachments, and activity history right where the issue is being solved.',
                  'Temukan percakapan, lampiran, dan riwayat aktivitas langsung di tiket yang sedang ditangani.',
                ),
              ],
            ].map(([title, description]) => (
              <div key={title}>
                <h3>{title}</h3>
                <p>{description}</p>
              </div>
            ))}
          </div>
        </section>

        <section className="public-story">
          <div className="public-container public-story-grid" data-scroll-reveal>
            <img
              src="/images/team-collaboration.jpg"
              alt={t(
                'Two colleagues discussing an issue on a laptop',
                'Dua rekan kerja mendiskusikan masalah di laptop',
              )}
              width="1100"
              height="733"
              loading="lazy"
            />
            <div>
              <h2>{t('Work together on the next fix.', 'Selesaikan kendala bersama.')}</h2>
              <p>
                {t(
                  'Talk to employees through public comments. Give your IT team the technical context in internal notes. Keep both conversations connected to the same ticket.',
                  'Berkomunikasi dengan karyawan lewat komentar publik. Bagikan konteks teknis kepada tim IT melalui catatan internal. Kedua percakapan tetap terhubung pada tiket yang sama.',
                )}
              </p>
              <Link
                href={auth.user ? appHref : '/register'}
                className="public-button public-button-outline"
              >
                {auth.user ? t('Open app', 'Buka aplikasi') : t('Create an account', 'Buat akun')}
              </Link>
            </div>
          </div>
        </section>

        <section id="faq" className="public-faq public-container" data-scroll-reveal>
          <h2>{t('A few things to know.', 'Hal yang perlu Anda tahu.')}</h2>
          <div className="public-faq-list">
            {faqs.map(({ question, answer }) => (
              <details key={question}>
                <summary>
                  {question}
                  <span className="public-faq-icon" aria-hidden="true" />
                </summary>
                <p>{answer}</p>
              </details>
            ))}
          </div>
        </section>

        <section className="public-final-cta">
          <div className="public-container" data-scroll-reveal>
            <h2>{t('Your next fix starts here.', 'Mulai langkah menuju solusi.')}</h2>
            <p>
              {t(
                'Bring your requests into one place with ResolveIT.',
                'Kelola permintaan Anda dalam satu tempat bersama ResolveIT.',
              )}
            </p>
            <Link
              href={auth.user ? appHref : '/register'}
              className="public-button public-button-white"
            >
              {auth.user
                ? t('Open app', 'Buka aplikasi')
                : t('Create your account', 'Buat akun Anda')}
            </Link>
          </div>
        </section>
      </main>
    </PublicLayout>
  );
}
