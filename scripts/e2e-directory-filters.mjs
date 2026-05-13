#!/usr/bin/env node
/**
 * Browser proof: shops directory filter radios receive real pointer hits (elementFromPoint + click).
 *
 * Env:
 *   CULVERS_E2E_BASE_URL — default https://culversquare-co-uk.stackstaging.com (no trailing slash)
 *
 * Requires: npx playwright install chromium (once)
 */
import { chromium } from 'playwright';

const base = (process.env.CULVERS_E2E_BASE_URL || 'https://culversquare-co-uk.stackstaging.com').replace(/\/$/, '');
const shopsPath = '/shops/';

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  viewport: { width: 1440, height: 900 },
  ignoreHTTPSErrors: true,
});
const page = await context.newPage();

try {
  await page.goto(`${base}${shopsPath}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
} catch (err) {
  console.error('Navigation failed:', err.message);
  await browser.close();
  process.exit(1);
}

await page.waitForSelector('section.directory-archive', { timeout: 45000 });

/* Let Alpine + ScrollSmoother initialise */
await page.waitForTimeout(1500);

const pill = page.locator('.directory-archive__filter-pill').first();
await pill.scrollIntoViewIfNeeded();
await page.waitForTimeout(300);

const opts = page.locator('.directory-archive__filter-option');
const n = await opts.count();
if (n < 2) {
  console.error(`Expected ≥2 filter options, got ${n}`);
  await browser.close();
  process.exit(1);
}

/* Index 1 = first real taxonomy row after "All" */
const target = opts.nth(1);
await target.scrollIntoViewIfNeeded();
await page.waitForTimeout(400);

const handle = await target.elementHandle();
if (!handle) {
  console.error('No element handle for filter option');
  await browser.close();
  process.exit(1);
}

const box = await handle.boundingBox();
if (!box) {
  console.error('Filter option has no layout box');
  await browser.close();
  process.exit(1);
}

const cx = Math.floor(box.x + box.width / 2);
const cy = Math.floor(box.y + box.height / 2);

const probe = await page.evaluate(({ x, y }) => {
  const el = document.elementFromPoint(x, y);
  if (!el) return null;
  const btn = el.closest('button');
  return {
    topTag: el.tagName,
    topClass: typeof el.className === 'string' ? el.className : '',
    buttonClass: btn?.className || '',
    buttonRole: btn?.getAttribute('role') || '',
  };
}, { x: cx, y: cy });

console.log('elementFromPoint(@filter centre):', probe);

const hitIsFilterButton =
  probe &&
  typeof probe.buttonClass === 'string' &&
  probe.buttonClass.includes('directory-archive__filter-option');

if (!hitIsFilterButton) {
  console.error('FAIL: elementFromPoint did not resolve to a directory filter <button>.');
  await browser.close();
  process.exit(2);
}

const urlBefore = page.url();
await target.click();
await page.waitForTimeout(500);
const urlAfter = page.url();

console.log('URL before:', urlBefore);
console.log('URL after:', urlAfter);

const qsChanged = urlAfter !== urlBefore && (urlAfter.includes('category=') || urlAfter.includes('type='));
if (!qsChanged) {
  console.warn('WARN: URL query did not change — options may be empty on this env; hit-test passed.');
}

await browser.close();
console.log('OK: filter control is on top at click point.');
process.exit(0);
