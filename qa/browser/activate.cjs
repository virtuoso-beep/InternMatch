// Activates the synthetic roster account through the emailed link (Mailpit), as a real student would.
const k = require('./flowkit.cjs');

(async () => {
  const list = await (await fetch('http://localhost:8025/api/v1/messages?limit=20')).json();
  const msg = list.messages.find((m) => m.To[0].Address === process.env.QA_STUDENT_EMAIL);
  const body = await (await fetch(`http://localhost:8025/api/v1/message/${msg.ID}`)).json();
  const link = (body.Text + body.HTML).match(/https?:\/\/[^\s"<>]+activate\?token=[^\s"<>&]+/)[0];
  const browser = await k.chromium.launch({ headless: true, channel: 'msedge' });
  const page = await browser.newPage();
  await page.goto(link, { waitUntil: 'networkidle', timeout: 60000 });
  await page.waitForTimeout(2500);
  await page.getByLabel('New password').fill(process.env.QA_STUDENT_PW);
  await page.getByLabel('Confirm password').fill(process.env.QA_STUDENT_PW);
  await page.getByRole('button', { name: /activate/i }).click();
  await page.waitForTimeout(3000);
  k.log('after activation:', (await page.locator('[role=status],[role=alert],h1').allInnerTexts()).join(' / ').slice(0, 200));
  await browser.close();
  k.log('account status:', k.php(`echo App\\Models\\User::where('email','${process.env.QA_STUDENT_EMAIL}')->first()->status->value;`));
})().catch((e) => console.error('ACTIVATE ERROR', e.message.slice(0, 300)));
