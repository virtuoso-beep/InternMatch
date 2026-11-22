const { chromium } = require('./harness.cjs');
const EMAIL = process.env.QA_STUDENT_EMAIL; // a synthetic account; the script never completes a reset
(async () => {
  const b = await chromium.launch({ headless: true, channel: 'msedge' });
  const p = await b.newPage({ viewport: { width: 1440, height: 900 } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  const before = await (await fetch('http://localhost:8025/api/v1/messages')).json();
  await p.goto('http://localhost:8080/auth', { waitUntil: 'networkidle' });
  await p.waitForTimeout(1200);
  await p.getByRole('link', { name: 'Forgot password?' }).click();
  await p.waitForURL(/forgot-password/);
  await p.waitForTimeout(1200);
  await p.getByRole('button', { name: 'Send reset link' }).click();
  console.log('1 empty:', await p.locator('#email-error').innerText());
  await p.locator('#email').fill('nobody.qa@internmatch.test');
  await p.getByRole('button', { name: 'Send reset link' }).click();
  await p.waitForTimeout(2500);
  const unknownMsg = (await p.locator('[role=status]').innerText()).slice(0, 90);
  console.log('2 unknown address answer:', unknownMsg);
  await p.goto('http://localhost:8080/forgot-password', { waitUntil: 'networkidle' });
  await p.waitForTimeout(1200);
  await p.locator('#email').fill(EMAIL);
  await p.getByRole('button', { name: 'Send reset link' }).click();
  await p.waitForTimeout(2500);
  const knownMsg = (await p.locator('[role=status]').innerText()).slice(0, 90);
  console.log('3 same answer for real account:', knownMsg === unknownMsg);
  await p.screenshot({ path: require('path').join(__dirname, 'out', 'forgot-sent.png') });
  let link = null;
  for (let i = 0; i < 20 && !link; i++) {
    await p.waitForTimeout(1500);
    const list = await (await fetch('http://localhost:8025/api/v1/messages')).json();
    const msg = list.messages.find(m => m.To.some(t => t.Address === EMAIL) && m.Subject.includes('Reset') && !before.messages.some(o => o.ID === m.ID));
    if (msg) { const full = await (await fetch('http://localhost:8025/api/v1/message/' + msg.ID)).json(); link = (full.Text.match(/https?:[^\s)\]]+reset-password[^\s)\]]+/) || [])[0]; console.log('4 email subject:', msg.Subject); }
  }
  console.log('5 link found:', Boolean(link), link ? link.replace(/token=[^&]+/, 'token=<redacted>') : '');
  const unknownMail = (await (await fetch('http://localhost:8025/api/v1/messages')).json()).messages.filter(m => m.To.some(t => t.Address === 'nobody.qa@internmatch.test')).length;
  console.log('6 mail to unknown address:', unknownMail);
  if (link) {
    await p.goto(link.replace('http://localhost:8080', 'http://localhost:8080'), { waitUntil: 'networkidle' });
    await p.waitForTimeout(1500);
    console.log('7 page title:', await p.locator('h1').innerText(), '| account shown:', await p.getByText(EMAIL).count() > 0);
    await p.locator('#new-password').fill('weakpass');
    console.log('8 rules met:', await p.locator('#password-rules li.text-success').count(), 'of', await p.locator('#password-rules li').count());
    await p.getByRole('button', { name: 'Set new password' }).click();
    console.log('9 weak:', await p.locator('#new-password-error').innerText());
    await p.locator('#new-password').fill('Reset-Passw0rd-2026!');
    await p.locator('#confirm-password').fill('Reset-Passw0rd-2026?');
    console.log('10 mismatch:', await p.locator('#confirm-password-error').innerText(), '| rules met:', await p.locator('#password-rules li.text-success').count());
    await p.screenshot({ path: require('path').join(__dirname, 'out', 'reset-form.png') });
    const bad = link.replace(/token=([^&]+)/, (m, t) => 'token=' + t.slice(0, -4) + 'zzzz');
    await p.goto(bad, { waitUntil: 'networkidle' });
    await p.waitForTimeout(1500);
    await p.locator('#new-password').fill('Reset-Passw0rd-2026!');
    await p.locator('#confirm-password').fill('Reset-Passw0rd-2026!');
    await p.getByRole('button', { name: 'Set new password' }).click();
    await p.waitForTimeout(2500);
    console.log('11 tampered token:', (await p.locator('[role=alert]').first().innerText()).slice(0, 70));
  }
  console.log('errors:', JSON.stringify(errs));
  await b.close();
})().catch(e => console.error('ERR', e.message.slice(0, 300)));
