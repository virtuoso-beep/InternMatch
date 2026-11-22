// Load time per list page: from navigation until the network is idle, with the number of API requests.
const { newSession, login, chromium } = require('./harness.cjs');

const PAGES = {
  coordinator: ['/coordinator', '/coordinator/students', '/coordinator/recommendations', '/coordinator/approvals', '/coordinator/hosts', '/coordinator/moa', '/coordinator/opportunities', '/coordinator/progress', '/coordinator/requirements', '/coordinator/evaluations', '/coordinator/monitoring-settings', '/coordinator/analytics'],
  supervisor: ['/supervisor', '/supervisor/interns', '/supervisor/attendance', '/supervisor/evaluations', '/supervisor/company', '/supervisor/opportunities', '/supervisor/moa'],
  admin: ['students', 'users', 'hosts', 'opportunities', 'moa', 'programs', 'terms', 'audit'],
};

(async () => {
  const role = process.argv[2];
  const browser = await chromium.launch({ headless: true, channel: 'msedge' });
  const { page } = await newSession(browser, { width: 1440, height: 900, scheme: 'light' });
  await login(page, role);
  const list = process.env.PT_PAGES ? process.env.PT_PAGES.split(',') : PAGES[role];
  for (const p of [...list, ...list]) {
    const url = role === 'admin' ? `http://localhost:8000/manage/administration?section=${p}` : `http://localhost:8080${p}`;
    let count = 0;
    let slowest = { ms: 0, url: '' };
    const starts = new Map();
    const onReq = (r) => { if (r.url().includes('/api/')) { count++; starts.set(r, Date.now()); } };
    const onRes = (r) => { const s = starts.get(r.request()); if (s) { const ms = Date.now() - s; if (ms > slowest.ms) slowest = { ms, url: r.url().replace(/^https?:\/\/[^/]+/, '') }; } };
    page.on('request', onReq);
    page.on('response', onRes);
    const t = Date.now();
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 120000 });
    await page.waitForLoadState('networkidle', { timeout: 300000 }).catch(() => {});
    const ms = Date.now() - t;
    page.off('request', onReq);
    page.off('response', onRes);
    console.log(`${role.padEnd(11)} ${p.padEnd(32)} ${String(ms).padStart(6)} ms  ${String(count).padStart(4)} API requests  slowest ${slowest.ms} ms ${slowest.url.slice(0, 70)}`);
  }
  await browser.close();
})().catch((e) => console.error('ERR', e.message.slice(0, 300)));
