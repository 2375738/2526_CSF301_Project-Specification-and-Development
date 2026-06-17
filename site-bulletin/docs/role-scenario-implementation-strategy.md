# Role Scenario Implementation Strategy

Date: 2026-05-06

Source matrix: [`role-scenario-fit-matrix.md`](role-scenario-fit-matrix.md)

Purpose: provide a durable implementation strategy for the role-scenario backlog so work can continue across interrupted sessions without rediscovering priorities, scope, or validation expectations.

## Strategy Principles

1. Build role workflows, not isolated widgets.
   Every change should improve a real daily scenario for employee, manager, operations manager, HR, or admin.

2. Keep the app narrow.
   All production behavior belongs in `site-bulletin/`. Product strategy docs belong in `site-bulletin/docs/` unless they govern repository-level execution.

3. Ship in vertical slices.
   Each milestone should include data/service logic, route/controller changes if needed, Blade UI, tests, and implementation-log evidence.

4. Preserve role boundaries.
   Any change touching tickets, analytics, announcements, messages, role requests, or audit logs must explicitly test employee, manager, ops manager, HR, and admin access where relevant.

5. Make interruption cheap.
   Every session should leave a clear state: completed, in progress, blocked, tests run, next file to open.

## Durable Workstream IDs

Use these IDs in commits, implementation logs, branch names, notes, and future chat requests.

| ID | Workstream | Priority | Outcome |
|---|---|---:|---|
| RS-01 | Action-led role home screens | P1 | Each role has a clear "what needs my attention now" surface. |
| RS-02 | Unified scope and permission model | P1 | Department and leadership visibility rules are consistent across modules. |
| RS-03 | Employee help and self-service | P2 | Employees can search help, choose report paths, and track requests with less ambiguity. |
| RS-04 | Repeat issue and breach prevention | P2 | Managers and ops managers can see recurring blockers and likely breaches before they escalate. |
| RS-05 | HR-specific workspace | P2 | HR has a dedicated daily queue for approvals, sensitive cases, people tickets, and policy acknowledgement. |
| RS-06 | Admin readiness and system health | P3 | Admin can verify production/demo readiness, scheduler health, and configuration completeness. |

## Recommended Implementation Order

### Phase 0: Baseline And Guardrails

Goal: make later work safer before adding larger workflows.

Scope:
- Create or confirm shared role/department scope helpers.
- Identify duplicated scoping logic in controllers/policies.
- Add characterization tests before replacing behavior.
- Confirm existing role demo users and seeded data support employee, manager, ops manager, HR, and admin review.

Primary workstream: `RS-02`

Suggested files:
- `app/Models/User.php`
- `app/Policies/*`
- `app/Http/Middleware/EnsureRole.php`
- `app/Http/Controllers/Public/*`
- `tests/Feature/*`

Done when:
- There is a shared service or policy path for department scope.
- Existing behavior remains intact.
- Focused tests prove managed/unmanaged department visibility across tickets, analytics, role requests, messages/broadcasts, announcements, and audit logs.

Validation:
- `php artisan test tests/Feature/Tickets tests/Feature/Governance tests/Feature/Announcements tests/Feature/Messaging`
- `php artisan test tests/Feature/LayoutNavigationTest.php`

### Phase 1: Action-Led Home Screens

Goal: turn the dashboard into role-specific next-action surfaces.

Scope:
- Add a small, stable next-action model or view-data service.
- Employee: urgent acknowledgement, unread manager/HR/support message, ticket waiting on employee, resolved ticket awaiting confirmation, performance risk if already surfaced.
- Manager: breached tickets, waiting employee tickets, pending approvals, unread conversations, role requests.
- Ops manager: site-wide triage pressure, department SLA pressure, aging tickets, stale automation/data warnings if available.
- HR: HR approvals, sensitive tickets, role requests, policy acknowledgement exceptions.
- Admin: configuration/system health summary, missing data, demo/prototype flags, failed/stale jobs if available.

