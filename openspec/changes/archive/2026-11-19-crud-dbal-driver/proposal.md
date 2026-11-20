## Why

`scaffold:crud` and `make:repository` still generate JSON-file repositories, even though the framework now has a configured DBAL connection (`database-foundation`) and migrations (`database-migrations`). The agreed vision is that a user who needs a basic CRUD gets a real database-backed resource with one command. This change makes DBAL the default generated driver and keeps JSON as an explicit option, as the first step of the "choose your data layer" design (ORM drivers come later).

## What Changes

- `scaffold:crud <Resource>` and `make:repository <Name>` gain `--driver=dbal|json`, defaulting to `dbal` (behavior change: the default generated repository is no longer JSON).
- With `dbal`, they generate `Dbal<Resource>Repository` (using `Doctrine\DBAL\Connection`, autowired) and a migration `Version<timestamp>Create<Resources>Table` that creates the table, in addition to the existing handlers, entity, service, interface, routes and binding file.
- With `json`, output is exactly what is generated today.
- The binding file (`config/dependencies/<resource>.php`) points the repository interface at the repository of the selected driver.
- Any other driver value is rejected with a clear error before anything is written; `doctrine` and `cycle` are listed as planned, not available.
- Re-running the generator does not create a second migration for a table whose migration already exists.
- "Next steps" output tells DBAL users to run `./bin/roadster migrate`.
- README and `CLAUDE.md` describe the driver option and the DBAL default. The Todo example stays JSON.

## Non-goals

- No Doctrine ORM or Cycle ORM drivers (`orm-doctrine`, `orm-cycle`), and no `--driver` values for them yet.
- No change to the Todo example, its repository or its storage.
- No custom columns, relations, validation or pluralization rules: the generated table has the same fields as the generated entity (`id`, `name`, `created_at`, `updated_at`) and its name is the lowercase resource plus `s`, like the route.
- No automatic execution of migrations and no data migration from JSON files.
- No pagination or filtering in the generated repository.

## Capabilities

### New Capabilities

None.

### Modified Capabilities
- `crud-scaffolding`: generators accept a driver, default to DBAL, generate the DBAL repository and table migration, and bind the interface to the selected driver.

## Impact

- `src/Console/Commands/ScaffoldCrudCommand.php`, `MakeRepositoryCommand.php`, `MakeMigrationCommand.php`, `src/Console/DatabaseMigrations.php`, a new `src/Console/RepositoryDriver.php`.
- `templates/crud/`: new `DbalRepository.php.stub` and `migration.php.stub`; `dependencies.php.stub` takes the repository class.
- Tests (including an end-to-end test that generates a resource, migrates and exercises the generated repository), `README.md`, `CLAUDE.md`, `ROADMAP.md`.
- Users of the generators get a database table instead of a `storage/*.json` file by default and must run `migrate`.
