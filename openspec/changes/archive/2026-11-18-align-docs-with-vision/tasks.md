## 1. README positioning and claims

- [x] 1.1 Rewrite the tagline and intro (lines 3-5) with the positioning statement and verify `grep -n -i "ultraligero\|minimalista" README.md` finds nothing
- [x] 1.2 Update the Features list: remove "<10MB" and "12,000+ req/s", reword "Sin Dependencias" to "Sin servicios externos para empezar" and the Docker bullet, and verify `grep -n "12,000\|10MB" README.md` finds nothing in that list
- [x] 1.3 Replace the Performance table and the "10x/3x más rápido" claims with a note that benchmarks are pending (roadmap item 12), and remove the Req/s and memory rows from the comparison table; verify `grep -n -i "más rápido que\|Req/s" README.md` finds nothing
- [x] 1.4 Label the Docker section as an example Dockerfile that is not part of the repo, and verify the text says so
- [x] 1.5 Update "¿Por qué Roadster?" and the use cases so they fit a general-purpose framework (ORM optional, CRUD and high-concurrency users) and verify the "NO uses Roadster si" list no longer lists a complex ORM as a reason

## 2. Roadmap

- [x] 2.1 Replace the README Roadmap section (dated versions) with a short summary of the four phases and a link to `ROADMAP.md`, and verify no `2025` dates remain in that section
- [x] 2.2 Create `ROADMAP.md` from the design (principle, phases, done list, the 17-row table, driver-choice note, "updated when each change is archived" note) and verify it has 17 numbered rows and that the README link resolves to the file

## 3. Project description elsewhere

- [x] 3.1 Update the overview and status in `CLAUDE.md` to the new positioning with a pointer to `ROADMAP.md`, and verify `grep -n -i "ultralight" CLAUDE.md` finds nothing
- [x] 3.2 Update the `description` in `composer.json` and verify `composer validate` reports it as valid
- [x] 3.3 Replace "Educational and minimalist" in the `context` of `openspec/config.yaml` with the new positioning and a pointer to `ROADMAP.md`, and verify `openspec instructions proposal --change align-docs-with-vision --json` still returns the context with the new text

## 4. Verification

- [x] 4.1 Verify `grep -rn -i "ultralight\|educational and minimalist\|12,000" README.md CLAUDE.md composer.json openspec/config.yaml ROADMAP.md` finds no stale positioning or claims, and run `composer check` to confirm PHPStan and Pest are still green
