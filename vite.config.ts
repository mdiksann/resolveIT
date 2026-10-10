import { fileURLToPath, URL } from 'node:url';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
export default defineConfig({
  server: {
    host: '127.0.0.1',
    port: 5173,
    cors: true,
  },
  resolve: { alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) } },
  plugins: [laravel({ input: ['resources/js/app.tsx'], refresh: true }), react(), tailwindcss()],
});
