## Context

Observed in the repository:

- `config/container.php` registers the Todo example by hand: repository closure, `TodoService` closure and five handler closures, all of the form `new X($container->get(Y::class))`. `ContainerFactory` builds a Laminas `ServiceManager` from `$config['dependencies']`.
- `config/routes.php` requires `routes/todo.php` explicitly; `public/index.php` calls it after building the pipeline.
- `laminas-servicemanager` (^3.22, already a direct dependency) ships `ReflectionBasedAbstractFactory`, which resolves constructor parameters that are classes/interfaces from the container and falls back to default values for scalar parameters (so `JsonTodoRepository::__construct(?string $filePath = null)` works).
- Generators hard-code `dirname(__DIR__, 3)`; existing tests that run them write into the real repo (the stray `TestGeneratedHandlerHandler.php` came from that).
- `templates/repository.php.stub` holds only the interface; `templates/crud/` has the full set of stubs, including `Entity.php.stub` and `JsonRepository.php.stub`.
- Routes can only reference handlers by class name, so autowired handlers need no route change.

## Goals / Non-Goals

**Goals:**
- After `scaffold:crud` or `make:repository`, no hand edit is needed for the code to run.
- Keep generators append-only: they create files, never rewrite existing ones.
- Keep `composer check` green and the Todo API behavior identical.

**Non-Goals:**
- Compiled container, attributes, driver selection, middleware discovery (see proposal).

## Decisions

1. **Autowire with `ReflectionBasedAbstractFactory` as an abstract factory.** Built into the dependency already used, no new package. Alternative: write our own reflection container or switch to PHP-DI; rejected as a new dependency and migration for no extra benefit. Explicit `factories` still take precedence over abstract factories, so special cases remain possible.
2. **Interface bindings through `config/dependencies/*.php`, using `aliases`.** An interface cannot be autowired, so each resource ships a tiny file: `['aliases' => [XRepositoryInterface::class => JsonXRepository::class]]`. `JsonXRepository` is then autowired. Swapping the implementation later (the `--driver` increments) is a one-line change in that file. Alternative: have the generator edit `container.php`; rejected because mutating PHP source is fragile and conflicts with user edits.
3. **Glob loading in `container.php` and `routes.php`.** `config/container.php` loads `config/dependencies/*.php` (sorted) and merges only `aliases` and `factories` keys with `array_replace`, so a later file replaces an earlier entry for the same key instead of creating a nested array as `array_merge_recursive` would. `config/routes.php` loads `config/routes/*.php` (sorted) after the core `/health` route. Alternative: explicit include lines per resource; rejected because that is the manual step being removed.
4. **Migrate Todo to the same mechanism** so the example matches what generators produce: add `config/dependencies/todo.php`, delete the 8 explicit factories and the manual `todo.php` route require. The existing Todo feature and unit tests act as the regression guard.
5. **Shared `StubWriter` helper** (`src/Console/StubWriter.php`) used by `scaffold:crud` and `make:repository`: renders a stub with placeholders and writes it unless the target exists, returning created/skipped. Removes the duplicated loop and makes the generators testable.
6. **Injectable base path.** `ScaffoldCrudCommand` and `MakeRepositoryCommand` take `?string $basePath = null` (default: project root), as `RoadRunnerServer` does. Tests generate into a temp directory.
7. **`make:repository` generates the entity only if missing**, so it is usable on its own and does not clobber an existing entity. If `Json<Name>Repository` already exists the command fails before writing anything, to avoid half-generated sets. `scaffold:crud` keeps its current "skip with warning" behavior.
8. **New stubs:** `templates/crud/dependencies.php.stub` (the alias file). `make:repository` reuses `crud/Entity.php.stub`, `crud/Repository.php.stub` (interface) and `crud/JsonRepository.php.stub`; the unused-by-crud `templates/repository.php.stub` is left alone.
9. **`make:handler`/`make:service` messages**: the "register in container" line is replaced by a note that classes with typed constructor dependencies are autowired.

## Risks / Trade-offs

- Autowiring makes any class with resolvable dependencies constructible via `$container->get()`. Only application code calls the container with class names, so this does not expose new input paths; noted for the future `runtime-seam` work.
- A typo in a dependency file breaks container creation for every request. Mitigated by a test that builds the container from the real `config/` directory.
- Glob order is alphabetical, so same-key overrides depend on file names; documented in the container spec scenario and in a comment in `container.php`.
- Singletons live for the whole worker (unchanged); resettable services are the scope of `worker-reset-hook`.
