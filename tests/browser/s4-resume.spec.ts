import { expect, test } from '@playwright/test';

// S4-1 resume with real playback in Chromium: a logged-in student listens,
// pauses (which saves), reloads, and returns to find the position restored
// with nothing auto-playing. Anonymous listening is never saved (proved by
// the absence of a progress row via the reader's zero initial position).

const LOGIN = '/login';
const READER = '/app/topics/city-park?level=A2';
const F = { email: 'browser-f@example.com', password: 'password' };

async function login(page, user: { email: string; password: string }) {
  await page.goto(LOGIN);
  await page.getByLabel('ایمیل', { exact: true }).fill(user.email);
  await page.getByLabel('رمز عبور', { exact: true }).fill(user.password);
  await page.getByRole('button', { name: 'ورود' }).click();
  await page.waitForURL('**/account**');
}

test('S4-1 resume: pause saves, reload restores, nothing auto-plays', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page, F);
  await page.goto(READER);
  await page.waitForFunction(() => Number.isFinite(document.querySelector<HTMLAudioElement>('#lesson-audio')?.duration));

  // Listen: play, jump to 12 s (inside the 20 s A2 fixture), pause to save.
  await page.locator('#player-play').click();
  await expect
    .poll(() => page.locator('#lesson-audio').evaluate((a: HTMLAudioElement) => !a.paused))
    .toBe(true);
  await page.locator('#lesson-audio').evaluate((a: HTMLAudioElement) => {
    a.currentTime = 12;
  });
  await page.locator('#player-play').click();
  await expect
    .poll(() => page.locator('#lesson-audio').evaluate((a: HTMLAudioElement) => a.paused))
    .toBe(true);

  // Wait for the pause-save to reach the server (resume notice appears on reload).
  await page.waitForTimeout(800);

  // Reload: position restored, still paused.
  await page.reload();
  await page.waitForFunction(() => Number.isFinite(document.querySelector<HTMLAudioElement>('#lesson-audio')?.duration));
  await expect(page.locator('#lesson-audio')).toHaveJSProperty('paused', true);
  const restored = await page.locator('#lesson-audio').evaluate((a: HTMLAudioElement) => a.currentTime);
  expect(restored).toBeGreaterThan(10);
  expect(restored).toBeLessThan(14);
  await expect(page.getByText('ادامه از موقعیت ذخیره‌شده')).toBeVisible();

  // Return via navigation: still restored, still paused.
  await page.goto('/app');
  await page.goto(READER);
  await page.waitForFunction(() => Number.isFinite(document.querySelector<HTMLAudioElement>('#lesson-audio')?.duration));
  await expect(page.locator('#lesson-audio')).toHaveJSProperty('paused', true);
  const returned = await page.locator('#lesson-audio').evaluate((a: HTMLAudioElement) => a.currentTime);
  expect(returned).toBeGreaterThan(10);
});

test('S4-1 anonymous listening leaves no saved position', async ({ page }) => {
  await page.goto(READER);
  await page.waitForFunction(() => Number.isFinite(document.querySelector<HTMLAudioElement>('#lesson-audio')?.duration));
  const initial = await page.locator('#fe-lesson-data').getAttribute('data-initial-position');
  expect(Number(initial)).toBe(0);
  await expect(page.getByText('ادامه از موقعیت ذخیره‌شده')).toHaveCount(0);
});