Primary workstream: `RS-01`

Suggested files:
- `app/Services/DashboardDataService.php`
- new `app/Services/RoleActionService.php` or equivalent
- `resources/views/dashboard/partials/*`
- `resources/views/dashboard.blade.php`
- `tests/Feature/DashboardTrendPanelsTest.php`
- new role-specific dashboard feature tests

Done when:
- Each role sees an action-led section.
- The section has no more than 4-6 high-priority items.
- Every item links to the page where the user can complete the action.
- Empty states are role-specific and useful.

Validation:
- `php artisan test tests/Feature/DashboardTrendPanelsTest.php`
- `php artisan test tests/Feature/LayoutNavigationTest.php`
- `npm run build`

### Phase 2: Employee Self-Service

Goal: make employee help-seeking and issue reporting more obvious.

Scope:
- Add guided report path for "I am not sure".
- Add ticket index tabs: `Needs me`, `In progress`, `Waiting on team`, `Resolved`.
- Consider unified "Find help" search across knowledge snippets, quick links, and announcements.
- Add contextual guidance between messaging and ticket reporting.

Primary workstreams: `RS-03`, partial `RS-01`

Suggested files:
- `app/Http/Controllers/Public/ReportTicketController.php`
- `app/Http/Controllers/Public/TicketViewController.php`
- `app/Http/Controllers/Public/KnowledgeSnippetController.php`
- `resources/views/tickets/*`
- `resources/views/knowledge/index.blade.php`
- `resources/views/dashboard/partials/*`
- `tests/Feature/Tickets/*`
- `tests/Feature/KnowledgeSnippetSearchTest.php`

Done when:
- Employee can choose a guided reporting path without category knowledge.
- Ticket index makes "needs me" work obvious.
- Help results respect current visibility rules.

Validation:
- `php artisan test tests/Feature/Tickets tests/Feature/KnowledgeSnippetSearchTest.php`
- `npm run build`

### Phase 3: Manager And Ops Prevention

Goal: move from reactive ticket tracking to operational prevention.

Scope:
- Add repeat issue clustering by category/template/location/department.
- Add breach-risk or aging-pressure panel.
- Improve triage actions for ops manager: assign, transfer, escalate, bulk action where low risk.
- Add department comparison for ops manager where current dashboard is too manager-like.

Primary workstream: `RS-04`

Suggested files:
- `app/Services/DepartmentAnalyticsService.php`
- `app/Services/DashboardDataService.php`
- `app/Http/Controllers/Public/SupportTriageBoardController.php`
- `app/Http/Controllers/Admin/AnalyticsController.php`
- `resources/views/tickets/triage.blade.php`
- `resources/views/admin/analytics.blade.php`
- dashboard partials
- `tests/Feature/Tickets/TriageBoardTest.php`
- `tests/Feature/Analytics/*`

Done when:
- Manager/ops can see top repeated blockers.
- Ops manager has site-wide pressure view.
- Breach-risk items have direct action routes.

Validation:
- `php artisan test tests/Feature/Tickets/TriageBoardTest.php tests/Feature/Analytics`
- `php artisan test tests/Feature/DashboardTrendPanelsTest.php`
- `npm run build`

### Phase 4: HR Workspace

Goal: stop treating HR as just another generic leadership user.

Scope:
- Add HR dashboard/workspace section or page.
- Include HR approvals, sensitive tickets, people tickets, role requests, policy acknowledgement exceptions.
- Improve public/internal update affordances for sensitive ticket work.
- Add acknowledgement follow-up queue for policy/site-wide updates.

Primary workstream: `RS-05`

