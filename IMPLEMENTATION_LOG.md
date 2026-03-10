# Implementation Log

## Purpose
Track what changed, why it changed, and what remains, without overloading `PARITY_MATRIX.md`.

## Block: Product Direction
- Date: 2026-03-10
- Scope: Role-based use case framing and agent handoff context.
- Context:
  - The app exists to cover operational communication gaps not well served by Amazon A to Z.
  - A to Z is useful for HR and general company information, but does not solve site-specific communication, employee performance visibility, ticket transparency, or shift-time messaging for most floor associates.
  - Site Bulletin should act as the site execution and communication layer for employees, managers, and support/admin teams.
- Rationale:
  - Future agents should optimize for operational self-service, written communication, and reduced dependency on verbal relay through managers.

## Block: Role-Based Use Cases
- Date: 2026-03-10
- Scope: Primary user journeys the product should support.
- Employee use cases:
  - Start of shift: open the app and immediately see urgent site news, department updates, safety/process changes, and local quick links.
  - During shift: check personal ticket status without needing to find a manager for updates.
  - Performance visibility: review own productivity, quality, and trend snapshots without waiting for manager feedback.
  - Clarification path: message manager or support contact when blocked or confused during active work.
  - Self-service issue reporting: create tickets for equipment, facilities, HR, safety, or process blockers from a mobile-friendly surface.
  - Written confirmation: re-read announcements and updates instead of relying only on verbal briefs.
- Manager use cases:
  - Shift launch: publish department announcements with clear targeting and urgency.
  - Shift monitoring: watch SLA risk, ticket backlog, performance, and quality without switching between fragmented tools.
  - Drilldown: move from dashboard signals to filtered ticket lists for the exact issue cluster needing action.
  - Team communication: broadcast operational changes and answer direct employee questions in writing.
  - Escalation handling: create tickets on behalf of employees and track stuck issues.
  - Oversight: use governance/audit flows for access, role, and operational accountability.
- Admin or support use cases:
  - Publish site-specific information not covered well in A to Z.
  - Triage incoming tickets by department, category, SLA risk, and ownership.
  - Communicate resolution progress back to requesters clearly in-app.
  - Track recurring issue patterns and use analytics to identify systemic blockers.
  - Support auditability and access governance.

## Block: Current Coverage Assessment
- Date: 2026-03-10
- Scope: What the Laravel app already supports well.
- Current strengths:
  - Dashboards for employee and manager visibility.
  - Announcement centre with read state, filtering, sorting, and manager compose flow.
  - Ticket reporting, ticket detail, comments, attachments, filters, and SLA visibility.
  - Internal messaging with direct chats, department broadcasts, search, unread indicators, and thread view.
  - Profile settings and notification preferences.
  - Governance and audit flows for higher-privilege users.
  - Site quick links and public-facing local information.
  - Demo login and browser smoke testing for major user flows.
- Assessment:
  - The app already covers the main product thesis better than A to Z for site-specific operations.
  - Remaining work is less about parity with the guide and more about operational depth, clarity, and mobile-first execution.

## Block: Prioritized Roadmap
- Date: 2026-03-10
- Scope: Recommended next implementation order after current parity work.
- Priority 1:
  - Employee self-service visibility.
  - Add a stronger `My Work Today` surface with shift context, assigned area, current blockers, personal targets, and latest quality/performance status.
- Priority 2:
  - Ticket lifecycle clarity.
  - Show clearer timelines, ownership, next expected action, and understandable resolution status for employees.
- Priority 3:
  - Acknowledgement-based announcements.
  - Add `read`, `understood`, and optionally `need clarification` responses so managers know who has actually seen operational updates.
- Priority 4:
  - Role-routed communication shortcuts.
  - Add explicit actions like `Message my manager`, `Ask support`, `Escalate to HR`, `Contact safety`.
- Priority 5:
  - Manager attention queue.
  - Create a dedicated workflow view for urgent unread items, breached tickets, pending approvals, and unresolved employee blockers.
- Priority 6:
  - Admin/support triage board.
  - Add a queue-oriented workbench for support teams with ownership, grouped filters, and response tracking.

## Block: Suggested Features To Spec Next
- Date: 2026-03-10
- Scope: Candidate enhancements worth defining before implementation.
- Candidate features:
  - Employee `My Work Today` page.
  - Ticket timeline and ETA/next-step messaging.
  - Announcement acknowledgement tracking.
  - Knowledge snippets / micro-SOP search.
  - Fast issue reporting optimized for floor/mobile usage.
  - Manager action queue.
  - Support triage board.
- Suggested delivery order:
  - `My Work Today`
  - ticket transparency improvements
  - announcement acknowledgements
  - routed communication shortcuts
  - manager/support operational queue views

