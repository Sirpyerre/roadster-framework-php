## Context

Current docs: `README.md` (375 lines, Spanish) positions Roadster as ultralight/minimalist and includes unverified numbers in the Features list (lines 34-40), "Performance" (116-130), "Comparación" (251-262), "Roadmap" (281-306) and a Dockerfile snippet (198-219) although the repo has no Docker files. `CLAUDE.md` says "ultralight PHP framework optimized for RoadRunner". `composer.json` description says "Ultralight PHP framework". `openspec/config.yaml` context says "Educational and minimalist". There is no benchmark in the repo, and `scaffold:crud` still needs manual container wiring. See proposal.md for motivation.

## Goals / Non-Goals

**Goals:**
- One consistent positioning across every file that states it.
- No claim in the docs that the repo cannot back up today.
- A roadmap that a contributor can pick one item from and turn into an OpenSpec change.

**Non-Goals:**
- Rewriting sections unrelated to positioning, claims or roadmap.

## Decisions

- **Positioning statement (README, Spanish):** "Roadster es un framework PHP de propósito general para APIs y microservicios: scaffolding al estilo Artisan para quien necesita un CRUD rápido, y runtimes de larga vida (RoadRunner hoy, Swoole opcional más adelante) para quien necesita alta concurrencia." English equivalent for `CLAUDE.md`, `composer.json` and the OpenSpec context.
- **Remove numbers instead of caveating them.** A "~estimado" label still advertises a figure. The Performance section becomes a note that reproducible benchmarks arrive with roadmap item 12; the comparison table keeps only qualitative rows. Alternative: keep the numbers marked unverified; rejected because it still presents invented data.
- **Honest feature list.** "Sin dependencias" becomes "Sin servicios externos para empezar"; "Docker Ready" becomes "Docker (ejemplo de Dockerfile incluido en la guía)" and the Dockerfile section is labeled as an example, not part of the repo.
- **Roadmap has no calendar dates.** Items are ordered by dependency and phase, with a status column. Dates in the old roadmap are already in the past and were never tracked.
- **`ROADMAP.md` is the single source for scope; the README only summarizes and links to it.** This avoids two lists drifting apart.
- **The OpenSpec `context` edit is limited to the positioning sentence** (and a pointer to `ROADMAP.md`); all other context lines stay as they are.

### ROADMAP.md content (source for the apply step)

Phases: **A** CRUD foundations, **B** ORM of your choice, **C** high concurrency, **D** microservices. Principle to state at the top: one application codebase that is safe for long-lived processes (stateless services, no globals, request state only in PSR-7 attributes), so changing runtime is configuration, not a rewrite.

Done: `fix-repo-hygiene`, `harden-json-repository` (spec `json-file-persistence`) and `fix-rr-config-symlink`. The table below keeps every item, with a status column.

| # | Change | Phase | Scope | Depends on | Status |
|---|---|---|---|---|---|
| 1 | `fix-rr-config-symlink` | A | Restore `.rr.yaml` as a regular file and add a guard test | - | done |
| 2 | `align-docs-with-vision` | A | This change | - | in progress |
| 3 | `fix-serve-stop-reload` | A | Make `serve --stop` and `--reload` work (RoadRunner needs a pid file, which `ServeCommand` never creates) and fix the docs that mention `serve:stop` and `serve:reload`, which are not real commands | - | planned |
| 4 | `container-autoregister` | A | Generators register handlers, services and repositories; `make:repository` | - | planned |
| 5 | `database-foundation` | A | Doctrine DBAL (no ORM), `config/database.php`, `.env` variables, SQLite default, database check in `/health` | - | planned |
| 6 | `database-migrations` | A | `make:migration`, `migrate`, `migrate:rollback`, `migrate:status` | 5 | planned |
| 7 | `crud-dbal-driver` | A | `scaffold:crud --driver=dbal` as default, with generated migration | 4, 5, 6 | planned |
| 8 | `request-validation` | A | `make:request`, uniform 422 responses | - | planned |
| 9 | `worker-reset-hook` | B | Resettable services between requests in the worker loop | - | planned |
| 10 | `orm-doctrine` | B | Optional Doctrine ORM driver, clears the `EntityManager` per request | 7, 9 | planned |
| 11 | `orm-cycle` | B | Optional Cycle ORM driver (built for RoadRunner workers) | 7, 9 | planned |
| 12 | `benchmark-harness` | C | Reproducible benchmark; README cites its numbers | 7 | planned |
| 13 | `runtime-seam` | C | `Runtime` interface with RoadRunner and classic adapters | 9 | planned |
| 14 | `swoole-driver` | C | Optional Swoole driver with a coroutine-aware connection pool | 12, 13 | planned |
| 15 | `observability` | D | Monolog wired in, request id, RoadRunner metrics | - | planned |
| 16 | `readiness-checks` | D | `/ready` checking database and dependencies | 5 | planned |
| 17 | `jobs-queue` | D | Queues through RoadRunner's `jobs` plugin, `make:job` | 4 | planned |

Driver choice (items 5, 7, 10, 11): the domain keeps depending on repository interfaces; `scaffold:crud --driver=json|dbal|doctrine|cycle` picks the infrastructure stub, `config/database.php` holds `orm: none|doctrine|cycle`, and ORM packages are opt-in via `composer require`.

## Risks / Trade-offs

- [Removing numbers makes the README less "selling"] → Honest docs now, real numbers after item 12.
- [ROADMAP.md goes stale] → Each row is updated when its OpenSpec change is archived; stated at the top of the file.
- [Editing `openspec/config.yaml` affects every future proposal] → Limited to the positioning sentence, and verified with `openspec instructions` afterwards.
