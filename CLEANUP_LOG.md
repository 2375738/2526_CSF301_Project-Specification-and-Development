# Cleanup Log

## 2026-02-18 - Repository Scope Cleanup

### Objective
Keep only files directly related to:
- the Laravel app (`site-bulletin`), and
- the TypeScript sample guide (`Site Bulletin Implementation Guide`).

### Removed
- `app/` (top-level duplicate scaffold; not the main Laravel app)
- `database/` (top-level duplicate migrations/factories; not the main Laravel app)
- `docs/` (contained extra planning and an additional prototype bundle)
- `scripts/` (auxiliary scraping script not required for core runtime)
- `package-lock.json` (root-level lockfile not used by the kept app structure)

### Retained (Intentional)
- `site-bulletin/` (primary PHP/Laravel application)
- `Site Bulletin Implementation Guide/` (sample TypeScript implementation guide)
- `data/cwl1informationportal/` (still referenced by `site-bulletin/database/seeders/DatabaseSeeder.php`)
- `README.md`
- `AGENTS.md`

### Notes
Next phase is a structured parity comparison between Laravel and the implementation guide, followed by gap implementation and functionality polish.

## 2026-03-16 - Root Documentation Consolidation

### Objective
Move product-facing documentation into the Laravel app folder and keep the repository root limited to codebase directories plus governance/coordination files.

### Changed
- moved the detailed product/setup documentation into `site-bulletin/README.md`
- replaced the root `README.md` with a short repo guide

### Removed
- `PARITY_MATRIX.md`
- `TAB_UX_COMPARISON_MATRIX.md`

### Retained (Intentional)
- `site-bulletin/`
- `data/`
- `AGENTS.md`
- `CLEANUP_LOG.md`
- `IMPLEMENTATION_LOG.md`
- `README.md` (repo-level only)

### Notes
The root folder is now closer to the intended narrowed scope in `AGENTS.md`, while application-specific setup and feature documentation live with the Laravel app itself.

## 2026-03-17 - Removed Legacy Implementation Guide Folder

### Objective
Remove the unused TypeScript implementation-guide bundle so the repository contains only the active Laravel app, supporting data, and governance files.

### Removed
- `Site Bulletin Implementation Guide/`

### Updated
- `AGENTS.md`
- `README.md`
- `site-bulletin/README.md`

### Retained (Intentional)
- `site-bulletin/`
- `data/`
- `AGENTS.md`
- `CLEANUP_LOG.md`
- `IMPLEMENTATION_LOG.md`
- `README.md`

### Notes
The implementation guide had become historical reference only. Runtime, seeding, and testing all now depend solely on the Laravel application in `site-bulletin/`.

## 2026-06-17 - Laravel App Scope Cleanup

### Objective
Keep the repository focused on the active Laravel implementation and avoid reintroducing local coursework/editor artifacts into version control.

### Removed
- `site-bulletin/docs/disclaimer.md`
- `site-bulletin/docs/outline.md`
- `site-bulletin/docs/phase-0-baseline.md`
- `site-bulletin/docs/phase-1-foundation-plan.md`
- `site-bulletin/docs/phase-3-messaging-plan.md`
- `site-bulletin/docs/risk-register.csv`

### Added
- root `.gitignore` for local `.vscode/` settings and root-level PDF coursework exports

### Retained (Intentional)
- `site-bulletin/`
- `data/`
- root governance docs
- current implementation docs in `site-bulletin/docs/role-scenario-*.md`

### Validation
- `php artisan test`
- `npm run build`
- targeted Playwright smoke for guest login redirect, demo login, and authenticated dashboard shell