## Block: Implementation Backlog
- Date: 2026-03-10
- Scope: Actionable feature backlog for future agents.
- Item:
  - Feature: `My Work Today`
  - User role: Employee
  - Problem solved: Employees still need to infer shift priorities from multiple widgets instead of one focused work surface.
  - Existing support: Employee dashboard already shows performance snapshots, news, links, and messaging previews.
  - Gap: No single page summarises shift context, blockers, target status, assigned area, and active actions.
  - Likely Laravel surfaces:
    - extend [DashboardController.php](/c:/Users/kadet/Documents/2526_CSF301_Project%20Specification%20and%20Development/site-bulletin/app/Http/Controllers/Public/DashboardController.php)
    - add employee-first partials under `resources/views/dashboard/partials/`
    - optionally add dedicated `/my-work` route if dashboard becomes too mixed
  - Test strategy:
    - feature test for employee-only content visibility
    - Playwright smoke for employee landing experience
  - Priority: P1
  - Status: Partial
- Item:
  - Feature: Ticket lifecycle transparency
  - User role: Employee
  - Problem solved: Employees can see tickets, but status meaning, ownership, next step, and likely resolution path are still too implicit.
  - Existing support: Ticket list/detail, comments, attachments, SLA flags, status changes.
  - Gap: No plain-language timeline, next-action summary, resolver ownership card, or ETA-style guidance.
  - Likely Laravel surfaces:
    - enhance [TicketViewController.php](/c:/Users/kadet/Documents/2526_CSF301_Project%20Specification%20and%20Development/site-bulletin/app/Http/Controllers/Public/TicketViewController.php)
    - improve `resources/views/tickets/show.blade.php`
    - optionally add presenter/view-model layer for status semantics
  - Test strategy:
    - feature tests for ticket detail visibility and status messaging
    - Playwright coverage for employee ticket detail comprehension path
  - Priority: P2
  - Status: Partial
- Item:
  - Feature: Announcement acknowledgements
  - User role: Employee, Manager
  - Problem solved: Managers cannot distinguish between unread, understood, and requires-clarification states for operational updates.
  - Existing support: Announcement read/unread state and mark-all-read.
  - Gap: No acknowledgement model beyond read receipts.
  - Likely Laravel surfaces:
    - extend announcement data model and controller flows
    - update [AnnouncementController.php](/c:/Users/kadet/Documents/2526_CSF301_Project%20Specification%20and%20Development/site-bulletin/app/Http/Controllers/Public/AnnouncementController.php) if implemented there
    - update `resources/views/announcements/index.blade.php` and `show.blade.php`
    - add manager reporting surface for acknowledgement breakdown
  - Test strategy:
    - migration/model tests for acknowledgement states
    - feature tests for employee acknowledgement actions
    - Playwright test for acknowledge and manager visibility path
  - Priority: P3
  - Status: Partial
- Item:
  - Feature: Routed communication shortcuts
  - User role: Employee
  - Problem solved: Messaging exists, but employees still need to decide who to contact instead of using role-based shortcuts.
  - Existing support: Direct conversations, department broadcasts, search, unread state.
  - Gap: No quick actions like `Message my manager`, `Ask support`, or `Escalate to HR`.
  - Likely Laravel surfaces:
    - extend [ConversationController.php](/c:/Users/kadet/Documents/2526_CSF301_Project%20Specification%20and%20Development/site-bulletin/app/Http/Controllers/Messaging/ConversationController.php)
    - add quick-start actions on dashboard, tickets, or profile surfaces
    - possibly derive default contacts from `ManagerRelationship`, department, or role policy rules
  - Test strategy:
    - feature tests for shortcut visibility by role/department
    - policy tests for valid routing targets
    - Playwright smoke for shortcut-to-thread flow
  - Priority: P4
  - Status: Partial
- Item:
  - Feature: Manager attention queue
  - User role: Manager
  - Problem solved: Managers have analytics but not a single action queue of urgent unread items, blocked associates, and breached work.
  - Existing support: Manager dashboard KPIs, SLA health, drilldowns, governance widgets, unread counts.
  - Gap: No explicit “what needs attention now” queue.
  - Likely Laravel surfaces:
    - extend [DashboardController.php](/c:/Users/kadet/Documents/2526_CSF301_Project%20Specification%20and%20Development/site-bulletin/app/Http/Controllers/Public/DashboardController.php)
    - add manager queue partials within `resources/views/dashboard/partials/`
    - optionally add dedicated manager worklist route
  - Test strategy:
    - feature tests for queue population by breached tickets, unread messages, pending requests
    - Playwright coverage for manager queue actions
  - Priority: P5
  - Status: Partial
- Item:
  - Feature: Admin/support triage board
  - User role: HR, Admin, Support
  - Problem solved: Support/admin users need a queue-oriented workbench, not just generic ticket lists and analytics.
  - Existing support: Ticket filters, analytics, governance, announcements.
  - Gap: No ownership-centric triage board with grouped queues, response tracking, and workload segmentation.
  - Likely Laravel surfaces:
    - new controller and route near `tickets` or `analytics`
    - extend ticket query strategies from [TicketViewController.php](/c:/Users/kadet/Documents/2526_CSF301_Project%20Specification%20and%20Development/site-bulletin/app/Http/Controllers/Public/TicketViewController.php)
    - possibly a Filament admin page for higher-volume triage
  - Test strategy:
    - feature tests for role-gated access and queue filters
    - browser tests for triage actions and grouped queue rendering
  - Priority: P6
  - Status: Partial
