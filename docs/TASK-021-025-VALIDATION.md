# TASK-021 through TASK-025 validation

Validated 2026-10-09. Scope stops at TASK-025. Existing Laravel/Inertia architecture, shared primitives, policies and model workflows were reused. No dependencies or migrations were added.

## Acceptance criteria

| Ticket   | Status | Evidence                                                                                                                                                                                                                                                                                                               |
| -------- | ------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| TASK-021 | PASS   | Admin create/rename/deactivate and all role denials; canonical case uniqueness; retired options excluded from creation and retained on existing tickets; pagination and unknown-field validation.                                                                                                                      |
| TASK-022 | PASS   | First active priority becomes default; transactional serialized swaps; default cannot be retired or cleared without a replacement; exactly one active default across request sequences; bounds, role matrix, retired option display and fixed existing due dates.                                                      |
| TASK-023 | PASS   | Employee redirected to own scoped ticket queue; fixture asserts every metric, zero counts and assignment pagination; six bounded SELECT queries for the populated dashboard.                                                                                                                                           |
| TASK-024 | PASS   | Shared due-date and icon/text overdue components on queue/detail/dashboard; server-owned overdue flags for every status and strict due boundary; explicit server timestamp for relative display; centralized application-timezone formatting with absolute tooltips; contrast exceeds 4.5:1.                           |
| TASK-025 | PASS   | Local-only guard on both seeders; 1 admin, 2 agents, 5 employees, 6 categories, 4 priorities, one default, 40 tickets/eight per status, overdue and unassigned examples, 54 comments including 14 internal notes, five private files; repeat counts/history unchanged; two actual fresh migration/seed runs succeeded. |

## Checks

- `composer test`: **442 passed**, **3576 assertions** against dedicated PostgreSQL.
- `npm run test:unit`: **3 passed** (timezone/DST formatting, relative boundaries/invalid dates, badge contrast).
- `composer lint`, `npm run lint`, `npm run format:check`, `npm run typecheck`: **PASS**.
- `npm run build`: **PASS**.
- `php artisan migrate:fresh --seed --no-interaction` twice, followed by `php artisan db:seed --no-interaction`: **PASS**, only against the disposable `resolveit_foundation_test` database with `APP_ENV=local`.
- Firefox: real keyboard creation, rename, default swap and retirement on configuration forms; duplicate validation feedback; employee redirect and agent denial; retired options excluded; due tooltips and overdue text; no page overflow at 360, 768, 1024 and 1440 pixels: **PASS**.

## Data and security decisions

- No schema changes. Rare priority writes use a PostgreSQL table lock inside their transaction to serialize default selection, including the first-ever insert.
- Administration uses existing policies and auth middleware. Requests normalize and validate names, numeric bounds and booleans and reject unknown fields. Client capabilities only control visibility.
- Due-date badges use server-provided overdue status and a server response timestamp; the browser clock never determines overdue state. The application timezone is shared by Inertia.
- Seeded accounts retain existing credentials and roles. Existing configuration and ticket history are preserved. Private files use stable generated demo paths; existing authorized download routes serve them. Assignment and transition histories use the existing model methods and centralized activity recording; comments and files use model relationships and the same activity recording path.

## Remaining issues and verification limits

No known ticket implementation issues. Validation used host PHP 8.5.11, Node 24.21.0 and PostgreSQL 18.6. Docker runtime, PostgreSQL 17 and hosted CI were not exercised during this change. The pre-existing gitignore excludes `TASKS.md`; its local statuses were updated without changing ignore rules. Temporary browser/server/database processes used for validation were stopped after checks.

## Changed files

- `README.md`
- `TASKS.md` (local status updates; already ignored by Git)
- `app/Http/Controllers/CategoryController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/PriorityController.php`
- `app/Http/Controllers/TicketController.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Requests/ConfigurationIndexRequest.php`
- `app/Http/Requests/DashboardRequest.php`
- `app/Http/Requests/DeactivateCategoryRequest.php`
- `app/Http/Requests/NormalizesConfigurationName.php`
- `app/Http/Requests/PriorityActionRequest.php`
- `app/Http/Requests/StoreCategoryRequest.php`
- `app/Http/Requests/StorePriorityRequest.php`
- `app/Http/Requests/UpdateCategoryRequest.php`
- `app/Http/Requests/UpdatePriorityRequest.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/DemoSeeder.php`
- `docs/TASK-021-025-VALIDATION.md`
- `package.json`
- `resources/js/Components/ConfigurationPagination.tsx`
- `resources/js/Components/FormErrors.tsx`
- `resources/js/Components/Tickets/DueDate.tsx`
- `resources/js/Layouts/AppLayout.tsx`
- `resources/js/Lib/dateFormatting.ts`
- `resources/js/Pages/Admin/Categories.tsx`
- `resources/js/Pages/Admin/Priorities.tsx`
- `resources/js/Pages/Dashboard.tsx`
- `resources/js/Pages/Tickets/Index.tsx`
- `resources/js/Pages/Tickets/Show.tsx`
- `resources/js/Types/index.ts`
- `routes/web.php`
- `tests/Feature/CategoryManagementTest.php`
- `tests/Feature/DashboardMetricsTest.php`
- `tests/Feature/DemoSeederTest.php`
- `tests/Feature/DueDateTest.php`
- `tests/Feature/FoundationTest.php`
- `tests/Feature/PriorityManagementTest.php`
- `tests/Feature/RoleContextTest.php`
- `tests/Feature/RouteGuardTest.php`
- `tests/Feature/UserRoleManagementTest.php`
- `tests/Unit/dateFormatting.test.ts`
