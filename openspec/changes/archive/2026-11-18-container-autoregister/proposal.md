## Why

`scaffold:crud` generates 10 files but leaves the user to hand-edit `config/container.php` (about 15 lines per resource: repository binding, service factory, five handler factories) and `config/routes.php` before anything works. `make:handler` and `make:service` also end with "register in config/container.php". That manual wiring is the main gap between the Artisan-style promise and what the CLI delivers, and there is no `make:repository`.

## What Changes

- The container autowires concrete classes through Laminas' `ReflectionBasedAbstractFactory`: handlers, services and implementations with typed constructor dependencies need no explicit factory.
- Interface bindings live in `config/dependencies/*.php` (one file per resource, `aliases`/`factories` only) and are loaded automatically by `config/container.php`.
- Route files in `config/routes/*.php` are loaded automatically by `config/routes.php`.
- The existing Todo example is migrated to this mechanism: its 8 explicit factories and manual route `require` are removed.
- `scaffold:crud` additionally generates `config/dependencies/<resource>.php` binding `<Resource>RepositoryInterface` to `Json<Resource>Repository`; its "Next steps" no longer ask for manual registration.
- New `make:repository <Name>` command: generates the repository interface, a JSON implementation and the dependency binding (and the entity if it is missing).
- `make:handler` and `make:service` "Next steps" stop asking for container registration when the class is autowirable.
- Commands accept an injectable base path so Pest tests generate into a temp directory instead of the repository.

## Non-goals

- No change to the data layer: generated repositories stay JSON-backed (`--driver` comes with `crud-dbal-driver`).
- No compiled/cached container and no attribute- or annotation-based registration.
- No auto-registration of middleware; the pipeline in `public/index.php` stays explicit.
- No editing of existing PHP files by the generators: wiring comes from files they create, never from rewriting `container.php` or `routes.php`.

## Capabilities

### New Capabilities
- `container-autowiring`: how classes and interface bindings get into the container without hand-written factories, and how route files are discovered.
- `crud-scaffolding`: what `scaffold:crud` and `make:repository` generate and that the result works without manual registration.

### Modified Capabilities

## Impact

- `config/container.php`, `config/routes.php`, new `config/dependencies/todo.php`, `src/App/Container/ContainerFactory.php` (if needed), `src/Console/Commands/ScaffoldCrudCommand.php`, `MakeHandlerCommand.php`, `MakeServiceCommand.php`, new `MakeRepositoryCommand.php`, `src/Console/Application.php`, `templates/` (dependency stub, repository stubs), tests, `README.md`, `CLAUDE.md`, `ROADMAP.md`.
- Behavior change for existing apps: any class with resolvable constructor dependencies becomes resolvable from the container, and every file in `config/routes/` is now loaded.
- Services stay shared singletons inside a long-lived worker, same as today.
