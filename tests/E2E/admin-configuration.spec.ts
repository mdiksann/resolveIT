import { test, expect } from '@playwright/test';
import { authFile } from './helpers';

test.use({ storageState: authFile('admin') });

test('admin configures category and default SLA priority; employee sees options; agent is blocked from administration', async ({
  page,
  browser,
}) => {
  const suffix = `${test.info().repeatEachIndex}-${test.info().retry}`;
  const category = `e2e category ${suffix}`;
  const priority = `e2e priority ${suffix}`;
  await page.goto('/admin/categories');
  await page.getByLabel('New category name', { exact: true }).fill(category);
  await page.getByRole('button', { name: 'Create category', exact: true }).click();
  await expect(page.getByRole('form', { name: `Rename ${category}`, exact: true })).toBeVisible();
  await page.goto('/admin/priorities');
  await page.getByLabel('New priority name', { exact: true }).fill(priority);
  await page.locator('#rank').fill('5');
  await page.locator('#sla_hours').fill('12');
  await page.getByLabel('Make default', { exact: true }).check();
  await page.getByRole('button', { name: 'Create priority', exact: true }).click();
  const row = page
    .getByRole('row')
    .filter({ has: page.getByRole('form', { name: `Edit ${priority}`, exact: true }) });
  await expect(row).toContainText('Default');
  await expect(row.getByLabel('SLA hours', { exact: true })).toHaveValue('12');

  const employee = await browser.newContext({
    baseURL: new URL(page.url()).origin,
    storageState: authFile('employee'),
  });
  try {
    const employeePage = await employee.newPage();
    await employeePage.goto('/tickets/create');
    await expect(
      employeePage.getByLabel('Category (optional)').locator('option', { hasText: category }),
    ).toHaveCount(1);
    const select = employeePage.getByLabel('Priority', { exact: true });
    const option = select.locator('option').filter({ hasText: priority });
    await expect(option).toHaveCount(1);
    await expect(select).toHaveValue((await option.getAttribute('value')) ?? 'missing');
    await employeePage.getByLabel('Title', { exact: true }).fill(`Configured SLA ${suffix}`);
    await employeePage
      .getByLabel('Description', { exact: true })
      .fill('The newly configured default should set a twelve hour SLA.');
    await employeePage.getByLabel('Category (optional)').selectOption({ label: category });
    // Explicit default fallback exercises the server's default, too.
    await select.selectOption('');
    await employeePage.getByRole('button', { name: 'Create ticket', exact: true }).click();
    await expect(employeePage).toHaveURL(/\/tickets\/\d+$/);
    await expect(employeePage.locator('main').getByText(priority, { exact: true })).toBeVisible();
    const details = employeePage.getByRole('region', { name: 'Ticket details' });
    await expect(details).toContainText(category);
    const created = await details
      .locator('dt', { hasText: /^Created$/ })
      .locator('..')
      .locator('time')
      .getAttribute('datetime');
    const due = await details
      .locator('dt', { hasText: /^Due$/ })
      .locator('..')
      .locator('time')
      .getAttribute('datetime');
    expect(Date.parse(due ?? '') - Date.parse(created ?? '')).toBe(12 * 60 * 60 * 1000);
  } finally {
    await employee.close();
  }

  const agent = await browser.newContext({
    baseURL: new URL(page.url()).origin,
    storageState: authFile('agent'),
  });
  try {
    const agentPage = await agent.newPage();
    await agentPage.goto('/dashboard');
    for (const name of ['Categories', 'Priorities', 'Users']) {
      await expect(agentPage.getByRole('link', { name, exact: true })).toHaveCount(0);
    }
    const overdue = agentPage
      .locator('a[href="/tickets?overdue=1"]')
      .filter({ has: agentPage.locator('dt', { hasText: /^Overdue$/ }) });
    // DemoSeeder creates 12 overdue Open/Assigned/In Progress tickets; new tickets are not overdue.
    await expect(overdue.locator('dd')).toHaveText('12');
    await overdue.click();
    await expect(agentPage.getByRole('row').filter({ hasText: '[Demo 01]' })).toContainText(
      'Overdue by',
    );
    for (const url of ['/admin', '/admin/categories', '/admin/priorities', '/admin/users']) {
      const response = await agentPage.goto(url);
      expect(response?.status()).toBe(403);
      await expect(agentPage.getByRole('heading', { name: /Access denied/i })).toBeVisible();
    }
  } finally {
    await agent.close();
  }
});
