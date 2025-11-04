# Phase Implementation Playbook

Use this guide whenever you pick up a new phase for the Site Bulletin project. Follow every step to ensure features, tests, documentation, and work logs stay aligned with the roadmap.

## 1. Understand the Scope
- Read the relevant plan (for example `docs/phase-3-messaging-plan.md`) plus any linked specs.
- Review recent progress in `docs/progress-log.md` so you know what is already finished or pending follow-ups.
- Capture open questions, risky assumptions, or missing context in your working notes before coding.

## 2. Break Down the Work
- Draft a mini-plan listing the major deliverables for the phase: migrations, models, policies, controllers, UI, background jobs, notifications, etc.
- Flag required updates to factories/seeders and any integrations that may need refactoring.
- Identify where tests must be added or rewritten (feature, unit, policy, request validation).

## 3. Data & Domain Changes
- Create or modify database migrations first; run them locally to confirm schema alignment.
- Update Eloquent models, casts, scopes, and relationships to reflect new fields or logic.
- Adjust factories and seeders to keep sample data realistic and to support automated tests.

## 4. Application Logic
- Implement policies, gates, and authorization checks early so routes cannot be called out of scope.
- Build or update controllers, form requests, jobs, and services with small, testable methods.
- Keep validation rules close to the entry points (form requests) and add guard clauses for role-specific behaviour.

## 5. UI & UX
- Update Blade views, components, or Filament resources to expose new capabilities.
- Ensure UX states for success, validation errors, empty data, and permission-denied cases are handled gracefully.
- Add visual indicators that reinforce new features (badges, filters, locked states, etc.).

## 6. Tests
- Cover happy paths and key edge cases. Minimum expectations:
  - Feature tests for HTTP flows (creation, visibility, filters).
  - Policy or unit tests for authorization and domain calculations.
  - Validation tests for new request rules.
- Run the full test suite (`php artisan test`). If it fails, fix or adjust as part of the phase. Never leave broken tests.

## 7. Manual Verification
- Perform smoke tests in the browser or via HTTP clients for critical flows the automated tests cannot cover easily.
- Capture screenshots or quick notes when behaviour is nuanced, especially for locked/permissioned interfaces.

## 8. Documentation & Logging
- Update `docs/progress-log.md` with:
  - Completion date.
  - Summary of implemented items.
  - Notable files or modules touched.
  - Outstanding follow-ups or technical debt.
- If the phase plan document needs refinement (scope shifts, lessons learned), append a section with clarifications.

## 9. Clean-Up
- Remove unused code, feature flags, or TODO placeholders created during the phase.
- Re-run `php artisan test` to confirm the tree is clean.
- Ensure git status only contains intended changes before requesting review or committing.

## 10. Hand-Off Checklist
- ✅ Schema & models updated.
- ✅ Application logic (controllers, services, jobs) complete.
- ✅ UI and UX flows updated, including edge states.
- ✅ Factories/seeders refreshed as needed.
- ✅ Automated tests added and passing.
- ✅ Manual smoke tests done.
- ✅ Documentation and `docs/progress-log.md` updated with the summary.
- ✅ Worktree clean apart from phase deliverables.

Follow this checklist every time; it keeps phases consistent, testable, and easy for the next contributor to understand. If a new requirement doesn’t fit the current structure, add an addendum to this guide so the playbook evolves with the project.
