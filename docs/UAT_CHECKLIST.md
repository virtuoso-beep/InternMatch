# User Acceptance Test (UAT) Checklist — InternMatch

**Project:** InternMatch, UM Tagum College internship placement system
**Version tested:** ____________ (commit or release)  **Site address:** ______________________________
**Test period:** ______________ to ______________

## How to use this checklist

- Test on the deployed site (production or a staging copy), not on a developer's computer.
- Use **real roles, test records**: real staff test their own role; students and host data may be test
  records created for UAT. Do not use real student personal data you are not permitted to use.
- Work **in the order below**. Each part creates what the next one needs (an admin creates the term and
  accounts; a coordinator places a student; the supervisor then logs hours, and so on).
- For each scenario write **Pass** or **Fail**, your name and the date. For a Fail, write what happened in
  the Notes column (what you clicked, what you expected, what you saw) and take a screenshot.
- Words in **bold** are the exact menu or button names on screen.

## 0. Before testing (administrator)

| # | Scenario | Steps | Expected result | Pass/Fail | Tester | Date | Notes |
|---|---|---|---|---|---|---|---|
| 0.1 | First sign-in | Open `<site>/manage`, sign in with the administrator account created at deployment | The administration **Dashboard** opens after the privacy notice is acknowledged | | | | |
| 0.2 | Academic term | **Academic Terms** → add the UAT term and mark it active | Term is listed as active; the dashboard no longer says "No academic term is open" | | | | |
| 0.3 | Program | **Programs & Curriculum** → check the program under test (required hours, requirements) | Program shows the correct required hours | | | | |
| 0.4 | Staff accounts | **User Accounts** → create a coordinator (assigned to the program), a dean and a host supervisor | Each person receives an activation e-mail | | | | |
| 0.5 | Account activation | Each staff member opens the e-mail link and sets a password | They can sign in; the link cannot be used twice | | | | |

## 1. Coordinator: roster, host, agreement and opportunity

| # | Scenario | Steps | Expected result | Pass/Fail | Tester | Date | Notes |
|---|---|---|---|---|---|---|---|
| 1.1 | Sign in | Sign in at `<site>/auth` | **Dashboard** opens with the program's figures | | | | |
| 1.2 | Import students | **Student Profiles** → import the UAT roster (Excel) | Preview shows the rows; after import the students are listed and receive activation e-mails | | | | |
| 1.3 | Host establishment | **Host Establishments** → add a host with its address | Host is listed; its location appears on **Accessibility Map** | | | | |
| 1.4 | Agreement (MOA) | **Agreements** → register the host's agreement, select the program, dates | Agreement is active and covers the program | | | | |
| 1.5 | Opportunity | **Opportunities** → add an internship with tasks, competencies and slots for the program | Opportunity is published | | | | |
| 1.6 | Requirements | **Requirements** → check the documents students must submit | The list matches the program's requirements | | | | |

## 2. Student: profile, requirements and recommendations

| # | Scenario | Steps | Expected result | Pass/Fail | Tester | Date | Notes |
|---|---|---|---|---|---|---|---|
| 2.1 | Activate and sign in | Open the activation e-mail, set a password, sign in | Student **Dashboard** opens; privacy notice shown once | | | | |
| 2.2 | Profile and location | Profile → fill in details and home location | Saved; location is used for distance only | | | | |
| 2.3 | Competencies | **Competencies** → add skills | Skills are saved | | | | |
| 2.4 | Upload requirements | **Requirements** → upload each document | Each shows "Under review" | | | | |
| 2.5 | Recommendations | **Recommendations** → generate | Internships are listed with rank and similarity score; only internships the student can actually take (valid agreement, slot left) appear | | | | |
| 2.6 | Browse opportunities | **Opportunities** | Published internships for the program are shown | | | | |

## 3. Coordinator: review and placement

| # | Scenario | Steps | Expected result | Pass/Fail | Tester | Date | Notes |
|---|---|---|---|---|---|---|---|
| 3.1 | Review a requirement | **Requirements** → approve one document; return another with a reason | Student sees "Approved" and the returned one with the reason | | | | |
| 3.2 | Student re-uploads | (Student) re-upload the returned document; (Coordinator) approve | Status becomes "Approved" | | | | |
| 3.3 | Judge a recommendation | **Recommendations** → mark one suggestion suitable or not suitable, with a reason | Judgment is saved (these teach the recommendations later) | | | | |
| 3.4 | Approve a placement | **Pending Approvals** → approve the student's placement | Student's **My Internship** shows the placement; the student is notified | | | | |
| 3.5 | Start deployment | **Internship Progress** → start the deployment | Status becomes active; the supervisor sees the intern | | | | |

## 4. Host supervisor: hours, journals and evaluation