Suggested files:
- `app/Http/Controllers/Public/DashboardController.php`
- `app/Services/DashboardDataService.php`
- `app/Http/Controllers/Public/TicketApprovalQueueController.php`
- `app/Http/Controllers/Public/AnnouncementController.php`
- `resources/views/tickets/show.blade.php`
- `resources/views/tickets/approvals.blade.php`
- `resources/views/announcements/show.blade.php`
- new HR partial/page if needed
- `tests/Feature/Tickets/TicketApprovalQueueTest.php`
- `tests/Feature/Tickets/TicketAttachmentVisibilityTest.php`
- `tests/Feature/Announcements/AnnouncementReadFlowTest.php`

Done when:
- HR has a daily work queue distinct from manager/ops.
- Sensitive-ticket private/public actions are hard to confuse.
- Policy acknowledgement exceptions can be found and acted on.

Validation:
- `php artisan test tests/Feature/Tickets/TicketApprovalQueueTest.php tests/Feature/Tickets/TicketAttachmentVisibilityTest.php tests/Feature/Announcements`
- `npm run build`

### Phase 5: Admin Readiness And Health

Goal: make production readiness and configuration health visible.

Scope:
- Add readiness command, page, or both.
- Check demo login/simulation/prototype flags.
- Check `APP_DEBUG`, queue driver, mailer, scheduler freshness, failed jobs, storage link, users without departments/managers, stale analytics/demo samples.
- Add admin-facing checklist or health card.

Primary workstream: `RS-06`

Suggested files:
- `config/site_bulletin.php`
- `app/Console/Commands/*`
- `app/Console/Kernel.php`
- `app/Http/Controllers/Public/GovernanceController.php` or admin controller
- `resources/views/governance/*` or new admin health view
- `tests/Feature/Governance/*`
- new command tests

Done when:
- Admin can identify unsafe production settings without reading `.env`.
- Readiness checks are testable and documented.
- No demo/prototype behavior is implicit in production-facing mode.

Validation:
- `php artisan test tests/Feature/Governance`
- targeted command tests
- `npm run build` if a UI is added

## Session Interruption Protocol

At the start of every session:
1. Read this file.
2. Read `role-scenario-fit-matrix.md`.
3. Run `git status --short`.
4. Identify the active workstream ID from the user's request or latest implementation-log entry.
5. Inspect only the files needed for that workstream.

Before making code changes:
1. State the selected workstream and phase.
2. Note the intended files/modules.
3. Confirm whether the change is UI/UX-impacting. If yes, update `IMPLEMENTATION_LOG.md`.

Before ending a session:
1. Run focused tests for touched behavior.
2. Run `npm run build` for UI/asset changes.
3. Run `git diff --check`.
4. Update this document's "Current Progress Ledger" if a phase status changed.
5. Update `IMPLEMENTATION_LOG.md` for UI/UX changes with date, scope, reason, and validation evidence.
6. Leave a final answer with:
   - workstream ID
   - files changed
   - tests run
   - next recommended step

If interrupted mid-change:
1. Do not start a new workstream next session.
2. Resume from `git status --short` and the "Current Progress Ledger".
3. Inspect changed files before editing further.
4. Preserve unrelated user changes.

## Branch And Commit Strategy

Recommended branch naming:
- `rs-01-role-actions`
- `rs-02-scope-model`
- `rs-03-employee-self-service`
- `rs-04-prevention`
- `rs-05-hr-workspace`
- `rs-06-admin-readiness`

Commit size:
- Prefer one vertical slice per commit.
- Do not mix unrelated workstream IDs in one commit unless a shared foundation change is explicitly needed.

Commit message pattern:
```text
RS-01 Add employee next-action dashboard strip
```

PR / review description pattern:
```text
Workstream: RS-01 Action-led role home screens
Scenario: Employee starts shift and sees immediate actions
Validation:
- php artisan test ...
- npm run build
Notes:
- ...
```

## Definition Of Ready

A slice is ready to implement when:
- The target role and scenario are named.
- The current implementation files are identified.
- The desired user-visible outcome is clear.
- Access rules are known.
- Tests to add/update are known.
- Any UI change has an implementation-log plan.

