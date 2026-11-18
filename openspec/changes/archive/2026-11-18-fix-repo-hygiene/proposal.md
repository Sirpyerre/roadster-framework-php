## Why

The repo carries leftovers and ambiguity that make it harder to trust and reproduce: a generated test handler was committed by accident, the RoadRunner config exists twice, and `composer.lock` and `phpunit.xml` are not versioned, so installs and test runs differ between machines.

## What Changes

- Remove `src/Web/Rest/TestGeneratedHandlerHandler.php`, a stray file left over from testing `make:handler` (nothing references it; `MakeCommandsTest` generates and deletes its own `TestGeneratedHandler.php`).
- Remove the duplicate RoadRunner config. `.rr.yaml` and `config/roadrunner.yaml` are byte-identical; keep `.rr.yaml` (the only one `ServeCommand` reads) and delete `config/roadrunner.yaml`.
- Fix `README.md` project structure, which lists `config/roadrunner.yaml`, to point at `.rr.yaml`.
- Start tracking `composer.lock` and `phpunit.xml` (already removed from `.gitignore`).

## Non-Goals

- Changing RoadRunner settings or `ServeCommand` behavior.
- Fixing the PHPStan or Pest deprecation warnings, or upgrading dependencies.
- Any other `.gitignore` cleanup beyond what is already done.

## Capabilities

### New Capabilities
<!-- None: no spec-level behavior changes. -->

### Modified Capabilities
<!-- None. This is a tooling and hygiene change, so the change sets `skip_specs: true`. -->

## Impact

- Deleted files: `src/Web/Rest/TestGeneratedHandlerHandler.php`, `config/roadrunner.yaml`.
- Edited: `README.md` (project structure block).
- Newly tracked: `composer.lock`, `phpunit.xml`.
- No runtime, API, or dependency changes. `composer check` must stay green.
