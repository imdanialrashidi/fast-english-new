import { expect, test } from '@playwright/test';

// S1-5 rendered checks: no horizontal scroll, player never covers text,
// touch targets >= 44px, screenshots for actual inspection.
const SHOTS = 'test-results/browser-shots';

async function readerChecks(page, shot: string) {
  await page.evaluate(() => document.fonts.ready);
  const metrics = await page.evaluate(() => {
    const de = document.documentElement;
    const bar = document.querySelector<HTMLElement>('.fe-playerbar')!;
    const main = document.querySelector<HTMLElement>('#fe-content')!;
    const controls = Array.from(
      document.querySelectorAll<HTMLElement>('.fe-btn, .fe-level-link, #player-speed, .fe-seek'),
    )
      .filter((el) => el.offsetParent !== null)
      .map((el) => {
      const r = el.getBoundingClientRect();
      return { w: r.width, h: r.height, label: el.textContent?.trim().slice(0, 24) ?? '' };
    });
    return {
      noHScroll: de.scrollWidth <= window.innerWidth + 1,
      barH: bar.getBoundingClientRect().height,
      padBottom: Number.parseFloat(getComputedStyle(main).paddingBottom),
      controls,
    };
  });

  expect(metrics.noHScroll, 'no horizontal scroll').toBe(true);
  expect(metrics.padBottom, 'text padding clears the fixed player').toBeGreaterThanOrEqual(metrics.barH);
  for (const c of metrics.controls) {
    expect(`${c.label}: ${c.w}x${c.h}`).toBeTruthy();
    expect(c.w, `width of "${c.label}"`).toBeGreaterThanOrEqual(44);
    expect(c.h, `height of "${c.label}"`).toBeGreaterThanOrEqual(44);
  }
  await page.screenshot({ path: `${SHOTS}/${shot}` });
}

test('360px reader, A2', async ({ page }) => {
  await page.setViewportSize({ width: 360, height: 740 });
  await page.goto('/app/topics/city-park?level=A2');
  await readerChecks(page, 's1-360-a2.png');
});

test('360px reader, B1', async ({ page }) => {
  await page.setViewportSize({ width: 360, height: 740 });
  await page.goto('/app/topics/city-park?level=B1');
  await readerChecks(page, 's1-360-b1.png');
});

test('390px reader, A2', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/app/topics/city-park?level=A2');
  await readerChecks(page, 's1-390-a2.png');
});

test('390px reader, B1', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/app/topics/city-park?level=B1');
  await readerChecks(page, 's1-390-b1.png');
});

test('320px reflow keeps controls without horizontal scroll', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 700 });
  await page.goto('/app/topics/city-park?level=B1');
  await readerChecks(page, 's1-320-b1.png');
  await expect(page.locator('#player-play')).toBeVisible();
  await expect(page.getByRole('link', { name: 'A2', exact: true })).toBeVisible();
});
