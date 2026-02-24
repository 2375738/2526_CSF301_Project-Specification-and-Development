# AGENTS.md

## Repository Scope
This repository is intentionally narrowed to two implementation tracks:
1. `site-bulletin/` - the primary Laravel PHP application.
2. `Site Bulletin Implementation Guide/` - the TypeScript/React sample implementation guide.

Supporting data retained for Laravel seeders:
- `data/cwl1informationportal/`

## What To Keep
- `site-bulletin/**`
- `Site Bulletin Implementation Guide/**`
- `data/**` only when referenced by app seeders or runtime.
- Root documentation that governs work execution (`README.md`, `AGENTS.md`, `CLEANUP_LOG.md`).

## What To Avoid Re-Adding
- Duplicate prototype bundles unrelated to the target app.
- Legacy planning folders not required to run, test, or compare the two target implementations.
- Top-level duplicate app scaffolds that mirror code already inside `site-bulletin/`.

## Current Project Goal
1. Compare Laravel app (`site-bulletin`) against the sample guide (`Site Bulletin Implementation Guide`).
2. Identify implemented vs missing features.
3. Implement missing guide-aligned behavior in Laravel.
4. Polish and stabilize functionality after parity is reached.

## Working Rules For Contributors
- Treat `site-bulletin/` as source of truth for production behavior.
- Treat `Site Bulletin Implementation Guide/` as the feature/UX reference.
- Before adding new files at repo root, justify why they are needed for run/build/test/compare.
- Keep changes traceable in `CLEANUP_LOG.md` or a dedicated implementation log.

## Git Update Cadence
- Push meaningful progress to GitHub at least once every 7 days, even during active development.
- Push immediately after completing a parity milestone or production-impacting fix.
- Do not leave completed work only in local state.
