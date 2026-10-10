import { expect, test } from '@playwright/test';

// S8-2/S8-9 (reset form): the Persian forgot-password form at 360 + 390.
// No horizontal scroll, 44px targets, unknown email gets the neutral
// status message (same as a known one — no account oracle).

async function checkResetForm(page, width: number) {
  await page.setViewportSize({ width, height: 844 });
  const errors: string[] = [];
  page.on('pageerror', (e) => errors.push(String(e)));
  await page.goto('/forgot-password');
  await expect(page.getByRole('heading', { name: 'بازیابی رمز عبور' })).toBeVisible();

  const scroll = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth: window.innerWidth,
  }));
  expect(scroll.scrollWidth).toBeLessThanOrEqual(scroll.innerWidth);

  const small = await page.evaluate(() => {
    const els = Array.from(document.querySelectorAll('a, button, input'));
    return els
      .filter((el) => (el as HTMLElement).offsetParent !== null)
      .map((el) => {
        const r = (el as HTMLElement).getBoundingClientRect();
        return { label: (el.getAttribute('id') ?? el.textContent ?? '').trim().slice(0, 30), w: r.width, h: r.height };
      })
      .filter((m) => m.w < 44 || m.h < 44);
  });
  expect(small).toEqual([]);
  expect(errors).toEqual([]);
}

test('S8 reset 360: unknown email gets the neutral message', async ({ page }) => {
  await checkResetForm(page, 360);
  await page.getByLabel('ایمیل', { exact: true }).fill(`s8-unknown-${Date.now()}@example.com`);
  await page.getByRole('button', { name: 'ارسال پیوند بازیابی' }).click();
  // The neutral Persian status — identical for known and unknown emails.
  await expect(page.getByText('اگر حسابی با این ایمیل وجود داشته باشد، پیوند بازیابی ارسال شد.')).toBeVisible();
  // No existence oracle leaks into the page.
  await expect(page.getByText(/can't find a user/i)).toHaveCount(0);
  await page.screenshot({ path: 'test-results/browser-shots/s8-reset-360.png' });
});

test('S8 reset 390: same form recomposed', async ({ page }) => {
  await checkResetForm(page, 390);
  await page.screenshot({ path: 'test-results/browser-shots/s8-reset-390.png' });
});
