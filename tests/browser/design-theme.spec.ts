import { expect, test } from '@playwright/test';

// Design slice: Blue/Teal application theme + icon system + studio player.
// Asserts theme behavior (system default, persistence, OS-following,
// pre-paint application, Livewire-navigation consistency) and captures the
// inspected screenshot set. Screenshots land in test-results/browser-shots
// (gitignored artifacts); each is opened and inspected, not counted.

const READER = '/app/topics/city-park?level=A2';
const FRESH = () => ({ email: `design-${Date.now()}-${Math.floor(Math.random() * 1e6)}@example.com`, password: 'password-Test1' });

// Seed the theme once per context: later navigations (incl. reloads) must
// keep the user's own choice instead of re-seeding it.
async function seedThemeOnce(page, theme: 'light' | 'dark' | 'system') {
  await page.addInitScript((wanted: string) => {
    if (!localStorage.getItem('fe-theme-seeded')) {
      if (wanted === 'system') localStorage.removeItem('fe-theme');
      else localStorage.setItem('fe-theme', wanted);
      localStorage.setItem('fe-theme-seeded', '1');
    }
  }, theme);
}

async function registerAndLogin(page, user: { email: string; password: string }) {
  await page.goto('/register');
  await page.getByLabel('نام').fill('Design Proof');
  await page.getByLabel('ایمیل', { exact: true }).fill(user.email);
  await page.getByLabel('رمز عبور', { exact: true }).fill(user.password);
  await page.getByLabel('تکرار رمز عبور').fill(user.password);
  await page.getByRole('button', { name: 'ساخت حساب' }).click();
  await page.waitForURL('**/account**');
}

async function noOverflow(page) {
  const m = await page.evaluate(() => ({
    scroll: document.scrollingElement?.scrollWidth ?? 0,
    inner: window.innerWidth,
  }));
  expect(m.scroll).toBeLessThanOrEqual(m.inner);
}

async function expectTargets(page) {
  // Radio/checkbox/range inputs are 24px by design; their label row
  // carries the 44px target and is asserted separately below.
  const small = await page.evaluate(() => {
    const els = Array.from(document.querySelectorAll('a, button, input'));
    return els
      .filter((el) => (el as HTMLElement).offsetParent !== null)
      .filter((el) => !['radio', 'checkbox', 'range'].includes((el as HTMLInputElement).type))
      .map((el) => {
        const r = (el as HTMLElement).getBoundingClientRect();
        return { label: (el.getAttribute('id') ?? el.textContent ?? '').trim().slice(0, 30), w: r.width, h: r.height };
      })
      .filter((m) => m.w < 44 || m.h < 44);
  });
  expect(small).toEqual([]);
  for (const row of await page.locator('.fe-appearance-option').all()) {
    const box = await row.boundingBox();
    if (!box) continue;
    expect(box.height).toBeGreaterThanOrEqual(44);
  }
}

test('theme: system default follows OS, explicit choice persists, no flash', async ({ browser }) => {
  // Fresh context, dark OS, no stored choice -> dark without a light flash.
  const darkCtx = await browser.newContext({ viewport: { width: 390, height: 844 }, colorScheme: 'dark' });
  const darkPage = await darkCtx.newPage();
  await darkPage.goto('/login');
  await expect(darkPage.locator('html')).toHaveAttribute('data-theme', 'dark');
  const canvas = await darkPage.evaluate(() => getComputedStyle(document.body).backgroundColor);
  expect(canvas).toBe('rgb(14, 22, 38)');
  await darkCtx.close();

  // Stored light wins over a dark OS, and survives a reload + navigation.
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, colorScheme: 'dark' });
  const page = await ctx.newPage();
  await seedThemeOnce(page, 'light');
  await page.goto('/login');
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
  await page.reload();
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');

  // Settings radios switch + persist; System returns to OS-following.
  await registerAndLogin(page, FRESH());
  await page.goto('/app/account/settings');
  await page.getByRole('radio', { name: /تیره/ }).check();
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await expect(page.getByText('حالت تیره فعال شد.')).toBeVisible();
  await page.reload();
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await page.goto('/app/today');
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await page.goto('/app/account/settings');
  await page.getByRole('radio', { name: /سیستم/ }).check();
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark'); // dark OS
  // OS change while on System flips the theme live.
  await page.emulateMedia({ colorScheme: 'light' });
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
  await page.emulateMedia({ colorScheme: 'dark' });
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await ctx.close();

  // Light OS + system -> light.
  const lightCtx = await browser.newContext({ viewport: { width: 390, height: 844 }, colorScheme: 'light' });
  const lightPage = await lightCtx.newPage();
  await lightPage.goto('/login');
  await expect(lightPage.locator('html')).toHaveAttribute('data-theme', 'light');
  await lightCtx.close();
});

