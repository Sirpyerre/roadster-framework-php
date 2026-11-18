## 1. Shared file store

- [x] 1.1 Create `JsonFileStore` in `src/Infrastructure/Storage/` with `read()` (shared lock; missing or empty file returns `[]`; invalid JSON or non-list throws `RuntimeException`) and verify with new unit tests covering missing, empty, valid, invalid JSON and non-list content
- [x] 1.2 Add `transaction(callable)` holding an exclusive sidecar lock across read, callback and atomic write (temp file plus rename, errors on encode/write/rename failure throw) and verify with tests that an unwritable directory throws and that a failed write leaves the previous contents intact
- [x] 1.3 Add a concurrency test that spawns several PHP processes each saving distinct items to one file and verify the final file holds every item exactly once

## 2. Use the store in the repository

- [x] 2.1 Refactor `JsonTodoRepository` to use `JsonFileStore` (`save` and `delete` via `transaction()`, reads via `read()`), keeping the `TodoRepositoryInterface` unchanged, and verify the existing `JsonTodoRepositoryTest` still passes
- [x] 2.2 Add repository tests for corrupt file on `findAll` and on `save` (error thrown, file unchanged) and verify they pass
- [x] 2.3 Add a feature test in `tests/Feature/TodoApiTest.php` that points the API at a corrupt file and verify it returns a 500 JSON error

## 3. Generator and housekeeping

- [x] 3.1 Update `templates/crud/JsonRepository.php.stub` to use `JsonFileStore` and verify that running `scaffold:crud` for a throwaway resource produces a repository that passes `./vendor/bin/phpstan analyse` (delete the generated files afterwards)
- [x] 3.2 Add `storage/*.lock` to `.gitignore` and verify `git check-ignore storage/todos.json.lock` matches

## 4. Verification

- [x] 4.1 Run `composer check` and verify PHPStan reports no errors and all Pest tests pass
