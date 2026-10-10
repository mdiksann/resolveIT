import { expect, test } from '@playwright/test';

for (const viewport of [
  { width: 1920, height: 1080 },
  { width: 1536, height: 864 },
  { width: 1480, height: 752 },
  { width: 1440, height: 900 },
  { width: 1280, height: 720 },
  { width: 1024, height: 768 },
  { width: 768, height: 1024 },
  { width: 360, height: 800 },
]) {
  test(`landing fits ${viewport.width}×${viewport.height} in both languages`, async ({ page }) => {
    await page.setViewportSize(viewport);
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto('/landing');
    await page.evaluate(() => document.fonts.ready);
    for (const language of ['en', 'id']) {
      await page.getByRole('combobox').selectOption(language);
      await expect(page.locator('.public-hero h1')).toBeVisible();
      expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(
        viewport.width,
      );
      if (viewport.width > 760) {
        const button = await page.locator('.public-signup-form button').boundingBox();
        if (!button) throw new Error('The signup button is missing.');
        expect(button.y + button.height).toBeLessThanOrEqual(viewport.height);
        const copy = await page.locator('.public-hero-copy').boundingBox();
        const brand = await page.locator('.public-header .public-brand').boundingBox();
        if (!copy || !brand) throw new Error('The hero or header is missing.');
        expect(Math.abs(copy.x - brand.x)).toBeLessThan(1);
      }
    }
    if (viewport.width === 1920 || viewport.width === 1480 || viewport.width === 360) {
      await page.screenshot({ path: test.info().outputPath('landing.png') });
    }
  });
}

test('sections reveal once and navigation scrolls to an unobscured FAQ', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await page.goto('/landing');
  const story = page.locator('.public-story-grid');
  await expect(story).toHaveClass(/public-reveal-pending/);
  await expect(story).toHaveCSS('opacity', '0');
  await expect(story).toHaveCSS('transform', 'matrix(1, 0, 0, 1, 0, 48)');
  expect(
    await page
      .locator('.public-hero h1')
      .evaluate((element) => getComputedStyle(element).animationName),
  ).toBe('public-enter');
  expect(
    await page
      .locator('.public-benefit-columns > div')
      .nth(2)
      .evaluate((element) => getComputedStyle(element).transitionDelay),
  ).toContain('0.28s');
  await story.scrollIntoViewIfNeeded();
  await expect(story).not.toHaveClass(/public-reveal-pending/);
  await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
  await page
    .getByRole('navigation', { name: /Main navigation|Navigasi utama/ })
    .getByText('FAQ')
    .click();
  await expect(page).toHaveURL(/\/landing#faq$/);
  await expect(page.locator('#faq')).not.toHaveClass(/public-reveal-pending/);
  await expect
    .poll(async () => {
      const faq = await page.locator('#faq').boundingBox();
      const header = await page.locator('.public-header').boundingBox();
      return (
        faq !== null && header !== null && faq.y >= header.height && faq.y < header.height + 60
      );
    })
    .toBe(true);
  await expect(story).not.toHaveClass(/public-reveal-pending/);
});

test('reduced motion keeps content readable and disables smooth scrolling', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/landing');
  await expect(page.locator('.public-hero h1')).toBeVisible();
  expect(
    await page
      .locator('.public-hero h1')
      .evaluate((element) => getComputedStyle(element).animationName),
  ).toBe('none');
  await expect(page.locator('.public-reveal-pending')).toHaveCount(0);
  expect(await page.evaluate(() => getComputedStyle(document.documentElement).scrollBehavior)).toBe(
    'auto',
  );
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await page.reload();
  await expect(page.locator('.public-reveal-pending').first()).toBeAttached();
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect(page.locator('.public-reveal-pending')).toHaveCount(0);
});
