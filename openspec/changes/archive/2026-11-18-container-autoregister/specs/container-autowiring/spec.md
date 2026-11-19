## ADDED Requirements

### Requirement: Concrete classes are autowired
The container SHALL resolve any concrete class whose constructor parameters are classes/interfaces known to the container, or have default values, without an explicit factory.

#### Scenario: Handler without explicit factory
- **WHEN** a handler whose constructor takes a registered service is requested from the container and has no factory entry
- **THEN** the container returns an instance with its dependency injected

#### Scenario: Optional scalar parameter
- **WHEN** a class has a constructor parameter such as `?string $filePath = null`
- **THEN** the container uses the default value for it

### Requirement: Interface bindings are discovered
The container configuration SHALL load every PHP file in `config/dependencies/` and merge its `aliases` and `factories` into the container dependencies.

#### Scenario: Binding file is picked up
- **WHEN** `config/dependencies/todo.php` maps `TodoRepositoryInterface` to `JsonTodoRepository`
- **THEN** `TodoService` resolves with a `JsonTodoRepository` without any entry in `config/container.php`

#### Scenario: Explicit factory still wins
- **WHEN** a class has both an explicit factory and autowiring available
- **THEN** the explicit factory is used

### Requirement: Route files are discovered
The route configuration SHALL load every PHP file in `config/routes/`, in alphabetical order, in addition to the core routes.

#### Scenario: New route file is served
- **WHEN** a file defining `GET /api/products` is added to `config/routes/`
- **THEN** the route is registered without editing `config/routes.php`

### Requirement: Existing Todo API is unchanged
Migrating the Todo example to autowiring SHALL NOT change its HTTP behavior.

#### Scenario: Todo endpoints still work
- **WHEN** the Todo feature tests run
- **THEN** they pass unchanged
