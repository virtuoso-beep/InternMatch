// Browser QA harness for InternMatch modals. Credentials come only from environment variables
// (QA_<ROLE>_EMAIL / QA_<ROLE>_PW) and are never written to disk.
// Playwright: `npm install` in qa/browser, or set PLAYWRIGHT_MODULE to an installed copy.
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs');
const path = require('path');

const OUT = path.join(__dirname, 'out');
fs.mkdirSync(OUT, { recursive: true });
const PORTAL = 'http://localhost:8080';
const MANAGE = 'http://localhost:8000';

function creds(role) {
  const email = process.env[`QA_${role.toUpperCase()}_EMAIL`];
  const pw = process.env[`QA_${role.toUpperCase()}_PW`];
  if (!email || !pw) throw new Error(`Missing QA credentials for ${role}`);
  return { email, pw };
}

async function newSession(browser, { width, height, scheme }) {
  const ctx = await browser.newContext({ viewport: { width, height }, colorScheme: scheme });
  const page = await ctx.newPage();
  page.setDefaultTimeout(15000);
  const errors = [];
  page.on('pageerror', (e) => errors.push('pageerror: ' + e.message.slice(0, 160)));
  page.on('console', (m) => { if (m.type() === 'error') errors.push('console: ' + m.text().slice(0, 160)); });
  return { ctx, page, errors };
}

