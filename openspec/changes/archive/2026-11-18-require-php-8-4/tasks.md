## 1. Declare the new minimum

- [x] 1.1 Set `"php": "^8.4.1"` in `composer.json`
- [x] 1.2 Run `composer update --lock` and confirm the diff of `composer.lock` is limited to `content-hash` and `platform` (no package version changes)
- [x] 1.3 `composer validate` and `composer install --dry-run` succeed

## 2. Documentation

- [x] 2.1 `README.md`: requirements say PHP 8.4.1 or higher; Docker example uses `php:8.4-cli-alpine`
- [x] 2.2 `CLAUDE.md` code style line and `openspec/config.yaml` tech stack line say PHP 8.4+
- [x] 2.3 Search tracked files (excluding `vendor/` and the archive) and confirm no remaining mention of PHP 8.3 as supported

## 3. PHPStan memory

- [x] 3.1 Set `--memory-limit=512M` in the `analyse` script of `composer.json`
- [x] 3.2 Clear PHPStan's result cache and confirm `composer check` passes on a cold cache

## 4. Verification and roadmap

- [x] 4.1 `composer check` passes
- [x] 4.2 Add the change to the "Hecho" list in `ROADMAP.md` when archiving
