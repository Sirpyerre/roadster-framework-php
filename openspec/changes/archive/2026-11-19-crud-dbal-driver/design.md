## Context

Observed in the repository:

- `ScaffoldCrudCommand` and `MakeRepositoryCommand` each list stubs and targets, use `StubWriter`, and take an optional base path. The repository stub is `JsonRepository.php.stub`; `dependencies.php.stub` hardcodes `Json{{resource}}Repository`.
- The generated entity builds itself from an array (`id`, `name`, `createdAt`, `updatedAt` as ATOM strings) and exposes `toArray()`/`jsonSerialize()`; the service creates entities from request data and calls `update()` then `save()`.
- `make:migration` builds the class name and timestamp inline and writes `templates/migration.php.stub` through `StubWriter`; migrations live in `database/migrations/` under namespace `Database\Migrations` and are loaded by Doctrine's finder (any `.php` file), ordered by class name.
- The container autowires concrete classes and loads `config/dependencies/*.php`; a `Doctrine\DBAL\Connection` is provided and lazy.
- The route name is `strtolower(resource) . 's'`; that is the existing naming convention for the plural.
- Tests already generate into temp directories; the migration tests build per-command `Application`s with a temp SQLite file.

## Goals / Non-Goals

**Goals:**
- One command produces a working, database-backed CRUD with the schema created by a migration.
- Add drivers later (Doctrine ORM, Cycle) by adding one case and its stubs, not by rewriting the commands.
- Keep the JSON driver and its output byte-for-byte as it is.

**Non-Goals:** ORM drivers, Todo migration, custom columns, pluralization, pagination (see proposal).

## Decisions

1. **A `RepositoryDriver` enum** (`src/Console/RepositoryDriver.php`, cases `Dbal`, `Json`) describes each driver: the repository stub, the repository class name for a resource, whether it needs a migration, and the "next steps" hint. `--driver` is parsed with `tryFrom`; an unknown value fails before any write with a message listing the available drivers and noting that `doctrine`/`cycle` are planned. Adding a driver later is a new case. Alternative: `if/else` in each command; rejected because both commands and the ORM increments need the same switch.
2. **`DbalRepository.php.stub`** generates `Dbal<Resource>Repository` with a `Connection` constructor argument (autowired). Methods: `findAll()` (ordered by `created_at`), `findById()`, `save()` and `delete()`.
   - `save()` runs in `$connection->transactional()`: it checks whether the id exists, then `update` or `insert`. It does not rely on `UPDATE` row counts, because MySQL reports changed rows and would wrongly insert when nothing changed.
   - Rows are turned into entities through the entity constructor (`id`, `name`, `createdAt`, `updatedAt` as ATOM strings) and entities are written through `toArray()`, so the entity stub does not change.
   - Timestamps are stored as UTC `datetime_immutable` columns: the repository converts to UTC on write and reads them as UTC, so the value does not depend on the server timezone. Sub-second precision is dropped, which matches the ATOM format the API already returns.
3. **`migration.php.stub` for the table** uses DBAL's Schema API (portable across SQLite, MySQL and PostgreSQL): columns `id` (string, 32, primary key), `name` (string 255), `created_at` (datetime_immutable), `updated_at` (datetime_immutable, nullable); `down()` drops the table. Table name is `{{routeName}}` (lowercase resource + `s`); the class is `Version<UTC timestamp>Create<Resources>Table` with a derived description.
4. **Shared migration naming.** The class-name/timestamp logic moves from `MakeMigrationCommand` into `DatabaseMigrations::className(string $name, ?DateTimeImmutable $now = null)` and is used by both `make:migration` and the generators, so the format cannot drift.
5. **Idempotent migration.** Before writing, the generator globs `database/migrations/Version*Create<Resources>Table.php`; if one exists, it is skipped with the same "already exists" warning used for other files, so a re-run never creates a second `CREATE TABLE` migration.
6. **Binding stub takes the repository class** (`{{repositoryClass}}`) and its `use` line, so the same `dependencies.php.stub` serves every driver.
7. **Defaults and `make:repository`.** `dbal` is the default for both commands (as in the roadmap). `make:repository --driver=dbal` also generates the table migration, since a DBAL repository without a table does not work. The "repository already exists" check uses the selected driver's file.
8. **JSON storage directory** (`storage/`) is only created for the JSON driver.
9. **Testing strategy.** Command tests with a temp base path cover both drivers and the error path. An end-to-end test scaffolds a uniquely named resource, requires the generated PHP files, runs the generated migration against a temp SQLite file with `DatabaseMigrations`, and exercises the generated repository (insert, find, update, delete, UTC round trip, rollback). A final manual smoke test serves the API.

## Risks / Trade-offs

- Default behavior changes for the generators (new in this branch, not yet released): documented in README and the proposal.
- The generated table only has the entity's fields; users who add columns must edit the entity, the repository and the migration by hand (documented). A column-aware generator is future work.
- `save()` has a small check-then-write window; ids are random 128-bit values and the write runs in a transaction, so a collision is not a practical concern.
- Pluralization is naive (`categorys`); inherited from the route naming and left for a separate fix.
- Loading the generated classes in the end-to-end test requires unique resource names per run (PHP cannot redeclare classes).