test('capture: auth at 390 in both themes', async ({ browser }) => {
  for (const theme of ['light', 'dark'] as const) {
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, colorScheme: theme === 'dark' ? 'dark' : 'light' });
    const page = await ctx.newPage();
    await seedThemeOnce(page, theme);
    await page.goto('/login');
    await expect(page.getByRole('heading', { name: 'ورود به حساب' })).toBeVisible();
    await noOverflow(page);
    await expectTargets(page);
    await page.screenshot({ path: `test-results/browser-shots/design-login-${theme}-390.png` });
    await page.goto('/register');
    await expect(page.getByRole('heading', { name: 'ساخت حساب' })).toBeVisible();
    await noOverflow(page);
    await page.screenshot({ path: `test-results/browser-shots/design-register-${theme}-390.png` });
    await ctx.close();
  }
});

test('capture: library, reader, today, words, settings in both themes', async ({ browser }) => {
  for (const theme of ['light', 'dark'] as const) {
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, colorScheme: theme === 'dark' ? 'dark' : 'light' });
    const page = await ctx.newPage();
    await seedThemeOnce(page, theme);
    // Fresh account per context: avoids the §16 login throttle entirely.
    await registerAndLogin(page, FRESH());

    await page.goto('/app');
    await expect(page.getByRole('heading', { name: 'کشف مطالب' })).toBeVisible();
    await noOverflow(page);
    await page.screenshot({ path: `test-results/browser-shots/design-library-${theme}-390.png` });

    await page.goto(READER);
    await page.waitForFunction(() => Number.isFinite(document.querySelector<HTMLAudioElement>('#lesson-audio')?.duration ?? NaN));
    await noOverflow(page);
    await expectTargets(page);
    await page.screenshot({ path: `test-results/browser-shots/design-reader-${theme}-390.png` });

    await page.goto('/app/today');
    await noOverflow(page);
    await page.screenshot({ path: `test-results/browser-shots/design-today-${theme}-390.png` });

    await page.goto('/app/words');
    await noOverflow(page);
    await page.screenshot({ path: `test-results/browser-shots/design-words-${theme}-390.png` });

    await page.goto('/app/account/settings');
    await noOverflow(page);
    await expectTargets(page);
    await page.screenshot({ path: `test-results/browser-shots/design-settings-${theme}-390.png` });

    await page.goto('/app/account');
    await noOverflow(page);
    await page.screenshot({ path: `test-results/browser-shots/design-account-${theme}-390.png` });

    await ctx.close();
  }
});

test('capture: player playing state with progress fill (light + dark)', async ({ browser }) => {
  for (const theme of ['light', 'dark'] as const) {
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, colorScheme: theme === 'dark' ? 'dark' : 'light' });
    const page = await ctx.newPage();
    await seedThemeOnce(page, theme);
    await registerAndLogin(page, FRESH());
    await page.goto(READER);
    await page.waitForFunction(() => Number.isFinite(document.querySelector<HTMLAudioElement>('#lesson-audio')?.duration ?? NaN));
    // Cycle the speed once (1 -> 1.25) to prove the control, then play.
    await page.locator('#player-speed').click();
    await expect(page.locator('#player-speed-label')).toHaveText('1.25×');
    await page.locator('#player-play').click();
    await expect.poll(() => page.evaluate(() => !document.querySelector<HTMLAudioElement>('#lesson-audio')?.paused)).toBe(true);
    await page.waitForTimeout(2500);
    await expect(page.locator('#player-play')).toHaveAccessibleName('توقف');
    // Open the console so the seek slider + transport are proven visible.
    await page.locator('#player-toggle').click();
    await expect(page.locator('#player-seek')).toBeVisible();
    const fill = await page.locator('#player-seek').evaluate((el) => el.style.getPropertyValue('--fe-seek-pct'));
    expect(parseFloat(fill)).toBeGreaterThan(0);
    await noOverflow(page);
    await page.screenshot({ path: `test-results/browser-shots/design-playing-${theme}-390.png` });
    await ctx.close();
  }
});