- Item:
  - Feature: Knowledge snippets / micro-SOP search
  - User role: Employee, Manager
  - Problem solved: Repeated operational questions still depend on verbal relay or local tribal knowledge.
  - Existing support: Quick links and announcements.
  - Gap: No searchable short-form operational guidance embedded in workflow.
  - Likely Laravel surfaces:
    - new lightweight content model or repurposed quick-link categories with searchable content
    - dashboard and ticket/report surfaces for contextual help
  - Test strategy:
    - feature tests for search relevance and audience restrictions
    - Playwright check for search and open flow
  - Priority: P7
  - Status: Partial
- Item:
  - Feature: Fast issue reporting for mobile/floor use
  - User role: Employee
  - Problem solved: Current reporting flow may still be too general for high-movement floor usage.
  - Existing support: `/tickets/report` flow already exists.
  - Gap: No ultra-fast path for common issue types with prefilled categories, shorter forms, and minimal input burden.
  - Likely Laravel surfaces:
    - improve [ReportTicketController.php](/c:/Users/kadet/Documents/2526_CSF301_Project%20Specification%20and%20Development/site-bulletin/app/Http/Controllers/Public/ReportTicketController.php)
    - redesign `resources/views/tickets/report.blade.php`
    - possibly add issue presets on dashboard or tickets index
  - Test strategy:
    - feature tests for fast-path category presets
    - Playwright mobile viewport test for rapid issue creation
  - Priority: P8
  - Status: Partial

## Block: Backlog Rules
- Date: 2026-03-10
- Scope: How future agents should use the backlog.
- Rules:
  - Update `Status` when an item moves from `Planned` to `In Progress`, `Partial`, or `Done`.
  - If a new feature is added, place it in priority order and note the likely Laravel surfaces before implementation starts.
  - When an item is completed, add a dated implementation block below and reference validation evidence.
  - Keep this backlog focused on operational-product development, not one-off cosmetic tweaks.

## Block: Agent Handoff Notes
- Date: 2026-03-10
- Scope: Guidance for future contributors.
- Instructions:
  - Treat parity with the guide as largely achieved; optimize next for operational usefulness in the warehouse/site context.
  - When proposing changes, ask whether they reduce reliance on verbal communication or manager-only visibility.
  - Prefer features that help floor associates act without needing a laptop or manager lookup.
  - Keep `PARITY_MATRIX.md` focused on guide comparison and keep product planning / roadmap detail in this file.
  - For every implemented UI/UX change, append a dated block with scope, rationale, and validation evidence.

## Block: Product Build
- Date: 2026-03-10
- Scope: Employee `My Work Today` first iteration.
- Changes:
  - Added a dedicated employee-first `My Work Today` dashboard section with:
    - shift context
    - department summary
    - manager reference
    - throughput and quality snapshot deltas
    - unread update counts
    - open ticket summary
    - actionable ticket focus list
    - shift targets
    - attention-needed counts
  - Reused the existing dashboard route and controller instead of splitting the employee landing experience into a separate first-pass page.
  - Added plain-language ticket next-step messaging so raw statuses are easier for associates to understand.
- Rationale:
  - This is the first direct implementation from the post-parity operational roadmap.
  - The goal is to reduce manager-dependent status checking and give employees one place to understand what matters right now.

## Block: Product Build
- Date: 2026-03-10
- Scope: Ticket lifecycle transparency first iteration.
- Changes:
  - Added a plain-language `What This Means` section to the ticket detail page with:
    - current status explanation
    - ownership summary
    - next-step guidance
    - latest visible update
    - service note
    - requester action callout when the ticket is waiting on employee input or fix confirmation
  - Reworked status timeline copy so employees see readable lifecycle stages instead of only raw status labels.
  - Kept the existing ticket workflow and manager triage controls intact; this iteration improves comprehension rather than changing the state model.
- Rationale:
  - Employees should not need a manager to translate ticket state into plain operational meaning.
  - This closes the first part of the roadmap item around visibility of ownership, expectation, and next action.

## Block: Product Build
- Date: 2026-03-10
- Scope: Announcement acknowledgements first iteration.
- Changes:
  - Extended `announcement_reads` so each read receipt can store acknowledgement state and timestamp.
  - Added employee-facing acknowledgement actions on the announcement detail page:
    - `Understood`
    - `Need Clarification`
  - Added current acknowledgement state visibility on the announcement detail page and announcement list.
  - Added a manager-facing acknowledgement summary block on the announcement detail page showing:
    - total read
    - understood
    - need clarification
    - read only
  - Kept the existing unread/read flow intact by extending the same receipt record instead of introducing a separate acknowledgement model.
- Rationale:
  - Managers need more than a binary read receipt to know whether an operational update was actually clear.
  - Employees need an in-app way to signal that a message was read but still needs follow-up.

