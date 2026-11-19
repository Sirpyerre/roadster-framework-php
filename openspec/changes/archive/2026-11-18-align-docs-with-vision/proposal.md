## Why

The project docs describe Roadster as an "ultralight", "minimalist", educational framework and back that with numbers nobody measured (12,000+ req/s, <10MB per worker, "10x faster than Laravel", a comparison table), a Docker promise with no Dockerfile in the repo, and a roadmap with dates that have already passed (Jan to Mar 2025). The project's direction is now different: a general-purpose framework for microservices that gives CRUD users Laravel-style scaffolding and gives high-concurrency users a path to long-lived runtimes (RoadRunner now, Swoole optionally later). The docs, the AI-agent instructions and the OpenSpec project context should say that, and the work ahead should be broken into small, independent increments.

## What Changes

- Rewrite the positioning in `README.md` (tagline, intro, features, "Why Roadster", use cases) to the general-purpose vision, in Spanish like the rest of the README.
- Remove unmeasured claims (12,000+ req/s, <10MB, "10x/3x faster", the comparison table's Req/s and memory rows) and state that benchmarks are pending; mark the Docker section as an example, not something shipped.
- Replace the dated README roadmap with a short summary that links to a new `ROADMAP.md`.
- Add `ROADMAP.md`: the vision, the phases and 17 ordered increments (one OpenSpec change each) with scope, non-goals and dependencies, plus what is already done.
- Update the project description in `CLAUDE.md`, `composer.json` and the `context` in `openspec/config.yaml`.

## Non-Goals

- Any code or behavior change, including the RoadRunner config fix (separate change `fix-rr-config-symlink`).
- Implementing any roadmap item (database layer, ORMs, Swoole, benchmarks).
- Creating OpenSpec changes for the roadmap items; each is proposed when its turn comes.
- Translating the README or changing its overall structure beyond the sections listed.

## Capabilities

### New Capabilities
<!-- None: documentation and project-context only. -->

### Modified Capabilities
<!-- None. The change sets `skip_specs: true`. -->

## Impact

- Files: `README.md`, `ROADMAP.md` (new), `CLAUDE.md`, `composer.json` (description only), `openspec/config.yaml` (context only).
- No code, dependency or runtime changes. `composer check` must stay green.
