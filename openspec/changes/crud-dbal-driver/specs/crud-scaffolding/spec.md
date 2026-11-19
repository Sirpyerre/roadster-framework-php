## MODIFIED Requirements

### Requirement: Scaffolded CRUD works without manual registration
`scaffold:crud <Resource>` SHALL generate, besides handlers, entity, service, repository interface and routes, the repository of the selected driver and a `config/dependencies/<resource>.php` binding the repository interface to it, so the endpoints work immediately (after `migrate` for the DBAL driver).

#### Scenario: Generated binding
- **WHEN** `scaffold:crud Product` runs
- **THEN** `config/dependencies/product.php` exists and aliases `ProductRepositoryInterface` to `DbalProductRepository`

#### Scenario: Generated binding for the JSON driver
- **WHEN** `scaffold:crud Product --driver=json` runs
- **THEN** `config/dependencies/product.php` aliases `ProductRepositoryInterface` to `JsonProductRepository`

#### Scenario: Next steps do not ask for registration
- **WHEN** `scaffold:crud Product` finishes
- **THEN** the output does not tell the user to edit `config/container.php` or `config/routes.php`

#### Scenario: Existing files are not overwritten
- **WHEN** a target file already exists
- **THEN** it is skipped with a warning and the other files are still generated

### Requirement: Generate a repository
`make:repository <Name>` SHALL generate `<Name>RepositoryInterface`, the repository of the selected driver, `config/dependencies/<name>.php`, the table migration when the driver is `dbal`, and the `<Name>` entity only if it does not already exist.

#### Scenario: Repository generated
- **WHEN** `make:repository Invoice` runs and no `Invoice` entity exists
- **THEN** the entity, the interface, `DbalInvoiceRepository`, the dependency file and the `CreateInvoicesTable` migration are created

#### Scenario: JSON repository generated
- **WHEN** `make:repository Invoice --driver=json` runs
- **THEN** `JsonInvoiceRepository` is created and no migration is created

#### Scenario: Existing entity kept
- **WHEN** `make:repository Invoice` runs and `Invoice` already exists
- **THEN** the entity file is left untouched

#### Scenario: Existing repository fails clearly
- **WHEN** the repository file of the selected driver already exists
- **THEN** the command fails with an error and writes nothing

## ADDED Requirements

### Requirement: Choose the repository driver
`scaffold:crud` and `make:repository` SHALL accept `--driver` with the values `dbal` (default) and `json`, and SHALL reject any other value before writing anything.

#### Scenario: Default driver is DBAL
- **WHEN** `scaffold:crud Product` runs without `--driver`
- **THEN** `DbalProductRepository` is generated and `JsonProductRepository` is not

#### Scenario: JSON driver unchanged
- **WHEN** `scaffold:crud Product --driver=json` runs
- **THEN** the generated files are the same set as before this change, with `JsonProductRepository` and no migration

#### Scenario: Unsupported driver
- **WHEN** `scaffold:crud Product --driver=cycle` runs
- **THEN** it fails with an error that lists the available drivers, and no file is written

### Requirement: DBAL scaffold generates the table migration
With the `dbal` driver the generators SHALL create a migration `Version<timestamp>Create<Resources>Table` in `database/migrations/` that creates a table named after the lowercase resource plus `s` with columns `id` (primary key), `name`, `created_at` and `updated_at` (nullable), and drops it on `down`.

#### Scenario: Migration created
- **WHEN** `scaffold:crud Product` runs
- **THEN** `database/migrations/Version<timestamp>CreateProductsTable.php` exists and creates table `products`

#### Scenario: Migration is not duplicated
- **WHEN** a `Version*CreateProductsTable.php` migration already exists and `scaffold:crud Product` runs again
- **THEN** no second migration is created and the existing one is reported as skipped

#### Scenario: Next steps mention migrate
- **WHEN** `scaffold:crud Product` finishes with the `dbal` driver
- **THEN** the output tells the user to run `./bin/roadster migrate`

### Requirement: Generated DBAL repository persists through the connection
The generated DBAL repository SHALL read and write the resource's table through the shared `Doctrine\DBAL\Connection`, store timestamps in UTC, and return entities equivalent to the ones saved.

#### Scenario: Save and find
- **WHEN** a new entity is saved and then fetched by id after the migration has run
- **THEN** the fetched entity has the same id, name, creation time and no update time

#### Scenario: Update existing
- **WHEN** a saved entity is changed and saved again
- **THEN** the table still has one row for that id with the new name and an update time

#### Scenario: Save without changes
- **WHEN** an existing entity is saved twice with identical data
- **THEN** no duplicate row or error occurs

#### Scenario: Delete
- **WHEN** an existing id is deleted
- **THEN** the repository returns true and the row is gone; deleting an unknown id returns false

#### Scenario: List
- **WHEN** several entities exist
- **THEN** the repository lists all of them ordered by creation time

#### Scenario: Timestamps independent of server timezone
- **WHEN** the PHP default timezone is not UTC
- **THEN** a saved entity's creation time, when fetched, is the same instant
