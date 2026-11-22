// Flow 1: the coordinator imports a small synthetic roster and the accounts are created.
const path = require('path');
const k = require('./flowkit.cjs');

(async () => {
  const browser = await k.chromium.launch({ headless: true, channel: 'msedge' });
  const { page, errors } = await k.session('coordinator', browser);
  const before = k.php("echo App\\Models\\User::where('email','like','qa.roster.%')->count();");
  k.log('qa roster accounts before:', before);
  await k.open(page, '/coordinator/students');
  await page.getByRole('button', { name: 'Import Students' }).click();
  const area = page.getByRole('region', { name: 'Import student records' });
  await area.locator('input[type=file]').setInputFiles(path.join(__dirname, 'fixtures', 'qa-roster.xlsx'));
  await area.getByRole('button', { name: 'Upload Excel' }).click();
  await area.getByRole('button', { name: 'Build editable preview' }).waitFor({ timeout: 30000 });
  await area.getByRole('button', { name: 'Build editable preview' }).click();
  await page.waitForTimeout(1500);
  const program = area.locator('label', { hasText: 'Assigned program' }).locator('select');
  await program.selectOption({ label: await program.locator('option', { hasText: 'BSA' }).first().innerText() });
  const term = area.getByLabel('Import academic term');
  await term.selectOption({ label: await term.locator('option', { hasText: 'SYN-QA-2026' }).first().innerText() });
  const date = area.getByLabel('Default enrollment date');
  if (await date.count()) {
    await area.locator('summary', { hasText: 'Apply confirmed enrollment details' }).click();
    await date.fill('2026-08-03');
    await area.getByRole('button', { name: 'Fill blank enrollment fields' }).click();
  }
  await area.getByRole('button', { name: 'Validate and detect duplicates' }).click();
  await page.waitForTimeout(3000);
  await k.shot(page, '1-preview');
  const summary = (await area.innerText()).replace(/\s+/g, ' ');
  k.log('preview text:', summary.match(/Preview summary.{0,200}/)?.[0] ?? summary.slice(-300));
  await area.getByLabel(/I reviewed the records/).check();
  await area.getByRole('button', { name: 'Confirm import' }).click();
  await page.waitForTimeout(500);
  k.log('confirm dialog:', await k.dialogText(page));
  await k.shot(page, '1-confirm-modal');
  await k.confirm(page, 'Confirm import', { double: true });
  k.log('toast:', await k.toast(page, 30000));
  await page.waitForTimeout(1500);
  await k.shot(page, '1-result');
  const after = k.php("echo json_encode(App\\Models\\User::where('email','like','qa.roster.%')->get(['id','email','status','role'])->map(fn(\$u)=>[\$u->id,\$u->email,\$u->status->value,\$u->role?->value]));");
  k.log('qa roster accounts after:', after);
  const enrollments = k.php("echo App\\Models\\StudentEnrollment::where('program_term_id',4)->count();");
  k.log('enrollments in SYN-QA-2026 BSA:', enrollments);
  if (errors.length) k.log('JS errors:', [...new Set(errors)].slice(0, 4).join(' | '));
  await browser.close();
})().catch((e) => console.error('FLOW1 ERROR', e.message.slice(0, 300)));
