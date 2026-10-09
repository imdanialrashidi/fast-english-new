import { defineConfig, devices } from '@playwright/test';

// S1 browser lane: one Chromium project, one worker, no retries, no
// video/trace. The server is started separately (see active exec plan) so
// PHP runs on the pinned 8.4.26 image with PostgreSQL; BASE_URL points at it.
export default defineConfig({
  testDir: './tests/browser',
  timeout: 60_000,
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: 'list',
  use: {
    baseURL: process.env.BASE_URL ?? 'http://127.0.0.1:8111',
    trace: 'off',
    video: 'off',
    screenshot: 'off',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  outputDir: 'test-results/browser',
});
