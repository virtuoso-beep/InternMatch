# InternMatch backend

## InternMatch backend structure

This Laravel application serves the REST/JSON API for the separate TanStack Start + React application in `../frontend`. Keep files grouped by their Laravel responsibility; add new directories only when implemented code needs them.

```text
app/
  Enums/                    Fixed roles, permissions, and domain statuses
  Http/
    Controllers/            Account access and the shared controller base
      Auth/                 Session login, identity, and logout
    Middleware/             Disabled-account request checks
    Requests/               Account-access validation and authorization
      Auth/                 Login validation and authentication
    Resources/              Explicit authenticated-user JSON representation
  Models/                   Domain records, casts, and relationships
  Policies/                 Resource authorization and assignment scope
  Providers/                Application bootstrapping and permission Gates
bootstrap/                  Route, middleware, exception, and provider registration
config/                     Framework, database, CORS, and Sanctum configuration
database/
  factories/                Model factories and reusable test states
  migrations/               Ordered schema history
  seeders/                  Default seeder and InternMatch reference data
routes/
  api.php                   Session and account-access API endpoints
  web.php                   Existing welcome page
  console.php               Existing console command
resources/                  Welcome-page Blade view and its CSS/JS assets
public/                     HTTP entry point and public assets
storage/                    Private/public files and generated runtime data
tests/
  Feature/                  HTTP, authorization, and database integration tests
  Unit/                     Tests independent of the Laravel application
  TestCase.php              Shared Laravel test base
```

### Domain index

All models remain directly in `app/Models`, with matching factories in `database/factories`. This preserves the existing namespaces, relationship references, factory resolution, and conventional policy discovery in `app/Policies`.

| Domain | Models |
| --- | --- |
| Account / access | `User`, `UserProfile` |
| Academic | `Program`, `AcademicTerm`, `ProgramTerm`, `Student`, `StudentEnrollment` |
| Hosts / opportunities | `HostEstablishment`, `Moa`, `Opportunity`, `OpportunityInterest` |
| Competencies / documents | `Competency`, `StudentCompetency`, `CompetencyEvidence`, `Document` |
| Requirements | `RequirementType`, `ProgramTermRequirement`, `RequirementSubmission`, `RequirementReview` |
| Placement / monitoring | `Placement`, `PlacementDecision`, `TimeLog`, `JournalEntry`, `MonitoringFlag` |
| Evaluation | `EvaluationRubric`, `EvaluationCriterion`, `Evaluation`, `EvaluationScore` |

### Responsibility and discovery boundaries

Controllers coordinate HTTP operations, Form Requests validate and authorize incoming data, Resources define JSON output, and middleware checks cross-cutting request conditions. Policies retain resource-level access rules; `Role` and `Permission` in `app/Enums` define the fixed permission matrix. `AppServiceProvider` registers permission Gates and session-only Sanctum behavior.

`bootstrap/app.php` registers the three route files, the `/up` health endpoint, the stateful API middleware, the `account.enabled` alias, and API exception rendering. `bootstrap/providers.php` registers `AppServiceProvider`. Keep the existing protected route group in `routes/api.php`; the current API does not need additional route files.

`AuthenticationTest` and `AccountAccessTest` cover the HTTP boundary. `InternMatchPermissionsTest` and `InternMatchSchemaTest` remain in `tests/Feature` because they boot Laravel and use database records, policies, relationships, factories, and seeders. `tests/Feature/ExampleTest.php` checks the existing welcome route; `tests/Unit/ExampleTest.php` is the isolated starter test.

There are currently no application-owned Actions, Services, Jobs, Events, Listeners, Notifications, Rules, Exceptions, or Console command classes requiring additional folders. The console closure stays in `routes/console.php`; exception configuration stays in `bootstrap/app.php`. AI/ML has not been implemented.

The welcome view, `resources/css/app.css`, `resources/js/app.js`, `vite.config.js`, and backend `package.json` support the existing web page. They remain separate from the application frontend. The starter unit test, `inspire` command, and default sample-user seeder require a separate cleanup decision; retaining them preserves existing tests, commands, and seeding behavior. `InternMatchReferenceSeeder` remains independently invokable and is not automatically added to `DatabaseSeeder`.

Dependencies and generated runtime files belong in ignored locations such as `vendor/`, `bootstrap/cache/`, `storage/framework/`, and `storage/logs/`. Keep the existing `.gitignore` placeholders that preserve required writable directories.

## InternMatch authentication

The backend exposes first-party SPA session authentication for the separate TanStack Start frontend. The installed dependency lock requires PHP 8.4.1 or newer. Run tests with that runtime using `php vendor/phpunit/phpunit/phpunit`; tests use SQLite in memory, not the application MySQL database.

Local defaults allow `http://localhost:8080` and `http://127.0.0.1:8080`. Use the same hostname for frontend and backend (for example, `localhost:8080` and `localhost:8000`). For deployment, configure `FRONTEND_URL` (comma-separated full origins), `SANCTUM_STATEFUL_DOMAINS` (hosts including nonstandard ports), and session domain/secure-cookie settings for the actual HTTPS domains. Cookie SPA authentication requires a shared parent domain. The existing `.env` and MySQL settings are preserved.

The browser must send credentials, `Accept: application/json`, and its Origin/Referer. First request `GET /sanctum/csrf-cookie`, then send the URL-decoded `XSRF-TOKEN` cookie as `X-XSRF-TOKEN` on mutations. Cookies and CSRF tokens rotate on login/logout; use the current cookie. There is no bearer-token login or token table requirement.

| Endpoint | Behavior |
| --- | --- |
| `POST /api/login` | Email/password login; five failed attempts per email/IP trigger a 60-second lockout. |
| `POST /api/logout` | Authenticated logout; invalidates session and regenerates CSRF token. |
| `GET /api/user` | Authenticated identity, role, status, and effective permissions under `data`. |
| `PATCH /api/users/{user}/access` | Active administrators may update another user's role and/or status using existing enum values. Self-modification is forbidden; other fields are ignored. |

Pending accounts may log in, inspect their identity, and log out, but receive no effective permissions. Active accounts retain the existing role and resource-policy rules. Disabled accounts cannot log in; an existing session is rejected and invalidated on its next protected request. Role assignment and activation are separate explicit changes; assigning a role does not activate an account. An initial active administrator must be provisioned through a trusted administrative process; there is no public elevation or registration endpoint.

Protected application operations must apply authentication, `account.enabled`, the appropriate permission Gate, and the relevant resource policy. There is no administrator bypass: only an assigned Practicum Coordinator can decide placements. Existing policy discovery, ownership, host membership, and program-term assignment checks remain authoritative.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
