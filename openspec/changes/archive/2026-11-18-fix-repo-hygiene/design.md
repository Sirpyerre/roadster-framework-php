## Context

`ServeCommand` hard-codes `$basePath . '/.rr.yaml'` and passes it to `rr serve -c`. Nothing reads `config/roadrunner.yaml`; only the README structure block mentions it. The two files are identical today, so any edit to one silently drifts from the other. The stray `TestGeneratedHandlerHandler.php` is tracked in git but unreferenced. See proposal.md for motivation.

## Goals / Non-Goals

**Goals:**
- A single source of truth for the RoadRunner config.
- No leftover generated code in `src/`.
- Reproducible installs and test runs from tracked `composer.lock` and `phpunit.xml`.

**Non-Goals:**
- Relocating the config or changing `ServeCommand`.

## Decisions

- **Keep `.rr.yaml`, delete `config/roadrunner.yaml`.** `.rr.yaml` is the file `ServeCommand` uses and is RoadRunner's conventional default location, so no code or test changes. Alternative: keep `config/roadrunner.yaml` and update `ServeCommand`; rejected because it adds a code change to a cleanup and the README structure block can simply be corrected.
- **Track `phpunit.xml` and `composer.lock`.** Roadster is an application skeleton (`type: project`), where the lock file pins what users get and CI relies on it. `phpunit.xml` configures strict warnings and random order that tests depend on.

## Risks / Trade-offs

- [Someone runs `rr serve` pointing at `config/roadrunner.yaml`] → Nothing in the repo does; the README is updated in the same change.
- [Committed `composer.lock` pins versions that carry the existing deprecation warnings] → Out of scope; upgrades are a separate change.
