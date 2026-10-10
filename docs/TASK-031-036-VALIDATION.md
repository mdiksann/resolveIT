# TASK-031 through TASK-036 validation

Validated 2026-10-10. Scope stops at TASK-036. No application screens, routes, policies, models or database schema changed. The only added dependency is the explicitly approved `@playwright/test` development dependency. Existing domain tests, factories, HTTP request patterns and local-only `DemoSeeder` are reused.

## Acceptance criteria

| Ticket   | Status | Evidence                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| -------- | ------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| TASK-031 | PASS   | Complete 25-pair transition map and 150 role/ownership transition decisions; existing ticket/configuration/user policy matrices, expanded self-role and summary fallbacks, SLA values 1/4/24/72/720 with exact seconds and immutable deadlines, all-status strict overdue boundaries, timezone/DST/relative-date checks. Deliberately removing Assigned → In Progress and adding one SLA hour each failed the corresponding tests in a disposable checkout. |
| TASK-032 | PASS   | All 32 application routes represented by 172 guest/employee/agent/admin and ownership cases. Router comparison rejects uncovered routes; every application Form Request has an HTTP invalid-input case and coverage assertion. Existing tests prove note/body/activity/file secrecy, MIME and size rejection, upload/download/removal authorization, scoped binding, and foreign-ticket denials. Database guards run before migrations.                     |
| TASK-033 | PASS   | Playwright Chromium setup with built-asset webServer, isolated private storage and database sessions, login-based employee/agent/admin storage states, dedicated PostgreSQL `resolveit_e2e`, existing DemoSeeder, CI retry/report configuration, first-retry traces and failure screenshots. Unsafe database names/reset invocation are rejected before database setup.                                                                                     |
| TASK-034 | PASS   | Employee create and due date → agent queue/self-assignment/work/private note/public comment/resolve → employee reopen → agent resolve → employee close. Visible status assertions at each step and exact final timeline order; employee cannot see the private note or its activity. Two consecutive headless passes with retries disabled.                                                                                                                 |
| TASK-035 | PASS   | Inline rejected HTML error; accepted private upload and downloaded byte equality; agent sees attachment but cannot remove another uploader's file. Admin creates category and 12-hour default priority; employee sees both options, server default applies and exact SLA is asserted. Seeded overdue count is 12; seeded ticket is visibly overdue. Agent administration links absent and all direct admin URLs return 403. Both specs pass twice.          |
| TASK-036 | PASS   | Required check sequence passes on fresh source snapshot with independently installed lockfile dependencies. Query-log growth audit on index/show/dashboard; no shipped TODO/FIXME/debug dumps; safe shared props, internal-note filtering and operational log contexts reviewed.                                                                                                                                                                            |

## Business-rule coverage

| Rule                                                    | Assertion source                                                                                |
| ------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| BR-03 fixed calendar-hour SLA                           | SlaContractTest; TicketCreationTest; TicketLifecycleTest                                        |
| BR-04 strict overdue comparison and terminal exclusions | DueDateTest; TicketQueueTest; DashboardMetricsTest                                              |
| BR-05 complete transition map                           | TicketTransitionMatrixTest; TicketPolicyTest; TicketTransitionTest                              |
| BR-06 role/ownership transition limits                  | TicketPolicyTest; EndpointAuthorizationMatrixTest; TicketTransitionTest                         |
| BR-07 unassignment only from Open/Assigned              | TicketAssignmentTest; TicketLifecycleTest                                                       |
| BR-08 edit limits                                       | TicketPolicyTest; TicketEditingTest                                                             |
| BR-09 closed comment/attachment restrictions            | TicketPolicyTest; TicketCommentsTest; TicketAttachmentsTest; browser lifecycle                  |
| BR-10 internal-note secrecy                             | TicketCommentsTest; TicketAttachmentsTest; TicketLifecycleTest; browser lifecycle               |
| BR-11 append-only complete audit writes                 | TicketCollaborationSchemaTest; TicketLifecycleTest; TicketActivitySummaryTest; browser timeline |
| BR-12 retired configuration and one default             | CategoryManagementTest; PriorityManagementTest; browser configuration                           |

SLA/overdue computation remains in the existing HTTP implementation, so its contract is tested through feature requests rather than extracting new helpers solely for unit tests. Pure transition, policy and summary logic stays in PHPUnit unit tests; the existing TypeScript date helper uses Node's native test runner.

## Verification results

- `composer lint`: PASS.
- `composer test`: **663 passed / 4,271 assertions**, dedicated PostgreSQL test database.
- `npm run lint`, `npm run format:check`, `npm run typecheck`: PASS.
- `npm run build`: PASS.
- `npm run test:e2e -- --repeat-each=2 --retries=0`: **9 passed** (three login setup tests plus two repetitions of each critical-flow spec), no skipped flows or retries.
- `npm run test:unit`: **3 passed**.
- Fresh source snapshot created from Git HEAD with the intended patch/new files applied, no copied build/cache/session state, followed by `composer install --no-interaction --prefer-dist` and `npm ci`. The checks above run again in the mandatory order on that snapshot.
- Mutation sanity: transition removal and SLA +1 hour are both detected; modified application files restored before the final sweep.
- Isolation probes: unsafe PHPUnit target, unsafe Playwright target and standalone E2E reset each fail before database setup.
- Query log: index **≤10**, populated detail **≤17**, dashboard **≤6** SELECT queries with one versus twenty distinct records; counts do not grow per row.
- Source audit: no TODO/FIXME/`dd()`/`dump()` in `app`, `resources`, `routes` or `database`. Shared users expose only id/name/role; attachment props omit disk/path; employee responses filter internal comments, activities and related files. Operational success logs use IDs/role transitions rather than bodies or credentials.

## Limits and known issues

- The Vite build emits an existing font URL warning; the tracked Manrope font is successfully served at runtime and browser tests pass. No font or unrelated visual changes were made.
- GitHub Actions is configured for the same headless sweep and failure artifacts; a hosted run was not triggered from this session. Local fresh-source verification is the evidence reported here.
- Database reset guards enforce explicit test naming and PostgreSQL connection checks. These names must still be reserved for disposable data; PostgreSQL credentials should restrict access to test databases where practical.

See [TESTING.md](TESTING.md) for setup, isolation and commands. Known issues are also appended to TASKS.md.

## Changed files

- Test infrastructure: `playwright.config.ts`, `package.json`, `package-lock.json`, `tsconfig.json`, `eslint.config.js`, `.gitignore`, `.prettierignore`, `.github/workflows/ci.yml`, `tests/TestCase.php`.
- Unit coverage: `tests/Unit/UserPolicyTest.php`, `tests/Unit/TicketActivitySummaryTest.php`, `tests/Unit/dateFormatting.test.ts`.
- Feature coverage: `tests/Feature/EndpointAuthorizationMatrixTest.php`, `tests/Feature/FormRequestCoverageTest.php`, `tests/Feature/SlaContractTest.php`, `tests/Feature/QueryGrowthTest.php`.
- Browser coverage/support: `tests/E2E/auth.setup.ts`, `tests/E2E/helpers.ts`, `tests/E2E/ticket-lifecycle.spec.ts`, `tests/E2E/attachments.spec.ts`, `tests/E2E/admin-configuration.spec.ts`, `tests/E2E/prepare.php`, `tests/E2E/router.php`.
- Documentation/status: `docs/TESTING.md`, `docs/VALIDATION.md`, `docs/TASK-031-036-VALIDATION.md`, `TASKS.md` (already Git-ignored by the repository).
