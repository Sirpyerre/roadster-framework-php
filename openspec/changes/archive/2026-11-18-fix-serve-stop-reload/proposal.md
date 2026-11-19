## Why

`./bin/roadster serve --stop` and `serve --reload` do not work: `rr stop` needs a `.pid` file that only exists when RoadRunner is started with `-p`, and `rr reset` needs an `rpc` section that `.rr.yaml` does not have. README and `CLAUDE.md` document `serve:stop` and `serve:reload`, which are not commands. Laravel Octane, Symfony CLI and most frameworks use one `namespace:action` command per lifecycle action, not flags.

## What Changes

- New commands `serve:stop`, `serve:reload` and `serve:status`, following the Laravel Octane convention.
- **BREAKING**: remove the `--stop` and `--reload` options from `serve` (no aliases; the project is v0.1).
- `serve` starts RoadRunner with `-p` and a fixed working directory so a `.pid` file is created in the project root.
- `.rr.yaml` gains an `rpc` section bound to `127.0.0.1` (port from `RR_RPC_PORT`, default 6001) so `rr reset` can reach the server.
- The new commands print an actionable message and exit non-zero when no server is running, instead of a raw RoadRunner error.
- README and `CLAUDE.md` document the three commands; `restart` is documented as a planned follow-up in `ROADMAP.md`.
- Pest tests cover command registration, option removal, the RPC section in `.rr.yaml`, and the "not running" and stale-pid paths.

## Non-goals

- No `serve:restart` yet (documented in `ROADMAP.md` as a later idea): `serve:reload` covers worker reloads.
- No daemon/background mode for `serve`.
- No change to the hot-reload watcher (`reload:` section) or to worker pool settings.
- No authentication or remote access for RPC: it listens on loopback only.

## Capabilities

### New Capabilities
- `server-lifecycle`: starting, stopping, reloading and inspecting the RoadRunner server through the `serve`, `serve:stop`, `serve:reload` and `serve:status` commands.

### Modified Capabilities

## Impact

- `src/Console/Commands/ServeCommand.php` (options removed, `-p`/`-w` added), new `ServeStopCommand`, `ServeReloadCommand`, `ServeStatusCommand`, a shared `RoadRunnerServer` helper, `src/Console/Application.php` (registration), `.rr.yaml`, tests, `README.md`, `CLAUDE.md`, `ROADMAP.md`.
- New `.pid` file at the project root while the server runs (already covered by `*.pid` in `.gitignore`).
- Opens a local RPC port (default 6001) on loopback.
- Anyone calling `serve --stop` / `--reload` must switch to the new commands; those options never worked.
