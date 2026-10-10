# Current validation (2026-10-10)

TASK-031 through TASK-036 are verified in [TASK-031-036-VALIDATION.md](TASK-031-036-VALIDATION.md). The full PostgreSQL suite, static checks, production build and two consecutive headless browser passes are green. Setup and database isolation are documented in [TESTING.md](TESTING.md).

The older environment blockers below are historical. PostgreSQL and loopback browser testing now work with the required sandbox access; Docker runtime and hosted GitHub Actions remain outside this ticket scope.

---

# Validation status

Checked after dependencies were installed on 2026-10-05. Both Composer and npm lockfiles are now present. The resolved framework is Laravel 13.34.0, tested on PHP 8.5.11 and Node 24.21.0. Locked PHP dependencies require PHP 8.4.1 or newer; Docker and CI use the PHP 8.4 release line.

## Passed

| Check                                                                                 | Actual result                                                                                                                                     |
| ------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `composer validate --strict`                                                          | Composer manifest and lockfile valid                                                                                                              |
| `composer check-platform-reqs`                                                        | Installed dependencies satisfy the host runtime                                                                                                   |
| `php artisan about`                                                                   | Laravel boots; PostgreSQL, database sessions/cache/queue and local log mail configured                                                            |
| `php artisan route:list --except-vendor`                                              | Generic application routes load                                                                                                                   |
| `composer lint`                                                                       | Pint passes                                                                                                                                       |
| `npm run lint`                                                                        | ESLint passes                                                                                                                                     |
| `npm run format:check`                                                                | Prettier passes                                                                                                                                   |
| `npm run typecheck`                                                                   | Strict TypeScript checks pass                                                                                                                     |
| `npm run build`                                                                       | Vite production build succeeds                                                                                                                    |
| `php artisan test --testsuite=Unit`                                                   | Isolated User policy passes                                                                                                                       |
| `DB_CONNECTION=sqlite DB_DATABASE=:memory: composer test`                             | Supplemental complete PHP suite passes: 20 tests covering authentication, authorization, input validation, errors and CSRF                        |
| `php artisan migrate --seed`, then `php artisan db:seed` with an isolated SQLite file | Framework migrations and repeat seeding pass; exactly one ADMIN and one USER remain                                                               |
| In-process HTTP kernel smoke with compiled assets and the isolated SQLite file        | Login page, encrypted database sessions, cookie CSRF transport, login, authenticated Inertia dashboard, USER denial, ADMIN access and logout pass |
| `docker compose config --quiet`                                                       | Compose configuration resolves successfully                                                                                                       |

SQLite was used only as a supplemental test override. The environment example, PHPUnit default and GitHub Actions service still use PostgreSQL. Passing SQLite checks does not establish PostgreSQL integration or Docker runtime behavior.

## Remaining environment blockers

- `composer test` against the default PostgreSQL test database fails before feature assertions: PostgreSQL is not reachable from this sandbox at the configured local host/port.
- A fresh isolated PostgreSQL cluster initializes, but startup fails because creating its Unix socket is denied (`Operation not permitted`).
- `php artisan serve` cannot bind a local listening socket. Laravel boots and responds through its in-process HTTP kernel; a network/browser smoke check is still pending.
- The Docker client is available, but daemon access at the configured Podman socket is denied. The image build and `docker compose up --build -d` have not been verified successfully.
- GitHub Actions configuration exists; an actual hosted CI run has not been performed.

## Complete these checks in a normal local terminal

1. Configure PostgreSQL with dedicated development and test databases as described in README. Never run the test suite against valuable data.
2. Run `php artisan migrate --seed`, `php artisan db:show` and `composer test` using PostgreSQL. Fix any database-specific failures.
3. Start Laravel and Vite and verify auth pages, profile feedback, keyboard navigation, menus and narrow-screen layout in a browser. Inspect local reset emails privately.
4. Confirm friendly 403/404/419/500 behavior with `APP_DEBUG=false`, and JSON 422 validation.
5. Run `docker compose up --build -d`, migrate/seed, check database health and an HTTP response at the configured host port, and verify persistent volumes.
6. Reproduce README setup from a fresh checkout with the included lockfiles. Run all quality checks and CI before template publication.

Do not report the remaining checks as passed until they have been run in an environment that permits them.