## Block: Product Build
- Date: 2026-03-10
- Scope: Routed communication shortcuts first iteration.
- Changes:
  - Added routed quick-contact options on the messages page for:
    - `Message My Manager`
    - `Ask Support`
    - `Escalate to HR`
  - Shortcut routing is derived from existing org/application data:
    - manager from `ManagerRelationship`
    - support from the `Support` department or fallback operational leadership
    - HR from `hr` or `admin` users
  - Extended the message creation flow so shortcut submissions use the same `messages.store` endpoint instead of creating a parallel messaging path.
  - Added direct-thread reuse for two-person shortcut conversations so repeated manager contact reopens the same thread instead of creating duplicates.
- Rationale:
  - Employees should not need to decide manually who to contact for common escalation paths.
  - Reusing existing direct threads keeps communication history coherent and reduces inbox noise.

## Block: Product Build
- Date: 2026-03-10
- Scope: Manager attention queue first iteration.
- Changes:
  - Added a dedicated `Attention Queue` block to the manager dashboard.
  - The queue now surfaces manager-actionable work in four buckets:
    - breached tickets
    - tickets waiting on employee response
    - unread conversations
    - pending role requests in managed departments
  - Each bucket links back into the existing operational surfaces (`Tasks`, `Messages`, `Role Requests`) instead of creating a parallel workflow.
  - The queue is populated from live application data already used elsewhere in the product, so it reflects real unresolved work rather than synthetic status cards.
- Rationale:
  - Managers need an explicit “what needs attention now” view, not only analytics and drilldowns.
  - This closes the first operational pass on turning dashboard insight into an action queue.

## Block: Product Build
- Date: 2026-03-10
- Scope: Admin/support triage board first iteration.
- Changes:
  - Added a dedicated `Support Triage Board` route and controller for `ops_manager`, `hr`, and `admin` users.
  - Added grouped triage queues for:
    - unassigned new tickets
    - breached queue
    - waiting on employee
    - recently updated open work
  - Added queue summaries for:
    - ownership load
    - category mix
    - department mix
  - Added an entry point from the main tickets page for triage-capable users.
  - Kept triage actions linked to the existing ticket list and ticket detail pages instead of creating a separate resolution workflow.
- Rationale:
  - Support/admin users need a queue-oriented workbench for active operational load, not only a generic ticket list.
  - This creates the first explicit higher-volume triage surface without duplicating ticket resolution logic.

## Block: Product Build
- Date: 2026-03-10
- Scope: Knowledge snippets / micro-SOP search first iteration.
- Changes:
  - Added a lightweight `knowledge_snippets` content model and searchable `Knowledge Snippets` page.
  - Added route and navigation entry so employees and managers can reach short operational answers directly in-app.
  - Seeded starter snippets for common floor questions such as:
    - scanner reset steps
    - missed punch route
    - transport escalation
    - a manager-only escalation note
  - Added audience-aware visibility so manager-only guidance does not leak to employee users.
- Rationale:
  - Repeated operational questions should not depend only on verbal relay or tribal knowledge.
  - This provides a lightweight knowledge layer without forcing the app into a full CMS implementation.

## Block: Product Build
- Date: 2026-03-10
- Scope: Fast issue reporting for mobile/floor use first iteration.
- Changes:
  - Added a `Fast Report` section to the ticket report page with common issue presets for:
    - scanner issues
    - safety concerns
    - facilities issues
    - transport problems
  - Presets now preload:
    - category
    - title
    - starter description text
    - location placeholder/default
  - Kept the existing full report form intact so the same route supports both rapid floor reporting and detailed submissions.
- Rationale:
  - High-movement floor reporting needs fewer typing steps and a lower-friction entry point than the generic full form.
  - This creates a true fast path without splitting ticket creation into a separate workflow.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=PublicDashboardTest`
- Result: Passed.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=TicketLifecycleTransparencyTest`
  - `php artisan test --filter=TicketIndexViewTest`
  - `php artisan test --filter=DashboardTrendPanelsTest`
- Result: Passed.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=AnnouncementReadFlowTest`
  - `php artisan test --filter=AnnouncementCreationPermissionsTest`
  - `php artisan test --filter=DashboardTrendPanelsTest`
- Result: Passed.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=ConversationCreationTest`
  - `php artisan test --filter=ConversationSearchPreviewTest`
  - `php artisan test --filter=ConversationLockTest`
- Result: Passed.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=ConversationCreationTest`
  - `php artisan test --filter=RoleChangeRequestTest`
- Result: Passed.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=TriageBoardTest`
  - `php artisan test --filter=TicketIndexViewTest`
  - `php artisan test --filter=TicketFiltersTest`
