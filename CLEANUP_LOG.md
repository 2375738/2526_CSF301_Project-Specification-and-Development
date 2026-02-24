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