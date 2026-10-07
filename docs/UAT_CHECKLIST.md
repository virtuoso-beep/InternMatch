# UAT Checklist

**Project:** InternMatch
**Phase:** 17 and 19

---

## 1. Student Role
| Scenario | Steps to Reproduce | Expected Result | Pass/Fail | Tester | Date |
| --- | --- | --- | --- | --- | --- |
| Login | 1. Navigate to `/login`<br>2. Enter student credentials<br>3. Click Login | Redirected to student dashboard. Sees recommended internships. | | | |
| View Recommendations | 1. Login as Student<br>2. Navigate to Recommendations | List of AI-recommended internships loads within 3s. | | | |
| Apply for Internship | 1. Click on an internship<br>2. Click 'Apply'<br>3. Submit application | Application status changes to 'Applied'. Success message shown. | | | |

## 2. Host / Supervisor Role
| Scenario | Steps to Reproduce | Expected Result | Pass/Fail | Tester | Date |
| --- | --- | --- | --- | --- | --- |
| Login | 1. Enter host credentials | Redirected to host dashboard. | | | |
| Post Internship | 1. Navigate to 'Post Job'<br>2. Fill details<br>3. Submit | Job is listed under 'My Postings'. | | | |
| Review Application | 1. Go to Applications<br>2. Accept a student | Student status updates to 'Accepted'. | | | |

## 3. Coordinator Role
| Scenario | Steps to Reproduce | Expected Result | Pass/Fail | Tester | Date |
| --- | --- | --- | --- | --- | --- |
| Login | 1. Enter coordinator credentials | Redirected to coordinator dashboard. | | | |
| Approve Internship | 1. Go to Pending Internships<br>2. Click 'Approve' | Internship status changes to 'Active'. | | | |
| Match Override | 1. Manually assign student to internship | System successfully assigns and notifies parties. | | | |

## 4. Dean Role
| Scenario | Steps to Reproduce | Expected Result | Pass/Fail | Tester | Date |
| --- | --- | --- | --- | --- | --- |
| Login | 1. Enter dean credentials | Redirected to dean dashboard with analytics. | | | |
| View Analytics | 1. Navigate to 'Reports' | Placement statistics and charts are visible. | | | |

## 5. Admin Role
| Scenario | Steps to Reproduce | Expected Result | Pass/Fail | Tester | Date |
| --- | --- | --- | --- | --- | --- |
| System Health | 1. Login as Admin<br>2. Go to 'System Status' | Shows AI, DB, and Backend services as Online. | | | |
| User Management | 1. Go to 'Users'<br>2. Impersonate a student | Session transitions to student view safely. | | | |

---

## Sign-off

**UAT Lead:** _________________________  **Date:** ____________
**Project Manager:** __________________  **Date:** ____________