- Result: Passed.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=KnowledgeSnippetSearchTest`
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=TicketIndexViewTest`
- Result: Passed.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=FastIssueReportingTest`
  - `php artisan test --filter=TicketOnBehalfTest`
  - `php artisan test --filter=TicketIndexViewTest`
- Result: Passed.

## Block: Testing
- Date: 2026-03-10
- Scope: Browser smoke expansion and local stability adjustment.
- Changes:
  - Expanded Playwright smoke coverage to include:
    - knowledge snippet search
    - fast report preset preload
    - announcement acknowledgement
    - manager attention queue visibility
  - Adjusted Playwright local execution to run serially against the single Laravel dev server so smoke tests are stable in this environment.
- Rationale:
  - The roadmap now spans multiple new UI surfaces and needed browser coverage beyond the original shell/navigation checks.
  - Serial execution avoids false negatives caused by overloading a single local `php artisan serve` instance with parallel browser workers.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `npm run test:ui`
  - `npx playwright test`
- Result:
  - `npm run test:ui` initially exposed Playwright concurrency instability and one locator collision; both were fixed.
  - Final `npx playwright test` passed with 8/8 smoke tests green against the seeded app.

## Block: UX Polish
- Date: 2026-03-10
- Scope: Consistency pass across newly added roadmap surfaces.
- Changes:
  - Added clearer back-navigation and cross-links on new pages such as:
    - knowledge snippets
    - fast report
    - support triage
  - Added context copy to reduce ambiguity on:
    - knowledge search results
    - fast report preset state
    - triage board usage
  - Tightened manager attention queue links so queue actions preserve department scope when opening the ticket list.
  - Added a clarification follow-up prompt on announcement acknowledgement when a user marks `Need Clarification`.
  - Removed minor dead view code from the triage board.
- Rationale:
  - The first implementation pass added several new surfaces quickly; this pass makes them feel like one product instead of separate feature drops.
  - Most polish work focused on orientation, action continuity, and reducing unnecessary user decisions.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=KnowledgeSnippetSearchTest`
  - `php artisan test --filter=FastIssueReportingTest`
  - `php artisan test --filter=AnnouncementReadFlowTest`
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=TriageBoardTest`
  - `npx playwright test`
- Result: Passed.

## Block: Parity
- Date: 2026-03-09
- Scope: Login quick-access parity and tickets CTA polish.
- Changes:
  - Added guide-style demo preset buttons on the Laravel login screen for seeded employee and manager entry.
  - Kept the configurable role/department demo selector as a secondary path for department-specific checks.
  - Added a persistent primary `Report issue` CTA to the tickets index header so the task surface keeps the guide's direct-create affordance.
- Rationale:
  - Close the remaining mismatch where Laravel had demo login capability but not the guide's fast-entry ergonomics.
  - Restore a first-class ticket creation action on the main tasks page instead of only relying on the sidebar or empty states.

## Block: Testing
- Date: 2026-03-09
- Scope: Browser-driven UI smoke coverage.
- Changes:
  - Added Playwright configuration targeting the local Laravel server with installed Chrome.
  - Added smoke coverage for:
    - demo login shell access
    - announcement search/open flow
    - inbox search/open flow
    - tickets CTA visibility
- Rationale:
  - Complement Laravel feature tests with real browser checks against rendered UI and navigation.

## Block: Validation
- Date: 2026-03-09
- Commands:
  - `php artisan test --filter=AuthenticationTest`
  - `php artisan test --filter=TicketIndexViewTest`
  - `npm run test:ui`
- Result: Passed.

## Block: Parity
- Date: 2026-02-24
- Scope: Manager dashboard parity refinements.
- Changes:
  - Added responsive hover interactions for productivity/quality trend charts.
  - Added SLA panel hover interactions and explanatory snapshot label.
  - Stabilized tooltip behavior for narrow/mobile screens.
- Rationale:
  - Match guide-style interactive readability while keeping values understandable in PHP app.

## Block: UX Decisions
- Date: 2026-02-24
- Topic: Time scale semantics
- Decision:
  - Keep `7d/24h/3h` controls for productivity/quality trends.
  - Treat SLA donut as real-time distribution of current open tickets (not time-bucket trend).
- Reason:
  - Productivity/quality are true time-series metrics.
  - SLA donut is composition at a point in time and is clearer when labeled as snapshot.

## Block: Next Enhancements
- Add drill-down from breach counts to filtered ticket list.
  - Example: click a breach data point -> open tasks view filtered by `breached` + date window.
- Add ticket-type breakdown to SLA area.
  - Example: `HR`, `Safety`, `Operations`, `Facilities` split with clickable filters.
- Add dedicated SLA trend chart (separate from donut).
  - Example: rolling `7d` breach trend with click-through to underlying tickets.

## Block: Validation
- Date: 2026-02-24
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=Dashboard`
- Result: Passed.

## Block: Parity
- Date: 2026-02-24
- Scope: Dashboard-to-Tasks drilldown and ticket-type filter flow.
- Changes:
  - Implemented clickable breach drilldown bars on manager dashboard that open Tasks pre-filtered by:
    - `breached=1`
    - day window (`from_date` + `to_date`)
    - manager department scope.
  - Added `Ticket Type Breakdown` card with clickable actions:
    - `View all` (type + period + department)
    - `Breaches` (type + period + department + breached only).
  - Extended ticket index backend filters to support:
    - `breached`
    - `category_id`
    - `from_date` / `to_date` date window.
  - Updated ticket list filter UI to expose these controls directly.