| # | Scenario | Steps | Expected result | Pass/Fail | Tester | Date | Notes |
|---|---|---|---|---|---|---|---|
| 4.1 | Sign in | Sign in at `<site>/auth` | Supervisor **Dashboard** opens | | | | |
| 4.2 | Assigned interns | **Assigned Interns** | The placed student is listed | | | | |
| 4.3 | Student logs time | (Student) **My Internship** → submit two time logs and a weekly journal | Logs show as submitted | | | | |
| 4.4 | Certify hours | **Attendance & Hours** → certify one log, return one with a reason | Student sees the certified hours and the returned log with the reason | | | | |
| 4.5 | Corrected log | (Student) correct and resubmit; (Supervisor) certify | Certified hours increase against the required hours | | | | |
| 4.6 | Messages | Send a message to the student in the inbox | Student receives it | | | | |
| 4.7 | Final evaluation | **Evaluations** → complete and submit the evaluation | Coordinator and student see the evaluation | | | | |

## 5. Coordinator: completion

| # | Scenario | Steps | Expected result | Pass/Fail | Tester | Date | Notes |
|---|---|---|---|---|---|---|---|
| 5.1 | Monitoring alerts | **Internship Progress** | Students behind schedule or missing logs are flagged | | | | |
| 5.2 | End deployment | Certify hours and end the deployment | Placement is completed; the supervisor's chat becomes read-only | | | | |
| 5.3 | Report | **Analytics** → choose the cohort → **Generate report** → approve it | Report is listed; it can be downloaded as CSV, Excel and PDF | | | | |

## 6. Dean

| # | Scenario | Steps | Expected result | Pass/Fail | Tester | Date | Notes |
|---|---|---|---|---|---|---|---|
| 6.1 | Executive dashboard | Sign in → **Executive Dashboard** | Figures match the placements made in this UAT | | | | |
| 6.2 | Analytics | **Program Performance**, **Placement Equity**, **Analytics** | Charts load; no test data unless "Include test data" is ticked | | | | |
| 6.3 | Reports | **Accreditation Reports** | The coordinator-approved report is visible and downloadable | | | | |
| 6.4 | Export and audit | **Export Data**, **Audit Trail** | Export downloads; the audit trail lists the UAT actions | | | | |

## 7. Administrator: oversight

| # | Scenario | Steps | Expected result | Pass/Fail | Tester | Date | Notes |
|---|---|---|---|---|---|---|---|
| 7.1 | Accounts | **User Accounts** → disable and re-enable a test account | Disabled account cannot sign in; works again after re-enabling | | | | |
| 7.2 | Roles | **Roles & Permissions** | Each role's permissions are shown | | | | |
| 7.3 | Recommendation learning | **System Settings** → "Recommendation learning" | Shows the live count of coordinator judgments | | | | |
| 7.4 | Backups | **Backup & Recovery** | The latest nightly backup is listed as completed | | | | |
| 7.5 | Audit trail | **Audit Trail** | Account, placement and report actions from this UAT are listed | | | | |

## 8. Access, privacy and quality checks

| # | Scenario | Steps | Expected result | Pass/Fail | Tester | Date | Notes |
|---|---|---|---|---|---|---|---|
| 8.1 | Role separation | As a student, open a coordinator address (for example `<site>/coordinator`) | Access is refused | | | | |
| 8.2 | Program scope | As a coordinator of another program, look for the UAT student | The student is not visible | | | | |
| 8.3 | Sign-out | Sign out, then press the browser's Back button | No private page is shown | | | | |
| 8.4 | Password reset | **Forgot password** on the sign-in page | Reset e-mail arrives; new password works | | | | |
| 8.5 | Browsers | Repeat 1.1, 2.5 and 6.1 in Chrome, Edge and Firefox | Same result in each | | | | |
| 8.6 | Phone | Repeat 2.1 to 2.5 and 4.3 on a phone | Pages fit the screen; no sideways scrolling | | | | |
| 8.7 | Speed | Time 2.5 (recommendations) and 6.1 (dashboard) | Each loads within 3 seconds on campus internet | | | | |
| 8.8 | HTTPS | Open `http://<site>` | Browser is redirected to `https://` with a valid certificate | | | | |

## Results summary

| Part | Scenarios | Passed | Failed | Notes |
|---|---|---|---|---|
| 0 Administrator setup | 5 | | | |
| 1 Coordinator setup | 6 | | | |
| 2 Student | 6 | | | |
| 3 Coordinator placement | 5 | | | |
| 4 Supervisor | 7 | | | |
| 5 Completion | 3 | | | |
| 6 Dean | 4 | | | |
| 7 Administrator | 5 | | | |
| 8 Access and quality | 8 | | | |
| **Total** | **49** | | | |

## Sign-off

By signing, each person confirms they tested their part and the results above are accurate.

| Role | Name | Signature | Date |
|---|---|---|---|
| UAT lead | | | |
| Practicum coordinator | | | |
| Host supervisor | | | |
| Program chair / Dean | | | |
| System administrator | | | |
| Student representative | | | |
