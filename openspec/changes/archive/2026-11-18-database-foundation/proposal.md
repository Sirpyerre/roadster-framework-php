## Why

Roadster has no database layer: repositories are JSON files, `.env.example` lists commented `DB_*` variables nothing reads, and `/health` cannot tell whether a database is reachable. The roadmap's data-layer choice (DBAL by default, optional ORM) and every later increment (migrations, DBAL CRUD driver, ORMs, readiness) need a configured connection first. This change adds only that foundation.

## What Changes

- Add `doctrine/dbal` (^4.0) and `ext-pdo` as requirements.
- New `config/database.php` that builds connection parameters from `.env`, with SQLite at `storage/database.sqlite` as the default so nothing needs to be configured to start.
- Supported drivers: `sqlite` (default), `mysql`, `pgsql`, selected with `DB_CONNECTION`; an unsupported value fails with a clear error.
- The container provides a shared, lazily-connected `Doctrine\DBAL\Connection`.
- `/health` includes `checks.database` and answers 503 with `status: unhealthy` when the database does not respond, without exposing connection details.
- `.env.example` documents the variables (SQLite default, MySQL and PostgreSQL examples); tests use an in-memory SQLite database.
- README and `CLAUDE.md` document the database configuration.

## Non-goals

- No ORM (Doctrine ORM and Cycle come in later increments) and no query builder abstractions of our own.
- No migrations (`database-migrations`) and no repository changes: generated and Todo repositories stay JSON-backed (`crud-dbal-driver`).
- No connection pool or reconnect/reset logic for long-lived workers (`worker-reset-hook`).
- No separate `/ready` endpoint; this stays in `readiness-checks`, which may later split liveness from readiness.
- No database creation or `db:*` CLI commands.

## Capabilities

### New Capabilities
- `database-connection`: how the database connection is configured, provided by the container and checked by `/health`.

### Modified Capabilities

## Impact

- `composer.json`/`composer.lock`, `config/database.php`, `config/container.php` or `config/dependencies/database.php`, `src/Web/Rest/HealthCheckHandler.php`, `.env.example`, `phpunit.xml`, tests, `README.md`, `CLAUDE.md`, `ROADMAP.md`.
- `/health` response gains a `checks` object and can now return 503. Existing consumers reading `status` and `timestamp` keep working while the database is reachable.
- First `/health` call with the default SQLite creates `storage/database.sqlite` (already ignored by git through `*.sqlite`).
