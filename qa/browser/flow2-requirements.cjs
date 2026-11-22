// Flow 2: the student uploads requirements; the coordinator approves one and returns one with a reason;
// the student sees the reason and re-uploads; the coordinator approves the replacement.
const fs = require('fs');
const path = require('path');
const k = require('./flowkit.cjs');

const pdf = (name) => {
  const file = path.join(__dirname, name);
  fs.writeFileSync(file, '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n');
  return file;
};
const STUDENT = 'Qa Rosterone';
const REQS = ['Medical Certificate', 'Barangay Clearance'];
const status = () => k.php("echo json_encode(DB::table('requirement_submissions as s')->join('program_term_requirements as r','r.id','=','s.program_term_requirement_id')->join('requirement_types as t','t.id','=','r.requirement_type_id')->join('student_enrollments as e','e.id','=','s.student_enrollment_id')->where('r.program_term_id',4)->whereIn('e.student_id',DB::table('students')->whereIn('user_id',App\\Models\\User::where('email','like','qa.roster.one%')->pluck('id'))->pluck('id'))->orderBy('s.id')->get(['t.name','s.revision','s.status']));");

async function coordinatorCard(page) {
  await k.open(page, '/coordinator/requirements');
  await page.waitForTimeout(4000);
  const card = page.locator(`xpath=//*[self::h2 or self::h3][contains(normalize-space(.), '${STUDENT}')]/ancestor::*[.//section][1]`).first();
  await card.scrollIntoViewIfNeeded();
  return card;
}
const reqSection = (card, name) => card.locator(`xpath=.//section[.//h3[starts-with(normalize-space(.), '${name}')]]`).first();

