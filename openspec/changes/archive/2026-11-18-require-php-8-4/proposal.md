## Why

`composer.json` declares `php: ^8.3`, but `composer.lock` already resolves packages that need a newer PHP: `symfony/string` 8.0.1, `symfony/yaml` 8.0.1 and `symfony/http-client` 8.0.3 require PHP >= 8.4, and `symfony/stopwatch` 8.1.0 and `symfony/var-exporter` 8.1.10 (added with `doctrine/migrations`) require PHP >= 8.4.1. A project on PHP 8.3 therefore cannot install from the lock, so the declared minimum is false. The decision is to make PHP 8.4 the supported minimum rather than downgrade the lock.

## What Changes

- `composer.json`: `php` requirement becomes `^8.4.1` (8.4.1 is the highest minimum among the locked packages).
- `composer.lock`: refreshed so its metadata matches the new requirement, with no package version changes.
- `composer analyse` runs PHPStan with `--memory-limit=512M`: a full (cold-cache) analysis no longer fits PHP's default 128M since DBAL and Doctrine Migrations were added, and changing `composer.json` invalidates the cache, so `composer check` crashed. Found while applying this change.
- Documentation states the same minimum: README requirements, the Docker example image (`php:8.4-cli-alpine`), `CLAUDE.md` code style line, and the tech stack line in `openspec/config.yaml`.

## Non-goals

- No package upgrades or downgrades, and no fix for the `composer audit` advisories (a separate dependency-maintenance change).
- No use of PHP 8.4-only language features in the code; this change only declares the minimum.
- No CI, Docker or platform configuration beyond the documentation example (the repository has none).
- No change to the support policy beyond the minimum (supported upper versions are whatever the locked packages allow).

## Capabilities

### New Capabilities

None. This change does not alter behavior (`specs` is skipped).

### Modified Capabilities

None.

## Impact

- `composer.json`, `composer.lock`, `README.md`, `CLAUDE.md`, `openspec/config.yaml`, `ROADMAP.md`.
- Anyone on PHP 8.3 or 8.4.0 can no longer install the framework; in practice they already could not, because of the locked packages.
- Locked dev dependency `brianium/paratest` 7.4.8 allows PHP up to 8.4 only, so installing the dev dependencies on PHP 8.5 would fail until it is updated; noted, not addressed here.
