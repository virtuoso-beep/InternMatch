# Coordinator recommendation judgments

The native management panel lists recommendation history only for an active coordinator's assigned programs. Administrators cannot make academic judgments.

A review records the recommendation ID, coordinator ID, judgment (`suitable`, `unsuitable`, or `uncertain`), supporting reason, explicit confirmation, approver and timestamps. A reason must contain 10–5,000 characters. The reviewer can inspect the frozen explanation and all earlier judgments before confirming a new judgment.

Every confirmation creates a new history entry and an audit record. It never edits the recommendation snapshot, overwrites an earlier judgment, or creates a placement. The allocation service uses the latest confirmed judgment for that recommendation; unsuitable and uncertain judgments exclude it from proposals. Final placement remains a separate coordinator decision with current eligibility checks.

Synthetic QA judgments are retained as development evidence. They are not department-approved training data and do not establish a validated machine-learning model. Training, evaluation and production acceptance remain blocked until their required evidence is supplied.

Verification on October 1, 2026: two native/service tests passed with 25 assertions. A browser check confirmed the frozen explanation, saved a synthetic coordinator judgment, reloaded the screen, and verified that the judgment remained in history and the original recommendation facts were unchanged.
