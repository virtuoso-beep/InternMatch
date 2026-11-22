// Flow 3: recommendations are regenerated, the coordinator approves a placement, the student sees it,
// and the coordinator starts the deployment. The synthetic host, agreement, capacity and opportunity are
// created through their modals on the way (valid saves).
const fs = require('fs');
const path = require('path');
const k = require('./flowkit.cjs');

const STUDENT = 'Qa Rosterone';
const MOA = 'SYN-QA-MOA-2026';
const OPP = 'SYN QA Audit Assistant';
const pdf = path.join(__dirname, 'qa-moa.pdf');
fs.writeFileSync(pdf, '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n');
const pick = async (select, text) => select.selectOption({ label: await select.locator('option', { hasText: text }).first().innerText() });
const step = async (name, fn) => {
  try {
    await fn();
  } catch (e) {
    k.log(`STEP FAILED ${name}:`, e.message.split('\n')[0].slice(0, 220));
    throw e;
  }
};

(async () => {
  const browser = await k.chromium.launch({ headless: true, channel: 'msedge' });
  const sup = await k.session('supervisor', browser);
  const coord = await k.session('coordinator', browser);
  const stu = await k.session('student', browser);
  const [sp, cp, tp] = [sup.page, coord.page, stu.page];

  await step('host capacity (supervisor)', async () => {
    await k.open(sp, '/supervisor/company');
    await sp.locator('details', { has: sp.locator('summary', { hasText: 'program capacities' }) }).first().evaluate((d) => { d.open = true; });
    await sp.getByRole('button', { name: 'Set program capacity' }).first().click();
    const d = k.dialog(sp);
    await pick(d.locator('select[name="capacities.0.program_id"]'), 'BSA');
    await pick(d.locator('select[name="capacities.0.academic_term_id"]'), 'SYN-QA-2026');
    await d.locator('input[name="capacities.0.capacity"]').fill('5');
    await k.confirm(sp, 'Save capacity');
    k.log('capacity toast:', await k.toast(sp));
  });

  await step('add MOA (coordinator)', async () => {
    await k.open(cp, '/coordinator/moa');
    await cp.getByRole('button', { name: 'Add MOA' }).click();
    const d = k.dialog(cp);
    await pick(d.locator('select[name=host_establishment_id]'), 'REYES');
    await d.locator('input[name=reference_number]').fill(MOA);
    await d.locator('input[name=effective_on]').fill('2026-08-01');
    await d.locator('input[name=expires_on]').fill('2027-07-31');
    await d.locator('[data-field=program_ids] label', { hasText: /^BSA$/ }).locator('input').check();
    await k.confirm(cp, 'Save MOA', { double: true });
    k.log('MOA toast:', await k.toast(cp));
    k.log('MOA rows with ref:', k.php(`echo DB::table('moas')->where('reference_number','${MOA}')->count();`));
  });

  await step('upload executed agreement', async () => {
    await k.open(cp, '/coordinator/moa');
    const card = cp.locator(`xpath=//*[self::h2 or self::h3][contains(normalize-space(.), '${MOA}')]/ancestor::*[.//button][1]`).first();
    await card.getByRole('button', { name: 'Upload executed agreement' }).click();
    const d = k.dialog(cp);
    k.log('upload modal:', (await k.dialogText(cp)).slice(0, 140));
    await d.locator('input[type=file]').setInputFiles(pdf);
    await k.confirm(cp, 'Upload document');
    k.log('upload toast:', await k.toast(cp));
  });

  await step('set MOA active', async () => {
    await k.open(cp, '/coordinator/moa');
    const card = cp.locator(`xpath=//*[self::h2 or self::h3][contains(normalize-space(.), '${MOA}')]/ancestor::*[.//button][1]`).first();
    await card.getByRole('button', { name: 'Edit agreement record' }).click();
    const d = k.dialog(cp);
    k.log('edit opens pre-filled:', await d.locator('input[name=reference_number]').inputValue());
    await d.locator('select[name=status]').selectOption('active');
    await k.confirm(cp, 'Save changes');
    k.log('MOA active toast:', await k.toast(cp));
  });

  await step('add opportunity (coordinator)', async () => {
    await k.open(cp, '/coordinator/opportunities');
    await cp.getByRole('button', { name: 'Add opportunity' }).click();
    const d = k.dialog(cp);
    await k.confirm(cp, 'Save opportunity');
    await cp.waitForTimeout(400);
    k.log('empty submit keeps modal open:', await d.isVisible());
    await pick(d.locator('select[name=host_establishment_id]'), 'REYES');
    await pick(d.locator('select[name=academic_term_id]'), 'SYN-QA-2026');
    await d.locator('input[name=title]').fill(OPP);
    await d.locator('textarea[name=description]').fill('Synthetic QA opportunity: support the audit team with working papers and reconciliations.');
    await d.locator('textarea[name=tasks]').fill('Prepare bank reconciliations; assist in vouching; update working papers.');
    await d.locator('input[name=starts_on]').fill('2026-08-01');
    await d.locator('input[name=ends_on]').fill('2027-03-31');
    await d.locator('select[name=status]').selectOption('published');
    await pick(d.locator('select[name="programs.0.program_id"]'), 'BSA');
    await d.locator('input[name="programs.0.capacity"]').fill('3');
    const boxes = d.locator('[data-field=competency_ids] input[type=checkbox]');
    for (let i = 0; i < Math.min(4, await boxes.count()); i++) await boxes.nth(i).check();
    await k.confirm(cp, 'Save opportunity');
    k.log('opportunity toast:', await k.toast(cp));
  });

  await step('student competencies', async () => {
    await k.open(tp, '/student/competencies');
    for (let i = 1; i <= 3; i++) {
      await tp.getByRole('button', { name: 'Add competency' }).first().click();
      const d = k.dialog(tp);
      const sel = d.locator('select[name=competency_id]');
      await sel.selectOption({ index: i });
      await d.locator('input[name=level]').fill(String(70 + i * 5));
      await k.confirm(tp, 'Save competency');
      k.log(`competency ${i} toast:`, await k.toast(tp));
      await tp.waitForTimeout(800);
    }
  });

  await step('embeddings ready', async () => {
    for (let i = 0; i < 40; i++) {
      const ready = k.php(`$o=App\\Models\\Opportunity::where('title','${OPP}')->first(); $s=App\\Models\\Student::whereIn('user_id',App\\Models\\User::where('email','qa.roster.one@students.internmatch.test')->pluck('id'))->first(); echo (DB::table('embeddings')->where('owner_type',$o->getMorphClass())->where('owner_id',$o->id)->exists()?1:0).(DB::table('embeddings')->where('owner_type',$s->getMorphClass())->where('owner_id',$s->id)->exists()?1:0);`);
      k.log('embeddings opportunity/student:', ready);
      if (ready === '11') return;
      await tp.waitForTimeout(5000);
    }
    throw new Error('embeddings not ready');
  });

  await step('student regenerates recommendations', async () => {
    await k.open(tp, '/student/recommendations');
    await tp.getByRole('button', { name: /^(Generate|Refresh) recommendations$/ }).click();
    k.log('confirm:', (await k.dialogText(tp)).slice(0, 120));
    await k.confirm(tp, (await k.dialog(tp).getByRole('button', { name: /recommendations$/ }).innerText()).trim());
    k.log('recommendations toast:', await k.toast(tp));
    await tp.waitForTimeout(1500);
    k.log('recommended opportunity visible:', await tp.getByText(OPP).first().isVisible().catch(() => false));
    await k.shot(tp, '3-recommendations');
  });

  await step('academic review (coordinator)', async () => {
    await k.open(cp, '/coordinator/approvals');
    await pick(cp.getByLabel('Program and term'), 'Synthetic QA term 2026');
    await cp.waitForTimeout(1500);
    await cp.locator('details', { has: cp.locator('summary', { hasText: 'Student eligibility' }) }).evaluate((d) => { d.open = true; });
    const row = cp.locator('ul[aria-label="Student eligibility"] > li', { hasText: STUDENT }).first();
    await row.getByRole('button', { name: 'Academic review' }).click();
    const d = k.dialog(cp);
    await d.getByLabel('Department academic eligibility confirmed').check();
    await d.locator('textarea').fill('Fourth-year standing confirmed against department records (synthetic QA).');
    await k.confirm(cp, 'Save academic review');
    k.log('academic review toast:', await k.toast(cp));
  });

  await step('create proposal batch', async () => {
    await cp.locator('details', { has: cp.locator('summary', { hasText: 'Create new proposals' }) }).evaluate((d) => { d.open = true; });
    await cp.getByRole('button', { name: 'Create proposal batch' }).click();
    k.log('confirm:', (await k.dialogText(cp)).slice(0, 140));
    await k.confirm(cp, 'Create proposal batch', { double: true });
    k.log('batch toast:', await k.toast(cp));
    k.log('proposal batches for term:', k.php("echo DB::table('placement_proposals')->where('program_term_id',4)->count();"));
  });

  await step('approve placement', async () => {
    await cp.waitForTimeout(1500);
    const item = cp.locator('ul[aria-label="Proposed placements"] > li', { hasText: STUDENT }).first();
    await k.shot(cp, '3-approvals-proposals');
    k.log('proposal item:', (await item.innerText()).replace(/\s+/g, ' ').slice(0, 200));
    await item.getByRole('button', { name: 'Review placement' }).click();
    const d = k.dialog(cp);
    await pick(d.getByLabel('Supervisor'), 'Hannah');
    await d.getByLabel('Start date').fill('2026-12-18');
    await d.getByLabel('End date').fill('2026-09-01');
    k.log('end before start warning:', await d.getByText('End date must not precede start date.').isVisible(), 'approve disabled:', await d.getByRole('button', { name: 'Approve placement' }).isDisabled());
    await d.getByLabel('Start date').fill('2026-09-01');
    await d.getByLabel('End date').fill('2026-12-18');
    await d.locator('textarea').fill('Recommended match within capacity; MOA active; requirements approved (synthetic QA).');
    await k.confirm(cp, 'Approve placement');
    k.log('approve toast:', await k.toast(cp));
    k.log('placement:', k.php("echo json_encode(DB::table('placements')->where('student_enrollment_id', DB::table('student_enrollments')->where('program_term_id',4)->whereIn('student_id',DB::table('students')->whereIn('user_id',App\\Models\\User::where('email','qa.roster.one@students.internmatch.test')->pluck('id'))->pluck('id'))->value('id'))->get(['id','status','starts_on','ends_on','supervisor_id']));"));
  });

  await step('student sees placement', async () => {
    await k.open(tp, '/student');
    const text = (await tp.locator('main').innerText()).replace(/\s+/g, ' ');
    k.log('student dashboard mentions host:', /REYES/i.test(text), /approved/i.test(text));
    await k.shot(tp, '3-student-placement');
  });

  await step('start deployment (coordinator)', async () => {
    await k.open(cp, '/coordinator/progress');
    await cp.locator('tr', { hasText: STUDENT }).first().getByRole('button', { name: 'View logs and journals' }).click();
    await cp.waitForTimeout(2000);
    await cp.getByRole('button', { name: 'Start deployment' }).click();
    k.log('confirm:', (await k.dialogText(cp)).slice(0, 160));
    await k.confirm(cp, 'Start deployment');
    k.log('start toast:', await k.toast(cp));
  });

  for (const s of [sup, coord, stu]) if (s.errors.length) k.log('JS errors:', [...new Set(s.errors)].slice(0, 3).join(' | '));
  await browser.close();
})().catch((e) => console.error('FLOW3 ERROR', e.message.split('\n')[0].slice(0, 300)));