(async () => {
  const browser = await k.chromium.launch({ headless: true, channel: 'msedge' });
  const coord = await k.session('coordinator', browser);
  const page = coord.page;

  // 1. Configure two pre-deployment document requirements (invalid first: nothing selected).
  await k.open(page, '/coordinator/requirements');
  await page.getByRole('button', { name: 'Configure requirement' }).click();
  await page.waitForTimeout(400);
  await k.confirm(page, 'Save requirement');
  await page.waitForTimeout(600);
  const invalidStillOpen = await k.dialog(page).isVisible();
  const before = k.php('echo DB::table(\'program_term_requirements\')->where(\'program_term_id\',4)->count();');
  k.log('invalid submit keeps modal open:', invalidStillOpen, 'rows:', before);
  for (const [i, name] of REQS.entries()) {
    if (i > 0) {
      await page.getByRole('button', { name: 'Configure requirement' }).click();
      await page.waitForTimeout(400);
    }
    const d = k.dialog(page);
    const pt = d.locator('select[name=program_term_id]');
    await pt.selectOption({ label: await pt.locator('option', { hasText: 'SYN-QA-2026' }).first().innerText() });
    await d.locator('select[name=requirement_type_id]').selectOption({ label: name });
    await k.confirm(page, 'Save requirement', { double: i === 0 });
    k.log(`configured ${name}; toast:`, await k.toast(page));
    await page.waitForTimeout(800);
  }
  k.log('requirements now:', k.php("echo json_encode(DB::table('program_term_requirements')->where('program_term_id',4)->get(['requirement_type_id','required_before_deployment']));"));

  // 2. Student uploads both documents (invalid file type first).
  const stu = await k.session('student', browser);
  const sp = stu.page;
  await k.open(sp, '/student/requirements');
  const bad = path.join(__dirname, 'notes.txt');
  fs.writeFileSync(bad, 'not a document');
  for (const [i, name] of REQS.entries()) {
    await sp.locator('button:not([disabled])', { hasText: 'Upload document' }).first().click({ timeout: 180000 });
    await sp.waitForTimeout(400);
    const d = k.dialog(sp);
    await d.locator('select[name=requirement_id]').selectOption({ label: name });
    if (i === 0) {
      await d.locator('input[type=file]').setInputFiles(bad);
      await k.confirm(sp, 'Submit document');
      await sp.waitForTimeout(500);
      k.log('invalid file message:', (await k.dialogText(sp)).match(/Use PDF[^.]*\./)?.[0] ?? 'NOT SHOWN');
    }
    await d.locator('input[type=file]').setInputFiles(pdf(`qa-${i}.pdf`));
    await k.confirm(sp, 'Submit document', { double: i === 0 });
    k.log(`uploaded ${name}; toast:`, await k.toast(sp));
    await sp.waitForTimeout(1000);
  }
  await k.shot(sp, '2-student-uploaded');
  k.log('after upload:', status());

  // 3. Coordinator approves the first and returns the second with a reason.
  let card = await coordinatorCard(page);
  await reqSection(card, REQS[0]).getByRole('button', { name: 'Approve' }).click();
  k.log('approve dialog:', await k.dialogText(page));
  await k.confirm(page, 'Approve');
  k.log('toast:', await k.toast(page));
  card = await coordinatorCard(page);
  await reqSection(card, REQS[1]).getByRole('button', { name: 'Return for correction' }).click();
  await k.confirm(page, 'Return document');
  await page.waitForTimeout(500);
  k.log('empty reason keeps modal open:', await k.dialog(page).isVisible());
  await k.dialog(page).locator('textarea[name=comments]').fill('The clearance is unsigned. Upload the signed copy.');
  await k.shot(page, '2-return-reason');
  await k.confirm(page, 'Return document');
  k.log('toast:', await k.toast(page));
  k.log('after review:', status());

  // 4. Student sees the reason and uploads a replacement.
  await k.open(sp, '/student/requirements');
  await sp.getByRole('button', { name: /^View/ }).nth(1).click().catch(async () => {
    await sp.getByRole('button', { name: new RegExp(`View.*${REQS[1]}`) }).first().click();
  });
  await sp.waitForTimeout(600);
  const details = await k.dialogText(sp);
  k.log('student sees reason:', /unsigned/.test(details), details.slice(0, 160));
  await k.shot(sp, '2-student-sees-reason');
  await k.dialog(sp).getByRole('button', { name: 'Upload replacement revision' }).click();
  await sp.waitForTimeout(400);
  const d = k.dialog(sp);
  await d.locator('select[name=requirement_id]').selectOption({ label: REQS[1] });
  await d.locator('input[type=file]').setInputFiles(pdf('qa-2.pdf'));
  await k.confirm(sp, 'Submit document');
  k.log('re-upload toast:', await k.toast(sp));

  // 5. Coordinator approves the replacement.
  card = await coordinatorCard(page);
  await reqSection(card, REQS[1]).getByRole('button', { name: 'Approve' }).click();
  await k.confirm(page, 'Approve');
  k.log('toast:', await k.toast(page));
  // 6. Remaining pre-deployment documents created by the import: upload and approve.
  const EXTRA = ['Resume; Application Letter; Institutional Requirmeents', 'Endorsement Letter'];
  for (const [i, name] of EXTRA.entries()) {
    await k.open(sp, '/student/requirements');
    await sp.locator('button:not([disabled])', { hasText: 'Upload document' }).first().click({ timeout: 180000 });
    await sp.waitForTimeout(400);
    const dx = k.dialog(sp);
    await dx.locator('select[name=requirement_id]').selectOption({ label: name });
    await dx.locator('input[type=file]').setInputFiles(pdf(`qa-extra-${i}.pdf`));
    await k.confirm(sp, 'Submit document');
    k.log(`uploaded ${name}; toast:`, await k.toast(sp));
    card = await coordinatorCard(page);
    await reqSection(card, name).getByRole('button', { name: 'Approve' }).click();
    await k.confirm(page, 'Approve');
    k.log('toast:', await k.toast(page));
  }
  k.log('final:', status());
  for (const s of [coord, stu]) if (s.errors.length) k.log('JS errors:', [...new Set(s.errors)].slice(0, 3).join(' | '));
  await browser.close();
})().catch((e) => console.error('FLOW2 ERROR', e.message.slice(0, 400)));
