import { test, expect } from '@playwright/test';
import { createTicket, expectStatus, login, logout, transition } from './helpers';

test('employee and agent complete the ticket lifecycle with a private note', async ({ page }) => {
  const title = `Lifecycle request ${test.info().repeatEachIndex}-${test.info().retry}`;
  await login(page, 'employee');
  const path = await createTicket(page, title);
  const details = page.getByRole('region', { name: 'Ticket details' });
  await expect(
    details.locator('dt', { hasText: /^Due$/ }).locator('..').locator('time'),
  ).toHaveAttribute('datetime', /\d{4}-\d{2}-\d{2}T/);
  await logout(page);

  await login(page, 'agent');
  await page.goto('/tickets');
  await page.getByLabel('Search titles').fill(title);
  await page.getByRole('button', { name: 'Apply filters' }).click();
  await page.getByRole('link', { name: new RegExp(title) }).click();
  await expect(page).toHaveURL(new RegExp(`${path}$`));
  await expectStatus(page, 'Open');
  await page.getByRole('button', { name: 'Assign to me', exact: true }).click();
  await expectStatus(page, 'Assigned');
  await transition(page, 'In Progress');
  await page.getByLabel('Visibility').selectOption('internal');
  await page
    .getByLabel('Internal note', { exact: true })
    .fill('Private diagnostic: inspect infrastructure logs.');
  await page.getByRole('button', { name: 'Add internal note', exact: true }).click();
  await expect(
    page.getByText('Private diagnostic: inspect infrastructure logs.', { exact: true }),
  ).toBeVisible();
  await expect(page.getByLabel('Internal note', { exact: true })).toHaveValue('');
  await expect(page.getByRole('button', { name: 'Add internal note', exact: true })).toBeEnabled();
  await page.getByLabel('Visibility').selectOption('public');
  await page
    .getByLabel('Public comment', { exact: true })
    .fill('The connection has been repaired. Please verify.');
  await page.getByRole('button', { name: 'Add comment', exact: true }).click();
  await expect(
    page.getByText('The connection has been repaired. Please verify.', { exact: true }),
  ).toBeVisible();
  await transition(page, 'Resolved');
  await logout(page);

  await login(page, 'employee');
  await page.goto(path);
  await expectStatus(page, 'Resolved');
  await expect(
    page.getByText('Private diagnostic: inspect infrastructure logs.', { exact: true }),
  ).toHaveCount(0);
  await expect(page.getByLabel('Visibility')).toHaveCount(0);
  await expect(page.getByRole('region', { name: 'Activity', exact: true })).not.toContainText(
    'Added internal note',
  );
  await transition(page, 'In Progress');
  await logout(page);

  await login(page, 'agent');
  await page.goto(path);
  await expectStatus(page, 'In Progress');
  await transition(page, 'Resolved');
  await logout(page);

  await login(page, 'employee');
  await page.goto(path);
  await expectStatus(page, 'Resolved');
  await transition(page, 'Closed');
  const timeline = page.getByRole('region', { name: 'Activity', exact: true });
  await expect(timeline.locator('li')).toHaveCount(8);
  await expect(timeline.locator('li p')).toHaveText([
    'Employee · Status: Resolved → Closed',
    'Agent · Status: In Progress → Resolved',
    'Employee · Status: Resolved → In Progress',
    'Agent · Status: In Progress → Resolved',
    'Agent · Added public comment',
    'Agent · Status: Assigned → In Progress',
    'Agent · Assignment: None → Agent',
    'Employee · Created ticket',
  ]);
  await expect(page.getByRole('button', { name: 'Change status', exact: true })).toHaveCount(0);
  await expect(page.getByText('Comments are unavailable on closed tickets.')).toBeVisible();
});
