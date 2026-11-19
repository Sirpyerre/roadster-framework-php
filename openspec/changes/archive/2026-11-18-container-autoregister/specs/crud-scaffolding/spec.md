## ADDED Requirements

### Requirement: Scaffolded CRUD works without manual registration
`scaffold:crud <Resource>` SHALL generate, besides handlers, entity, service, interface, JSON repository and routes, a `config/dependencies/<resource>.php` binding the repository interface to the JSON repository, so the endpoints work immediately.

#### Scenario: Generated binding
- **WHEN** `scaffold:crud Product` runs
- **THEN** `config/dependencies/product.php` exists and aliases `ProductRepositoryInterface` to `JsonProductRepository`

#### Scenario: Next steps do not ask for registration
- **WHEN** `scaffold:crud Product` finishes
- **THEN** the output does not tell the user to edit `config/container.php` or `config/routes.php`

#### Scenario: Existing files are not overwritten
- **WHEN** a target file already exists
- **THEN** it is skipped with a warning and the other files are still generated

### Requirement: Generate a repository
`make:repository <Name>` SHALL generate `<Name>RepositoryInterface`, `Json<Name>Repository` and `config/dependencies/<name>.php`, and the `<Name>` entity only if it does not already exist.

#### Scenario: Repository generated
- **WHEN** `make:repository Invoice` runs and no `Invoice` entity exists
- **THEN** the entity, the interface, the JSON implementation and the dependency file are created

#### Scenario: Existing entity kept
- **WHEN** `make:repository Invoice` runs and `Invoice` already exists
- **THEN** the entity file is left untouched

#### Scenario: Existing repository fails clearly
- **WHEN** `Json<Name>Repository` already exists
- **THEN** the command fails with an error and writes nothing

### Requirement: Generators use the project base path they are given
Generator commands SHALL write only under their configured base path.

#### Scenario: Temp base path
- **WHEN** a generator command is built with a temporary base path
- **THEN** all generated files are created under that path and none under the repository
