// Flow 4: the student submits two time logs; the supervisor certifies one and returns one with a reason;
// the student corrects and resubmits; hours update against the program's 400 required hours.
// Also: a weekly journal is submitted and the coordinator returns it with a reason.
const k = require('./flowkit.cjs');

const placementId = () => k.php("echo DB::table('placements')->whereIn('student_enrollment_id', DB::table('student_enrollments')->where('program_term_id',4)->whereIn('student_id',DB::table('students')->whereIn('user_id',App\\Models\\User::where('email','qa.roster.one@students.internmatch.test')->pluck('id'))->pluck('id'))->pluck('id'))->value('id');");
const logs = (id) => k.php(`echo json_encode(DB::table('time_logs')->where('placement_id',${id})->orderBy('id')->get(['id','work_date','status','credited_minutes','notes']));`);

(async () => {
  const browser = await k.chromium.launch({ headless: true, channel: 'msedge' });
  const stu = await k.session('student', browser);
  const sup = await k.session('supervisor', browser);
  const coord = await k.session('coordinator', browser);
  const [tp, sp, cp] = [stu.page, sup.page, coord.page];
  const pid = placementId();
  k.log('placement', pid, k.php(`echo DB::table('placements')->where('id',${pid})->value('status');`));

  // Student: two logs (first with a double-click on submit).
  await k.open(tp, '/student/internship');
  await tp.getByRole('button', { name: 'Time logs', exact: true }).click();
  for (const [i, day] of ['2026-11-17', '2026-11-18'].entries()) {
    await tp.getByRole('button', { name: 'Add time log' }).click();
    const d = k.dialog(tp);
    if (i === 0) {
      await d.locator('input[name=time_in]').fill(`${day}T17:00`);
      await d.locator('input[name=time_out]').fill(`${day}T08:00`);
      await k.confirm(tp, 'Submit time log');
      await tp.waitForTimeout(3000);
      k.log('invalid interval error in modal:', (await k.dialogText(tp)).match(/(after|positive work interval)[^.]*\./i)?.[0] ?? 'NOT SHOWN');
    }
    await d.locator('input[name=time_in]').fill(`${day}T08:00`);
    await d.locator('input[name=time_out]').fill(`${day}T17:00`);
    await d.locator('input[name=break_minutes]').fill('60');
    await d.locator('textarea[name=notes]').fill(`Synthetic QA log ${i + 1}: reconciliations and vouching.`);
    await k.confirm(tp, 'Submit time log', { double: i === 0 });
    k.log(`log ${i + 1} toast:`, await k.toast(tp));
    await tp.waitForTimeout(1000);
  }
  k.log('logs after submit:', logs(pid));

  // Supervisor: certify the first, return the second.
  await k.open(sp, '/supervisor/attendance');
  await sp.waitForTimeout(2000);
  const row = (day) => sp.locator('tr', { hasText: day }).first();
  await row('2026-11-17').getByRole('button', { name: 'Certify hours' }).click();
  k.log('certify confirm:', (await k.dialogText(sp)).slice(0, 160));
  await k.confirm(sp, 'Certify hours');
  k.log('certify toast:', await k.toast(sp));
  await row('2026-11-18').getByRole('button', { name: 'Return for correction' }).click();
  await k.confirm(sp, 'Return log');
  await sp.waitForTimeout(400);
  k.log('empty reason keeps modal open:', await k.dialog(sp).isVisible());
  await k.dialog(sp).locator('textarea').fill('Time out does not match the gate log. Correct it to 16:00.');
  await k.confirm(sp, 'Return log');
  k.log('return toast:', await k.toast(sp));
  k.log('logs after review:', logs(pid));

  // Student sees the reason, corrects and resubmits.
  await k.open(tp, '/student/internship');
  await tp.getByRole('button', { name: 'Time logs', exact: true }).click();
  await tp.waitForTimeout(800);
  k.log('student sees reason:', await tp.getByText(/Returned for correction: Time out does not match/).first().isVisible());
  await tp.getByRole('button', { name: 'Correct and resubmit' }).first().click();
  const d = k.dialog(tp);
  k.log('modal shows reason:', /Correct it to 16:00/.test(await k.dialogText(tp)));
  await d.locator('input[name=time_out]').fill('2026-11-18T16:00');
  await d.locator('textarea[name=notes]').fill('Corrected time out to 16:00 per supervisor.');
  await k.confirm(tp, 'Resubmit time log');
  k.log('resubmit toast:', await k.toast(tp));
  await k.shot(tp, '4-student-logs');

  // Supervisor certifies the corrected log.
  await k.open(sp, '/supervisor/attendance');
  await sp.waitForTimeout(2000);
  await row('2026-11-18').getByRole('button', { name: 'Certify hours' }).click();
  await k.confirm(sp, 'Certify hours');
  k.log('certify toast:', await k.toast(sp));
  k.log('logs final:', logs(pid));

  // Progress against 400 required hours.
  await k.open(cp, '/coordinator/progress');
  const progressRow = (await cp.locator('tr', { hasText: 'Qa Rosterone' }).first().innerText()).replace(/\s+/g, ' ');
  k.log('coordinator progress row:', progressRow.slice(0, 200));

  // Journal: student submits, coordinator returns with a reason, student sees it.
  await k.open(tp, '/student/internship');
  await tp.getByRole('button', { name: 'Weekly journals', exact: true }).click();
  await tp.getByRole('button', { name: 'Add journal' }).click();
  const j = k.dialog(tp);
  await j.locator('input[name=week_starts_on]').fill('2026-11-16');
  await j.locator('textarea[name=content]').fill('Week of 16 November: bank reconciliations and vouching (synthetic QA).');
  await j.locator('select[name=status]').selectOption('submitted');
  await k.confirm(tp, 'Submit journal');
  k.log('journal toast:', await k.toast(tp));
  await cp.locator('tr', { hasText: 'Qa Rosterone' }).first().getByRole('button', { name: 'View logs and journals' }).click();
  await cp.waitForTimeout(2500);
  await cp.getByRole('button', { name: 'Return for correction' }).first().click();
  await k.dialog(cp).locator('textarea').fill('Describe each day separately.');
  await k.confirm(cp, 'Return journal');
  k.log('journal return toast:', await k.toast(cp));
  await k.open(tp, '/student/internship');
  await tp.getByRole('button', { name: 'Weekly journals', exact: true }).click();
  await tp.waitForTimeout(800);
  k.log('student sees journal reason:', await tp.getByText(/Returned for correction: Describe each day separately/).first().isVisible());
  for (const s of [stu, sup, coord]) if (s.errors.length) k.log('JS errors:', [...new Set(s.errors)].slice(0, 3).join(' | '));
  await browser.close();
})().catch((e) => console.error('FLOW4 ERROR', e.message.split('\n')[0].slice(0, 300)));
