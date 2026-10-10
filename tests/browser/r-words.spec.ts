import { expect, test } from '@playwright/test';

// R4 vocabulary notebook (VOCAB-01, AC-25): save a word from the reader via
// the real endpoint with its lesson context, find it in the notebook after
// reload, review it with a grade, and see it leave the due queue. A unique
// word per run keeps the lane re-runnable against fe_browser.

const WORD = `rword${Date.now().toString(36)}`;
const READER = '/app/topics/city-park?level=A2';

async function registerFreshStudent(page) {
  const email = `r-words-${Date.now()}-${Math.floor(Math.random() * 1e6)}@example.com`;
  const password = 'password123';
  await page.goto('/register');
  await page.getByLabel('نام').fill('R Words');
  await page.getByLabel('ایمیل', { exact: true }).fill(email);
  await page.getByLabel('رمز عبور', { exact: true }).fill(password);
  await page.getByLabel('تکرار رمز عبور').fill(password);
  await page.getByRole('button', { name: 'ساخت حساب' }).click();
  await page.waitForURL('**/account**');
}

test('R4 notebook: save from reader, persist, review with a grade', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await registerFreshStudent(page);

  // Save a word with the reader's real lesson id for true lesson context.
  await page.goto(READER);
  const lessonId = await page.locator('#fe-lesson-data').getAttribute('data-lesson-id');
  expect(Number(lessonId)).toBeGreaterThan(0);
  const saved: number = await page.evaluate(
    (args: { word: string; lesson: number }) => {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
      return fetch('/app/words', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ word: args.word, meaning_fa: 'واژه آزمایشی', lesson_id: args.lesson }),
      }).then((res) => res.status);
    },
    { word: WORD, lesson: Number(lessonId) },
  );
  expect(saved).toBe(201);

  // Notebook lists the word after reload with its saved meaning.
  await page.goto('/app/words');
  await expect(page.getByText(WORD)).toBeVisible();
  await expect(page.getByText('واژه آزمایشی')).toBeVisible();

  // Review: the word is due, shows its meaning, and grades forward.
  await page.goto('/app/words/review');
  await expect(page.locator('.fe-flash-word')).toHaveText(WORD);
  await page.getByRole('button', { name: 'نمایش معنی' }).click();
  await expect(page.getByText('واژه آزمایشی')).toBeVisible();
  await page.getByRole('button', { name: 'خوب', exact: true }).click();
  await page.waitForLoadState('load');
  // After a Good grade the word leaves the due queue.
  await expect(page.getByText('واژه‌ای برای مرور نیست')).toBeVisible();

  await page.screenshot({ path: 'test-results/browser-shots/r-words-390.png' });
});
