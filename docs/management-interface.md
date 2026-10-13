# Administrator interface

Only Administrators use Laravel Filament at http://localhost:8000/manage. The previous React administrator URLs on port 8080 redirect here, including administrator sign-in through the main login page. Practicum Coordinators, Students, Host Supervisors and Program Chairs/Deans stay on port 8080. Coordinators retain their existing placement approvals and program-scoped APIs; they cannot enter Filament.

The Laravel Filament shell serves administrator screens and their assets on port 8000. Migrated React screens use the same authenticated Laravel session and APIs. The server restricts the panel and embedded workspaces to active administrators. Existing accounts, passwords, records and approval policies remain unchanged. Administrators still cannot approve placements.

After changing administrator UI code, run `./build-management.ps1` with the existing frontend container running. This builds assets using its installed dependencies and copies them into `backend/public/admin-ui`. Alternatively, with frontend dependencies installed locally, run `npm run build:admin` from `frontend`. Build these assets before packaging the backend for deployment. Generated assets are ignored by Git. A missing build returns an explicit service-unavailable message.

Administrators retain student enrollment management and record-editing links alongside the migrated administrative screens. Port 8080 remains necessary for the other roles; do not stop that service to remove the old administrator interface.

## Verification (2026-10-12)

- Dedicated `internmatch_testing` database: 18 tests / 331 assertions passed (`ManagementWorkspaceTest`, `FilamentPanelTest`, `AccountAccessTest`). Includes admin-only panel access, inactive-account rejection, coordinator assignment checks and admin placement-approval rejection.
- Browser: original five role accounts sign in successfully; Coordinator, Student, Supervisor and Dean remain on 8080. Administrator lands on 8000. Direct Filament administrator login works; the original Coordinator account receives 403 at `/manage`.
- All eleven migrated administrator sections load without API alerts; old 8080 administrator links redirect to the corresponding Filament section. Dashboard refresh and other-role desktop/mobile checks passed.
- TypeScript and production build pass. Targeted ESLint has zero errors and three Fast Refresh warnings for context exports / the standalone entry.
- Private browser QA output is retained under `.local/management-results.json`, `.local/admin-canonical.png`, `.local/admin-canonical-mobile.png` and `.local/dashboard-refresh/`.
