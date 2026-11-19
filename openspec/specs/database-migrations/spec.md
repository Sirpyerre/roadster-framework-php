## Purpose

Defines how versioned database schema migrations are created, applied, reverted and inspected from the `roadster` CLI, built on Doctrine Migrations.

## Requirements

### Requirement: Generate a migration
`make:migration <Name>` SHALL create a migration class under `database/migrations/` whose name starts with a UTC timestamp followed by the given name (for example `Version20261118123456CreateProducts`), with empty `up` and `down` methods and a human-readable description derived from the name.

#### Scenario: Migration created
- **WHEN** `make:migration CreateProducts` runs
- **THEN** a file `database/migrations/Version<timestamp>CreateProducts.php` exists with a class of the same name extending Doctrine's `AbstractMigration`

#### Scenario: Invalid name rejected
- **WHEN** `make:migration` is given a name that is not a valid PHP class name fragment
- **THEN** it fails with an error and creates no file

#### Scenario: Existing file not overwritten
- **WHEN** the target file already exists
- **THEN** the command fails and leaves the existing file untouched

### Requirement: Apply pending migrations
`migrate` SHALL execute every pending migration in ascending order of name, record each in the `migrations` table, and exit with status 0. Running it again with nothing pending SHALL change nothing.

#### Scenario: Migrate from empty
- **WHEN** two migrations exist and none has run, and `migrate` runs
- **THEN** both run in order and both are recorded as executed

#### Scenario: Nothing pending
- **WHEN** all migrations are already executed and `migrate` runs
- **THEN** no migration runs and the command exits with status 0

### Requirement: A failing migration is reported and not recorded
If a migration throws, `migrate` SHALL exit with a non-zero status, SHALL NOT record that migration as executed, and SHALL roll back the changes of the whole run when the database supports transactional DDL.

#### Scenario: Failure leaves schema unchanged
- **WHEN** the second of two pending migrations throws on SQLite
- **THEN** the command exits non-zero and neither migration is recorded as executed

### Requirement: Roll back the last migration
`migrate:rollback` SHALL revert only the most recently executed migration by running its `down` method and removing its record.

#### Scenario: Roll back one
- **WHEN** two migrations are executed and `migrate:rollback` runs
- **THEN** only the second is reverted and the first remains executed

#### Scenario: Roll back the only executed migration
- **WHEN** exactly one migration is executed and `migrate:rollback` runs
- **THEN** it is reverted and no migration remains recorded as executed

#### Scenario: Nothing to roll back
- **WHEN** no migration has been executed and `migrate:rollback` runs
- **THEN** the command reports that there is nothing to roll back and exits with status 0

### Requirement: Show migration status
`migrate:status` SHALL report the number of executed, available and new (pending) migrations, and the previous, current, next and latest versions.

#### Scenario: Status with pending migrations
- **WHEN** one migration is executed and one is pending
- **THEN** the output reports 1 executed, 2 available and 1 new

#### Scenario: Status shows versions
- **WHEN** one migration is executed and a second is pending
- **THEN** the output shows the executed migration as current and the pending one as next and latest

### Requirement: Database access is lazy
Registering the migration commands SHALL NOT open a database connection or fail on invalid database configuration; only running a migration command SHALL need the connection.

#### Scenario: Unrelated command with bad configuration
- **WHEN** `DB_CONNECTION` is invalid and an unrelated command such as `routes:list` runs
- **THEN** it completes normally

### Requirement: Documentation
README and `CLAUDE.md` SHALL describe `make:migration`, `migrate`, `migrate:rollback` and `migrate:status`.

#### Scenario: Commands documented
- **WHEN** README and `CLAUDE.md` are read
- **THEN** each of the four commands is listed
