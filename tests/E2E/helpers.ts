import { expect, type Page } from '@playwright/test';

export type Account = 'employee' | 'agent' | 'admin';
export const authFile = (account: Account) => `playwright/.auth/${account}.json`;

export async function login(page: Page, account: Account) {
  await page.goto('/login');
  await page.getByRole('combobox', { name: /^(Language|Bahasa)$/ }).selectOption('en');
  await page.getByLabel('Email', { exact: true }).fill(`${account}@example.test`);
  await page.getByLabel('Password', { exact: true }).fill('local-password');
  await page.getByRole('button', { name: 'Log in', exact: true }).click();
  await expect(page.getByRole('button', { name: /User menu for/ })).toBeVisible();
}

export async function logout(page: Page) {
  await page.getByRole('button', { name: /User menu for/ }).click();
  await page.getByRole('menuitem', { name: 'Log out' }).click();
  await expect(page).toHaveURL(/\/$/);
}

export async function createTicket(page: Page, title: string) {
  await page.goto('/tickets/create');
  await page.getByLabel('Title', { exact: true }).fill(title);
  await page
    .getByLabel('Description', { exact: true })
    .fill('A browser test helpdesk request requiring investigation.');
  await page.getByRole('button', { name: 'Create ticket', exact: true }).click();
  await expect(page).toHaveURL(/\/tickets\/\d+$/);
  await expectStatus(page, 'Open');
  return new URL(page.url()).pathname;
}

export async function expectStatus(page: Page, status: string) {
  // The badge is outside the timeline and transition selector.
  await expect(
    page.locator('main span').filter({ hasText: new RegExp(`^${status}$`) }),
  ).toBeVisible();
}

export async function transition(page: Page, status: string) {
  await page.getByLabel('Next status').selectOption({ label: status });
  await page.getByRole('button', { name: 'Change status', exact: true }).click();
  const dialog = page.getByRole('dialog');
  await expect(dialog.getByRole('heading', { name: 'Change ticket status?' })).toBeVisible();
  await dialog.getByRole('button', { name: `Move to ${status}`, exact: true }).click();
  await expect(dialog).not.toBeVisible();
  await expectStatus(page, status);
}
