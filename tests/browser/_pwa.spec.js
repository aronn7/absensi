import { test, expect } from '@playwright/test';

test('PWA: manifest linked and service worker registers', async ({ page }) => {
  await page.goto('/');
  const manifestHref = await page.locator('link[rel=manifest]').getAttribute('href');
  expect(manifestHref).toContain('manifest.json');

  const swRegistered = await page.evaluate(async () => {
    if (!('serviceWorker' in navigator)) return 'unsupported';
    // wait up to 5s for registration
    for (let i = 0; i < 50; i++) {
      const reg = await navigator.serviceWorker.getRegistration();
      if (reg) return reg.active ? 'active' : reg.installing ? 'installing' : 'registered';
      await new Promise(r => setTimeout(r, 100));
    }
    return 'none';
  });
  console.log('SW STATUS:', swRegistered);
  expect(['active', 'installed', 'registered', 'installing']).toContain(swRegistered);

  const manifest = await page.evaluate(async () => await (await fetch('/manifest.json')).json());
  expect(manifest.name).toBeTruthy();
  expect(manifest.icons.length).toBeGreaterThan(0);
  expect(manifest.display).toBe('standalone');
});
