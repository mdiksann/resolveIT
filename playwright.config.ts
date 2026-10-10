import { defineConfig, devices } from '@playwright/test';
import { readFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { parseEnv } from 'node:util';

const local = existsSync('.env') ? parseEnv(readFileSync('.env', 'utf8')) : {};
const inherited = Object.fromEntries(
  Object.entries(process.env).filter((entry): entry is [string, string] => entry[1] !== undefined),
);
const database = process.env.E2E_DB_DATABASE ?? 'resolveit_e2e';
if (!/^[a-z][a-z0-9_]*_e2e$/.test(database)) {
  throw new Error('E2E_DB_DATABASE must name a disposable PostgreSQL database ending in _e2e.');
}
const storage = resolve('storage/e2e');

export default defineConfig({
  testDir: './tests/E2E',
  fullyParallel: false,
  timeout: 120_000,
  workers: 1,
  forbidOnly: !!process.env.CI,
  failOnFlakyTests: true,
  retries: process.env.CI ? 1 : 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: 'http://127.0.0.1:8011',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'setup', testMatch: /auth\.setup\.ts/ },
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
      dependencies: ['setup'],
      testIgnore: /auth\.setup\.ts/,
    },
  ],
  webServer: {
    command: 'php tests/E2E/prepare.php && php -S 127.0.0.1:8011 -t public tests/E2E/router.php',
    url: 'http://127.0.0.1:8011/login',
    reuseExistingServer: false,
    timeout: 120_000,
    env: {
      ...local,
      ...inherited,
      APP_ENV: 'local', // DemoSeeder remains local-only; real browser CSRF stays enabled.
      APP_DEBUG: 'false',
      APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
      APP_URL: 'http://127.0.0.1:8011',
      APP_CONFIG_CACHE: `${storage}/config.php`,
      APP_ROUTES_CACHE: `${storage}/routes.php`,
      LARAVEL_STORAGE_PATH: storage,
      DB_CONNECTION: 'pgsql',
      DB_DATABASE: database,
      DB_URL: '',
      SESSION_CONNECTION: 'pgsql',
      SESSION_DRIVER: 'database',
      SESSION_COOKIE: 'resolveit_e2e_session',
      SESSION_DOMAIN: '',
      SESSION_SECURE_COOKIE: 'false',
      SESSION_ENCRYPT: 'true',
      CACHE_STORE: 'array',
      QUEUE_CONNECTION: 'database',
      MAIL_MAILER: 'array',
      LOG_CHANNEL: 'single',
    },
  },
});
