## Why

`./bin/roadster serve` is broken on `main`. `.rr.yaml` is a git symlink (`.rr.yaml -> config/roadrunner.yaml`), and the `fix-repo-hygiene` change deleted its target while believing the two files were separate, identical copies (`diff` follows symlinks, so it reported no difference). `.rr.yaml` is now a dangling link, so `ServeCommand` fails with "RoadRunner config not found". Nothing caught it: `ServeCommandTest` only checks the command's options, and the verification run only called `serve --help`.

## What Changes

- Replace the dangling `.rr.yaml` symlink with a regular file holding the original 28-line RoadRunner config (recovered from commit `542cf12^`).
- Add tests that fail if the RoadRunner config is missing, is not a regular file, or loses its core sections.
- Verify `serve` end to end against a real RoadRunner binary instead of only `--help`.

## Non-Goals

- Changing any RoadRunner setting (workers, ports, reload, supervisor).
- Changing `ServeCommand` behavior or the config location (it keeps reading `.rr.yaml` at the project root).
- Making the CLI download or install the RoadRunner binary.

## Capabilities

### New Capabilities
<!-- None: restores the intended behavior of an existing command; no new spec-level behavior. -->

### Modified Capabilities
<!-- None. Tooling and bug fix, so the change sets `skip_specs: true`. -->

## Impact

- Files: `.rr.yaml` (symlink becomes a regular file), `tests/Unit/Console/Commands/ServeCommandTest.php`.
- No runtime, API or dependency changes. `README.md` already lists `.rr.yaml` at the project root.
- `composer check` must stay green.
