## 1. Autowiring

- [x] 1.1 Register `ReflectionBasedAbstractFactory` in `config/container.php` and load `config/dependencies/*.php` (sorted, only `aliases`/`factories`, merged with `array_replace`)
- [x] 1.2 Create `config/dependencies/todo.php` aliasing `TodoRepositoryInterface` to `JsonTodoRepository`; remove the Todo repository, service and handler factories from `config/container.php`
- [x] 1.3 Load `config/routes/*.php` (sorted) from `config/routes.php` and remove the explicit Todo route require
- [x] 1.4 Pest tests: container built from the real config resolves `TodoService` and all five Todo handlers; an explicit factory overrides autowiring; scalar-default parameter uses its default; existing Todo feature tests pass unchanged

## 2. Generators

- [x] 2.1 Add `StubWriter` (`src/Console/StubWriter.php`) with unit tests (render, create, skip existing)
- [x] 2.2 Add `templates/crud/dependencies.php.stub`
- [x] 2.3 Refactor `ScaffoldCrudCommand` to use `StubWriter`, accept an optional base path, generate `config/dependencies/<resource>.php`, and drop the manual-registration "Next steps"
- [x] 2.4 Add `MakeRepositoryCommand` (`make:repository`): interface, JSON implementation, dependency file, entity only if missing; fail before writing if the JSON repository exists; register it in `Application`
- [x] 2.5 Update "Next steps" in `MakeHandlerCommand` and `MakeServiceCommand`
- [x] 2.6 Pest tests with a temp base path: scaffold generates the binding file and files lint with `php -l`; generated dependency file maps interface to implementation; make:repository cases (new entity, existing entity kept, existing repository fails and writes nothing)

## 3. Documentation

- [x] 3.1 Document autowiring, `config/dependencies/`, `config/routes/` and `make:repository` in `README.md` and `CLAUDE.md`
- [x] 3.2 Mark `container-autoregister` as done in `ROADMAP.md` when archiving

## 4. Verification

- [x] 4.1 `composer check` passes
- [x] 4.2 Manual smoke test: `scaffold:crud Product`, start the server, `POST` and `GET /api/products` work with no manual edits; clean the generated files afterwards
