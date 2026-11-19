## Context

`ServeCommand` defines `--stop` and `--reload`, which shell out to `rr stop` and `rr reset`. Observed with the local `./rr`:

- `rr stop` fails with `open .pid: no such file or directory ... you must run RR with './rr serve -p'`. `startServer()` never passes `-p`.
- `rr reset` fails with `rpc service not specified in the configuration`. `.rr.yaml` has no `rpc` section.
- Both resolve `.pid` and `.rr.yaml` relative to the current directory, so they only work from the project root. `startServer()` passes `-c` but not `-w`.
- `findRoadRunnerBinary()` exists in both `ServeCommand` and `InitCommand`.
- README and `CLAUDE.md` already name `serve:stop` / `serve:reload`.

Reference conventions: Laravel Octane (`octane:start|stop|reload|status`), Symfony CLI (`server:start|stop|status`). Both use `namespace:action`.

## Goals / Non-Goals

**Goals:**
- `serve:stop`, `serve:reload`, `serve:status` work from any directory against this project's server.
- Clear message and non-zero exit when nothing is running.
- Docs name only real commands.

**Non-Goals:**
- `serve:restart`, daemon/background mode, multiple servers per project, remote RPC.

## Decisions

1. **Separate `namespace:action` commands, remove the flags.** Matches Octane and the Artisan-style positioning; the docs already used these names. The flags never worked and the project is v0.1, so they are removed without aliases. Alternative: keep flags as aliases, rejected to avoid two ways to do one thing.
2. **Shared `RoadRunnerServer` helper** (`src/Console/RoadRunnerServer.php`) holding the project base path, binary lookup, pid file path, `isRunning()`/`pid()` with stale-file cleanup, and building the `rr` command lines. The three new commands and `serve` use it, and `ServeCommand` drops its private `findRoadRunnerBinary()`. `InitCommand`'s copy is left alone (out of scope).
3. **Start with `-p` and `-w <basePath>`.** `-p` creates `.pid`; `-w` pins the working directory so the pid location is deterministic. Stop and reload use the same `-w` and `-c`.
4. **Add `rpc: listen: tcp://127.0.0.1:${RR_RPC_PORT:-6001}` to `.rr.yaml`.** Required by `rr reset`; loopback only. Alternative: signal the process from PHP using the pid file; rejected because RPC reset is the supported RoadRunner path and signals need `posix`/`pcntl` handling.
5. **Detect "not running" in PHP before calling `rr`.** `isRunning()` reads `.pid` and checks the process with `posix_kill($pid, 0)` when `posix` is available (otherwise trusts the file). A stale `.pid` is removed and reported as not running.
6. **`serve:status` exit code** is 0 when running, 1 when not, so it can be used in scripts.
7. **`serve:restart` is deferred, not rejected.** It would stop then start; the start step needs the foreground `serve` behavior to be rethought (daemon mode), so it is recorded in `ROADMAP.md` as an idea instead.

## Risks / Trade-offs

- Port 6001 may be taken → configurable with `RR_RPC_PORT`; `serve` gets no new option to keep scope small.
- `.pid` left behind after a crash → handled by the stale-pid check.
- `posix` extension missing on some systems → fall back to trusting the pid file and let `rr` report any error.
- Removing `--stop`/`--reload` is breaking for anyone who tried them; they never worked, so the impact is only documentation.