- Rationale:
  - Close actionability gap so dashboard insights can be investigated immediately in Tasks.

## Block: Validation
- Date: 2026-02-24
- Commands:
  - `php artisan test --filter=TicketFiltersTest`
  - `php artisan test --filter=DashboardTrendPanelsTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-24
- Scope: Click-through from productivity/quality trend points.
- Changes:
  - Added click actions on productivity chart point hit-zones to open `Tasks` with:
    - `department_id`
    - `from_date`
    - `to_date`
  - Added same click actions on quality chart point hit-zones with identical filter context.
  - Added in-card helper text to communicate that chart points are actionable.
- Rationale:
  - Convert trend visuals from passive display into direct operational navigation.

## Block: Validation
- Date: 2026-02-24
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=TicketFiltersTest`
- Result: Passed.

## Block: Hardening
- Date: 2026-03-10
- Scope: Permissions and edge-case audit for newly added roadmap surfaces.
- Changes:
  - Fixed support triage scoping so `ops_manager` users without any managed departments do not fall through to a global ticket queue.
  - Expanded knowledge snippet visibility so `admin` users can access `managers` audience content, matching the intended privileged audience model.
  - Added regression coverage for both cases:
    - triage empty scoped queue for unmanaged `ops_manager`
    - manager-only knowledge visibility for `admin`
- Rationale:
  - Prevent overexposure of ticket data through an empty scope edge case.
  - Align privileged content visibility with the rest of the role model instead of excluding admins from manager-level operational guidance.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=TriageBoardTest`
  - `php artisan test --filter=KnowledgeSnippetSearchTest`
- Result: Passed.

## Block: Hardening
- Date: 2026-03-10
- Scope: Second permissions and scope audit for announcements and manager dashboard surfaces.
- Changes:
  - Expanded manager-only announcement visibility to include `admin`, keeping announcement audience rules aligned with the privileged role model.
  - Added acknowledgement update regression coverage to prove repeated acknowledgement changes update a single read receipt instead of creating duplicates.
  - Fixed manager dashboard scope selection so operational panels and the attention queue fall back to a managed department when `primary_department_id` does not match actual managed assignments.
- Rationale:
  - Remove inconsistent privileged-access behavior between announcements and other manager-only content.
  - Protect acknowledgement analytics from duplicate receipt edge cases.
  - Prevent manager dashboards from showing empty or misleading department context due to stale or mismatched profile data.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=AnnouncementReadFlowTest`
  - `php artisan test --filter=DashboardTrendPanelsTest`
- Result: Passed.

## Block: Hardening
- Date: 2026-03-10
- Scope: Routed messaging fallback and direct-thread reuse audit.
- Changes:
  - Changed routed shortcut submission to return a validation error when the target contact is unavailable instead of failing with a hard `403`.
  - Added a self-recipient guard to routed shortcut resolution so malformed org data cannot route a conversation back to the sender.
  - Fixed direct-thread reuse to ignore locked threads and reuse the newest unlocked matching direct conversation when one exists.
  - Added regression coverage for:
    - forged shortcut submission without a valid recipient
    - locked-latest thread with older reusable unlocked direct conversation
- Rationale:
  - Make messaging failures recoverable in the UI instead of presenting authorization errors for missing routing data.
  - Prevent unnecessary duplicate direct conversations when the latest matching thread is locked but a valid reusable thread still exists.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=ConversationCreationTest`
  - `php artisan test --filter=ConversationLockTest`
- Result: Passed.

## Block: Testing
- Date: 2026-03-10
- Scope: Browser coverage expansion for hardened role and visibility flows.
- Changes:
  - Extended Playwright smoke coverage to include:
    - acknowledgement state switching from `Need Clarification` to `Understood`
    - manager visibility of manager-only knowledge snippets
    - employee exclusion from manager-only knowledge snippets
    - manager denial of triage board entry and direct route access
- Rationale:
  - Move browser coverage beyond happy-path navigation and lock in the role/scope behaviors hardened in the backend test passes.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `npx playwright test`
- Result: Passed (`12/12`).

## Block: Parity
- Date: 2026-02-25
- Scope: True multi-department seed distribution and manager/SLA data realism.
- Changes:
  - Refactored `DatabaseSeeder` to generate tickets across all core departments (`Customer Returns`, `Kariba`, `Inbound`, `ICQA`, `Outbound`, `Support`, `TOM`) with department-specific assignees/requesters.
  - Ensured each core department has a manager-role account (including `Support`) so demo manager login by department always resolves.
  - Added department-aware conversation seeding (one operational department conversation per department + participants from that department).
  - Replaced inbound-only hardcoded `DepartmentMetric` seed block with computed metrics:
    - clear old metrics
    - recalculate last 7 days via `DepartmentAnalyticsService`.
  - Fixed fallback category seed bug (`$people` reference) to use `Support`.
  - Made org-structure seeding rerun-safer with `updateOrCreate` for generated managers/employees and reporting relationships.
  - Tuned open-ticket timing distribution so manager SLA blocks show mixed within/breached states instead of near-100% breached.
  - Updated `DepartmentAnalyticsService::recalculateForDate()` to be date-scoped (ticket activity + end-of-day open snapshot), preventing flat identical values across all trend days.
- Rationale:
  - Ensure every manager department has meaningful seeded SLA/ticket/messaging data and prevent inbound-only bias.

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan migrate:fresh --seed`
  - `php artisan test --filter=DemoLoginTest`
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=TicketFiltersTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-24
- Scope: Department semantic refinement (Kariba context).
- Changes:
  - Updated mock department profile semantics for `Kariba` to reflect VNA workflow:
    - vendor-packed items are stowed without unboxing
    - faster downstream transfer/processing for requesting warehouses.
  - Added department context line in manager dashboard trend header:
    - department code + short department description.
