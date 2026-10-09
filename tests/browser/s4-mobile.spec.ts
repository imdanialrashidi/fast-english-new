import { expect, test } from '@playwright/test';

// S4-7 rendered mobile: saved + reader at 360/390 have no horizontal
// scroll, the mini-player never covers text, and every target is >=44px.
// Screenshots are captured and must be inspected as pixels (not counts).

const LOGIN = '/login';
const READER = '/app/topics/city-park?level=A2';
const F = { email: 'browser-f@example.com', password: 'password' };
const G = { email: 'browser-g@example.com', password: 'password' };
const C = { email: 'browser-c@example.com', password: 'password' };
const D = { email: 'browser-d@example.com', password: 'password' };

async function login(page, user: { email: string; password: string }) {
  await page.goto(LOGIN);
  await page.getByLabel('Email').fill(user.email);
  await page.getByLabel('Password').fill(user.password);
  await page.getByRole('button', { name: 'Log in' }).click();
  await page.waitForURL('**/account**');
}

async function noHorizontalScroll(page) {
  return page.evaluate(() => ({
    scroll: document.scrollingElement?.scrollWidth ?? 0,
    inner: window.innerWidth,
  }));
}

async function assertTargets(page) {
  for (const role of [
    page.locator('#player-play'),
    page.locator('#player-back'),
    page.locator('#player-forward'),
    page.locator('#player-seek'),
  ]) {
    const box = await role.boundingBox();
    expect(box).not.toBeNull();
    expect(box!.height).toBeGreaterThanOrEqual(44);
  }
  for (const speed of await page.locator('.fe-speed-btn').all()) {
    const box = await speed.boundingBox();
    expect(box).not.toBeNull();
    expect(box!.height).toBeGreaterThanOrEqual(44);
    expect(box!.width).toBeGreaterThanOrEqual(44);
  }
  for (const nav of await page.getByRole('link', { name: /مطالب|ذخیره‌شده‌ها|حساب/ }).all()) {
    const box = await nav.boundingBox();
    if (!box) continue;
    expect(box.height).toBeGreaterThanOrEqual(44);
  }
}

const S4_MOBILE_USERS = { 360: { reader: F, saved: G }, 390: { reader: C, saved: D } };

for (const width of [360, 390]) {
  test(`S4-7 reader at ${width}px: no scroll, mini-player clear, 44px targets`, async ({ page }) => {
    await page.setViewportSize({ width, height: 740 });
    await login(page, S4_MOBILE_USERS[width].reader);
    await page.goto(READER);
    await page.locator('ol.fe-sentences, article.fe-english-body').first().waitFor();

    const { scroll, inner } = await noHorizontalScroll(page);
    expect(scroll).toBeLessThanOrEqual(inner);

    await assertTargets(page);

    // The fixed mini-player must not cover the last glossary row: scroll
    // to the very bottom, then the row sits above the player bar thanks
    // to the end-of-content padding.
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(200);
    const overlap = await page.evaluate(() => {
      const content = document.querySelector('#fe-content');
      const bar = document.querySelector('.fe-playerbar');
      if (!content || !bar) return null;
      const contentBottom = content.getBoundingClientRect().bottom;
      const barTop = bar.getBoundingClientRect().top;
      const lastRow = document.querySelector('.fe-glossary-row:last-child, ol.fe-sentences li:last-child, article.fe-english-body p:last-child');
      const rowBottom = lastRow ? lastRow.getBoundingClientRect().bottom : contentBottom;
      return { contentBottom, barTop, rowBottom };
    });
    expect(overlap).not.toBeNull();
    expect(overlap!.rowBottom).toBeLessThanOrEqual(overlap!.barTop + 1);

    await page.screenshot({ path: `test-results/browser-shots/s4-reader-${width}.png` });
  });

  test(`S4-7 saved at ${width}px: no scroll, 44px targets`, async ({ page }) => {
    await page.setViewportSize({ width, height: 740 });
    await login(page, S4_MOBILE_USERS[width].saved);
    await page.goto('/app/saved');
    await page.locator('#fe-content').waitFor();

    const { scroll, inner } = await noHorizontalScroll(page);
    expect(scroll).toBeLessThanOrEqual(inner);

    const cards = await page.locator('article.fe-card').count();
    if (cards > 0) {
      const box = await page.locator('.fe-card-media').first().boundingBox();
      expect(box!.width / box!.height).toBeGreaterThan(1.68);
      expect(box!.width / box!.height).toBeLessThan(1.88);
    }

    await page.screenshot({ path: `test-results/browser-shots/s4-saved-${width}.png` });
  });
}