## Definition Of Done

A slice is done when:
- User-visible scenario works for the target role.
- Other roles are not accidentally granted access.
- Empty states and error states are handled.
- Feature/authorization tests pass.
- Build passes when UI assets are touched.
- `IMPLEMENTATION_LOG.md` is updated for UI/UX changes.
- This strategy's progress ledger is updated.

## Current Progress Ledger

| Phase | Workstream | Status | Notes |
|---|---|---|---|
| Phase 0 | RS-02 | Complete | Shared `RoleScopeService` now covers public ticket visibility/actions, triage, on-behalf tickets, role requests, conversation broadcasts/options, audit ticket checks, announcement authoring, ticket approvals, ticket filters, analytics screen/export department selection, dashboard governance/attention widgets, and manager-accessible Filament ticket/content department surfaces. |
| Phase 1 | RS-01 | Complete | Added shared `RoleActionService` and a dashboard `Next Actions` section for employee, manager, ops manager, HR, and admin worklists, with role-specific action links and empty states. |
| Phase 2 | RS-03 | Complete | Employee ticket index now has self-service flow tabs; report issue has a guided recommendation path; knowledge search returns grouped visible snippets, quick links, and announcements. |
| Phase 3 | RS-04 | Complete | Added shared `OperationalPreventionService`, manager dashboard Prevention Watch, ops-manager site-wide pressure view, repeat issue clusters by department/category/template/location, breach/aging risk links, triage Prevention Pressure panel, and repeat-link ticket filters. |
| Phase 4 | RS-05 | Complete | Added shared `HrWorkspaceService`, dashboard `People Operations Queue` for HR approvals, sensitive cases, people tickets, role requests, and policy acknowledgement follow-up; added sensitive ticket handling guidance and HR workspace regression tests. |
| Phase 5 | RS-06 | Complete | Added shared `SystemReadinessService`, `site:readiness-check` command, admin-only governance System Health Checklist, and tests for readiness visibility/command output across debug, demo, queue, mail, storage, failed-job, user-department, manager relationship, metrics, and demo sample checks. |

## Consensus Decisions

Accepted on 2026-05-06: use the recommended option in each case.

1. `ops_manager` role shape:
   - Option A: site-wide command center.
   - Option B: manager with broader department permissions.
   - Decision: Option A, because it gives the role a distinct product reason.

2. HR dashboard:
   - Option A: distinct HR workspace.
   - Option B: generic leadership dashboard plus approval filters.
   - Decision: Option A, because HR work has different privacy and compliance needs.

3. Employee performance emphasis:
   - Option A: coaching/action feedback.
   - Option B: minimal metrics, more tickets/messages.
   - Decision: Option A, but keep language supportive and action-based.

4. Help/search shape:
   - Option A: unified search across knowledge, quick links, and announcements.
   - Option B: keep separate areas.
   - Decision: Option A, with grouped results to preserve clarity.

5. Role/access requests:
   - Option A: formal workflow with visible status and approver chain.
   - Option B: simple request list.
   - Decision: Option A, because it aligns with ticket approval behavior.

6. Duplicate/repeat detection:
   - Option A: first-class analytics and dashboard feature.
   - Option B: ticket-level duplicate linking only.
   - Decision: Option A, because prevention is one of the clearest product upgrades.

## First Slice Recommendation

Start with `RS-02` as the next implementation slice:

Title: shared role scope service.

Goal: centralize which departments and queues each role can view/manage.

Why first:
- It reduces risk before adding role-specific dashboards.
- It makes manager, ops manager, HR, and admin behavior easier to reason about.
- It creates reusable contracts for all later workstreams.

Likely deliverables:
- `RoleScopeService` or similar.
- Tests for employee, manager, ops manager, HR, admin department visibility.
- Refactor one or two high-risk consumers first, not every controller at once.
- Document remaining consumers for follow-up.