async function login(page, role) {
  const { email, pw } = creds(role);
  await page.goto(PORTAL + '/auth', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.getByLabel('Student ID or email', { exact: true }).waitFor({ timeout: 60000 });
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(2500); // let React hydrate before typing
  await page.getByLabel('Student ID or email', { exact: true }).fill(email);
  await page.getByLabel('Password', { exact: true }).fill(pw);
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await page.waitForURL((u) => !u.pathname.startsWith('/auth'), { timeout: 60000 });
  await page.waitForLoadState('networkidle').catch(() => {});
  // Only for the synthetic QA account created during testing; never for real accounts.
  if (process.env.QA_CONSENT_EMAIL && email === process.env.QA_CONSENT_EMAIL && email.endsWith('@students.internmatch.test')) {
    const notice = page.getByRole('button', { name: /I have read and acknowledge this notice/ });
    await notice.waitFor({ timeout: 20000 }).catch(() => {});
    if (await notice.count()) {
      await notice.first().click();
      await page.waitForTimeout(2000);
      console.log('privacy notice acknowledged for the synthetic QA account');
    }
  }
}

/** Root to search for buttons: the page, or the admin workspace iframe. */
function scope(page, admin) {
  return admin ? page.frameLocator('iframe').first() : page;
}

async function goto(page, target) {
  const url = target.admin ? `${MANAGE}/manage/administration?section=${target.path}` : PORTAL + target.path;
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(target.wait ?? 1200);
}

/** Evaluate inside the document that holds the dialog. */
async function inDoc(page, admin, fn, arg) {
  if (!admin) return page.evaluate(fn, arg);
  const frame = page.frames().find((f) => f.url().includes('/manage/admin-workspace'));
  return frame.evaluate(fn, arg);
}

const dialogInfo = () => {
  const dialogs = [...document.querySelectorAll('[role="dialog"]')].filter((d) => d.offsetParent !== null || d.getClientRects().length);
  const d = dialogs[dialogs.length - 1];
  if (!d) return null;
  const r = d.getBoundingClientRect();
  const titleId = d.getAttribute('aria-labelledby');
  const footerButtons = [...d.querySelectorAll('button')].filter((b) => b.textContent.trim() && !b.getAttribute('aria-label'));
  const last = footerButtons[footerButtons.length - 1];
  const lr = last ? last.getBoundingClientRect() : null;
  return {
    title: titleId ? document.getElementById(titleId)?.textContent?.trim() : null,
    modal: d.getAttribute('aria-modal'),
    box: [Math.round(r.left), Math.round(r.top), Math.round(r.width), Math.round(r.height)],
    vw: window.innerWidth,
    vh: window.innerHeight,
    footerVisible: lr ? lr.bottom <= window.innerHeight + 1 && lr.top >= 0 : false,
    hScroll: document.documentElement.scrollWidth > window.innerWidth + 1,
    focusInside: d.contains(document.activeElement),
    buttons: footerButtons.map((b) => b.textContent.trim()).slice(-4),
  };
};

/**
 * Structural checks for one modal: opens with the expected title, layout fits the viewport (full width at
 * phone size, footer visible), focus moves in and stays trapped, Esc closes, focus returns to the opener,
 * and for forms an edit triggers "Discard changes?".
 */
async function checkModal(page, target, item, variant) {
  const root = scope(page, target.admin);
  const result = { page: target.path, item: item.name, variant, checks: {} };
  try {
    const opener = (item.opener ? item.opener(root) : root.getByRole('button', { name: item.button, exact: item.exact ?? true })).first();
    try {
      await opener.waitFor({ timeout: item.optional ? 5000 : 15000 });
    } catch {
      if (item.optional) {
        result.na = 'not available for this account';
        return result;
      }
      throw new Error('opener not found');
    }
    if (await opener.isDisabled()) {
      if (item.optional) {
        result.na = 'disabled for this account';
        return result;
      }
      throw new Error('opener disabled');
    }
    await opener.click();
    let info = null;
    for (let i = 0; i < 20 && !info; i++) {
      await page.waitForTimeout(300); // some openers load data before the dialog appears
      info = await inDoc(page, target.admin, dialogInfo);
    }
    await page.waitForTimeout(300);
    info = await inDoc(page, target.admin, dialogInfo);
    if (!info) throw new Error('no dialog');
    result.title = info.title;
    result.checks.title = item.title ? new RegExp(item.title).test(info.title ?? '') : Boolean(info.title);
    const narrow = info.vw < 640;
    result.checks.layout = info.modal === 'true' && !info.hScroll && info.footerVisible &&
      (narrow ? info.box[2] >= info.vw - 2 : info.box[0] >= 0 && info.box[0] + info.box[2] <= info.vw && info.box[2] <= 900);
    result.layout = { box: info.box, vw: info.vw, footerVisible: info.footerVisible, hScroll: info.hScroll };
    // Focus trap: tab through and stay inside.
    let trapped = info.focusInside;
    for (let i = 0; i < 14 && trapped; i++) {
      await page.keyboard.press('Tab');
      trapped = (await inDoc(page, target.admin, dialogInfo))?.focusInside ?? false;
    }
    result.checks.focus = trapped;
    const shot = `${variant}-${target.role}-${target.path.replace(/[^a-z0-9]+/gi, '_')}-${item.name.replace(/[^a-z0-9]+/gi, '_')}.png`;
    await page.screenshot({ path: path.join(OUT, shot) });
    result.screenshot = shot;
    // Esc closes when unedited, focus returns to the opener.
    await page.keyboard.press('Escape');
    await page.waitForTimeout(350);
    info = await inDoc(page, target.admin, dialogInfo);
    result.checks.escape = !info;
    if (info) await clickInDialog(root, /^(Cancel|Close)$/);
    await page.waitForTimeout(200);
    result.checks.focusReturn = await inDoc(page, target.admin, () => {
      const a = document.activeElement;
      return Boolean(a && a !== document.body && (a.tagName === 'BUTTON' || a.getAttribute('role') === 'menuitem'));
    });
    // Discard prompt for edited forms.
    if (item.form) {
      if (item.prepare) await item.prepare(page, root);
      await opener.click();
      await page.waitForTimeout(400);
      const textField = root.locator('[role="dialog"] textarea:not([disabled]), [role="dialog"] input:is([type=text],[type=email],[type=password],[type=search],[type=tel],[type=url],:not([type])):not([disabled]):not([readonly])').first();
      const numberField = root.locator('[role="dialog"] input[type=number]:not([disabled]):not([readonly])').first();
      const field = (await textField.count()) ? textField : numberField;
      if (await field.count()) {
        await field.fill(field === textField ? 'QA discard check' : '7');
        await page.keyboard.press('Escape');
        await page.waitForTimeout(300);
        const prompt = root.getByRole('alertdialog');
        result.checks.discard = (await prompt.count()) > 0 && /Discard changes/i.test(await prompt.first().innerText());
        if (result.checks.discard) {
          await root.getByRole('button', { name: /Keep editing/i }).first().click();
          await page.waitForTimeout(200);
          const still = await inDoc(page, target.admin, dialogInfo);
          await clickInDialog(root, /^Cancel$/);
          await page.waitForTimeout(250);
          const prompt2 = root.getByRole('alertdialog');
          if (await prompt2.count()) await root.getByRole('button', { name: /^Discard/i }).first().click();
          await page.waitForTimeout(300);
          result.checks.discard = Boolean(still) && !(await inDoc(page, target.admin, dialogInfo));
        }
      } else {
        result.checks.discard = 'no editable field';
        await clickInDialog(root, /^Cancel$/);
      }
    }
  } catch (e) {
    result.error = e.message.slice(0, 200);
    await page.keyboard.press('Escape').catch(() => {});
  }
  result.pass = !result.error && Object.values(result.checks).every((v) => v === true || v === 'no editable field');
  return result;
}

async function clickInDialog(root, name) {
  const btn = root.locator('[role="dialog"]').last().getByRole('button', { name });
  if (await btn.count()) await btn.last().click();
}

const VARIANTS = [
  { name: 'desktop-light', width: 1440, height: 900, scheme: 'light' },
  { name: 'desktop-dark', width: 1440, height: 900, scheme: 'dark' },
  { name: 'phone-light', width: 375, height: 812, scheme: 'light' },
  { name: 'phone-dark', width: 375, height: 812, scheme: 'dark' },
];

/** Runs every modal of a role plan in all four variants. */
async function runPlan(role, plan, only) {
  const browser = await chromium.launch({ headless: true, channel: 'msedge' });
  const results = [];
  for (const variant of VARIANTS.filter((v) => !only || only.includes(v.name))) {
    const { ctx, page, errors } = await newSession(browser, variant);
    try {
      await login(page, role);
      for (const target of plan) {
        await goto(page, { ...target, role });
        if (target.before) await target.before(page, scope(page, target.admin)).catch(() => {});
        for (const item of target.items) {
          if (item.prepare) await item.prepare(page, scope(page, target.admin)).catch(() => {});
          const r = await checkModal(page, { ...target, role }, item, variant.name);
          results.push(r);
          if (r.na) {
            console.log(`N/A  ${variant.name} ${role} ${target.path} :: ${item.name} (${r.na})`);
            continue;
          }
          console.log(`${r.pass ? 'PASS' : 'FAIL'} ${variant.name} ${role} ${target.path} :: ${item.name} ${r.error ?? ''} ${r.pass ? '' : JSON.stringify(r.checks)} [${r.title ?? ''}]`);
        }
      }
    } catch (e) {
      console.log(`ABORT ${variant.name} ${role}: ${e.message.slice(0, 200)}`);
    }
    if (errors.length) console.log(`JS errors (${variant.name}): ${[...new Set(errors)].slice(0, 5).join(' | ')}`);
    await ctx.close();
  }
  await browser.close();
  fs.writeFileSync(path.join(OUT, `results-${role}.json`), JSON.stringify(results, null, 1));
  return results;
}

module.exports = { runPlan, newSession, login, goto, scope, inDoc, dialogInfo, PORTAL, MANAGE, OUT, chromium, VARIANTS };
