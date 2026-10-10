import { test, expect } from '@playwright/test';
import { readFile } from 'node:fs/promises';
import { authFile, createTicket } from './helpers';

test.use({ storageState: authFile('employee') });

test('private attachment rejects HTML, uploads, downloads and is visible to the agent', async ({
  page,
  browser,
}) => {
  const path = await createTicket(
    page,
    `Attachment request ${test.info().repeatEachIndex}-${test.info().retry}`,
  );
  const input = page.getByLabel('Upload attachment', { exact: true });
  await input.setInputFiles({
    name: 'rejected.html',
    mimeType: 'text/html',
    buffer: Buffer.from('<html><body>Rejected file</body></html>'),
  });
  await page.getByRole('button', { name: 'Upload file', exact: true }).click();
  await expect(input).toHaveAttribute('aria-invalid', 'true');
  await expect(page.locator('#file-error')).toContainText(/file|type|extension/i);
  await expect(page.getByRole('link', { name: 'rejected.html', exact: true })).toHaveCount(0);
  const content = 'VPN diagnostic: connection timed out.\n';
  await input.setInputFiles({
    name: 'diagnostic.txt',
    mimeType: 'text/plain',
    buffer: Buffer.from(content),
  });
  await page.getByRole('button', { name: 'Upload file', exact: true }).click();
  const file = page.getByRole('link', { name: 'diagnostic.txt', exact: true });
  await expect(file).toBeVisible();
  await expect(page.locator('#file-error')).toHaveCount(0);
  const downloadPromise = page.waitForEvent('download');
  await file.click();
  const download = await downloadPromise;
  expect(download.suggestedFilename()).toBe('diagnostic.txt');
  const downloadPath = await download.path();
  expect(downloadPath).not.toBeNull();
  if (downloadPath === null) throw new Error('Download did not produce a file.');
  expect(await readFile(downloadPath, 'utf8')).toBe(content);

  const agent = await browser.newContext({
    baseURL: new URL(page.url()).origin,
    storageState: authFile('agent'),
  });
  try {
    const agentPage = await agent.newPage();
    await agentPage.goto(path);
    await expect(
      agentPage.getByRole('link', { name: 'diagnostic.txt', exact: true }),
    ).toBeVisible();
    await expect(
      agentPage
        .getByRole('region', { name: 'Attachments', exact: true })
        .getByRole('button', { name: 'Remove', exact: true }),
    ).toHaveCount(0);
  } finally {
    await agent.close();
  }
});
