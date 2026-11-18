## Context

`JsonTodoRepository` reads the whole file in `readData()` and rewrites it in `writeData()` with `file_put_contents`, ignoring `false` results and returning `[]` for undecodable content. `save()` and `delete()` are read-modify-write sequences with no lock between the read and the write. `templates/crud/JsonRepository.php.stub` is the same class with placeholders, so every generated repository inherits the problem. Errors thrown from handlers already map to a generic 500 JSON response via `ErrorHandlerMiddleware`. See proposal.md for motivation and `specs/json-file-persistence/spec.md` for requirements.

## Goals / Non-Goals

**Goals:**
- Correct under RoadRunner's multiple workers on one host.
- One implementation of locking and atomic writes shared by the Todo repository and generated ones.
- Keep the repository interface and the Todo API contract unchanged.

**Non-Goals:**
- Performance tuning (the whole file is still read per operation) or multi-host safety.

## Decisions

- **Shared `JsonFileStore` class** in `src/Infrastructure/Storage/`, with `read(): array` and `transaction(callable): mixed`. The repository and the stub keep only entity mapping. Alternative: patch the locking code into both the class and the stub; rejected because the stub would copy it into every resource and the copies would drift.
- **Lock a sidecar file (`<file>.lock`) with `flock`**, not the data file. The data file is replaced by `rename`, which would break a lock held on the old inode. `LOCK_EX` wraps the whole read-modify-write in `transaction()`; `read()` takes `LOCK_SH`. Alternative: `LOCK_EX` on the data file via `file_put_contents`; rejected because it does not cover the read-modify-write gap.
- **Atomic write via temp file plus `rename`** in the same directory (same filesystem, so rename is atomic). Alternative: write in place under the lock; rejected because a crash mid-write leaves a truncated file.
- **Throw `RuntimeException` on corrupt data and failed writes** (decode with `JSON_THROW_ON_ERROR`, wrap `JsonException`). Alternative: treat corruption as empty and carry on, which is the current data-loss behavior. A missing or zero-length file stays an empty collection. The existing error middleware turns the exception into a 500.
- **Assumption:** no recovery or backup of a corrupt file is attempted; failing loudly is enough for this MVP.

## Risks / Trade-offs

- [`flock` is advisory and unreliable on some network filesystems] → Documented as a non-goal; RoadRunner workers share one local disk.
- [Lock contention under heavy write load serializes requests] → Acceptable for a file-backed example store; the lock is held only for one read plus one write.
- [Sidecar `.lock` files appear in `storage/`] → Add `storage/*.lock` to `.gitignore`.
- [Existing corrupt file now causes 500s instead of silently resetting] → Intended; this is the data-loss fix.
