## Why

`database-foundation` gave Roadster a configured DBAL connection but no way to create or evolve a schema: users would have to hand-run SQL. The roadmap's next increments (`crud-dbal-driver`, ORMs) need versioned, repeatable schema changes, and an Artisan-style `make:migration` / `migrate` flow is part of the framework promise. The decision is to build on `doctrine/migrations` rather than write a migration engine.

## What Changes

- Add `doctrine/migrations` (^3.9) as a dependency.
- New CLI commands with Roadster names, backed by Doctrine's engine:
  - `make:migration <Name>`: generates a migration class in `database/migrations/`.
  - `migrate`: runs pending migrations in order.
  - `migrate:rollback`: reverts the last executed migration.
  - `migrate:status`: shows executed and pending migrations.
- New `config/migrations.php` (migrations path and namespace, table `migrations`, all-or-nothing transactions) and a `database/migrations/` directory tracked in git.
- The commands obtain the database connection lazily from the container, so CLI commands unrelated to the database (such as `serve`) keep working even if `DB_*` is invalid.
- New stub `templates/migration.php.stub` for the generator.
- README and `CLAUDE.md` document the migration workflow.

## Non-goals

- No schema diffing or `migrations:diff` (needs the ORM, comes with the ORM increments), and none of Doctrine's other `migrations:*` commands are exposed (`execute`, `rollup`, `dump-schema`, ...).
- No change to `scaffold:crud` or repositories: generating a migration with the CRUD scaffold belongs to `crud-dbal-driver`.
- No seeders and no `migrate:fresh`/`reset` convenience commands.
- No rollback of several migrations at once (`--step`) in this change.
- No automatic running of migrations on server start.

## Capabilities

### New Capabilities
- `database-migrations`: creating, applying, reverting and inspecting versioned schema migrations from the CLI.

### Modified Capabilities

## Impact

- `composer.json`/`composer.lock` (+ `doctrine/migrations` and its small transitive packages, e.g. `symfony/var-exporter`, `symfony/stopwatch`).
- New: `config/migrations.php`, `config/dependencies/migrations.php`, `database/migrations/.gitkeep`, `src/Console/Commands/MakeMigrationCommand.php`, `MigrateRollbackCommand.php`, a migrations factory, `templates/migration.php.stub`, tests.
- Changed: `src/Console/Application.php`, `README.md`, `CLAUDE.md`, `ROADMAP.md`.
- A `migrations` table is created in the configured database the first time `migrate` or `migrate:status` runs.