- Rationale:
  - Keep prototype terminology aligned with real operation context for clearer interpretation by managers.

## Block: Validation
- Date: 2026-02-24
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-25
- Scope: Move mock operations schema from hardcoded controller logic to DB-backed profile data.
- Changes:
  - Added department operations profile schema fields via migration:
    - `ops_code`
    - `planned_headcount`
    - `target_units_per_hour`
    - `target_quality_pct`
    - `day_shift_start` / `day_shift_end`
    - `night_shift_start` / `night_shift_end`
  - Added `DepartmentOperationsProfileSeeder`:
    - seeds short-form departments for operational simulation (`Customer Returns`, `Kariba`, `Inbound`, `ICQA`, `Outbound`, `Support`, `TOM`)
    - seeds profile targets, headcount, shift windows, and descriptions (including Kariba VNA definition).
  - Updated `DatabaseSeeder` to call `DepartmentOperationsProfileSeeder`.
  - Updated `Department` model fillable/casts for operations profile fields.
  - Rewired dashboard manager KPI logic to read profile values from DB instead of hardcoded arrays.
- Rationale:
  - Keep mock data maintainable and ready for future replacement by live microservice feeds without controller rewrites.

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=TicketFiltersTest`
  - `php artisan test --filter=PublicDashboardTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-25
- Scope: Seed realism and public-facing dashboard content.
- Changes:
  - Improved ticket seed realism:
    - increased ticket volume and spread across recent days
    - priority-aware first-response and resolution timing variance
    - mixed healthy / near-threshold / breached open-ticket flows
    - realistic status-change and comment timestamps
  - Updated breach timeline behavior remains open-ticket aligned; data now has more natural day distribution after reseed.
  - Added dedicated ticket categories for operational triage:
    - `Safety`, `HR`, `Facilities`, `IT Support`, `Operations`, `Transport`
  - Added always-visible public info content:
    - new `General Information` quick-link category
    - links such as parking rota, shift timetable, transport, canteen, site map
    - public announcements for parking rota + shift reminders.
- Rationale:
  - Reduce unrealistic all-or-nothing breach patterns and restore practical public information presence on landing/dashboard.

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=PublicDashboardTest`
  - `php artisan test --filter=TicketFiltersTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-25
- Scope: Linktree-only quick links and department dropdown de-duplication.
- Changes:
  - Dashboard quick-link query now includes only categories with active links.
  - Removed seeded `General Information` quick-link category and cleanup logic deletes legacy instances.
  - Demo login department list now uses operational shortlist only:
    - `Customer Returns`, `Kariba`, `Inbound`, `ICQA`, `Outbound`, `Support`, `TOM`
  - Falls back to full list only if shortlist departments are unavailable.
- Rationale:
  - Keep quick links aligned with Linktree content and remove duplicate/noisy department options in demo entry flow.

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan test --filter=DemoLoginTest`
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=PublicDashboardTest`
- Result: Passed.

## Block: Fixes
- Date: 2026-02-25
- Scope: Internal server error on dashboard (`department_metrics` unique constraint).
- Issue:
  - `DepartmentAnalyticsService` could hit duplicate insert on `(department_id, metric_date)` during recalculation.
- Changes:
  - Replaced per-row `updateOrCreate` with atomic `upsert` in `DepartmentAnalyticsService`.
  - Preserved `metric_date` storage format expected by analytics tests.
