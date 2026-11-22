const { runPlan } = require('./harness.cjs');
const plans = require('./plans.cjs');
const role = process.argv[2];
const only = process.argv[3] ? process.argv[3].split(',') : null;
runPlan(role, plans[role], only).then((results) => {
  const counted = results.filter((r) => !r.na);
  console.log(`SUMMARY ${role}: ${counted.filter((r) => r.pass).length}/${counted.length} passed, ${results.length - counted.length} not applicable`);
}).catch((e) => console.error('FATAL', e.message));
