## 1. Restore the config

- [x] 1.1 Remove the dangling symlink and write `.rr.yaml` as a regular file from `git show 542cf12^:config/roadrunner.yaml`, then verify `test -f .rr.yaml && ! test -L .rr.yaml` succeeds and `diff .rr.yaml <(git show 542cf12^:config/roadrunner.yaml)` prints nothing
- [x] 1.2 Verify `git ls-files -s .rr.yaml` reports mode `100644` and that `grep -rn "roadrunner.yaml" README.md src tests bin composer.json` finds no references

## 2. Guard against regressions

- [x] 2.1 Add tests to `tests/Unit/Console/Commands/ServeCommandTest.php` asserting the project-root `.rr.yaml` exists, is a regular readable file (not a symlink) and contains `version: "3"`, `server:` and `http:`, and verify they pass
- [x] 2.2 Temporarily replace `.rr.yaml` with a dangling symlink, verify the new tests fail, then restore the file and verify they pass again

## 3. Verification

- [x] 3.1 Run `./vendor/bin/rr get-binary` if no `rr` binary is available (needs network), then `./bin/roadster serve` and verify `curl -s localhost:8080/health` returns a healthy JSON response; if the binary cannot be obtained, record that this smoke test was skipped and why
- [x] 3.2 Run `composer check` and verify PHPStan reports no errors and all Pest tests pass
