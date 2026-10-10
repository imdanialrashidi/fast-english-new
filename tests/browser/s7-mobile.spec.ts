import { expect, test } from '@playwright/test';

// S7-8 rendered mobile: the question and result pages at 360 and 390 CSS
// px have no horizontal scroll, all targets are at least 44 px, and the
// Persian copy renders. Screenshots are captured and must be inspected as
// pixels (not counts).

async function registerFreshLearner(page, tag: string) {
  const email = `s7m-${tag}-${Date.now()}-${Math.floor(Math.random() * 1e6)}@example.com`;
  const password = 'password123';
  await page.goto('/register');
  await page.getByLabel('نام').fill(`S7M ${tag}`);
  await page.getByLabel('ایمیل', { exact: true }).fill(email);
  await page.getByLabel('رمز عبور', { exact: true }).fill(password);
  await page.getByLabel('تکرار رمز عبور').fill(password);
  await page.getByRole('button', { name: 'ساخت حساب' }).click();
  await page.waitForURL('**/account**');
}

async function noHorizontalScroll(page) {
  return page.evaluate(() => ({
    scroll: document.scrollingElement?.scrollWidth ?? 0,
    inner: window.innerWidth,
  }));
}

async function assertPlacementTargets(page) {
  for (const button of await page.getByRole('button').all()) {
    if (!(await button.isVisible())) continue;
    const box = await button.boundingBox();
    if (!box) continue;
    expect(box.height).toBeGreaterThanOrEqual(44);
  }
  for (const option of await page.locator('.fe-option').all()) {
    if (!(await option.isVisible())) continue;
    const box = await option.boundingBox();
    if (!box) continue;
    expect(box.height).toBeGreaterThanOrEqual(44);
  }
  for (const nav of await page.getByRole('link', { name: /مطالب|ذخیره‌شده‌ها|حساب/ }).all()) {
    if (!(await nav.isVisible())) continue;
    const box = await nav.boundingBox();
    if (!box) continue;
    expect(box.height).toBeGreaterThanOrEqual(44);
  }
}

for (const width of [360, 390]) {
  test(`S7-8 placement at ${width}px: question + result, no scroll, 44px, Persian`, async ({
    page,
  }) => {
    await page.setViewportSize({ width, height: 740 });
    await registerFreshLearner(page, `w${width}`);

    await page.goto('/app/placement');
    await page.getByRole('button', { name: 'شروع آزمون' }).click();
    await page.waitForURL('**/app/placement/*');

    // Question page: Persian chrome, no scroll, 44px options.
    let measured = await noHorizontalScroll(page);
    expect(measured.scroll).toBeLessThanOrEqual(measured.inner);
    await expect(page.getByRole('heading', { name: 'سؤالات تعیین سطح' })).toBeVisible();
    await assertPlacementTargets(page);
    await page.screenshot({ path: `test-results/browser-shots/s7-question-${width}.png` });

    // Answer everything (first option) and submit for the result state.
    for (let position = 1; position <= 20; position++) {
      const section = page.locator(`section[aria-label="سؤال ${position}"]`);
      await section.locator('input[type="radio"][value="0"]').check();
      await section.getByRole('button', { name: `ذخیره پاسخ سؤال ${position}` }).click();
      await expect(page.getByText('پاسخ ذخیره شد.')).toBeVisible();
    }
    await page.getByRole('button', { name: 'ثبت نهایی پاسخ‌ها' }).click();
    await page.waitForURL('**/app/placement/result/*');

    // Result page: score + level + disclaimer, no scroll, 44px.
    measured = await noHorizontalScroll(page);
    expect(measured.scroll).toBeLessThanOrEqual(measured.inner);
    const bodyText = (await page.locator('#fe-content').textContent()) ?? '';
    expect(bodyText).toContain('نمره');
    expect(bodyText).toContain('سطح پیشنهادی');
    expect(bodyText).toContain('راهنمای اولیه، بدون گواهی رسمی');
    await assertPlacementTargets(page);
    await page.screenshot({ path: `test-results/browser-shots/s7-result-${width}.png` });
  });
}
