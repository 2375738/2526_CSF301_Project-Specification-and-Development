# AGENTS.md

## Repository Scope
This repository is intentionally narrowed to the primary Laravel PHP application:
1. `site-bulletin/` - the primary Laravel PHP application.

Supporting data retained for Laravel seeders:
- `data/cwl1informationportal/`

## What To Keep
- `site-bulletin/**`
- `data/**` only when referenced by app seeders or runtime.
- Root documentation that governs work execution (`README.md`, `AGENTS.md`, `CLEANUP_LOG.md`, `IMPLEMENTATION_LOG.md`).

## What To Avoid Re-Adding
- Duplicate prototype bundles unrelated to the target app.
- Legacy planning folders not required to run, test, or compare the two target implementations.
- Top-level duplicate app scaffolds that mirror code already inside `site-bulletin/`.

## Current Project Goal
1. Stabilize and polish the Laravel app (`site-bulletin`).
2. Implement and refine production-facing behavior in Laravel.
3. Keep repository scope narrow and maintainable.

## Working Rules For Contributors
- Treat `site-bulletin/` as source of truth for production behavior.
- Before adding new files at repo root, justify why they are needed for run/build/test/compare.
- Keep changes traceable in `CLEANUP_LOG.md` or a dedicated implementation log.
- Log every UI/UX change in `IMPLEMENTATION_LOG.md` with: date, scope, reason, and validation evidence (test command or manual check).

## Git Update Cadence
- Push meaningful progress to GitHub at least once every 7 days, even during active development.
- Push immediately after completing a parity milestone or production-impacting fix.
- Do not leave completed work only in local state.
