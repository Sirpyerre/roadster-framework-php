## 1. Dependency and configuration

- [x] 1.1 `composer require doctrine/migrations:^3.9` and keep `composer.lock` updated; confirm it resolves with DBAL 4.6 and Symfony Console 7
- [x] 1.2 Create `config/migrations.php` using only keys `ConfigurationArray` recognises (paths, `migrations` table, `all_or_nothing`, `transactional`) and `database/migrations/.gitkeep`
- [x] 1.3 Create `src/Console/DatabaseMigrations.php`: builds the `DependencyFactory` from `config/migrations.php` with a lazy container `ConnectionLoader`, accepting an optional `Connection` and migrations path for tests

## 2. Commands

- [x] 2.1 Register `migrate` (Doctrine `MigrateCommand`) and `migrate:status` (Doctrine `StatusCommand`) with Roadster names and cleared `migrations:*` aliases in `Application`
- [x] 2.2 Add `MigrateRollbackCommand` (`migrate:rollback`): counts executed migrations first and reports "nothing to roll back" (exit 0) when there are none, otherwise reverts the last one through `migrate prev`
- [x] 2.3 Add `templates/migration.php.stub` and `MakeMigrationCommand` (`make:migration`) using `StubWriter`: validates the name, writes `Version<timestamp><Name>`, sets the description, refuses to overwrite; accepts an injectable base path

## 3. Tests

- [x] 3.1 Pest tests with a temp SQLite file and temp migrations directory: migrate from empty applies in order, second `migrate` is a no-op, failing migration exits non-zero and is not recorded, `migrate:rollback` reverts only the last, rollback of the only executed migration, rollback with nothing executed, `migrate:status` counts and current/next/latest versions
- [x] 3.2 Pest tests for `make:migration`: file and class created, description derived from the name, invalid name rejected, existing file kept
- [x] 3.3 Pest tests that the four commands are registered (and the `migrations:*` names are not), and that `routes:list` runs with an invalid `DB_CONNECTION`

## 4. Documentation

- [x] 4.1 Document the four commands and the `database/migrations/` workflow in `README.md` and `CLAUDE.md`, including the MySQL transactional-DDL caveat
- [x] 4.2 Mark `database-migrations` as done in `ROADMAP.md` when archiving

## 5. Verification

- [x] 5.1 `composer check` passes
- [x] 5.2 Manual smoke test with the default SQLite file: `make:migration CreateProducts`, edit it to create a table, `migrate:status`, `migrate`, `migrate:rollback`; confirm the table appears and disappears, then clean up generated files and `storage/database.sqlite`
