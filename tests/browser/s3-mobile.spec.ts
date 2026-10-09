import { expect, test } from '@playwright/test';

// S3-7 rendered mobile proof for the library: no horizontal scroll, fixed
// cover aspect ratio, 44px control targets at 360/390, reflow at 320.
// S4 pre-slice: the ratio is owned by one fixed container (.fe-card-media,
// 16/9 = 1.777...); the 1.68-1.88 band is ±0.1 tolerance for subpixel
// rounding and border-box measurement at 320-390 px.

async function noHorizontalScroll(page): Promise<{ scroll: number; inner: number }> {
  return page.evaluate(() => ({
    scroll: document.scrollingElement?.scrollWidth ?? 0,
    inner: window.innerWidth,
  }));
}

test('S3-7 library at 360px: no scroll, kept aspect ratio, reachable controls', async ({ page }) => {
  await page.setViewportSize({ width: 360, height: 740 });
  await page.goto('/app');
  await page.locator('article.fe-card').first().waitFor();

  const { scroll, inner } = await noHorizontalScroll(page);
  expect(scroll).toBeLessThanOrEqual(inner);

  const cover = page.locator('.fe-card-media').first();
  const box = await cover.boundingBox();
  expect(box).not.toBeNull();
  expect(box!.width / box!.height).toBeGreaterThan(1.68);
  expect(box!.width / box!.height).toBeLessThan(1.88);

  for (const label of ['سطح', 'دسته', 'جست‌وجو در عنوان']) {
    const target = page.getByLabel(label);
    const size = await target.boundingBox();
    expect(size).not.toBeNull();
    expect(size!.height).toBeGreaterThanOrEqual(44);
  }
  const submit = page.getByRole('button', { name: 'اعمال' });
  const submitBox = await submit.boundingBox();
  expect(submitBox!.height).toBeGreaterThanOrEqual(44);
  expect(submitBox!.width).toBeGreaterThanOrEqual(44);

  await page.screenshot({ path: 'test-results/browser-shots/s3-mobile-360.png' });
});

test('S3-7 library at 390px: no scroll, kept aspect ratio', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/app');
  await page.locator('article.fe-card').first().waitFor();

  const { scroll, inner } = await noHorizontalScroll(page);
  expect(scroll).toBeLessThanOrEqual(inner);

  const box = await page.locator('.fe-card-media').first().boundingBox();
  expect(box).not.toBeNull();
  expect(box!.width / box!.height).toBeGreaterThan(1.68);
  expect(box!.width / box!.height).toBeLessThan(1.88);

  await page.screenshot({ path: 'test-results/browser-shots/s3-mobile-390.png' });
});

test('S3-7 library at 320px reflows without losing controls', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 700 });
  await page.goto('/app?level=A1');
  await page.locator('article.fe-card').first().waitFor();

  const { scroll, inner } = await noHorizontalScroll(page);
  expect(scroll).toBeLessThanOrEqual(inner);

  await expect(page.getByLabel('سطح')).toBeVisible();
  await expect(page.getByLabel('دسته')).toBeVisible();
  await expect(page.getByLabel('جست‌وجو در عنوان')).toBeVisible();
  await expect(page.getByRole('button', { name: 'اعمال' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Morning Market' })).toBeVisible();

  await page.screenshot({ path: 'test-results/browser-shots/s3-narrow-320.png' });
});