- Rationale:
  - Prevent race/conflict writes and stabilize homepage/dashboard metric refresh.

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan test --filter=DepartmentMetricsCommandTest`
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=PublicDashboardTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-25
- Scope: SLA block data consistency fix.
- Changes:
  - Switched manager SLA Health calculation from `department_metrics`-derived snapshot inputs to live open ticket dataset for the manager's department.
  - SLA donut segments now use current open ticket counts:
    - within SLA
    - at risk
    - breached
  - Added explicit count + percentage display on SLA legend rows.
  - Added open ticket count in SLA card subtitle for quick validation.
- Rationale:
  - Resolve mismatch where SLA donut could show no green despite existing non-breached open tickets.

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=PublicDashboardTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-25
- Scope: Realism fix for breach distribution and ticket timing.
- Changes:
  - Updated manager daily breach timeline to use **open-ticket-only** dataset (no mixed closed-ticket counts).
  - Refactored ticket seeding to produce realistic lifecycle timing:
    - priority-based first response and resolution targets
    - varied within-target and breached outcomes
    - spread `created_at`, `updated_at`, status-change timestamps, and comment timestamps
    - mixed open vs resolved/closed flows with non-uniform timing.
- Rationale:
  - Fix unrealistic pattern (single-day spike with zeros elsewhere) and improve mock-data credibility for SLA/performance views.

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=TicketFiltersTest`
  - `php artisan test --filter=PublicDashboardTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-25
- Scope: Daily Breach Drilldown whitespace and sparse-data UX improvement.
- Changes:
  - Added adaptive layout to `Daily Breach Drilldown`:
    - sparse data (0-1 non-zero breach days) => compact day-card grid
    - normal data => compacted bar chart height
  - Added 7-day breach total summary in block header.
  - Preserved click-through behavior to breached ticket filters for each day.
- Rationale:
  - Remove excessive blank vertical space when breach distribution is sparse (e.g., one spike day).

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=PublicDashboardTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-25
- Scope: Ticket Type Breakdown semantics update.
- Changes:
  - Decoupled `Ticket Type Breakdown` from `7d/24h/3h` scale.
  - Breakdown now represents **active open tickets now** for manager’s department.
  - Updated block subtitle/copy to reflect active-queue semantics.
  - Removed window date filters from type breakdown links.
- Rationale:
  - This block is operational triage focused and should reflect current actionable queue, not historical window totals.

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=PublicDashboardTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-25
- Scope: Remove legacy duplicate SLA widget from dashboard.
- Changes:
  - Removed `dashboard.partials.analytics-widget` include from main dashboard content.
  - Deleted unused `resources/views/dashboard/partials/analytics-widget.blade.php`.
- Rationale:
  - Widget duplicated SLA information already covered by aligned blocks:
    - Daily Breach Drilldown
    - SLA Health
    - Ticket Type Breakdown
  - Eliminates broken/irrelevant `View analytics` link path from manager dashboard surface.

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=PublicDashboardTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-25
- Scope: Manager dashboard SLA de-duplication and relevance cleanup.
- Changes:
  - Kept SLA-relevant blocks:
    - `Daily Breach Drilldown`
    - `SLA Health`
    - `Ticket Type Breakdown`
  - Removed duplicate SLA messaging from `Team Performance` callout.
  - Simplified `Benchmark Comparison` to resolution-only (removed SLA adherence comparison card).
- Rationale:
  - Reduce repeated SLA concepts on one page and keep only actionable/clear SLA context.

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=PublicDashboardTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-25
- Scope: Full SLA block alignment on manager dashboard.
- Changes:
  - Added ticket-derived daily SLA timeline generator (`buildManagerSlaTimeline`) from live department tickets.
  - Rewired `Daily Breach Drilldown` chart to use ticket-derived timeline instead of `department_metrics` snapshots.
  - Changed drilldown bar scale to breached-ticket counts/day (same data source as click-through filter).
  - Kept date-click behavior, now guaranteed to align with ticket-filtered results.
- Rationale:
  - Remove mixed data-source behavior across SLA visuals and ensure consistent interpretation of green/amber/red context.

## Block: Validation
- Date: 2026-02-25
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=PublicDashboardTest`
- Result: Passed.

## Block: Parity
- Date: 2026-02-24
- Scope: Shift-aware manager KPI model and health banding.
- Changes:
  - Reworked manager KPI generation to a shift-based operational model.
  - Added department mock profiles (short list from CWL1 availability references):
    - `Customer Returns`
    - `Kariba`
    - `Inbound`
    - `ICQA`
    - `Outbound`
    - `Support`
    - `TOM`
  - Added shift assumptions:
    - Day shift: `10:00-20:00`
    - Night shift: `18:30-04:45`
  - KPI calculations now include:
    - active vs planned staffing
    - shift target units
    - current shift units processed
    - avg productivity vs target %
    - quality vs target %
  - Added status band classification:
    - `>= 90%` green
    - `70%-89.9%` amber
    - `< 70%` red
  - Updated top manager cards to display shift context and status color semantics.
  - Refined mock series generation to reflect realistic behavior:
    - occasional above-target periods
    - quality generally near high-90s with variance
    - stronger early performance with mild end-of-window drop.
- Rationale:
  - Move from generic approximations to realistic operational simulation suitable for prototype parity and future replacement with live microservice feeds.

## Block: Validation
- Date: 2026-02-24
- Commands:
  - `php artisan test --filter=DashboardTrendPanelsTest`
  - `php artisan test --filter=TicketFiltersTest`
- Result: Passed.
