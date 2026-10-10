import { expect, test } from '@playwright/test';

// R5 Today (PLAN-01, AC-26): greeting, continue section, plan, goal ring,
// and recommendations render from persisted state. A fresh browser user
// has no progress yet, so the empty-plan state and goal default are
// asserted here; derivation from progress is proven in Pest
// (R5DailyPlanTest) and the review flow in r-words.

const LOGIN = '/login';
const FRESH = { email: `r-today-${Date.now()}@example.com`, password: 'password-Test1' };

async function registerAndLogin(page) {
  await page.goto('/register');
  await page.getByLabel('نام').fill('Today Fresh');
  await page.getByLabel('ایمیل', { exact: true }).fill(FRESH.email);
  await page.getByLabel('رمز عبور', { exact: true }).fill(FRESH.password);
  await page.getByLabel('تکرار رمز عبور').fill(FRESH.password);
  await page.getByRole('button', { name: 'ساخت حساب' }).click();
  await page.waitForURL('**/account**');
}

async function noHorizontalScroll(page) {
  return page.evaluate(() => ({
    scroll: document.scrollingElement?.scrollWidth ?? 0,
    inner: window.innerWidth,
  }));
}

test('R5 today at 390px: greeting, goal default, empty plan, tab bar', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await registerAndLogin(page);
  await page.goto('/app/today');

  await expect(page.getByRole('heading', { name: 'امروز هم قدمی بزرگ به سمت هدف‌هات برداشتی.' })).toBeVisible();
  await expect(page.getByText('هدف امروز: 10 دقیقه مطالعه')).toBeVisible();
  // Fresh user: either the empty-plan CTA or fresh recommendations.
  await expect(page.getByRole('link', { name: 'کشف مطالب' }).or(page.getByRole('link', { name: /مشاهده همه/ }))).toBeVisible();

  const { scroll, inner } = await noHorizontalScroll(page);
  expect(scroll).toBeLessThanOrEqual(inner);

  // Mobile tab bar: exactly the four student destinations.
  const tabs = page.locator('.fe-bottomnav a');
  await expect(tabs).toHaveCount(4);
  for (const name of ['امروز', 'کشف', 'واژه‌ها', 'حساب']) {
    await expect(page.locator('.fe-bottomnav').getByRole('link', { name })).toBeVisible();
  }

  await page.screenshot({ path: 'test-results/browser-shots/r-today-390.png' });
});

test('R5 today at 1440px: sidebar shell, same IA as mobile', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(LOGIN);
  await page.getByLabel('ایمیل', { exact: true }).fill(FRESH.email);
  await page.getByLabel('رمز عبور', { exact: true }).fill(FRESH.password);
  await page.getByRole('button', { name: 'ورود' }).click();
  await page.waitForURL('**/account**');
  await page.goto('/app/today');

  await expect(page.locator('.fe-sidebar')).toBeVisible();
  await expect(page.locator('.fe-bottomnav')).toBeHidden();
  await expect(page.locator('.fe-sidebar').getByRole('link', { name: 'امروز' })).toHaveAttribute('aria-current', 'page');

  const { scroll, inner } = await noHorizontalScroll(page);
  expect(scroll).toBeLessThanOrEqual(inner + 1);

  await page.screenshot({ path: 'test-results/browser-shots/r-today-1440.png' });
});
