## 1. Dependencies and configuration

- [x] 1.1 Require `doctrine/dbal` ^4.0 and `ext-pdo` (`composer require`) and keep `composer.lock` committed
- [x] 1.2 Create `config/database.php` mapping `DB_*` variables to DBAL parameters (sqlite default `storage/database.sqlite`, mysql, pgsql, relative path resolution, `:memory:`, unsupported driver error)
- [x] 1.3 Update `.env.example` with the documented `DB_*` block (SQLite default active, MySQL/PostgreSQL commented) and set `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:` in `phpunit.xml`
- [x] 1.4 Pest tests for `config/database.php`: default, mysql, pgsql, relative and absolute SQLite paths, `:memory:`, unsupported driver

## 2. Container

- [x] 2.1 Add `config/dependencies/database.php` with the `Doctrine\DBAL\Connection` factory
- [x] 2.2 Pest tests: connection resolves from the container, is the same instance twice, and does not connect until used

## 3. Health check

- [x] 3.1 Update `HealthCheckHandler` with optional `?Connection`, `checks.database`, 503 on failure and no leaked details; drop its `InvokableFactory` entry so it is autowired
- [x] 3.2 Pest tests: healthy with in-memory SQLite, unhealthy with a throwing connection (503, `unhealthy`, body without the exception message), no connection keeps the old payload

## 4. Documentation

- [x] 4.1 Document the database configuration in `README.md` and `CLAUDE.md`
- [x] 4.2 Mark `database-foundation` as done in `ROADMAP.md` when archiving

## 5. Verification

- [x] 5.1 `composer check` passes
- [x] 5.2 Manual smoke test: `serve`, `GET /health` returns 200 with `checks.database: ok` using the default SQLite; with an invalid `DB_DATABASE` directory it returns 503
