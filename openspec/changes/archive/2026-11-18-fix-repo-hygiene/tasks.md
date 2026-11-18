## 1. Remove leftovers

- [x] 1.1 Delete `src/Web/Rest/TestGeneratedHandlerHandler.php` and verify `grep -r TestGeneratedHandlerHandler src config tests` finds nothing
- [x] 1.2 Delete `config/roadrunner.yaml` and verify `.rr.yaml` is the only RoadRunner config (`git ls-files | grep -E "rr.yaml|roadrunner.yaml"`)
- [x] 1.3 Update the project structure block in `README.md` to list `.rr.yaml` instead of `config/roadrunner.yaml` and verify no `roadrunner.yaml` mention remains

## 2. Version reproducibility files

- [x] 2.1 Confirm `git status` shows `composer.lock` and `phpunit.xml` as untracked (not ignored) and verify `git check-ignore composer.lock phpunit.xml` prints nothing

## 3. Verification

- [x] 3.1 Run `composer check` and verify PHPStan reports no errors and all Pest tests pass
- [x] 3.2 Run `./bin/roadster serve --help` or `routes:list` and verify the CLI still works with the single `.rr.yaml`
