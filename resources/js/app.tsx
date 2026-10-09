import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';
const pages = import.meta.glob<{ default: ComponentType }>('./Pages/**/*.tsx');
createInertiaApp({
  title: (title) => `${title} · ${import.meta.env.VITE_APP_NAME || 'ResolveIT'}`,
  resolve: async (name) => {
    const load = pages[`./Pages/${name}.tsx`];
    if (!load) throw new Error(`Unknown Inertia page: ${name}`);
    return (await load()).default;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
  progress: { color: 'var(--color-primary)' },
});
