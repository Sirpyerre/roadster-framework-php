## Context

Observed in the repository and in `doctrine/migrations` 3.9 (inspected in a throwaway install, not in this project):

- Roadster has a DBAL `Connection` in the container (spec `database-connection`) and `config/database.php`. Commands are plain Symfony Console classes registered in `Application::registerCommands()` with no container access; tests inject base paths through constructors.
- `doctrine/migrations` ^3.9 requires PHP ^8.1, `doctrine/dbal ^3.6 || ^4` and `symfony/console ^5.4..^8` (compatible with the current ^7.0 and DBAL 4.6), plus `symfony/stopwatch`, `symfony/var-exporter`, `doctrine/event-manager`, `psr/log`.
- Doctrine's console commands take `(DependencyFactory $factory, ?string $name)` and some set their own alias (`MigrateCommand` has name `migrations:migrate` and alias `migrate`; `StatusCommand` alias `status`).
- Version aliases `first`, `prev`, `next`, `latest` resolve through the factory, so `migrate prev` reverts the last executed migration.
- `DependencyFactory::fromConnection(ConfigurationLoader, ConnectionLoader)` accepts a custom `ConnectionLoader`; `ConfigurationArray` takes a plain array. The default finder loads any `.php` file in the configured directory and orders migrations alphabetically by class name. `GenerateCommand` names classes `Version<timestamp>` and cannot take a descriptive name.

Confirmed against the official documentation and 3.9.x source (doctrine-project.org, github.com/doctrine/migrations):

- `ConfigurationArray` accepts these top-level keys only: `migrations_paths`, `migrations`, `connection`, `em`, `table_storage` (`table_name`, `version_column_name`, `version_column_length`, `executed_at_column_name`, `execution_time_column_name`), `organize_migrations`, `custom_template`, `all_or_nothing`, `transactional`, `check_database_platform`. Unknown keys throw `InvalidConfigurationKey`. Defaults: `all_or_nothing` false, `transactional` true, table `doctrine_migration_versions`.
- `all_or_nothing` wraps all migrations of one run in a single transaction and "only works if your database supports transactions for DDL statements"; `transactional` wraps each migration.
- When `up()`/`down()` throws, `DbalExecutor` rolls back (for transactional migrations), never writes the version record (it is written after execution, inside the same transaction) and rethrows, so the command fails.
- Alias `prev` resolves to the executed migration before the last one, and to version `0` when there is none: with exactly one executed migration `migrate prev` reverts it; with none executed it resolves to `0` too, so "nothing to roll back" must be detected by us.
- `migrate` asks `(y/n)` confirmation unless `--no-interaction`; `status` prints executed/available/new counts and the previous/current/next/latest versions. The narrative docs mention `status --show-versions`, but that option does not exist in 3.9.8 (listing versions with descriptions is `migrations:list`), which is outside this change.
- `ConnectionLoader` is a public interface with `getConnection(string|null $name = null): Connection`. The narrative docs only show `ExistingConnection` and `ConnectionRegistryConnection`, so a custom loader is a less-documented extension point; the interface is public and small, and a test pins the lazy behavior.

## Goals / Non-Goals

**Goals:**
- Artisan-style names (`make:migration`, `migrate`, `migrate:rollback`, `migrate:status`) on top of Doctrine's tested engine.
- No cost or failure for commands that do not touch the database.
- Testable without touching `storage/` or the real `database/migrations/`.

**Non-Goals:** diffing, seeders, `--step`, auto-migrate on boot, scaffold integration (see proposal).

## Decisions

1. **Use `doctrine/migrations` as the engine** (the stated decision). Alternative: a bespoke runner; rejected, since transactions, version tracking, ordering and DDL handling are already solved and the ORM increments will reuse the same tool.
2. **Expose only four Roadster-named commands.** `migrate` and `migrate:status` are Doctrine's `MigrateCommand` and `StatusCommand` constructed with our names (`new MigrateCommand($factory, 'migrate')`) and with their `migrations:*` aliases cleared, so the CLI shows one consistent vocabulary. `migrate:rollback` is a small Roadster command that first checks the number of executed migrations (printing "nothing to roll back" and exiting 0 when there are none) and otherwise runs `migrate` with version `prev`, keeping Doctrine's confirmation prompt and `--no-interaction` behavior. `make:migration` is ours (decision 5). Alternative: register all of Doctrine's `migrations:*` commands via `ConsoleRunner`; rejected as 15 extra commands that do not match the Roadster naming.
3. **`config/migrations.php`** returns the `ConfigurationArray` data using only the recognised keys: `migrations_paths` `Database\Migrations` => `database/migrations`, `table_storage.table_name` = `migrations`, `all_or_nothing` true (explicit, since the default is false) and `transactional` true. The array is loaded eagerly (a cheap PHP array, no I/O on the database).
4. **Lazy connection through a custom `ConnectionLoader`.** `Application` builds the `DependencyFactory` with `ConfigurationArray` plus a `ConnectionLoader` that calls `ContainerFactory::create()->get(Connection::class)` only when a command asks for the connection. Registering the commands therefore never reads `DB_*`; `serve` or `routes:list` with a broken `DB_CONNECTION` still work. A `DatabaseMigrations` factory class (`src/Console/DatabaseMigrations.php`) builds the factory and accepts an optional `Connection` and migrations path so tests can use a temp SQLite file and a temp directory.
5. **Own generator for descriptive names.** `make:migration CreateProducts` writes `Version<UTC timestamp>CreateProducts` (class and file) from `templates/migration.php.stub`, with `getDescription()` set from the CamelCase name ("Create products"), through the existing `StubWriter`. The name must match `^[A-Za-z][A-Za-z0-9]*$`. Timestamp-first naming keeps Doctrine's alphabetical ordering chronological. Alternative: delegate to Doctrine's `GenerateCommand`; rejected because it cannot carry the name.
6. **`database/migrations/` is tracked** with a `.gitkeep`; migration classes are loaded by Doctrine's finder, not by Composer autoload, so `composer.json` needs no change.
7. **Tests use a temporary SQLite file and a temp migrations directory**, fixtures written per test (a migration that creates a table, one that throws), and call the commands through `CommandTester`.

## Risks / Trade-offs

- MySQL does not support transactional DDL, so a failing migration there can leave partial changes; documented, as it is a database limitation. SQLite and PostgreSQL roll back.
- Clearing Doctrine's aliases and renaming commands relies on `setName`/`setAliases` after construction; covered by a test that lists the registered commands.
- Extra transitive packages (about 6). Accepted as part of the data layer.
- `make:migration` timestamps use second precision; two migrations generated in the same second with different names are still distinct, and the same name in the same second fails because the file exists.
