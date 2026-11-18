## Why

`JsonTodoRepository` (and the `scaffold:crud` stub that copies it into every generated resource) can lose data. Each operation reads the whole file, modifies it in memory, and rewrites it with a plain `file_put_contents`, with no locking. RoadRunner runs several workers in parallel (4 by default), so two concurrent writes can overwrite each other or leave a half-written file. On top of that, a corrupt file is read as an empty list, so the next save wipes it, and failed writes are silently ignored.

## What Changes

- Serialize read-modify-write operations across workers with an exclusive lock, and take a shared lock for reads.
- Write atomically (temp file in the same directory, then rename) so a reader or a crash never sees a partial file.
- Fail loudly instead of silently: a file that exists but contains invalid JSON, or JSON that is not a list, raises an error and is never overwritten; a failed encode, write or rename raises an error instead of being ignored.
- Keep the current behavior for a missing or empty file (an empty collection).
- Extract the file handling into one small reusable class so `JsonTodoRepository` and the `scaffold:crud` stub share it, instead of repeating the locking code in every generated repository.
- Add tests for corruption, write failure and concurrent writers.

## Non-Goals

- Replacing JSON storage with a database or adding a storage abstraction beyond the shared file class.
- Changing the Todo API contract, routes or response bodies for the normal paths.
- Untracking `storage/todos.json` (it is committed runtime data; worth a separate change).
- Locking across multiple machines or network filesystems.

## Capabilities

### New Capabilities
- `json-file-persistence`: guarantees for the JSON file storage used by repositories: no lost updates under concurrent workers, no partial files, and explicit errors for corrupt data or failed writes.

### Modified Capabilities
<!-- None: no spec exists yet under openspec/specs/. -->

## Impact

- Code: `src/Infrastructure/Todo/JsonTodoRepository.php`, a new shared file-storage class under `src/Infrastructure/`, `templates/crud/JsonRepository.php.stub`.
- Tests: `tests/Unit/Infrastructure/JsonTodoRepositoryTest.php` plus a new test for the shared class.
- Behavior: a corrupt `storage/*.json` now yields a 500 (through the existing error middleware) instead of an empty list.
- No new dependencies. `composer check` must stay green.
