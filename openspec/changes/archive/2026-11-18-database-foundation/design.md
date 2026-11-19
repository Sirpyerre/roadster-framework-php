## Context

Observed in the repository:

- No database dependency in `composer.json`; PHP here has `pdo_sqlite`, `pdo_mysql` and `pdo_pgsql`.
- `.env.example` has a commented `DB_*` block (mysql-flavored) that nothing reads; `env()` helper exists in `src/App/helpers.php`; `.env` is loaded by `bin/roadster` and `public/index.php`, not by tests.
- `config/container.php` already merges `config/dependencies/*.php` (`aliases` and `factories` only) and autowires concrete classes (spec `container-autowiring`).
- `HealthCheckHandler` has no constructor and is registered with `InvokableFactory`; unit tests call `new HealthCheckHandler()`.
- `.gitignore` already ignores `*.sqlite`; `storage/` holds the JSON repositories' data.
- Workers are long-lived (`public/index.php` loop), so a container service lives for the whole worker.

## Goals / Non-Goals

**Goals:**
- A working default (SQLite, zero config) and a one-variable switch to MySQL/PostgreSQL.
- A single, injectable `Connection` that later increments build on.
- `/health` that reports database reachability without leaking details.

**Non-Goals:**
- ORM, migrations, repository changes, reconnect logic, `/ready` (see proposal).

## Decisions

1. **Doctrine DBAL ^4 as the layer.** Matches the agreed vision (DBAL by default, ORM optional) and gives one API across SQLite/MySQL/PostgreSQL. Alternative: raw PDO; rejected because later increments (migrations, schema, ORM) need DBAL anyway.
2. **`config/database.php` returns plain parameters, not a connection.** It reads `env()` and returns `['driver' => ..., ...]` in DBAL parameter format (`pdo_sqlite` with `path`, `pdo_mysql`/`pdo_pgsql` with `host`, `port`, `dbname`, `user`, `password`). A pure array is easy to test per scenario. The mapping `sqlite|mysql|pgsql` to DBAL drivers and the unsupported-driver error live here.
3. **Relative SQLite paths resolve against `basePath()`**; absolute paths and `:memory:` pass through (`:memory:` maps to DBAL's `memory => true`). Default is `storage/database.sqlite`.
4. **Connection factory in `config/dependencies/database.php`.** A `factories` entry for `Doctrine\DBAL\Connection` that calls `DriverManager::getConnection(require config/database.php)`. DBAL connects lazily on first query, and the container caches the instance, so it is shared. Keeps `container.php` untouched and follows the autoregister convention.
5. **`HealthCheckHandler` takes an optional `?Connection $connection = null`.** The container autowires it (the `InvokableFactory` entry is removed). With `null` the handler returns the old payload, so existing unit tests stay valid. With a connection it runs `SELECT 1`; any `Throwable` yields 503, `status: unhealthy`, `checks.database: error`. The exception is not logged into the response; logging belongs to the `observability` increment.
6. **Test isolation.** `phpunit.xml` sets `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` so tests never touch `storage/`. Tests for MySQL/PostgreSQL parameter mapping only check the array, they do not connect.
7. **`ext-pdo` is required in `composer.json`; driver extensions are not**, since only the selected driver is needed. `init` already verifies extensions; extending that check is left out of scope.

## Risks / Trade-offs

- A persistent connection in a long-lived worker can go stale (MySQL "gone away"). Out of scope here; `worker-reset-hook` will reset/reconnect, and `/health` surfaces the symptom meanwhile.
- `/health` returning 503 on database failure makes orchestrator liveness probes restart pods when the database is down. Chosen deliberately (matches the roadmap); `readiness-checks` can split liveness and readiness later.
- The first `/health` call with the SQLite default creates `storage/database.sqlite`; the `storage/` directory must be writable (already true for the JSON stores).
- New dependency size and its transitive packages; accepted as the base of the data layer.
