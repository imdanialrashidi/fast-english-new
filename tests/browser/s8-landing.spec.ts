import { expect, test } from '@playwright/test';

// S8-1/S8-9 (landing): the short Persian landing at 360 + 390 CSS px.
// Promise, real sample link, how-it-works, DB plan list in the sales-on
// lane state (the browser lane serves with SALES_ENABLED=true so the S5/S6
// purchase journeys keep working; the sales-off preparing state is proven
// in Pest S8SalesFlagTest), install and download links, FAQ link, DRAFT
// label. No horizontal scroll and every touch target is at least 44px.

async function checkLanding(page, width: number) {
  await page.setViewportSize({ width, height: 844 });
  const errors: string[] = [];
  page.on('pageerror', (e) => errors.push(String(e)));
  await page.goto('/');
  await expect(page.getByRole('heading', { name: 'با داستان‌های واقعی انگلیسی را سریع‌تر یاد بگیر' })).toBeVisible();

  // No horizontal scroll.
  const scroll = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth: window.innerWidth,
  }));
  expect(scroll.scrollWidth).toBeLessThanOrEqual(scroll.innerWidth);

  // Every visible link/button is at least 44px in both dimensions.
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

test('S8 landing 360: draft promise, sample, preparing state, links', async ({ page }) => {
  await checkLanding(page, 360);
  await expect(page.getByText('DRAFT')).toBeVisible();
  await expect(page.getByRole('link', { name: /شروع کنید/ }).first()).toBeVisible();
  await expect(page.getByRole('heading', { name: 'منتخبی از مطالب' })).toBeVisible();
  // Sales-on lane state: DB plan names with durations (never amounts),
  // a purchase CTA, and no preparing-state text.
  await expect(page.getByText('یک‌ماهه آزمایشی (TEST)')).toBeVisible();
  await expect(page.getByRole('link', { name: 'مشاهده پلن‌ها و خرید' }).first()).toBeVisible();
  await expect(page.getByText('در دست آماده‌سازی')).toHaveCount(0);
  await expect(page.getByRole('link', { name: 'صفحه دانلود' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'مشاهده همه پرسش‌ها' })).toBeVisible();
  const html = await page.content();
  expect(html).toContain('lang="fa"');
  await page.screenshot({ path: 'test-results/browser-shots/s8-landing-360.png' });
});

test('S8 landing 390: same states recomposed', async ({ page }) => {
  await checkLanding(page, 390);
  await expect(page.getByText('یک‌ماهه آزمایشی (TEST)')).toBeVisible();
  await page.screenshot({ path: 'test-results/browser-shots/s8-landing-390.png' });
});
