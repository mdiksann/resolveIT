import { test as setup } from '@playwright/test';
import { authFile, login, type Account } from './helpers';

for (const account of ['employee', 'agent', 'admin'] satisfies Account[]) {
  setup(`can log in as ${account}`, async ({ page }) => {
    await login(page, account);
    await page.context().storageState({ path: authFile(account) });
  });
}
