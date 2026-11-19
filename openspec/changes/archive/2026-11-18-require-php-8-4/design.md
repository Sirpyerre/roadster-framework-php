## Context

Measured from `composer.lock` and the local environment (PHP 8.4.16):

- `composer.json` requires `php: ^8.3`; the lock records `platform: {php: ^8.3, ext-json: *, ext-pdo: *}`.
- Locked packages with PHP floors above 8.3: `symfony/string` v8.0.1 (>=8.4), `symfony/yaml` v8.0.1 (>=8.4), `symfony/http-client` v8.0.3 (>=8.4), `symfony/stopwatch` v8.1.0 (>=8.4.1), `symfony/var-exporter` v8.1.10 (>=8.4.1).
- They were resolved on PHP 8.4 because Composer resolves against the running PHP unless `config.platform.php` is set. An attempt to resolve for 8.3.0 (`config.platform.php`) failed because the already-locked `symfony/string` 8.0.1 cannot be satisfied on 8.3, and a full downgrade would touch many packages.
- Other locked packages: Laminas/Mezzio allow `~8.2 .. ~8.5`; dev-only `brianium/paratest` 7.4.8 allows `~8.2 .. ~8.4`.
- The only 8.3 mentions in tracked files: `composer.json`, README (requirements, Docker example), `CLAUDE.md`, `openspec/config.yaml`. No CI configuration exists.

## Goals / Non-Goals

**Goals:**
- The declared minimum PHP equals what the lock actually needs, so `composer install` on a supported PHP always succeeds.
- Documentation and the project context agree with it.

**Non-Goals:** dependency upgrades or audits, adopting 8.4-only syntax, CI/Docker infrastructure (see proposal).

## Decisions

1. **Raise the minimum instead of downgrading the lock.** The decision taken: PHP >= 8.4 is valid and declared. Downgrading means resolving many packages for 8.3 (and `symfony/console` 7.4 pulls `symfony/string` 8 on this lock), which is larger and riskier than declaring the real requirement.
2. **Constraint `^8.4.1`, not `^8.4`.** The highest floor in the lock is 8.4.1; `^8.4` would accept 8.4.0 and then fail on `symfony/stopwatch`/`var-exporter`. `^8.4.1` allows any 8.x from 8.4.1 and excludes 9.0. Alternative `>=8.4.1` was rejected because it would also accept PHP 9.
3. **Refresh the lock metadata only.** `composer update --lock` rewrites the `content-hash` and `platform` entries without changing package versions; the task verifies the package list is unchanged, and `composer validate` and `composer install --dry-run` pass.
4. **No `config.platform.php`.** Pinning the platform would hide mismatches between the local PHP and the declared minimum; the declared requirement is now the single source of truth.
5. **PHPStan memory limit in `composer analyse`.** A cold-cache analysis crashes a parallel worker at the default 128M (it passes at 512M with a ~160 MB peak and no code errors, including with the old `^8.3` requirement, so it is not caused by the PHP constraint). Warm caches had hidden it since DBAL and Migrations were added; any composer.json change or CI run exposes it. Setting `--memory-limit=512M` in the script keeps `composer check` reliable. Alternative: a `memory_limit` in `php.ini`, rejected because it is per-machine.
6. **Docs say "PHP 8.4.1 or higher"** and the Docker example uses `php:8.4-cli-alpine`.

## Risks / Trade-offs

- Users on PHP 8.3 are excluded; accepted, since they could not install the locked set anyway.
- Because the README does not promise 8.5, PHP 8.5 users may hit the `paratest` dev constraint (`~8.4`); it only affects `composer install` with dev dependencies and is left for dependency maintenance.
- The lock's `^8.3` platform entry is the only evidence of the old claim; after the refresh no file should mention 8.3 as a supported version (verified by a search task).
