# Quality checks and browser tests

Requirements: PHP 8.4.1+, Node 24, PostgreSQL 17, installed Composer/npm dependencies.

## Database isolation

PHPUnit uses `laravel_fullstack_starter_test` by default (see `phpunit.xml`). CI uses `starter_test`. Tests refuse to boot unless the effective database is PostgreSQL, its name starts with `test_` or ends in `_test`/`_e2e`, `APP_ENV=testing`, and configuration is uncached. This check runs before `RefreshDatabase` can migrate or truncate tables. Use disposable databases only.

Playwright always overrides the effective connection to PostgreSQL and clears `DB_URL`. It defaults to **`resolveit_e2e`**. Override only with `E2E_DB_DATABASE=another_e2e`; all other names are rejected. Connection credentials come from the shell environment or `.env`. Create this disposable database once with a PostgreSQL account allowed to create databases, for example:

```sh
createdb --host=127.0.0.1 --username=starter resolveit_e2e
npx playwright install --with-deps chromium
npm run test:e2e
```

Every browser run resets that E2E database and seeds it with the existing local-only `DemoSeeder`. This destroys prior E2E data. Development and PHPUnit databases are unaffected. Sessions, logs, views and private uploads live in ignored `storage/e2e`; saved browser sessions live in ignored `playwright/.auth`. Demo passwords are fictional local credentials.

The test-only PHP preview binds `127.0.0.1:8011` and serves built assets, including when the developer's Vite server is running. Playwright starts/stops it and refuses to reuse an existing server. The preview keeps real session authentication and CSRF enabled. It adds no application routes or authorization bypasses. The npm command builds assets before running tests.

## Required sweep

```sh
composer lint
composer test
npm run lint
npm run format:check
npm run typecheck
npm run build
npm run test:e2e
npm run test:unit
```

For the required two consecutive browser passes without retries:

```sh
npm run test:e2e -- --repeat-each=2 --retries=0
```

CI runs the headless Chromium suite twice with no retries and uploads failure reports. Normal CI invocations have one diagnostic retry; flaky successes still fail the run. Traces are recorded on the first retry and screenshots on failures. View results with `npx playwright show-report`; reports and traces may contain fictional test data and session state, so keep them private.

## Coverage contract

`EndpointAuthorizationMatrixTest` documents and executes all application routes across guest/employee/agent/admin and ticket ownership. Its router assertion fails when an application route is added without a matrix row. Fortify routes have their existing authentication/session-security tests. `FormRequestCoverageTest` checks that every application Form Request has an HTTP validation-failure case.

Domain coverage combines the existing complete transition and policy matrices with SLA boundary/immutability assertions, strict overdue boundaries, append-only audit/lifecycle tests, configuration default/retirement guards, and date-formatting checks. `QueryGrowthTest` compares one row with twenty rows on index/detail/dashboard to catch per-row queries.

Browser coverage includes real logins for all roles; the complete create/assign/work/note/comment/resolve/reopen/resolve/close journey and ordered timeline; rejected and accepted uploads plus downloaded byte equality; category/default-priority/SLA configuration; seeded overdue metrics; and hidden administration navigation plus direct URL denials for agents.
