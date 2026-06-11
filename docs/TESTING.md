# Testing strategy — Gnut06

This document describes how we prevent regressions and which tests to add next.

## CI pipeline

GitHub Actions workflow: [`.github/workflows/tests.yml`](../.github/workflows/tests.yml)

| Job | What it runs | When it fails |
|-----|----------------|---------------|
| **PHPUnit (Unit)** | Fast tests, no HTTP | Unit assertions fail |
| **PHPUnit (Functional)** | HTTP + MySQL `gnut06_test` (transaction rollback per test) | Functional assertions fail |
| **Coverage** | Full suite + Clover report | Thresholds in `app/scripts/check-coverage-threshold.php` |

Triggers: every push and pull request to `develop`, `main`, or `master`.

### Local commands (Docker)

```bash
docker exec symfony_asso php vendor/bin/phpunit
docker exec symfony_asso php vendor/bin/phpunit --testsuite=Unit
docker exec symfony_asso php vendor/bin/phpunit --testsuite=Functional
docker exec symfony_asso php -d xdebug.mode=coverage vendor/bin/phpunit --coverage-html var/coverage
```

Test env variables live in [`app/.env.test`](../app/.env.test).

### Test database (`gnut06_test`)

PHPUnit uses **`DATABASE_URL_TEST`** only (see `config/packages/doctrine.yaml` `when@test`). The dev database `gnut06` is never used for tests.

| Environment | `DATABASE_URL` (app) | `DATABASE_URL_TEST` (PHPUnit) |
|-------------|----------------------|-------------------------------|
| Docker | `mysql://…/gnut06` | `mysql://…/gnut06_test` (set in `docker-compose.yaml`) |
| CI | — | `gnut06_test` (GitHub Actions `env`) |
| Host machine | `.env` | `.env.test` (`127.0.0.1`) |

**First-time setup (existing MySQL volume):** the init script in `docker/mysql/init/` runs only on a fresh MySQL container. Create the test DB once:

```bash
docker exec mysql_gnut mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS gnut06_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Optional local override (e.g. different host/port): `app/.env.test.local` (gitignored).

## Test layers

1. **Unit** (`tests/Unit/`) — entities, pure services (Mailjet, Recaptcha stubs).
2. **Functional** (`tests/Functional/`) — controllers, security, forms, database effects.
3. **Smoke** (`PublicRouteSmokeTest`, `AdminAccessControlTest`) — app boots and routes respond.

Prefer functional tests for anything that changes HTTP status, redirects, CSRF, or persisted data.

## Shared helpers

[`tests/Functional/WebTestCase.php`](../app/tests/Functional/WebTestCase.php):

- `createUser()`, `createAdmin()`, `createTihUser()`
- `loginAs()`, `loginAsAdmin()`
- `getAdminTihCsrfToken()` — extract CSRF from rendered admin pages
- `getAdminUserPromoteCsrfToken()` / `getAdminUserDeleteCsrfToken()`
- `createSearchableTih()`, `createCompetence()` — seed data for search scenarios

Reuse this pattern for other admin modules.

### Scenario tests (model for the team)

Prefer **Given → When → Then** over bare `assertResponseIsSuccessful()`:

| File | Pattern |
|------|---------|
| `TihSearchTest` | Seed profiles with `createSearchableTih()`, filter, assert grid content and count |
| `RegistrationTest` | Submit form, assert DB state + redirect + session |
| `AuthenticationTest` | Form login, assert redirect to `/profil` and protected route access |
| `ContactFormTest` | Submit form, assert success or validation feedback (no external reCAPTCHA in test) |
| `AdminUserTest` | List/search users, promote/demote/delete with CSRF from rendered admin page |

## Coverage gates (CI)

Configured in [`app/scripts/check-coverage-threshold.php`](../app/scripts/check-coverage-threshold.php). Raise thresholds as tests are added.

| File | Minimum line coverage |
|------|------------------------|
| `AdminTihController.php` | 100% |
| `AdminUserController.php` | 40% (increase to 80% after `AdminUserTest`) |

## Next 5 test files (priority)

Based on `app/var/coverage` and business risk (admin + registration + donations).

### 1. `tests/Functional/AdminUserTest.php`

**Target:** `AdminUserController` (~43% lines)

Mirror `AdminTihTest`:

- List `/admin/user` for admin
- Promote user with valid CSRF → `ROLE_ADMIN`
- Demote admin with valid CSRF
- Delete user with `_method=DELETE` + valid CSRF
- Invalid CSRF leaves data unchanged (extend existing `AdminAccessControlTest` cases)

### 2. `tests/Functional/AdminCompetenceTest.php`

**Target:** `AdminCompetenceController` (0% — 52 lines)

Same shape as TIH admin:

- GET list + search `?q=`
- POST add competence (CSRF `add_competence`)
- POST delete (CSRF `delete_competence{id}`)

Required for TIH profiles that reference competences.

### 3. `tests/Functional/AdminDonsTest.php`

**Target:** `AdminDonsController` (~14% — 92 lines)

Mission-critical donation administration:

- GET `/admin/dons` as admin
- Update / cancel donation status with CSRF
- Access control (anonymous / user / admin)

### 4. `tests/Functional/RegistrationTihTest.php`

**Target:** `RegistrationController` (~25% — 59 lines)

Extend beyond page-load tests:

- Submit TIH registration with valid data (mock Recaptcha if needed)
- Assert user + `Tih` created and role `ROLE_TIH`
- Validation errors on invalid email/password

### 5. `tests/Functional/AdminDashboardTest.php`

**Target:** `AdminController` (~14% — 49 lines)

- Admin dashboard `/admin` renders
- Stats/widgets do not error with empty DB
- Non-admin denied

## Later backlog (0% admin controllers)

| Controller | Lines | Notes |
|------------|-------|--------|
| `AdminBenevoleController` | 85 | Volunteers admin |
| `AdminDonateurController` | 32 | Donors list/delete |
| `AdminEntrepriseController` | 30 | Companies |
| `AdminPayersController` | 34 | Payers |
| `AdminAssoRecommanderController` | 32 | Partner assos |

## PR checklist

Use the [pull request template](../.github/pull_request_template.md) on every PR.

## Optional next steps

- Add `dama/doctrine-test-bundle` to speed up functional tests (transaction rollback)
- Add 2–3 Playwright flows for login + admin TIH validate (client-side modals)
- Raise `AdminUserController` threshold to 80% once `AdminUserTest` is merged
