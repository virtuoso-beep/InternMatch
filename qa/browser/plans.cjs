// Every modal per role, with how to open it. `form: true` adds the "Discard changes?" check.
// `optional: true` marks openers that only exist for some records (reported as N/A when absent).
const btn = (name) => (root) => root.getByRole('button', { name });
const menu = (name) => (root) => root.getByRole('menuitem', { name });
const openAccount = async (page) => {
  await page.getByRole('button', { name: 'Account menu' }).click();
  await page.waitForTimeout(250);
};

const header = (base) => ({
  path: base,
  items: [
    { name: 'Sign out', prepare: openAccount, opener: menu('Sign out'), title: '^Sign out\\?$' },
    { name: 'Change password (menu)', prepare: openAccount, opener: menu('Change password'), title: '^Change password$', form: true },
  ],
});

module.exports = {
  student: [
    header('/student'),
    { path: '/student/competencies', items: [
      { name: 'Add competency', button: 'Add competency', title: '^Add competency$', form: true },
      { name: 'Edit competency', opener: btn(/^Edit/), title: '^Edit competency: ', form: true, optional: true },
      { name: 'Remove competency', opener: btn(/^Remove/), title: '^Remove .+\\?$', optional: true },
    ] },
    { path: '/student/requirements', items: [
      { name: 'Upload document', button: 'Upload document', title: '^Upload document$', form: true, optional: true },
      { name: 'Requirement details', opener: btn(/^View/), optional: true },
    ] },
    { path: '/student/opportunities', items: [
      { name: 'Opportunity details', opener: btn(/^View details/), optional: true },
    ] },
    { path: '/student/recommendations', items: [
      { name: 'Recommendation details', opener: btn(/^View details/), optional: true },
      { name: 'Refresh recommendations', opener: btn(/^(Refresh|Generate) recommendations$/), title: 'recommendations\\?$', optional: true },
      { name: 'Express interest', opener: btn(/^Express interest$/), title: '^Express interest in ', optional: true },
    ] },
    { path: '/student/internship', items: [
      { name: 'Add time log', button: 'Add time log', title: '^Add time log$', form: true, optional: true },
      { name: 'Add journal', button: 'Add journal', title: '^Add journal$', form: true, optional: true },
    ] },
    { path: '/student/profile', items: [
      { name: 'Change password (profile)', button: 'Change password', title: '^Change password$', form: true },
    ] },
  ],
  supervisor: [
    header('/supervisor'),
    { path: '/supervisor/attendance', items: [
      { name: 'Certify hours', opener: btn(/^Certify hours$/), title: '^Certify hours for ', optional: true },
      { name: 'Return log', opener: btn(/^Return for correction$/), title: '^Return the ', form: true, optional: true },
      { name: 'Certify pending batch', opener: btn(/^Review pending logs/), title: '^Certify pending hours$', optional: true },
    ] },
    { path: '/supervisor/evaluations', items: [
      { name: 'Add evaluation', button: 'Add evaluation', title: '^Add evaluation$', form: true, optional: true },
      { name: 'Edit draft evaluation', opener: btn(/^Edit draft evaluation$/), title: '^Edit evaluation: ', form: true, optional: true },
    ] },
    { path: '/supervisor/company', items: [
      { name: 'Edit profile', button: 'Edit profile', title: '^Edit profile: ', form: true },
      { name: 'Set program capacity', prepare: async (page) => { await page.locator('details', { has: page.locator('summary', { hasText: 'program capacities' }) }).first().evaluate((d) => { if (!d.open) d.open = true; }); await page.waitForTimeout(800); }, button: 'Set program capacity', title: '^Set program capacity: ', form: true },
    ] },
    { path: '/supervisor/opportunities', items: [
      { name: 'Add opportunity', button: 'Add opportunity', title: '^Add opportunity$', form: true },
      { name: 'Edit opportunity', opener: btn(/^Edit opportunity$/), title: '^Edit opportunity: ', form: true, optional: true },
    ] },
    { path: '/supervisor/moa', items: [
      { name: 'Add MOA', button: 'Add MOA', title: '^Add MOA$', form: true },
      { name: 'Edit MOA', opener: btn(/^Edit agreement record$/), title: '^Edit MOA for ', form: true, optional: true },
      { name: 'Upload executed agreement', opener: btn(/^Upload executed agreement$/), title: '^Upload executed agreement: ', optional: true },
    ] },
    { path: '/supervisor/profile', items: [
      { name: 'Change password (profile)', button: 'Change password', title: '^Change password$', form: true },
    ] },
  ],
  coordinator: [
    header('/coordinator'),
    { path: '/coordinator/students', items: [
      { name: 'Student profile', opener: (root) => root.locator('table button.underline').first(), optional: true },
      { name: 'Edit enrollment', opener: btn(/^Edit enrollment$/), title: '^Edit enrollment: ', form: true, optional: true },
      { name: 'Withdraw', opener: btn(/^Withdraw$/), title: '^Withdraw ', form: true, optional: true },
      { name: 'Send activation link', opener: btn(/activation link$/), title: 'activation link to ', optional: true },
    ] },
    { path: '/coordinator/recommendations', items: [
      { name: 'Regenerate batch', opener: btn(/^Regenerate/), title: '^Regenerate recommendation batch$', optional: true },
      { name: 'Review recommendation', opener: btn(/^Review/), title: '^Review recommendation for ', form: true, optional: true },
    ] },
    { path: '/coordinator/approvals', items: [
      { name: 'Create proposal batch', prepare: async (page) => { await page.locator('details', { has: page.locator('summary', { hasText: 'Create new proposals' }) }).first().evaluate((d) => { if (!d.open) d.open = true; }); }, opener: btn(/^Create proposal batch$/), title: '^Create a proposal batch for ', optional: true },
      { name: 'Review placement', opener: btn(/^Review placement$/), title: '^Review placement for ', optional: true },
      { name: 'Academic review', prepare: async (page) => { await page.locator('details', { has: page.locator('summary', { hasText: 'Student eligibility' }) }).first().evaluate((d) => { if (!d.open) d.open = true; }); }, opener: btn(/^Academic review$/), title: '^Academic review for ', optional: true },
    ] },
    { path: '/coordinator/hosts', items: [
      { name: 'Add host', button: 'Add host', title: '^Add host establishment$', form: true },
      { name: 'Edit profile', opener: btn(/^Edit profile$/), title: '^Edit profile: ', form: true, optional: true },
      { name: 'Archive host', opener: btn(/^Archive$/), title: '^Archive .+\\?$', optional: true },
      { name: 'Delete host', opener: btn(/^Delete$/), title: '^Delete .+\\?$', optional: true },
    ] },
    { path: '/coordinator/moa', items: [
      { name: 'Add MOA', button: 'Add MOA', title: '^Add MOA$', form: true },
      { name: 'Archive MOA', opener: btn(/^Archive$/), title: '^Archive MOA ', optional: true },
    ] },
    { path: '/coordinator/opportunities', items: [
      { name: 'Add opportunity', button: 'Add opportunity', title: '^Add opportunity$', form: true },
      { name: 'Archive opportunity', opener: btn(/^Archive$/), title: '^Archive .+\\?$', optional: true },
    ] },
    { path: '/coordinator/requirements', items: [
      { name: 'Configure requirement', button: 'Configure requirement', title: '^Configure requirement$', form: true },
    ] },
    { path: '/coordinator/monitoring-settings', items: [
      { name: 'New rubric version', opener: btn(/^New rubric version$/), title: '^New rubric version: ', form: true, optional: true },
      { name: 'Edit monitoring rules', opener: btn(/^Edit monitoring rules$/), title: '^Monitoring rules: ', form: true, optional: true },
    ] },
    { path: '/coordinator/profile', items: [
      { name: 'Change password (profile)', button: 'Change password', title: '^Change password$', form: true },
    ] },
  ],
  dean: [
    header('/dean'),
    { path: '/dean/profile', items: [
      { name: 'Change password (profile)', button: 'Change password', title: '^Change password$', form: true },
    ] },
  ],
  admin: [
    { path: 'terms', admin: true, items: [
      { name: 'Add term', opener: btn(/^Add term$/), title: '^Add academic term$', form: true },
      { name: 'Deactivate or activate term', opener: btn(/^(Deactivate|Set active)$/), title: '(Deactivate|Set) .+', optional: true },
    ] },
    { path: 'users', admin: true, items: [
      { name: 'Add user', opener: btn(/^Add user$/), title: '^Add user$', form: true },
      { name: 'Manage access', opener: btn(/^Manage access/), title: '^Manage access: ' },
      { name: 'Disable account', opener: btn(/^Disable$/), title: '^Disable .+\\?$', optional: true },
    ] },
    { path: 'students', admin: true, items: [
      { name: 'Add student', opener: btn(/^Add student$/), title: '^Add student$', form: true, optional: true },
    ] },
    { path: 'hosts', admin: true, items: [
      { name: 'Add host', opener: btn(/^Add host$/), title: '^Add host establishment$', form: true },
    ] },
    { path: 'moa', admin: true, items: [
      { name: 'Add MOA', opener: btn(/^Add MOA$/), title: '^Add MOA$', form: true },
    ] },
    { path: 'opportunities', admin: true, items: [
      { name: 'Add opportunity', opener: btn(/^Add opportunity$/), title: '^Add opportunity$', form: true },
    ] },
    { path: 'programs', admin: true, items: [
      { name: 'Edit program requirements', opener: btn(/^Edit requirements/), title: '^Edit requirements: ', form: true },
    ] },
    { path: 'chat-settings', admin: true, items: [
      { name: 'Disable or enable chat', opener: btn(/^(Disable|Enable) chat$/), title: 'chat for everyone\\?$' },
    ] },
    { path: 'profile', admin: true, items: [
      { name: 'Change password (profile)', opener: btn(/^Change password$/), title: '^Change password$', form: true },
    ] },
  ],
};
