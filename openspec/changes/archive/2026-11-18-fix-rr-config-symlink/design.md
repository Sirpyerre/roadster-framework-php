## Context

`ServeCommand` reads `$basePath . '/.rr.yaml'` and passes it to `rr serve -c`; it checks `file_exists()`, which is false for a dangling symlink. The repo tracked `.rr.yaml` as a symlink (mode 120000) to `config/roadrunner.yaml` since the first commit. `fix-repo-hygiene` removed `config/roadrunner.yaml`, leaving the link broken. Recovered content: `git show 542cf12^:config/roadrunner.yaml` (28 lines, identical to the first commit). See proposal.md for the motivation.

## Goals / Non-Goals

**Goals:**
- `.rr.yaml` is a single, real, tracked file that `ServeCommand` can read.
- A test guards against this class of regression.

**Non-Goals:**
- Relocating the config or touching RoadRunner settings.

## Decisions

- **Regular file at `.rr.yaml`, not a restored symlink.** `README.md` already documents `.rr.yaml` as the config, and nothing references `config/roadrunner.yaml`. Symlinks in git are fragile on Windows checkouts (they can materialize as text files containing the path), which matters for a general-purpose framework. Alternative: restore `config/roadrunner.yaml` and keep the symlink; rejected because it keeps the fragility and the two-name ambiguity this cleanup meant to remove.
- **Guard test checks file properties, not YAML parsing.** Assert `.rr.yaml` at the project root exists, `is_file()`, is not a link, is readable, and contains `version: "3"`, `server:` and `http:`. No new dependency is needed. `ServeCommand` checks for the RoadRunner binary before the config, so a command-level test cannot reach the config check on machines without `rr`.
- **End-to-end check is manual and mandatory in the tasks.** Run `serve` with a real binary and hit `/health`; if the binary cannot be obtained (network), say the check was skipped rather than claiming success.

## Risks / Trade-offs

- [The guard test checks strings, so a malformed YAML would pass] → The end-to-end `serve` smoke test covers real parsing.
- [Anyone relying on the old symlink target path] → Nothing in the repo does; the README never documented it.
