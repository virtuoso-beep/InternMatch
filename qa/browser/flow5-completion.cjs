// Flow 5: the supervisor submits a final evaluation; the coordinator certifies hours and ends the
// deployment; the supervisor can no longer send chat messages (UI read-only and backend 403).
// To reach the 400 required hours, earlier weekday logs are seeded as certified synthetic records.
const k = require('./flowkit.cjs');

const pid = k.php("echo DB::table('placements')->whereIn('student_enrollment_id', DB::table('student_enrollments')->where('program_term_id',4)->whereIn('student_id',DB::table('students')->whereIn('user_id',App\\Models\\User::where('email','qa.roster.one@students.internmatch.test')->pluck('id'))->pluck('id'))->pluck('id'))->value('id');");

(async () => {
  // Seed certified weekday logs 2026-09-01 .. 2026-11-13 (8 hours each), flagged synthetic.
  k.log('seeded:', k.php(`$p=App\\Models\\Placement::find(${pid}); $n=0; for($d=Carbon\\CarbonImmutable::parse('2026-09-01'); $d->lte(Carbon\\CarbonImmutable::parse('2026-11-13')); $d=$d->addDay()){ if($d->isWeekend()) continue; DB::table('time_logs')->insert(['placement_id'=>$p->id,'work_date'=>$d->toDateString(),'time_in'=>$d->setTime(0,0)->toDateTimeString(),'time_out'=>$d->setTime(9,0)->toDateTimeString(),'break_minutes'=>60,'credited_minutes'=>480,'status'=>'verified','verified_by'=>$p->supervisor_id,'verified_at'=>now(),'notes'=>'Seeded synthetic QA log','is_synthetic'=>1,'created_at'=>now(),'updated_at'=>now()]); $n++; } echo $n;`));
  k.log('certified minutes now:', k.php(`echo DB::table('time_logs')->where('placement_id',${pid})->where('status','verified')->sum('credited_minutes');`));

  const browser = await k.chromium.launch({ headless: true, channel: 'msedge' });
  const sup = await k.session('supervisor', browser);
  const coord = await k.session('coordinator', browser);
  const [sp, cp] = [sup.page, coord.page];

  // Supervisor: final evaluation.
  await k.open(sp, '/supervisor/evaluations');
  await sp.waitForTimeout(2000);
  await sp.getByRole('button', { name: 'Add evaluation' }).first().click();
  const d = k.dialog(sp);
  await d.locator('input[name=period]').fill('Final');
  await d.locator('select[name=status]').selectOption('submitted');
  await k.confirm(sp, 'Submit final evaluation');
  await sp.waitForTimeout(500);
  k.log('final without scores keeps modal open:', await d.isVisible());
  const scores = d.locator('input[type=number]');
  for (let i = 0; i < (await scores.count()); i++) await scores.nth(i).fill(await scores.nth(i).getAttribute('max'));
  await d.locator('textarea[name=comments]').fill('Reliable and accurate; ready for independent work (synthetic QA).');
  await k.confirm(sp, 'Submit final evaluation', { double: true });
  k.log('evaluation toast:', await k.toast(sp));
  k.log('evaluations:', k.php(`echo json_encode(DB::table('evaluations')->where('placement_id',${pid})->get(['period','status','submitted_at']));`));

  // Coordinator: certify hours, then end the deployment.
  await k.open(cp, '/coordinator/progress');
  await cp.locator('tr', { hasText: 'Qa Rosterone' }).first().getByRole('button', { name: 'View logs and journals' }).click();
  await cp.waitForTimeout(2500);
  await cp.getByRole('button', { name: 'Certify hours', exact: true }).first().click();
  k.log('certify confirm:', (await k.dialogText(cp)).slice(0, 220));
  await k.confirm(cp, 'Certify hours', { double: true });
  k.log('certify toast:', await k.toast(cp));
  await cp.waitForTimeout(2000);
  await cp.getByRole('button', { name: 'End deployment' }).click();
  k.log('end confirm:', (await k.dialogText(cp)).slice(0, 220));
  await k.shot(cp, '5-end-deployment');
  await k.confirm(cp, 'End deployment');
  k.log('end toast:', await k.toast(cp));
  k.log('placement:', k.php(`echo json_encode(DB::table('placements')->where('id',${pid})->first(['status','certified_minutes','completed_at']));`));

  // Supervisor chat: read-only in the UI and rejected by the backend.
  const conv = k.php(`echo DB::table('conversations')->where('placement_id',${pid})->value('id');`);
  await k.open(sp, '/supervisor');
  const api = await sp.evaluate(async (id) => {
    await fetch('/api/v1/csrf', { credentials: 'include' });
    const token = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
    const show = await fetch(`/api/v1/chat/conversations/${id}`, { credentials: 'include', headers: { Accept: 'application/json' } });
    const send = await fetch(`/api/v1/chat/conversations/${id}/messages`, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': token }, body: JSON.stringify({ body: 'QA after end', client_id: crypto.randomUUID() }) });
    return { show: show.status, writable: (await show.json()).conversation?.writable, send: send.status };
  }, conv);
  k.log('supervisor chat after end:', JSON.stringify(api));
  k.log('message stored:', k.php("echo DB::table('chat_messages')->where('body','QA after end')->count();"));
  for (const s of [sup, coord]) if (s.errors.length) k.log('JS errors:', [...new Set(s.errors)].slice(0, 3).join(' | '));
  await browser.close();
})().catch((e) => console.error('FLOW5 ERROR', e.message.split('\n')[0].slice(0, 300)));
