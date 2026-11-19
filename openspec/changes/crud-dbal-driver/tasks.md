## 1. Driver abstraction

- [x] 1.1 Add `RepositoryDriver` enum (`Dbal`, `Json`) with repository stub, repository class name, migration flag and next-steps hint; Pest tests including `tryFrom` of an unknown value
- [x] 1.2 Add `DatabaseMigrations::className(string $name, ?DateTimeImmutable $now = null)` and use it from `MakeMigrationCommand`; keep `make:migration` tests green
- [x] 1.3 Change `templates/crud/dependencies.php.stub` to take `{{repositoryClass}}` and update its callers (JSON output stays identical)

## 2. DBAL stubs

- [x] 2.1 Add `templates/crud/DbalRepository.php.stub` (`findAll`, `findById`, transactional `save` via exists-check, `delete`, UTC timestamps)
- [x] 2.2 Add `templates/crud/migration.php.stub` that creates and drops the resource table with the Schema API

## 3. Commands

- [x] 3.1 `scaffold:crud`: `--driver` (default `dbal`), validation before writing, driver-specific repository and binding, table migration with the already-exists check, JSON-only `storage/` creation, updated next steps
- [x] 3.2 `make:repository`: same `--driver` handling, migration for `dbal`, existing-repository check on the selected driver's file
- [x] 3.3 Update `tests/Unit/Console/Commands/GeneratorsTest.php` for the new default and add JSON-driver, unsupported-driver, migration-created, migration-not-duplicated and `make:repository` driver cases

## 4. End-to-end test

- [x] 4.1 Pest test: scaffold a uniquely named resource into a temp directory, require the generated PHP files, run its migration against a temp SQLite file with `DatabaseMigrations`, and check save/find/update/save-twice/delete/list, UTC round trip under a non-UTC default timezone, and rollback dropping the table
- [x] 4.2 Verify every generated PHP file lints (`php -l`) for both drivers

## 5. Documentation

- [x] 5.1 Document `--driver`, the DBAL default and the `migrate` step in `README.md` and `CLAUDE.md`, including how to add columns by hand
- [x] 5.2 Mark `crud-dbal-driver` as done in `ROADMAP.md` when archiving

## 6. Verification

- [x] 6.1 `composer check` passes (cold PHPStan cache)
- [x] 6.2 Manual smoke test: `scaffold:crud Product`, `migrate`, serve, `POST`/`GET`/`PUT`/`DELETE /api/products` work against SQLite, `migrate:rollback` drops the table; then clean up generated files and `storage/database.sqlite`
