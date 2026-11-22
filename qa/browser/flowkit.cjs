// Shared helpers for the end-to-end flows. Credentials come only from environment variables.
const { execFileSync } = require('child_process');
const path = require('path');
const { newSession, login, chromium, OUT, PORTAL } = require('./harness.cjs');

/** Runs PHP in the backend container (read checks and synthetic-only setup), returns trimmed stdout. */
function php(code) {
  const out = execFileSync('docker', ['compose', 'exec', '-T', 'backend', 'php', 'artisan', 'tinker', '--execute=' + code], {
    cwd: path.resolve(__dirname, '..', '..'), // repository root (docker compose) encoding: 'utf8', timeout: 120000,
  });
  return out.trim().split('\n').pop();
}

const log = (...a) => console.log(new Date().toISOString().slice(11, 19), ...a);

async function session(role, browser, variant = { width: 1440, height: 900, scheme: 'light' }) {
  const s = await newSession(browser, variant);
  await login(s.page, role);
  return s;
}

async function open(page, url) {
  await page.goto(PORTAL + url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForLoadState('networkidle', { timeout: 60000 }).catch(() => {});
  await page.waitForTimeout(1200);
}

const dialog = (page) => page.locator('[role="dialog"]').last();

/** Waits for a sonner toast and returns its text. */
async function toast(page, timeout = 30000) {
  const t = page.locator('[data-sonner-toast]').last();
  await t.waitFor({ timeout });
  return (await t.innerText()).replace(/\s+/g, ' ').trim();
}

/** Clicks the dialog's action button, optionally twice quickly (double-submit check). */
async function confirm(page, name, { double = false } = {}) {
  const b = dialog(page).getByRole('button', { name, exact: true });
  if (double) {
    await b.dblclick(); // two clicks in quick succession
  } else {
    await b.click();
  }
}

async function shot(page, name) {
  await page.screenshot({ path: path.join(OUT, `flow-${name}.png`), fullPage: false });
}

async function dialogText(page) {
  return (await dialog(page).innerText()).replace(/\s+/g, ' ').trim();
}

module.exports = { php, log, session, open, dialog, toast, confirm, shot, dialogText, chromium };
