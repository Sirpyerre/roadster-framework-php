## 1. RoadRunner config

- [x] 1.1 Add `rpc: listen: tcp://127.0.0.1:${RR_RPC_PORT:-6001}` to `.rr.yaml`
- [x] 1.2 Extend the `.rr.yaml` guard test in `ServeCommandTest.php` to assert the `rpc:` section and loopback address

## 2. Shared helper

- [x] 2.1 Create `RoadRunnerServer` in `src/Console/RoadRunnerServer.php` (base path, binary lookup, pid file, `isRunning()`/`pid()` with stale-pid cleanup, `rr` command building with `-w` and `-c`)
- [x] 2.2 Pest tests for `RoadRunnerServer`: no pid file, live pid (current process), stale pid removed, command building

## 3. Commands

- [x] 3.1 Update `ServeCommand`: start with `-p` and `-w <basePath>`, remove `--stop`/`--reload` and their methods, use `RoadRunnerServer`
- [x] 3.2 Add `ServeStopCommand` (`serve:stop`) and `ServeReloadCommand` (`serve:reload`): message and failure when not running, otherwise run `rr stop` / `rr reset`
- [x] 3.3 Add `ServeStatusCommand` (`serve:status`): running with pid exits 0, not running exits 1
- [x] 3.4 Register the new commands in `src/Console/Application.php`
- [x] 3.5 Pest tests: commands registered, `serve` has no `stop`/`reload` options, not-running output and exit codes for the three commands, stale `.pid` is removed

## 4. Documentation

- [x] 4.1 Document `serve:stop`, `serve:reload` and `serve:status` in `README.md` and `CLAUDE.md`
- [x] 4.2 Add `serve:restart` as a planned idea in `ROADMAP.md` and mark `fix-serve-stop-reload` as done when archiving

## 5. Verification

- [x] 5.1 `composer check` passes
- [x] 5.2 Manual smoke test: `./bin/roadster serve`, `/health` returns 200, `serve:status` shows the pid, `serve:reload` succeeds, `serve:stop` stops it and removes `.pid`; `serve:stop` again reports "not running"