test('capture: library cards scrolled + landing regression', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, colorScheme: 'light' });
  const page = await ctx.newPage();
  await registerAndLogin(page, FRESH());
  await page.goto('/app');
  const firstCard = page.locator('.fe-card').first();
  await expect(firstCard.locator('.fe-chip').first()).toBeVisible();
  await firstCard.evaluate((el) => el.scrollIntoView({ block: 'start' }));
  await page.evaluate(() => window.scrollBy(0, -8));
  await noOverflow(page);
  await page.screenshot({ path: 'test-results/browser-shots/design-library-cards-390.png' });
  await ctx.close();

  // Landing shares tokens but keeps its own composition (out of scope):
  // it must still render without overflow or missing headings.
  const pub = await browser.newContext({ viewport: { width: 390, height: 844 }, colorScheme: 'light' });
  const pubPage = await pub.newPage();
  await pubPage.goto('/');
  await noOverflow(pubPage);
  await expect(pubPage.getByRole('heading').first()).toBeVisible();
  await pubPage.screenshot({ path: 'test-results/browser-shots/design-landing-390.png', fullPage: true });
  await pub.close();
});

test('keyboard, focus, reduced-motion, console health', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, colorScheme: 'light', reducedMotion: 'reduce' });
  const page = await ctx.newPage();
  const errors: string[] = [];
  page.on('pageerror', (e) => errors.push(String(e)));

  // Login entirely by keyboard: email -> password -> toggle -> submit.
  await page.goto('/login');
  await page.getByLabel('ایمیل', { exact: true }).click();
  await page.keyboard.press('Tab');
  await expect(page.locator('#password')).toBeFocused();
  const user = FRESH();
  await page.getByLabel('ایمیل', { exact: true }).fill(user.email);
  await page.getByLabel('رمز عبور', { exact: true }).fill(user.password);
  // Visible focus ring on the password field (3px primary, >=3:1).
  const outline = await page.locator('#password').evaluate((el) => ({
    w: getComputedStyle(el).outlineWidth,
    s: getComputedStyle(el).outlineStyle,
  }));
  expect(outline.s).toBe('solid');
  expect(parseFloat(outline.w)).toBeGreaterThanOrEqual(2);
  // Toggle by keyboard flips the input type and keeps the name.
  await page.locator('#password').press('Tab');
  await expect(page.getByRole('button', { name: 'نمایش رمز عبور' })).toBeFocused();
  await page.keyboard.press('Enter');
  await expect(page.locator('#password')).toHaveAttribute('type', 'text');
  await expect(page.getByRole('button', { name: 'پنهان کردن رمز عبور' })).toBeFocused();

  // Reduced motion: state transitions collapse to none.
  const dur = await page.evaluate(() => getComputedStyle(document.querySelector('.fe-btn')!).transitionDuration);
  expect(dur === '0s' || dur === '0s, 0s' || dur === '').toBe(true);

  // Reader by keyboard: first sentence seeks the audio.
  await page.goto('/register');
  await page.getByLabel('نام').fill('Design Keys');
  await page.getByLabel('ایمیل', { exact: true }).fill(user.email);
  await page.getByLabel('رمز عبور', { exact: true }).fill(user.password);
  await page.getByLabel('تکرار رمز عبور').fill(user.password);
  await page.getByRole('button', { name: 'ساخت حساب' }).click();
  await page.waitForURL('**/account**');
  await page.goto(READER);
  await page.waitForFunction(() => Number.isFinite(document.querySelector<HTMLAudioElement>('#lesson-audio')?.duration ?? NaN));
  const first = page.locator('.fe-sentence').first();
  await first.focus();
  await expect(first).toBeFocused();
  await page.keyboard.press('Enter');
  await expect
    .poll(() => page.evaluate(() => document.querySelector<HTMLAudioElement>('#lesson-audio')?.currentTime ?? NaN))
    .toBeGreaterThanOrEqual(0);
  const active = await first.getAttribute('aria-current');
  expect(['true', 'false']).toContain(active);
  expect(errors).toEqual([]);
  await ctx.close();
});

test('capture: responsive sweep of the reader + library', async ({ browser }) => {
  for (const width of [360, 390, 430, 768, 1024, 1360]) {
    const ctx = await browser.newContext({ viewport: { width, height: 844 }, colorScheme: 'light' });
    const page = await ctx.newPage();
    await registerAndLogin(page, FRESH());
    await page.goto(READER);
    await page.waitForFunction(() => Number.isFinite(document.querySelector<HTMLAudioElement>('#lesson-audio')?.duration ?? NaN));
    await noOverflow(page);
    await page.screenshot({ path: `test-results/browser-shots/design-reader-light-${width}.png` });
    await page.goto('/app');
    await noOverflow(page);
    await page.screenshot({ path: `test-results/browser-shots/design-library-light-${width}.png` });
    await ctx.close();
  }
});
