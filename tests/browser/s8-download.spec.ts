import { expect, test } from '@playwright/test';

// S8-5 (download): the not-yet-published state at 360 + 390. No file,
// no link to a file, no checksum — and the metadata template renders no
// values until a real build fills them.

async function checkDownload(page, width: number) {
  await page.setViewportSize({ width, height: 844 });
  const errors: string[] = [];
  page.on('pageerror', (e) => errors.push(String(e)));
  await page.goto('/download');
  await expect(page.getByRole('heading', { name: 'دانلود' })).toBeVisible();
  await expect(page.getByText('فایل نصب هنوز منتشر نشده')).toBeVisible();

  const html = await page.content();
  expect(html).not.toMatch(/\.apk/i);
  expect(html).not.toMatch(/[0-9a-f]{64}/i);

  const scroll = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth: window.innerWidth,
  }));
  expect(scroll.scrollWidth).toBeLessThanOrEqual(scroll.innerWidth);

  const small = await page.evaluate(() => {
    const els = Array.from(document.querySelectorAll('a, button'));
    return els
      .filter((el) => (el as HTMLElement).offsetParent !== null)
      .map((el) => {
        const r = (el as HTMLElement).getBoundingClientRect();
        return { text: (el.textContent ?? '').trim().slice(0, 30), w: r.width, h: r.height };
      })
      .filter((m) => m.w < 44 || m.h < 44);
  });
  expect(small).toEqual([]);
  expect(errors).toEqual([]);
}

test('S8 download 360: not-published, no file or checksum', async ({ page }) => {
  await checkDownload(page, 360);
  await page.screenshot({ path: 'test-results/browser-shots/s8-download-360.png' });
});

test('S8 download 390: same state recomposed', async ({ page }) => {
  await checkDownload(page, 390);
  await page.screenshot({ path: 'test-results/browser-shots/s8-download-390.png' });
});
