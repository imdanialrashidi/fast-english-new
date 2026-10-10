import { expect, test } from '@playwright/test';

// S7 answer flow on Chromium: a fresh learner starts the pinned attempt,
// saves one answer per question through the real forms (resume-safe),
// submits once, sees the score + suggested level + disclaimer, and accepts
// the result into preferred_level. Real HTTP, no mocks. The fixture key
// rotates (position-1)%4, so selecting the first option everywhere scores
// exactly 5 → A2; the client never receives the key (proven in Pest S7-1).

async function registerFreshLearner(page, tag: string) {
  const email = `s7-${tag}-${Date.now()}-${Math.floor(Math.random() * 1e6)}@example.com`;
  const password = 'password123';
  await page.goto('/register');
  await page.getByLabel('نام').fill(`S7 ${tag}`);
  await page.getByLabel('ایمیل', { exact: true }).fill(email);
  await page.getByLabel('رمز عبور', { exact: true }).fill(password);
  await page.getByLabel('تکرار رمز عبور').fill(password);
  await page.getByRole('button', { name: 'ساخت حساب' }).click();
  await page.waitForURL('**/account**');
}

test('S7 flow: start, answer each question, submit once, accept sets level', async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await registerFreshLearner(page, 'flow');

  // Start pins an attempt to the current published version.
  await page.goto('/app/placement');
  await page.getByRole('heading', { name: 'تعیین سطح (اختیاری)' }).waitFor();
  await page.getByRole('button', { name: 'شروع آزمون' }).click();
  await page.waitForURL('**/app/placement/*');
  await expect(page.getByRole('heading', { name: 'سؤالات تعیین سطح' })).toBeVisible();

  // The answer key never reaches the client.
  const questionHtml = await page.content();
  expect(questionHtml).not.toContain('correct_option');
  expect(questionHtml).not.toContain('scoring_rules');

  // Save the first option on all 20 questions, one mutation per question.
  for (let position = 1; position <= 20; position++) {
    const section = page.locator(`section[aria-label="سؤال ${position}"]`);
    await expect(section).toBeVisible();
    await section.locator('input[type="radio"][value="0"]').check();
    await section.getByRole('button', { name: `ذخیره پاسخ سؤال ${position}` }).click();
    await expect(page.getByText('پاسخ ذخیره شد.')).toBeVisible();
  }

  // Resume check: reload keeps the selections.
  await page.reload();
  const checkedCount = await page.locator('input[type="radio"]:checked').count();
  expect(checkedCount).toBe(20);

  // Submit once → result with score, suggested level, and disclaimer.
  await page.getByRole('button', { name: 'ثبت نهایی پاسخ‌ها' }).click();
  await page.waitForURL('**/app/placement/result/*');
  await expect(page.getByRole('heading', { name: 'نتیجه تعیین سطح' })).toBeVisible();
  await expect(page.getByText('نمره: 5 از ۲۰')).toBeVisible();
  await expect(page.getByText('سطح پیشنهادی: A2')).toBeVisible();
  await expect(page.getByText('راهنمای اولیه، بدون گواهی رسمی')).toBeVisible();
  await page.screenshot({ path: 'test-results/browser-shots/s7-result-390.png' });

  // Accept stores the suggested level as the explicit preference.
  await page.getByRole('button', { name: 'تأیید و ذخیره سطح ترجیحی' }).click();
  await expect(page.getByText('سطح ترجیحی ذخیره شد.')).toBeVisible();

  await page.goto('/app/account');
  await expect(page.getByText('سطح ترجیحی: A2')).toBeVisible();
});
