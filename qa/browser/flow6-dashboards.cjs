// Flow 6: dean and admin dashboards hide synthetic data by default and show it when switched on.
// Compares what the page shows with database counts.
const k = require('./flowkit.cjs');

const db = JSON.parse(k.php(`echo json_encode([
  'users_real' => App\\Models\\User::where('is_synthetic', false)->count(),
  'users_all' => App\\Models\\User::count(),
  'dean_enrollments_real' => DB::table('student_enrollments')->join('program_terms','program_terms.id','=','student_enrollments.program_term_id')->whereIn('program_terms.program_id', App\\Models\\User::where('email','${process.env.QA_DEAN_EMAIL}')->first()->programs()->pluck('programs.id'))->where('student_enrollments.is_synthetic', false)->count(),
  'dean_enrollments_all' => DB::table('student_enrollments')->join('program_terms','program_terms.id','=','student_enrollments.program_term_id')->whereIn('program_terms.program_id', App\\Models\\User::where('email','${process.env.QA_DEAN_EMAIL}')->first()->programs()->pluck('programs.id'))->count(),
]);`));
k.log('database:', JSON.stringify(db));

async function capture(page, matcher, act) {
  const seen = [];
  const handler = async (r) => { if (matcher(r.url())) seen.push({ url: r.url().replace(/^https?:\/\/[^/]+/, ''), body: await r.json().catch(() => null) }); };
  page.on('response', handler);
  await act();
  await page.waitForTimeout(4000);
  page.off('response', handler);
  return seen;
}
const stat = (body, label) => body?.stats?.find((s) => s.label === label)?.value;

(async () => {
  const browser = await k.chromium.launch({ headless: true, channel: 'msedge' });
  const dean = await k.session('dean', browser);
  const dp = dean.page;
  let seen = await capture(dp, (u) => u.includes('/api/v1/dashboard'), () => k.open(dp, '/dean'));
  const hidden = seen.pop()?.body;
  k.log('dean default synthetic:', JSON.stringify(hidden?.synthetic), 'labels:', (hidden?.stats ?? []).map((s) => `${s.label}=${s.value}`).join('; ').slice(0, 300));
  const toggle = dp.getByRole('switch', { name: /synthetic/i }).or(dp.getByLabel(/Include synthetic data/i)).first();
  seen = await capture(dp, (u) => u.includes('/api/v1/dashboard'), () => toggle.click());
  const shown = seen.pop()?.body;
  k.log('dean switched on:', JSON.stringify(shown?.synthetic), 'labels:', (shown?.stats ?? []).map((s) => `${s.label}=${s.value}`).join('; ').slice(0, 300));
  await k.shot(dp, '6-dean-synthetic-on');

  const admin = await k.session('admin', browser);
  const ap = admin.page;
  seen = await capture(ap, (u) => u.includes('/api/v1/admin/overview'), async () => {
    await ap.goto('http://localhost:8000/manage', { waitUntil: 'domcontentloaded', timeout: 60000 });
  });
  const ah = seen.pop()?.body;
  k.log('admin default:', JSON.stringify(ah?.synthetic), 'total_users', ah?.total_users, '(db real', db.users_real + ')');
  const frame = ap.frameLocator('iframe').first();
  const atoggle = frame.getByRole('switch', { name: /synthetic/i }).or(frame.getByLabel(/Include synthetic data/i)).first();
  seen = await capture(ap, (u) => u.includes('/api/v1/admin/overview'), () => atoggle.click());
  const as = seen.pop()?.body;
  k.log('admin switched on:', JSON.stringify(as?.synthetic), 'total_users', as?.total_users, '(db all', db.users_all + ')');
  const shownText = (await frame.locator('main, body').first().innerText()).replace(/\s+/g, ' ');
  k.log('admin page shows total users:', new RegExp(`\\b${as?.total_users}\\b`).test(shownText));
  await k.shot(ap, '6-admin-synthetic-on');
  for (const s of [dean, admin]) if (s.errors.length) k.log('JS errors:', [...new Set(s.errors)].slice(0, 3).join(' | '));
  await browser.close();
})().catch((e) => console.error('FLOW6 ERROR', e.message.split('\n')[0].slice(0, 300)));
