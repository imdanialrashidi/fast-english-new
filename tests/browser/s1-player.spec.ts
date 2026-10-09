import { expect, test } from '@playwright/test';

const B1 = '/app/topics/city-park?level=B1';
const audio = (page) => page.locator('#lesson-audio');

// S1-4 playback part: real play/pause/seek/speed/clamp/error against the
// fixture MP3 over HTTP.
test('play and pause toggle with a visible label', async ({ page }) => {
  await page.goto(B1);
  const play = page.locator('#player-play');
  await expect(play).toHaveText('پخش');

  await play.click();
  await expect.poll(() => audio(page).evaluate((a: HTMLAudioElement) => !a.paused)).toBe(true);
  await expect(play).toHaveText('توقف');

  await play.click();
  await expect.poll(() => audio(page).evaluate((a: HTMLAudioElement) => a.paused)).toBe(true);
  await expect(play).toHaveText('پخش');
});

test('drag seek moves playback position', async ({ page }) => {
  await page.goto(B1);
  await page.waitForFunction(() => Number.isFinite(document.querySelector<HTMLAudioElement>('#lesson-audio')?.duration));

  // RTL page: the range is mirrored (min right, max left), so dragging
  // right-to-left moves playback forward.
  const seek = page.locator('#player-seek');
  const box = (await seek.boundingBox())!;
  const y = box.y + box.height / 2;
  await page.mouse.move(box.x + box.width - 10, y);
  await page.mouse.down();
  await page.mouse.move(box.x + 10, y, { steps: 12 });
  await page.mouse.up();

  await expect
    .poll(() => audio(page).evaluate((a: HTMLAudioElement) => a.currentTime))
    .toBeGreaterThan(20);
});

test('keyboard seek works and ±10s clamps at both bounds', async ({ page }) => {
  await page.goto(B1);
  await page.waitForFunction(() => Number.isFinite(document.querySelector<HTMLAudioElement>('#lesson-audio')?.duration));
  const duration = await audio(page).evaluate((a: HTMLAudioElement) => a.duration);
  expect(duration).toBeGreaterThan(25);

  const seek = page.locator('#player-seek');
  await seek.focus();

  // Keyboard: arrows move the native range control. The page is RTL, so
  // ArrowLeft increases the value and ArrowRight decreases it.
  const before = await audio(page).evaluate((a: HTMLAudioElement) => a.currentTime);
  await page.keyboard.press('ArrowLeft');
  await expect
    .poll(() => audio(page).evaluate((a: HTMLAudioElement) => a.currentTime))
    .toBeGreaterThan(before);

  // Lab baseline: keyboard-seek round trip.
  const t0 = Date.now();
  const mark = await audio(page).evaluate((a: HTMLAudioElement) => a.currentTime);
  await page.keyboard.press('ArrowLeft');
  await page.waitForFunction(
    (m) => (document.querySelector<HTMLAudioElement>('#lesson-audio')?.currentTime ?? 0) > m,
    mark,
  );
  console.log(`LAB seekResponseMs=${Date.now() - t0}`);

  // Upper clamp: End jumps to max, forward stays at duration. Duration is
  // re-read live: Chromium refines its estimate while metadata settles.
  await page.keyboard.press('End');
  await expect
    .poll(() => audio(page).evaluate((a: HTMLAudioElement) => a.currentTime))
    .toBeGreaterThan(duration - 1);
  await page.locator('#player-forward').click();
  const clamped = await audio(page).evaluate((a: HTMLAudioElement) => ({
    top: a.currentTime,
    dur: a.duration,
  }));
  expect(clamped.top).toBeLessThanOrEqual(clamped.dur + 0.25);

  // Lower clamp: Home jumps to 0, back stays at 0. Re-focus the range:
  // the previous button click moved keyboard focus onto the button.
  await seek.focus();
  await page.keyboard.press('Home');
  await expect.poll(() => audio(page).evaluate((a: HTMLAudioElement) => a.currentTime)).toBe(0);
  await page.locator('#player-back').click();
  await expect.poll(() => audio(page).evaluate((a: HTMLAudioElement) => a.currentTime)).toBe(0);
});

test('every speed applies to the audio element', async ({ page }) => {
  await page.goto(B1);
  for (const speed of ['0.75', '1', '1.25', '1.5']) {
    await page.getByRole('button', { name: `${speed}×` }).click();
    await expect
      .poll(() => audio(page).evaluate((a: HTMLAudioElement) => a.playbackRate))
      .toBe(Number(speed));
    await expect(page.getByRole('button', { name: `${speed}×` })).toHaveAttribute('aria-pressed', 'true');
  }
});

test('network failure shows a message with a working retry', async ({ page }) => {
  await page.route('**/media/lessons/*/audio', (route) => route.abort('failed'));
  await page.goto(B1);

  await expect(page.locator('#player-error')).toBeVisible();
  await expect(page.locator('#player-retry')).toBeVisible();
  const message = await page.locator('#player-error').textContent();
  expect(message!.trim().length).toBeGreaterThan(0);

  await page.unrouteAll({ behavior: 'wait' });
  await page.locator('#player-retry').click();
  await expect(page.locator('#player-duration')).not.toHaveText('–:––', { timeout: 10_000 });
  await expect(page.locator('#player-error')).toBeHidden();
});
