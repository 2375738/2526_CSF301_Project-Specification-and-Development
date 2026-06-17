# Implementation Log

## Purpose
Track what changed, why it changed, and what remains, without overloading `PARITY_MATRIX.md`.

## Block: Tab UX Review Matrix
- Date: 2026-03-11
- Scope: UX review artifact for first-level navigation and tab expectations across user roles.
- Reason:
  - The current app shell needs a clearer comparison against standard app conventions, the sample guide shell, and the Laravel implementation by role.
  - A dedicated matrix was added to support review before navigation changes are implemented.
- Validation evidence:
  - Reviewed current shell in `site-bulletin/resources/views/layouts/app.blade.php`.
  - Reviewed route availability in `site-bulletin/routes/web.php`.
  - Reviewed sample shell in `Site Bulletin Implementation Guide/src/app/App.tsx`.
  - Added `TAB_UX_COMPARISON_MATRIX.md` as a repo-level comparison artifact.

## Block: Fixed Header And Profile Dropdown
- Date: 2026-03-11
- Scope: Desktop app-shell header and wide sidebar cleanup.
- Reason:
  - The authenticated top bar needed to stay fixed for easier access to account actions.
  - The user requested a profile-picture style trigger near the top-right actions, with a dropdown similar to the reference shell.
  - Wide desktop navigation was simplified by removing the always-visible dot markers from sidebar items.
  - Header branding was expanded to include the site logo and product name on wide screens.
  - Branding visibility was adjusted so the sidebar logo shows at the top of the page and the fixed header logo appears only after scrolling.
- Validation evidence:
  - Updated `site-bulletin/resources/views/layouts/app.blade.php`.
  - Test command: `php artisan test tests/Feature/LayoutNavigationTest.php`

## Block: Dashboard Hero Compression
- Date: 2026-03-11
- Scope: Top section of the authenticated dashboard landing page.
- Reason:
  - The original hero banner consumed too much vertical space and behaved more like a marketing panel than an operational dashboard header.
  - The dashboard was updated to use a smaller operational summary strip with compact copy and quick actions so task-focused content appears earlier.
- Validation evidence:
  - Updated `site-bulletin/resources/views/dashboard/partials/content.blade.php`.
  - Test command: `php artisan test tests/Feature/DashboardTrendPanelsTest.php`

## Block: Figma Navigation And Dashboard Pass
- Date: 2026-03-11
- Scope: Figma-aligned shell navigation and dashboard information styling.
- Reason:
  - The active UX focus was narrowed to Figma-related improvements only.
  - Primary navigation needed icon support, unread badge support, and a more prototype-like mobile bottom bar.
  - Employee dashboard summary cards needed a cleaner label/value/context hierarchy with light motion and stronger visual anchors.
- Validation evidence:
  - Updated `TAB_UX_COMPARISON_MATRIX.md`.
  - Updated `site-bulletin/resources/views/layouts/app.blade.php`.
  - Added `site-bulletin/resources/views/components/nav-icon.blade.php`.
  - Updated `site-bulletin/resources/views/dashboard/partials/my-work-today.blade.php`.
  - Updated `site-bulletin/resources/css/app.css`.
  - Test command: `php artisan test tests/Feature/LayoutNavigationTest.php tests/Feature/DashboardTrendPanelsTest.php`

## Block: Employee Performance Persistence And Trend Fix
- Date: 2026-03-11
- Scope: Employee performance data model and employee dashboard trend charts.
- Reason:
  - Employee quality was previously derived from rank percentile rather than stored as a real persisted metric.
  - Employee `24h` and `3h` trend views were previously simulated rather than backed by stored intraday samples.
  - Employee chart axis bounds needed correction so productivity target lines and lower quality values remain visible.
- Validation evidence:
  - Added `site-bulletin/database/migrations/2026_03_11_150000_add_quality_score_to_performance_snapshots_table.php`.
  - Added `site-bulletin/database/migrations/2026_03_11_151000_create_performance_samples_table.php`.
  - Added `site-bulletin/app/Models/PerformanceSample.php`.
  - Updated `site-bulletin/app/Models/User.php`.
  - Updated `site-bulletin/app/Models/PerformanceSnapshot.php`.
  - Updated `site-bulletin/database/factories/PerformanceSnapshotFactory.php`.
  - Updated `site-bulletin/database/seeders/DatabaseSeeder.php`.
  - Updated `site-bulletin/app/Http/Controllers/Public/DashboardController.php`.
  - Updated `site-bulletin/resources/views/dashboard/partials/trend-panels.blade.php`.
  - Updated Filament performance snapshot form/table schema.
  - Test command: `php artisan test tests/Feature/DashboardTrendPanelsTest.php`
  - Seeder follow-up: replaced weekly employee performance snapshot `updateOrCreate` flow with bulk `upsert` to preserve idempotent seeding against the unique `(user_id, week_start)` key.

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

## Block: Product Spec
- Date: 2026-03-10
- Scope: Ticketing redesign assumptions for template-driven, role-aware operational workflows.
- Context:
  - Ticket creation should be fast and template-led, especially for floor employees using mobile devices during shift.
  - Not all tickets should expose the same fields to all roles.
  - Different resolver groups work different patterns:
    - employees and managers may operate days or nights
    - admin/support functions are mostly day-shift and often 5-of-7
    - night HR support should be treated as limited-scope rather than full-service
- Product decision:
  - Move away from a single generic ticket form toward scenario templates with prefilled defaults, conditional fields, and role-based routing.

## Block: Ticketing Use Cases
- Date: 2026-03-10
- Scope: Role-based scenarios that should define template design.
- Employee-initiated scenarios:
  - Equipment issue:
    - scanner not working
    - printer jam
    - station screen frozen
    - battery / charger missing
  - Facility issue:
    - damaged station
    - broken chair / desk
    - spill / hazard
    - blocked walkway
  - HR self-service request:
    - missed punch
    - clock-in / clock-out correction
    - shift swap request
    - attendance clarification
  - Transport / access:
    - shuttle missing
    - parking problem
    - badge / access issue
  - Quality / process clarification:
    - conflicting instruction
    - missing SOP
    - repeated defect pattern
- Manager-initiated scenarios:
  - on-behalf ticket for employee blocker
  - repeated equipment issue affecting multiple associates
  - department escalation for facilities / IT / transport
  - attendance or clock adjustment approval routing
  - shift swap endorsement
  - investigation or incident escalation with internal notes
- Admin / support / HR scenarios:
  - queue triage and assignment
  - request approval / rejection
  - duplicate merge
  - site-wide incident coordination
  - internal-only note exchange
  - audit-facing resolution evidence collection

## Block: Template Catalogue
- Date: 2026-03-10
- Scope: Proposed first-pass ticket templates for the prototype.
- Employee templates:
  - `scanner_issue`
    - category: IT Support / Operations
    - default priority: medium
    - key fields:
      - station or area
      - device asset tag
      - issue type
      - blocking work now? `yes/no`
      - photo optional
  - `station_equipment_fault`
    - category: Facilities / Operations
    - default priority: medium
    - key fields:
      - area
      - equipment type
      - safe to continue working? `yes/no`
      - photo optional
  - `safety_hazard`
    - category: Safety / Facilities
    - default priority: high
    - key fields:
      - location
      - hazard type
      - immediate danger? `yes/no`
      - photo optional
      - witness optional
  - `missed_punch`
    - category: HR
    - default priority: low
    - key fields:
      - shift date
      - expected start time
      - expected end time
      - clock event type `in/out/both`
      - screenshot optional
  - `shift_swap_request`
    - category: HR
    - default priority: low
    - key fields:
      - requester shift
      - requested replacement date
      - swap partner name or employee id
      - manager informed? `yes/no`
  - `transport_issue`
    - category: Transport
    - default priority: medium
    - key fields:
      - route
      - stop
      - travel direction
      - service missing / late / full
      - photo optional
- Manager templates:
  - `report_on_behalf`
    - category: variable
    - default priority: medium
    - key fields:
      - affected employee
      - impact scope `single station / small area / department`
      - manager summary
      - attachments optional
  - `department_blocker`
    - category: Operations / Facilities / IT
    - default priority: high
    - key fields:
      - department
      - affected headcount
      - work around available? `yes/no`
      - estimated operational impact
      - photo optional
  - `attendance_adjustment_approval`
    - category: HR
    - default priority: low
    - key fields:
      - employee
      - adjustment reason
      - manager recommendation
      - supporting screenshot optional
  - `incident_escalation`
    - category: Safety / Operations
    - default priority: high
    - key fields:
      - incident summary
      - who is affected
      - containment action taken
      - evidence attachments
- Admin / support templates:
  - `triage_assignment`
    - internal workflow template for assignment / reassignment
  - `resolver_follow_up`
    - internal request for more information from requester or manager
  - `approval_outcome`
    - approve / reject / needs-more-info for HR-style requests
  - `duplicate_merge`
    - internal template to link duplicate tickets to a primary case

## Block: Ticket Attributes
- Date: 2026-03-10
- Scope: Suggested field model by visibility and function.
- Core attributes for all tickets:
  - template key
  - category
  - requester
  - created for
  - department
  - location / area
  - priority
  - status
  - short title
  - structured detail payload
  - assigned team
  - assignee
  - due-by or target response window
  - working-hours calendar key
- Employee-visible attributes:
  - title
  - plain-language status
  - next step
  - expected responder group
  - latest public update
  - attachments they uploaded
  - approval state if relevant
  - ETA band `today / next shift / awaiting weekday team / more info needed`
- Manager-visible attributes:
  - all employee-visible fields
  - employee impact
  - affected shift
  - on-behalf relationship
  - department impact
  - internal routing summary where appropriate
- Support/admin-visible attributes:
  - all operational fields
  - private notes
  - assignment history
  - duplicate linkage
  - approval audit trail
  - hidden resolution notes
  - SLA stop/start state
  - shift-calendar adjusted due times
- Hidden from employees by default:
  - private comments
  - reassignment rationale
  - approval deliberation notes
  - duplicate merge reasoning
  - internal severity score
  - resolver staffing notes

## Block: Attachments And Evidence
- Date: 2026-03-10
- Scope: Attachment policy assumptions for prototype and future production hardening.
- Required capability:
  - keep general attachments on tickets
  - classify evidence by type:
    - photo
    - screenshot
    - document
    - timesheet evidence
    - manager-only note attachment
- Recommended rules:
  - employee templates should allow optional photo/screenshot upload where relevant
  - HR-style templates should allow screenshot/document evidence
  - safety and incident templates should strongly encourage evidence
  - private/internal evidence should be markable as resolver-only
- Future schema suggestion:
  - extend `ticket_attachments` with:
    - `visibility` (`public`, `internal`)
    - `kind` (`photo`, `screenshot`, `document`, `other`)
    - `label` or `description`

## Block: Ticketing Process
- Date: 2026-03-10
- Scope: Proposed lifecycle and ownership model by ticket type.
- Process model:
  - Initiation:
    - employee starts a template ticket directly
    - manager starts on-behalf or escalation ticket
    - admin/support can open internal workflow tickets
  - Auto-routing:
    - template selects default assigned team
    - department and shift context influence queue
    - some templates route first to manager approval before support queue
  - Triage:
    - support/admin/HR confirms template, priority, and owner
    - duplicate check performed for repeated incidents
  - Resolver action:
    - resolver adds public update or internal note
    - requester may be asked for more info
  - Approval outcome:
    - some templates require explicit approve/reject
    - outcome becomes visible to requester in plain language
  - Closure:
    - close with public resolution summary
    - keep internal notes private
- Approval examples:
  - `missed_punch`
    - initiated by employee
    - manager confirms if needed
    - HR resolves
  - `shift_swap_request`
    - initiated by employee
    - manager approves or rejects first
    - HR finalizes if site policy requires HR involvement
  - `scanner_issue`
    - initiated by employee or manager
    - no approval needed
    - routes straight to support / operations
  - `department_blocker`
    - initiated by manager
    - no approval needed
    - immediate ops/support triage

## Block: Shift-Aware SLA Assumptions
- Date: 2026-03-10
- Scope: Response expectations shaped by different working patterns.
- Suggested service calendars:
  - `24x7_ops`
    - operations, safety, critical facilities, active shift blockers
  - `site_days`
    - standard admin/support functions
    - mostly Monday-Friday daytime
  - `limited_night_hr`
    - basic HR night coverage for clock and attendance corrections only
- Suggested response bands:
  - active shift blocker:
    - first response within `15-30 minutes`
    - target resolution `same shift`
  - safety hazard:
    - immediate triage
    - target containment `within 15 minutes`
  - standard equipment fault:
    - first response within `1 hour`
    - resolution `same shift or next working shift`
  - missed punch:
    - first response `same working day`
    - resolution `within 1-2 admin working days`
  - shift swap:
    - manager decision `within 1 working day`
    - HR finalization `within 1-2 working days` if applicable
- UX requirement:
  - do not expose raw timestamps only
  - show adjusted language such as:
    - `Awaiting weekday HR team`
    - `Queued for next day-shift support window`
    - `Night shift coverage can only approve basic attendance fixes`

## Block: Database Direction
- Date: 2026-03-10
- Scope: Storage recommendation for prototype vs future production use.
- SQLite assessment:
  - fine for coursework prototype, local development, and low-concurrency demo flows
  - not ideal for heavier concurrent updates, queue workers, richer reporting, and attachment-heavy operational use
- Recommendation:
  - keep SQLite for fast prototype iteration right now
  - design next schema changes so migration to PostgreSQL is easy
- Preferred future target:
  - PostgreSQL
- Reason:
  - better concurrency characteristics
  - stronger indexing and reporting options
  - better path for JSON template payloads, audit queries, and operational analytics
- Do not optimize prematurely:
  - no immediate DB switch is required unless prototype constraints start blocking development or testing

## Block: Suggested Schema Changes
- Date: 2026-03-10
- Scope: Candidate migration work for a future ticket-template implementation pass.
- Candidate new tables:
  - `ticket_templates`
    - `key`
    - `name`
    - `requester_role`
    - `category_id`
    - `default_priority`
    - `assigned_team`
    - `requires_manager_approval`
    - `requires_hr_approval`
    - `working_hours_calendar`
    - `config_json`
  - `ticket_approvals`
    - `ticket_id`
    - `step`
    - `approver_id`
    - `status`
    - `decision_notes`
    - `decided_at`
- Candidate new columns on `tickets`:
  - `template_key`
  - `assigned_team`
  - `public_status_label`
  - `next_action_label`
  - `target_starts_at`
  - `target_resolves_at`
  - `working_hours_calendar`
  - `details_json`
- Candidate new columns on `ticket_comments`:
  - keep `is_private`
  - optionally add `comment_type` (`public_update`, `internal_note`, `request_for_info`, `approval_note`)

## Block: Recommended Delivery Order
- Date: 2026-03-10
- Scope: Suggested implementation sequence for the ticketing redesign.
- Order:
  - first:
    - define template model and static config
    - keep existing generic ticket flow as fallback
  - second:
    - implement employee templates:
      - `scanner_issue`
      - `safety_hazard`
      - `missed_punch`
      - `shift_swap_request`
  - third:
    - add approval model for attendance and shift-change requests
  - fourth:
    - add assigned-team and shift-aware ETA presentation on ticket detail
  - fifth:
    - add private/internal workflow actions on triage board

## Block: Product Build
- Date: 2026-03-10
- Scope: First implementation pass for static ticket templates in the Laravel report flow.
- Changes:
  - Added static template configuration in `site-bulletin/config/ticket_templates.php`.
  - Reworked the ticket report controller to load role-filtered templates instead of hardcoded fast-report presets.
  - Wired the report page to:
    - preload title, description, location, category, and default priority from template config
    - show template-specific prompts and evidence hints
    - keep the generic ticket form as a fallback when no template is selected
  - Added manager-only templates for:
    - `report_on_behalf`
    - `department_blocker`
  - Template default priority is now applied when the ticket is actually created.
- Rationale:
  - Establish the ticket-template model without requiring schema changes yet.
  - Keep implementation low-risk by layering templates on top of the existing ticket workflow first.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=FastIssueReportingTest`
  - `npx playwright test tests/Browser/smoke.spec.ts`
- Result: Passed.

## Block: Product Build
- Date: 2026-03-10
- Scope: Second ticket-template pass with schema-backed template identity and structured details.
- Changes:
  - Added ticket persistence fields:
    - `template_key`
    - `details_json`
  - Extended the report flow to save template-specific structured answers alongside the main ticket description.
  - Updated ticket detail to show:
    - template label
    - structured template answers in a dedicated summary block
  - Kept the generic description as the main human-readable narrative, with structured details acting as supplemental context.
- Rationale:
  - Move template support from UI-only preload into actual persisted ticket data.
  - Prepare the model for a later approval/routing phase without introducing that complexity yet.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=FastIssueReportingTest`
  - `php artisan test --filter=TicketLifecycleTransparencyTest`
  - `npx playwright test tests/Browser/smoke.spec.ts`
- Result: Passed.

## Block: Product Build
- Date: 2026-03-10
- Scope: First approval and routing layer for approval-backed ticket templates.
- Changes:
  - Added `ticket_approvals` persistence with:
    - approver role
    - approval status
    - public note
    - internal note
    - approver identity and decision timestamp
  - Added approval config for:
    - `missed_punch` -> HR review
    - `shift_swap_request` -> manager review
  - Ticket creation now auto-creates a pending approval record for approval-backed templates.
  - Ticket detail now shows a plain-language approval state for requesters and decision controls for eligible managers / HR / admin users.
  - Approval decisions currently drive ticket status as follows:
    - `approved` -> `resolved`
    - `rejected` -> `cancelled`
    - `needs_info` -> `waiting_employee`
- Rationale:
  - Introduce a usable approval-backed workflow without yet committing to full multi-step approval orchestration.
  - Keep requester communication simple while preserving internal notes for resolver roles.

## Block: Validation
- Date: 2026-03-10
- Commands:
  - `php artisan test --filter=TicketApprovalFlowTest`
  - `php artisan test --filter=FastIssueReportingTest`
  - `php artisan test --filter=TicketLifecycleTransparencyTest`
  - `npx playwright test tests/Browser/smoke.spec.ts`
- Result: Passed.

## Block: Product Build
- Date: 2026-03-11
- Scope: Multi-step approval extension for shift swaps and lightweight approval queue visibility.
- Changes:
  - Extended ticket approvals with queued follow-up steps and explicit `step_order`.
  - `shift_swap_request` now creates:
    - step 1: manager review (`pending`)
    - step 2: HR review (`queued`)
  - When manager review is approved:
    - HR review becomes active
    - ticket moves to `triaged` instead of resolving immediately
  - Added a lightweight `Approvals Waiting For You` queue on the ticket index for manager/HR/admin users.
  - Ticket detail now shows:
    - step numbers
    - queued-step messaging
    - requester-facing text for the next review stage
- Rationale:
  - Align shift swap workflow more closely with the intended real-world pattern where manager approval is not necessarily final.
  - Expose pending approval work without requiring a separate full queue application yet.

## Block: Validation
- Date: 2026-03-11
- Commands:
  - `php artisan test --filter=TicketApprovalFlowTest`
  - `php artisan test --filter=FastIssueReportingTest`
  - `php artisan test --filter=TicketIndexViewTest`
  - `npx playwright test tests/Browser/smoke.spec.ts`
- Result: Passed.

## Block: Product Build
- Date: 2026-03-11
- Scope: Approval messaging refinement and final HR approval completion path.
- Changes:
  - Added requester-facing approval guidance that distinguishes:
    - current manager review
    - queued HR finalization
    - active weekday HR review
    - completed approval chain
  - Added explicit final HR approval path for shift swaps so the second step resolves the ticket when completed.
  - Split approval presentation into:
    - current approval state
    - separate approval history
  - Kept internal approval notes restricted to manager/HR/admin viewers.
- Rationale:
  - Make the approval workflow understandable to employees without exposing internal resolver context.
  - Reflect the real-world distinction between current approval step and later weekday HR finalization.

## Block: Validation
- Date: 2026-03-11
- Commands:
  - `php artisan test --filter=TicketApprovalFlowTest`
  - `php artisan test --filter=TicketLifecycleTransparencyTest`
  - `npx playwright test tests/Browser/smoke.spec.ts`
- Result: Passed.

## Block: Product Build
- Date: 2026-03-11
- Scope: Dedicated approval workbench for manager and HR decision queues.
- Changes:
  - Added a dedicated approval queue page at `tickets/approvals` for `manager`, `ops_manager`, `hr`, and `admin`.
  - The new workbench shows:
    - queue summary counts for `pending`, `queued`, `needs_info`, and completed approval steps
    - searchable approval list with filters for status, reviewer role, step, and department
    - direct links back to the underlying ticket detail page
  - Approval scoping is now explicit:
    - `hr` and `admin` can see all approval steps
    - `manager` and `ops_manager` only see manager-review steps for their managed departments
    - users without approval roles are blocked at the route level
  - Added a new `Approval workbench` entry point from the tickets index page.
- Rationale:
  - The lightweight approval cards on the ticket index were sufficient for first-pass visibility, but not for actual review work.
  - Manager and HR users need a queue-oriented workbench so approval-backed templates can scale beyond one or two visible cards.

## Block: Testing
- Date: 2026-03-11
- Scope: Browser smoke stability for schema-backed approval features.
- Changes:
  - Updated Playwright web server bootstrap to run `php artisan migrate --force` before starting the local Laravel server.
  - Disabled Playwright `reuseExistingServer` so new routes and migrations are not hidden behind a stale long-running dev server process.
  - Added browser smoke coverage for opening the approval workbench from the tickets page as a manager.
- Rationale:
  - Schema-backed features such as ticket approvals were failing in browser tests when the reused dev database had not been migrated.
  - Restarting the local server per Playwright run removes stale route-state and migration drift from the smoke pipeline.

## Block: Validation
- Date: 2026-03-11
- Commands:
  - `php artisan test --filter=TicketApprovalQueueTest`
  - `php artisan test --filter=TicketApprovalFlowTest`
  - `npx playwright test tests/Browser/smoke.spec.ts --grep "approval workbench|triage board"`
- Result: Passed.

## Block: Product Build
- Date: 2026-03-11
- Scope: Typed ticket evidence and attachment visibility controls.
- Changes:
  - Extended `ticket_attachments` with:
    - `visibility` (`public`, `internal`)
    - `kind` (`photo`, `screenshot`, `document`, `timesheet`, `other`)
    - `label`
  - Updated ticket attachment uploads so:
    - employees can upload typed evidence but their attachments are always forced to `public`
    - manager, ops manager, HR, and admin users can mark an attachment as `internal only`
  - Ticket detail now:
    - shows template evidence guidance when available
    - labels evidence by kind
    - shows internal-visibility badges only to privileged users
    - hides internal-only attachments from employee requesters
  - Attachment downloads now enforce attachment visibility, not just ticket visibility.
- Rationale:
  - The ticketing model needed typed evidence to support HR-style proof, safety photos, and resolver documents in a consistent way.
  - Visibility controls are required so internal manager or HR evidence does not leak back to requesters.

## Block: Validation
- Date: 2026-03-11
- Commands:
  - `php artisan migrate --force`
  - `php artisan test --filter=TicketAttachmentVisibilityTest`
  - `php artisan test --filter=TicketLifecycleTransparencyTest`
  - `php artisan test --filter=TicketApprovalQueueTest`
- Result: Passed.

## Block: Product Build
- Date: 2026-03-11
- Scope: Direct approval actions on the approval workbench.
- Changes:
  - Extended the approval workbench so pending approvals can now be decided directly from the queue page.
  - Added inline decision controls for:
    - `Approve`
    - `Need Info`
    - `Reject`
  - Added separate fields for:
    - requester-visible public note
    - manager or HR internal note
  - Added queue-state guidance on the workbench:
    - queued steps are explicitly marked as not yet actionable
    - `needs_info` steps are marked as paused
    - completed steps point back to full ticket history
- Rationale:
  - The dedicated queue page needed to be operationally useful, not just a list of links.
  - Direct decision controls reduce context switching for manager and HR users handling routine approval traffic.

## Block: Validation
- Date: 2026-03-11
- Commands:
  - `php artisan test --filter=TicketApprovalQueueTest`
  - `php artisan test --filter=TicketApprovalFlowTest`
- Result: Passed.

## Block: Product Build
- Date: 2026-03-11
- Scope: Approval workbench prioritization and grouped queue buckets.
- Changes:
  - Refined the approval workbench with grouped queue buckets above the full result list:
    - `Waiting Now`
    - `Paused Waiting On Requester`
    - `Completed Recently`
  - Bucket contents are derived from the same filtered queue scope so reviewers can quickly focus on actionable work without losing filter context.
  - Refactored the main approval card into a shared partial so direct decision controls and queue-state messaging stay consistent across the page.
- Rationale:
  - A flat approval list still required too much manual scanning for manager and HR users.
  - Grouped buckets make the workbench usable as a real operational queue rather than only a searchable archive of approval rows.

## Block: Validation
- Date: 2026-03-11
- Commands:
  - `php artisan test --filter=TicketApprovalQueueTest`
  - `php artisan test --filter=TicketApprovalFlowTest`
- Result: Passed.

## Block: Testing
- Date: 2026-03-11
- Scope: Browser coverage expansion for approval workbench actions.
- Changes:
  - Extended Playwright smoke coverage to verify:
    - manager can open the approval workbench
    - employee can submit a `shift_swap_request`
    - manager can find that request in the approval workbench
    - manager can approve the request inline from the workbench
  - Added a browser-safe logout helper to support multi-role end-to-end approval testing in a single smoke flow.
- Rationale:
  - Approval workbench behavior now includes direct inline decisions, so browser coverage needed to move beyond simple page-load checks.
  - This locks in the full employee-to-manager approval path at the UI layer, not just controller tests.

## Block: Validation
- Date: 2026-03-11
- Commands:
  - `npx playwright test tests/Browser/smoke.spec.ts --grep "approval workbench|shift swap request and manager can approve"`
- Result: Passed.

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
## 2026-03-11 - Unified performance sample source for dashboard charts and employee cards
- Scope: `site-bulletin/app/Http/Controllers/Public/DashboardController.php`, `site-bulletin/resources/views/dashboard/partials/my-work-today.blade.php`, `site-bulletin/database/seeders/DatabaseSeeder.php`
- Reason: employee dashboard cards and charts were reading different layers of mock data, and manager performance charts were still synthetic rather than aggregating from the same per-user sample stream. This made one user's displayed productivity and quality feel unsynchronized across views.
- Change:
  - made 15-minute `performance_samples` the seeded source of truth for user performance and quality across the last 6 weeks
  - derived weekly `performance_snapshots` from those samples during seeding instead of maintaining an unrelated parallel dataset
  - updated employee `My Work Today` cards to read recent sample windows for current throughput, quality score, and hour-over-hour deltas
  - updated employee wording from snapshot/rank language to current sample-window language
  - updated manager trend data to aggregate department-level performance and quality from the same per-user sample table, with fallback to the prior synthetic/metric path when tests or sparse fixtures do not seed samples
- Validation:
  - `php artisan migrate:fresh --seed`
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php`

## 2026-03-11 - Announcement feed converted toward news-style updates
- Scope: `site-bulletin/database/seeders/DatabaseSeeder.php`, `site-bulletin/resources/views/announcements/index.blade.php`, `site-bulletin/resources/views/dashboard/partials/content.blade.php`, `site-bulletin/resources/views/dashboard/partials/news-widget.blade.php`
- Reason: announcement records and cards felt generic and low-signal, even though the app already had a clickable detail flow. The feed needed more concrete operational content and clearer news-like presentation.
- Change:
  - removed generic factory-seeded announcement filler and replaced it with a fixed set of site-relevant operational, transport, training, and department updates
  - made dashboard and announcement-index cards read more like news items with stronger headlines, excerpts, metadata, and explicit read/open actions
  - kept the existing announcement detail page and acknowledgement flow as the full-content destination
- Validation:
  - `php artisan db:seed`
  - `php artisan test tests/Feature/PublicDashboardNewsWidgetTest.php tests/Feature/Announcements/AnnouncementReadFlowTest.php tests/Feature/Announcements/AnnouncementCreationPermissionsTest.php`

## 2026-03-11 - Quick link category icons made category-specific
- Scope: `site-bulletin/resources/views/dashboard/partials/content.blade.php`
- Reason: the quick-link cards were using one generic icon for every category, which made the block feel repetitive and low-information.
- Change:
  - replaced the single reused category glyph with simple per-category icon mapping based on category name
  - current mappings include megaphone for `Hot Topics`, briefcase for `Current Vacancies`, building for `My Site`, sparkles for `Diversity, Equity & Inclusion`, wrench for `Site Tools`, and people for `PxT` / HR-style categories
- Validation:
  - `php artisan view:clear`
  - `php artisan test tests/Feature/PublicDashboardNewsWidgetTest.php tests/Feature/DashboardTrendPanelsTest.php`

## 2026-03-12 - Guest landing intro simplified
- Scope: `site-bulletin/resources/views/dashboard/partials/content.blade.php`
- Reason: the logged-out landing state was showing dashboard-style number cards with low value before sign-in, and the black intro panel felt too heavy for a login-first entry experience.
- Change:
  - removed the summary number cards for guests while keeping them for authenticated users
  - shifted the guest intro panel from black to a softer navy/slate-blue gradient
  - updated guest-facing copy to explain the product more directly
- Validation:
  - `php artisan test tests/Feature/PublicDashboardNewsWidgetTest.php tests/Feature/DashboardTrendPanelsTest.php`

## 2026-03-12 - Guest landing background animation added
- Scope: `site-bulletin/resources/views/dashboard/partials/content.blade.php`, `site-bulletin/resources/css/app.css`
- Reason: the logged-out intro needed more atmosphere after simplifying the guest landing state, but without adding heavy decorative content.
- Change:
  - added a subtle animated SVG-style background layer to the guest intro panel only
  - used drifting dot clusters and soft wave lines for a modern, lightly coastal feel
  - kept the motion low-contrast so it supports the hero instead of competing with the sign-in purpose
- Validation:
  - `php artisan test tests/Feature/PublicDashboardNewsWidgetTest.php tests/Feature/DashboardTrendPanelsTest.php`

## 2026-03-12 - Full-page background atmosphere made subtly animated
- Scope: `site-bulletin/resources/css/app.css`
- Reason: after adding motion to the guest hero, the rest of the page background could support that direction with a lighter site-wide atmosphere rather than leaving all motion concentrated in one block.
- Change:
  - added slow drift to the existing gradient background
  - added a very faint full-page dot/glow overlay using CSS-only pseudo-elements
  - kept the motion slower and lower-contrast than the guest hero so content remains the priority
  - slightly increased page-wide dot contrast and drift distance after initial review made the motion feel too static
- Validation:
  - `php artisan test tests/Feature/PublicDashboardNewsWidgetTest.php tests/Feature/DashboardTrendPanelsTest.php`

## 2026-03-14 - Wide-screen dashboard horizontal overflow fixed
- Scope: `site-bulletin/resources/views/layouts/app.blade.php`
- Reason: authenticated dashboard content could overflow horizontally on wide screens because the main content flex column was not constrained to shrink within the shell layout.
- Change:
  - added `min-w-0` to the main app content column
  - added `min-w-0` and `overflow-x-hidden` to the main content area wrapper
  - added `overflow-x-hidden` to the page body as a guard against shell-level sideways scrolling
- Validation:
  - `php artisan test tests/Feature/LayoutNavigationTest.php tests/Feature/PublicDashboardNewsWidgetTest.php tests/Feature/DashboardTrendPanelsTest.php`

## 2026-03-14 - Dashboard widget markup flow fixed
- Scope: `site-bulletin/resources/views/dashboard/partials/news-widget.blade.php`
- Reason: the authenticated dashboard could render later sections in the wrong position because the news widget contained an extra closing `div`, which broke the document flow after that block.
- Change:
  - removed the stray closing wrapper from the news widget card layout
  - restored correct section flow so dashboard blocks after the widget render in sequence instead of drifting into a broken horizontal layout
- Validation:
  - `php artisan test tests/Feature/PublicDashboardNewsWidgetTest.php tests/Feature/DashboardTrendPanelsTest.php tests/Feature/LayoutNavigationTest.php`

## 2026-03-14 - Employee short-window trend scales anchored to latest sample data
- Scope: `site-bulletin/app/Http/Controllers/Public/DashboardController.php`
- Reason: selecting `Last 24 hours` or `Last 3 hours` on the employee dashboard could still show the 7-day chart because seeded mock samples are not generated up to the real current time, causing short-window queries relative to `now()` to return empty and fall back to daily data.
- Change:
  - anchored employee sample-window aggregation to the latest available `performance_samples.recorded_at` timestamp instead of the real current time
  - kept `7d` as daily aggregation, while `24h` now resolves to recent hourly buckets and `3h` resolves to recent 15-minute points from the latest sample window
- Validation:
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php`

## 2026-03-14 - Messages experience redesigned into messenger-style shell
- Scope: `site-bulletin/app/Http/Controllers/Messaging/ConversationController.php`, `site-bulletin/resources/views/messages/_shell.blade.php`, `site-bulletin/resources/views/messages/index.blade.php`, `site-bulletin/resources/views/messages/show.blade.php`
- Reason: the existing messages area behaved like a dashboard page with cards and side widgets rather than a familiar messenger, which made the UX feel unlike the chat products users already understand.
- Change:
  - refactored message inbox and conversation routes to use a shared messenger-shell data model
  - replaced the old sectioned page layout with a two-pane inbox and active-thread experience
  - moved search and type filters into the left inbox column
  - redesigned conversation rows to show avatar, title, preview text, timestamp, type badge, and unread badge
  - redesigned the thread view with messenger-like header, date separators, left/right message bubbles, and bottom composer
  - moved `Quick Contact`, `Start Conversation`, and `Department Broadcast` into the empty-state compose panel instead of leaving them as separate dashboard-like side cards
- Validation:
  - `php artisan test tests/Feature/Messaging`
  - `php artisan test tests/Feature/LayoutNavigationTest.php`

## 2026-03-14 - Messages navigation made responsive for mobile thread flow
- Scope: `site-bulletin/resources/views/messages/_shell.blade.php`
- Reason: the new messenger layout still needed standard mobile navigation behavior, where opening a conversation on a small screen should replace the inbox and provide a clear return path without inventing a separate bottom back pattern.
- Change:
  - hid the inbox pane on smaller screens when a conversation is open
  - hid the thread pane on smaller screens when no conversation is selected
  - added a mobile-only back button in the thread header that returns to the inbox list
  - kept the two-pane messenger layout unchanged on wide screens
- Validation:
  - `php artisan test tests/Feature/Messaging tests/Feature/LayoutNavigationTest.php`

## 2026-03-14 - Messages compose tools moved behind New Chat entry point
- Scope: `site-bulletin/resources/views/messages/_shell.blade.php`
- Reason: the large wide-screen `Quick Contact` and compose utility blocks still felt more like dashboard widgets than messenger UI, even after the shell redesign.
- Change:
  - removed the large compose utility panels from the main wide-screen message layout
  - replaced the inbox header plus button with a real `New chat` launcher
  - moved `Quick Contact`, `Start Conversation`, and `Department Broadcast` into a modal-style compose surface
  - kept the empty thread state lightweight and messenger-like instead of filling it with permanent admin/utility cards
- Validation:
  - `php artisan test tests/Feature/Messaging tests/Feature/LayoutNavigationTest.php`

## 2026-03-14 - Messages density tuned for more realistic desktop messenger feel
- Scope: `site-bulletin/resources/views/messages/_shell.blade.php`
- Reason: after the shell redesign, the inbox and thread still felt too spacious compared with common desktop messaging products.
- Change:
  - reduced inbox column width slightly
  - tightened chat row padding, avatar size, filter chip sizing, and search bar height
  - tightened thread header spacing, badge spacing, message bubble padding, and composer height
  - reduced empty-state vertical footprint so the main pane feels less like a landing page
- Validation:
  - `php artisan test tests/Feature/Messaging tests/Feature/LayoutNavigationTest.php`

## 2026-03-15 - Message attachments added for files and images
- Scope: `site-bulletin/app/Http/Controllers/Messaging/ConversationController.php`, `site-bulletin/app/Http/Controllers/Messaging/MessageController.php`, `site-bulletin/app/Http/Controllers/Messaging/MessageAttachmentController.php`, `site-bulletin/app/Http/Requests/ConversationStoreRequest.php`, `site-bulletin/app/Http/Requests/MessageStoreRequest.php`, `site-bulletin/app/Models/Message.php`, `site-bulletin/app/Models/MessageAttachment.php`, `site-bulletin/resources/views/messages/_shell.blade.php`, `site-bulletin/routes/web.php`, `site-bulletin/database/migrations/2026_03_15_120000_make_message_body_nullable.php`, `site-bulletin/database/migrations/2026_03_15_120100_create_message_attachments_table.php`
- Reason: users needed to be able to attach images and files inside the messenger instead of being limited to text-only messages.
- Change:
  - added `message_attachments` persistence and a download controller/route for secured attachment access
  - made message body nullable so attachment-only messages are valid
  - extended both conversation creation and reply flows to accept up to 5 uploaded files
  - added file pickers to the thread composer and new-chat forms
  - rendered attachment cards inside the thread and improved inbox previews for attachment-only messages
- Validation:
  - `php artisan test tests/Feature/Messaging tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/Messaging/MessageAttachmentTest.php`

## 2026-03-15 - Inline image previews added to message threads
- Scope: `site-bulletin/app/Http/Controllers/Messaging/MessageAttachmentController.php`, `site-bulletin/app/Models/MessageAttachment.php`, `site-bulletin/resources/views/messages/_shell.blade.php`, `site-bulletin/routes/web.php`, `site-bulletin/tests/Feature/Messaging/MessageAttachmentTest.php`
- Reason: image attachments were only available as downloads, which made the messenger feel incomplete compared with standard chat products.
- Change:
  - added a secured inline preview route for image attachments stored on the private attachments disk
  - added a preview URL helper to message attachments
  - rendered image attachments as inline thumbnails/cards inside the thread while keeping non-image files as download cards
  - added test coverage for authorized inline image preview access
- Validation:
  - `php artisan test tests/Feature/Messaging tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/Messaging/MessageAttachmentTest.php`

## 2026-03-15 - Dashboard trend scale switching changed to client-side interaction
- Scope: `site-bulletin/resources/views/dashboard/partials/trend-panels.blade.php`
- Reason: switching between `7d`, `24h`, and `3h` was reloading the whole dashboard, when only the chart block needed to change.
- Change:
  - replaced dashboard trend scale links with client-side Alpine state toggles
  - pre-rendered employee and manager trend variants for each supported scale and switched them with `x-show`
  - kept the trend summaries aligned with the selected employee scale without navigating away from the page
- Validation:
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php tests/Feature/PublicDashboardNewsWidgetTest.php tests/Feature/LayoutNavigationTest.php`
  - `php artisan view:clear`

## 2026-03-16 - Performance simulation unified around 15-minute samples with explicit backfill command
- Scope: `site-bulletin/app/Services/DemoOperationsSimulationService.php`, `site-bulletin/app/Console/Commands/BackfillDemoOperationsData.php`, `site-bulletin/app/Console/Kernel.php`, `site-bulletin/app/Http/Controllers/Public/DashboardController.php`, `site-bulletin/database/seeders/DatabaseSeeder.php`, `site-bulletin/tests/Feature/Analytics/DemoBackfillOpsDataCommandTest.php`
- Reason: performance and quality charts needed a single source of truth instead of mixing frozen seed windows, snapshot fallbacks, and synthetic short-window behavior.
- Change:
  - added a deterministic simulation service that generates user performance samples in 15-minute intervals
  - made weekly performance snapshots derive from those samples instead of being seeded independently
  - added `php artisan demo:backfill-ops-data` for explicit demo refresh/backfill runs
  - scheduled the backfill command every 15 minutes and added a dashboard freshness check so the sample window can catch up to current local time
  - switched the database seeder to use the same simulation service rather than duplicating sample-generation logic
  - kept ticket/SLA analytics on their existing pipeline so the new performance refresh does not silently overwrite unrelated department metric fixtures
- Validation:
  - `php artisan test tests/Feature/Analytics/DemoBackfillOpsDataCommandTest.php tests/Feature/Analytics/DepartmentMetricsCommandTest.php tests/Feature/DashboardTrendPanelsTest.php`
  - `php artisan demo:backfill-ops-data --refresh --weeks=1 --end="2026-03-16 12:10:00"`

## 2026-03-16 - SLA simulation added to demo backfill pipeline
- Scope: `site-bulletin/app/Services/DemoTicketLifecycleSimulationService.php`, `site-bulletin/app/Console/Commands/BackfillDemoOperationsData.php`, `site-bulletin/app/Models/Ticket.php`, `site-bulletin/database/migrations/2026_03_16_120000_add_simulation_key_to_tickets_table.php`, `site-bulletin/database/seeders/DatabaseSeeder.php`, `site-bulletin/tests/Feature/Analytics/DemoBackfillOpsDataCommandTest.php`
- Reason: ticket/SLA dashboards were still relying on static seeded timelines, so the demo could drift away from current time even after performance samples were refreshed.
- Change:
  - added a deterministic SLA simulation service that rebuilds a rolling window of demo tickets using stable simulation keys
  - generated realistic ticket status-change trails, comments, final statuses, and breach flags from the same simulation clock used by the demo refresh flow
  - extended `demo:backfill-ops-data` so one command now refreshes both performance samples and SLA ticket timelines
  - added `simulation_key` to tickets so simulated records can be safely replaced without touching manual or test-created tickets
  - updated the seeder to layer current simulated SLA activity on top of the baseline seeded ticket set
- Validation:
  - `php artisan migrate`
  - `php artisan test tests/Feature/Analytics/DemoBackfillOpsDataCommandTest.php tests/Feature/Analytics/DepartmentMetricsCommandTest.php tests/Feature/DashboardTrendPanelsTest.php`
  - `php artisan demo:backfill-ops-data --refresh --weeks=1 --ticket-days=7 --end="2026-03-16 12:10:00"`

## 2026-03-16 - Application README moved into Laravel app folder
- Scope: `README.md`, `site-bulletin/README.md`
- Reason: the full product/setup documentation belonged with the actual Laravel app, while the repository root needed to stay focused on repo-level orientation and governance files.
- Change:
  - created a dedicated `site-bulletin/README.md` describing application functionality, demo accounts, setup, running, testing, scheduling, and the demo data model
  - replaced the root `README.md` with a short repository guide that points contributors to the Laravel app README
- Validation:
  - manual documentation review

## 2026-03-17 - Repository scope updated after removing legacy implementation guide
- Scope: `AGENTS.md`, `README.md`, `site-bulletin/README.md`, `CLEANUP_LOG.md`
- Reason: the `Site Bulletin Implementation Guide/` folder was no longer used by the application runtime or current workflow, so contributor guidance and repo documentation needed to match the reduced scope.
- Change:
  - removed implementation-guide references from repo governance and README files
  - narrowed repository guidance to the Laravel app plus supporting data/log files
  - recorded the cleanup so future contributors do not reintroduce the removed folder by assumption
- Validation:
  - manual documentation review

## 2026-03-17 - Primary navigation icons refreshed
- Scope: `site-bulletin/resources/views/components/nav-icon.blade.php`
- Reason: the existing primary navigation icons looked too generic and visually heavy, especially in the mobile bottom navigation.
- Change:
  - replaced the filled icon set with cleaner stroke-based icons for dashboard, announcements, messages, knowledge, tasks, profile, and governance
  - kept the shared nav icon component API unchanged so both sidebar and mobile navigation benefit from the refresh
  - aligned the icon family visually so active/inactive states read more consistently
- Validation:
  - `php artisan test tests/Feature/LayoutNavigationTest.php tests/Feature/Messaging tests/Feature/PublicDashboardNewsWidgetTest.php`

## 2026-03-17 - Mobile bottom navigation styling tightened
- Scope: `site-bulletin/resources/views/layouts/app.blade.php`
- Reason: after refreshing the icon set, the mobile bottom navigation still needed a clearer active state and tighter spacing to feel more intentional.
- Change:
  - increased visual separation between active and inactive tabs with a softer pill treatment, subtle shadow, and stronger contrast
  - tightened icon/label spacing and slightly adjusted icon sizing for a more balanced mobile rhythm
  - refined badge placement and bar shadow so the bottom nav feels cleaner and more app-like
- Validation:
  - `php artisan test tests/Feature/LayoutNavigationTest.php tests/Feature/Messaging tests/Feature/PublicDashboardNewsWidgetTest.php`

## 2026-03-17 - Mobile tab labels rebalanced
- Scope: `site-bulletin/resources/views/layouts/app.blade.php`
- Reason: the bottom navigation labels still carried too much equal weight, which made the active tab feel less distinct than standard mobile app navigation.
- Change:
  - reduced inactive label emphasis with smaller, lighter typography
  - increased active label emphasis so the selected tab reads more clearly at a glance
  - kept the existing icon and badge layout intact
- Validation:
  - `php artisan test tests/Feature/LayoutNavigationTest.php tests/Feature/PublicDashboardNewsWidgetTest.php`

## 2026-03-17 - Active mobile tab icons made more distinct
- Scope: `site-bulletin/resources/views/layouts/app.blade.php`
- Reason: after improving label emphasis, the selected mobile tab still benefited from stronger icon contrast relative to inactive tabs.
- Change:
  - gave active mobile icons stronger blue emphasis
  - reduced inactive icon contrast so the selected destination stands out faster
  - kept the bottom navigation structure and badge behavior unchanged
- Validation:
  - `php artisan test tests/Feature/LayoutNavigationTest.php tests/Feature/PublicDashboardNewsWidgetTest.php`

## 2026-03-17 - Fixed active mobile tab icon disappearing
- Scope: `site-bulletin/resources/views/layouts/app.blade.php`
- Reason: the selected mobile tab icon could visually disappear because its active-state size utility was not resolving consistently, unlike the desktop collapsed nav.
- Change:
  - replaced the active mobile icon size with a standard supported size utility
  - kept the active/inactive visual hierarchy the same while restoring reliable icon rendering
- Validation:
  - `php artisan test tests/Feature/LayoutNavigationTest.php tests/Feature/PublicDashboardNewsWidgetTest.php`

## 2026-03-17 - Employee dashboard summary row removed and attention cards made actionable
- Scope: `site-bulletin/resources/views/dashboard/partials/content.blade.php`, `site-bulletin/resources/views/dashboard/partials/my-work-today.blade.php`, `site-bulletin/app/Http/Controllers/Public/TicketViewController.php`
- Reason: the employee-only dashboard still showed an inventory-style summary row with little decision value, and the `Attention Needed` panel looked static despite being the most obvious action area.
- Change:
  - removed the authenticated summary-card row for employee dashboards so the main flow stays focused on current work, tickets, and performance
  - converted `Attention Needed` rows into clickable ticket filters for waiting-on-you, breached, and unassigned cases
  - added hover lift/arrow feedback and short supporting copy so the panel reads like an actionable work queue instead of passive stats
  - added an `unassigned` filter to the ticket index so the dashboard links land on the correct subset
- Validation:
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php tests/Feature/LayoutNavigationTest.php`

## 2026-03-17 - Removed redundant employee weekly history cards
- Scope: `site-bulletin/resources/views/dashboard/partials/content.blade.php`
- Reason: the employee dashboard already has an interactive performance trend section, so the static `Performance (Last 6 Weeks)` card grid was duplicating history with lower quality and adding unnecessary page length.
- Change:
  - removed the employee-only weekly history card block and its placeholder coursework note
  - left the trend charts as the single historical performance view for employees
- Validation:
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php tests/Feature/LayoutNavigationTest.php`

## 2026-03-17 - Introduced employee My Work area and removed announcements from primary nav
- Scope: `site-bulletin/routes/web.php`, `site-bulletin/app/Http/Controllers/Public/MyWorkController.php`, `site-bulletin/resources/views/my-work/index.blade.php`, `site-bulletin/resources/views/layouts/app.blade.php`, `site-bulletin/resources/views/components/nav-icon.blade.php`, `site-bulletin/resources/views/dashboard/partials/my-work-today.blade.php`, `site-bulletin/resources/views/dashboard/partials/trend-panels.blade.php`, `site-bulletin/resources/views/dashboard/partials/content.blade.php`
- Reason: detailed productivity and quality analytics were taking over the employee dashboard, while `Announcements` as a top-level tab duplicated the dashboard news feed and notification bell.
- Change:
  - added a dedicated employee `My Work` route and page for detailed throughput and quality analytics
  - moved the employee performance trend section off the main dashboard and into the new `My Work` page
  - made the employee throughput and quality summary cards on the dashboard clickable so they deep-link into detailed `My Work` sections
  - removed `Announcements` from the primary desktop/mobile tab bars and kept updates discoverable through the bell plus dashboard feed
  - removed the second large dashboard `Announcements` block so the home page no longer repeats the same news content twice
  - added a dedicated `My Work` nav icon and employee-specific nav structure
- Validation:
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/PublicDashboardNewsWidgetTest.php`

## 2026-03-17 - Employee summary cards made consistent and productivity wording normalized
- Scope: `site-bulletin/resources/views/dashboard/partials/my-work-today.blade.php`, `site-bulletin/resources/views/my-work/index.blade.php`
- Reason: the `My Work Today` summary row mixed clickable and non-clickable cards while using `throughput` wording in some places and `quality score` wording in others, which made the block feel inconsistent.
- Change:
  - made all four employee summary cards clickable with matching hover behavior and action labels
  - routed `Unread Updates` to the updates feed and `Open Tickets` to the ticket queue so the row behaves consistently
  - renamed employee-facing throughput labels to `Productivity Score` / `Target productivity` where the language is more natural
- Validation:
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/PublicDashboardNewsWidgetTest.php`

## 2026-03-17 - Desktop sidebar locked in place on wide screens
- Scope: `site-bulletin/resources/views/layouts/app.blade.php`
- Reason: on wide screens the primary navigation could scroll off-screen with the page content, forcing users to scroll back up just to change tabs, unlike the persistent mobile tab bar.
- Change:
  - made the authenticated desktop sidebar sticky for large screens so the primary tabs remain available while the main content scrolls
  - preserved the existing mobile behavior and the desktop collapse interaction
- Validation:
  - `php artisan test tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php`

## 2026-03-17 - Messaging inbox column narrowed on wide screens
- Scope: `site-bulletin/resources/views/messages/_shell.blade.php`
- Reason: the wide-screen inbox pane was taking more width than the chat list needed, which left the main conversation area unnecessarily compressed.
- Change:
  - reduced the desktop inbox column width from `340px` to `300px` on xl screens
  - added a slightly looser `320px` width only for very large `2xl` screens
  - left mobile and drilled-in conversation behavior unchanged
- Validation:
  - `php artisan test tests/Feature/Messaging tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/PublicDashboardNewsWidgetTest.php`

## 2026-03-17 - Fixed duplicate shell rendering on profile page
- Scope: `site-bulletin/resources/views/profile/edit.blade.php`, `site-bulletin/tests/Feature/ProfileTest.php`
- Reason: the profile page was rendering the full application shell multiple times because the Blade view extended the app layout more than once.
- Change:
  - removed duplicate `@extends('layouts.app')` directives from the profile edit view
  - added a regression check to confirm the profile page renders only one shell header/footer
- Validation:
  - `php artisan test tests/Feature/ProfileTest.php tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php`

## 2026-03-17 - Employee performance wording and status colors aligned to targets
- Scope: `site-bulletin/resources/views/dashboard/partials/trend-panels.blade.php`, `site-bulletin/resources/views/my-work/index.blade.php`
- Reason: employee-facing performance areas still used `throughput` terminology in key places and the summary cards did not visually communicate whether the user was actually meeting target.
- Change:
  - replaced the remaining employee `throughput` wording in the `My Work` page copy with `productivity`
  - changed the employee trend summary cards from static `Peak Throughput` / `Rank Trend` labels to `Current Productivity` / `Current Quality`
  - added target-aware card states so employee productivity and quality cards now switch between green, amber, and red depending on current performance against target
  - preserved best-in-window context as supporting detail instead of making it the primary metric
- Validation:
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/PublicDashboardNewsWidgetTest.php`

## 2026-03-17 - Messaging inbox widened and empty state reduced
- Scope: `site-bulletin/resources/views/messages/_shell.blade.php`
- Reason: on wide screens the empty conversation area was taking too much space for a low-information placeholder while the inbox pane felt tighter than necessary.
- Change:
  - widened the desktop inbox column so the chat list has more useful space
  - reduced the empty conversation view to a compact centered card instead of a large full-panel placeholder
  - kept a single `New chat` action in the empty state that opens the existing start-conversation prompt
- Follow-up:
  - increased the inbox width again after review because the first pass was still too conservative
  - reduced the empty-state card further and moved it higher so the right side no longer feels dominated by a start-conversation placeholder
  - removed the outer bordered white thread frame entirely in the empty state so only the compact starter card remains on the right
  - removed the redundant `Choose a chat` empty-state card entirely once the inbox `+` action was made prominent enough to serve as the single start-conversation entry point
  - changed the empty desktop messages layout to a single wider inbox so the page no longer reserves a large blank second pane when no chat is selected
  - made the empty desktop state a true full-width inbox and added lightweight inbox/thread transition animations so opening a conversation feels like a drill-in instead of a layout jump
- Validation:
  - `php artisan test tests/Feature/Messaging tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/PublicDashboardNewsWidgetTest.php`

## 2026-03-19 - Fixed manager dashboard bottom section shifting to the right
- Scope: `site-bulletin/resources/views/dashboard/partials/trend-panels.blade.php`
- Reason: the manager dashboard was rendering incorrectly near the bottom of the page because an extra closing wrapper inside the `Daily Breach Drilldown` block could pull later layout content, including the footer, into the wrong flow.
- Change:
  - removed the stray `</div>` from the manager breach drilldown section so the dashboard content tree stays balanced
  - restored normal page flow for the manager dashboard footer and lower sections
- Validation:
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/ProfileTest.php tests/Feature/PublicDashboardNewsWidgetTest.php`

## 2026-04-30 - Dashboard demo data source-of-truth alignment
- Scope: `site-bulletin/app/Services/DemoOperationsSimulationService.php`, `site-bulletin/app/Services/DemoTicketLifecycleSimulationService.php`, `site-bulletin/app/Http/Controllers/Public/DashboardController.php`, `site-bulletin/app/Http/Controllers/Admin/AnalyticsController.php`, dashboard/analytics tests.
- Reason: employee and manager performance dashboards needed to use realistic employee 15-minute productivity and quality samples as the canonical data source, while ticket/SLA demo data needed less breach-heavy operational patterns.
- Change:
  - limited generated performance samples to employee users and added deterministic shift-window attendance gaps
  - made manager dashboard department productivity, quality, active headcount, and current-shift unit totals derive from employee `performance_samples`
  - tuned rolling demo ticket priorities, statuses, first-response breaches, and resolution breaches toward more realistic operational distributions
  - fixed the analytics dashboard crash caused by enum-cast ticket priorities being used directly as collection keys
  - added regression tests for employee-only sample generation, manager sample aggregation, and analytics enum handling
- Validation:
  - `php artisan test tests/Feature/Analytics/DemoBackfillOpsDataCommandTest.php tests/Feature/DashboardTrendPanelsTest.php tests/Feature/Analytics/AnalyticsDashboardTest.php`

## 2026-04-30 - Operations UI/UX polish and data provenance
- Scope: `site-bulletin/resources/css/app.css`, `site-bulletin/resources/views/layouts/app.blade.php`, dashboard partials, analytics/profile/knowledge/ticket views, navigation tests.
- Reason: the prototype needed calmer production-facing visuals, clearer ticket/analytics navigation, explicit demo data provenance, and a fix for an Alpine tooltip crash on dashboard charts.
- Change:
  - replaced the animated multi-color page background with a quieter operations-tool surface
  - renamed primary navigation from `Tasks` to `Tickets` and exposed `Analytics` for manager/admin roles
  - added source/update labels to employee and manager dashboard performance areas
  - guarded chart tooltip style bindings so Alpine no longer reads coordinates from null tooltip state
  - toned down breach/severity colors and simplified the profile page into operational work-profile and account settings sections
  - added a decision queue to analytics so managers see breach pressure, response load, and resolution drag before raw tables
- Validation:
  - `php artisan test tests/Feature/LayoutNavigationTest.php tests/Feature/ProfileTest.php tests/Feature/DashboardTrendPanelsTest.php tests/Feature/Analytics/AnalyticsDashboardTest.php`
  - `npm run build`
  - Playwright smoke check against `http://127.0.0.1:8000/`, `/profile`, and `/analytics` as `manager@example.com`; all expected headings/navigation present and no browser console/page errors.

## 2026-04-30 - Actionable notifications, ticket timeline, and mobile icon polish
- Scope: notification dropdown, notification payloads, ticket detail timeline, primary navigation marks, and reduced-motion handling.
- Reason: the app needed clearer next-action notifications, a more employee-readable ticket history, and less icon-heavy mobile navigation.
- Change:
  - replaced the bell-only notification dropdown with an `Alerts` control that shows unread counts, notification type, direct next-action labels, and full-width accessible action buttons
  - added action labels and more direct destinations for ticket, announcement, and message notifications
  - replaced the status-only ticket timeline with a combined ticket timeline containing ticket creation, visible status changes, public/internal-visible updates, and visible evidence uploads
  - replaced the navigation glyphs with bolder, simpler SVG icons for mobile and collapsed sidebar states
  - added reduced-motion handling for dashboard, badge, chart, and messaging animations
- Validation:
  - `php artisan test tests/Feature/Notifications/ActionableNotificationDropdownTest.php tests/Feature/Tickets/TicketLifecycleTransparencyTest.php tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/Notifications/NotificationPreferencesDispatchTest.php`
  - `npm run build`
  - Follow-up validation after icon correction: `php artisan test tests/Feature/LayoutNavigationTest.php`; `npm run build`
  - Follow-up validation after icon polish: `php artisan test tests/Feature/LayoutNavigationTest.php`; `npm run build`

## 2026-04-30 - Ticket index CTA polish
- Scope: `site-bulletin/resources/views/tickets/index.blade.php`
- Reason: the `Report issue` button on the ticket index header was too pill-shaped and narrow on mobile, causing an awkward wrapped label and a weak visual relationship to the page header.
- Change:
  - changed the ticket header action group to stack cleanly on small screens and align to the right on wider screens
  - replaced the oversized pill CTA with a compact rounded rectangular button, plus mark, stable minimum height, and focus ring
  - matched secondary ticket actions to the same button height and radius system
- Validation:
  - `php artisan test tests/Feature/Tickets/TicketIndexViewTest.php`
  - `npm run build`

## 2026-05-05 - Navigation, demo boundary, and dashboard component cleanup
- Scope: `site-bulletin/resources/views/layouts/app.blade.php`, `site-bulletin/resources/views/dashboard/partials/content.blade.php`, `site-bulletin/resources/views/components/category-icon.blade.php`, navigation/dashboard services, browser smoke tests.
- Reason: the app needed production-facing demo/prototype boundaries, less query logic inside Blade layouts, more robust mobile navigation construction, and reusable quick-link icon rendering.
- Change:
  - moved shell navigation counts and nav item construction into a dedicated view-data service
  - made mobile navigation derive from named items instead of fragile desktop array indexes
  - hid the coursework prototype footer label behind explicit configuration
  - moved dashboard quick-link SVG selection into a reusable Blade component
  - updated browser smoke expectations from legacy `Tasks`/`Announcements` shell labels to current `Tickets`/`Knowledge` navigation
- Validation:
  - `php artisan test`
  - `npm run build`

## 2026-05-06 - RS-02 role scope foundation
- Scope: shared role/department scoping, support triage board, on-behalf ticket creation, role change requests, department broadcasts, audit-log visibility, announcement authoring, ticket approvals, ticket filters, analytics saved views/export, dashboard governance/attention data, manager-accessible Filament ticket/content department surfaces, and role-scenario strategy decisions.
- Reason: the role-scenario plan needs a single source of truth for department visibility before adding role-specific action dashboards and workspaces.
- Change:
  - added `RoleScopeService` for view/manage department scope decisions
  - encoded the accepted consensus that `ops_manager` is a site-wide command-center role
  - migrated selected high-risk consumers to the shared service
  - moved announcement audience options, approval workbench scoping, ticket department filtering, analytics department selection, and dashboard governance/role-request widgets onto the shared scope path
  - closed the remaining policy/export/admin-resource sweep by scoping public ticket authorization, analytics CSV export, and manager-accessible Filament ticket/content department controls
  - added scope characterization tests for employee, manager, ops manager, HR, and admin behavior
  - added regression coverage for ops-manager site-wide announcement and manager-approval behavior, manager analytics restrictions/export scoping, unmanaged ticket filter rejection, and ticket policy boundaries
  - updated the implementation strategy with accepted consensus decisions and marked RS-02 complete
- Validation:
  - `php artisan test tests/Feature/Announcements/AnnouncementCreationPermissionsTest.php tests/Feature/Tickets/TicketApprovalQueueTest.php tests/Feature/Analytics/SavedAnalyticsViewTest.php tests/Feature/DashboardTrendPanelsTest.php`
  - `php artisan test tests/Feature/TicketPermissionsTest.php tests/Feature/Analytics/AnalyticsDashboardTest.php tests/Feature/Analytics/AnalyticsDigestCommandTest.php tests/Feature/Announcements/AnnouncementCreationPermissionsTest.php tests/Feature/Tickets/TicketFiltersTest.php`
  - `php artisan test tests/Feature/Tickets tests/Feature/Governance tests/Feature/Announcements tests/Feature/Messaging tests/Feature/LayoutNavigationTest.php`
  - `php artisan test tests/Feature/Tickets tests/Feature/Analytics tests/Feature/Announcements tests/Feature/Governance tests/Feature/Messaging tests/Feature/DashboardTrendPanelsTest.php tests/Feature/LayoutNavigationTest.php`
  - `php artisan test`

## 2026-05-06 - RS-01 action-led role home screens
- Scope: dashboard role worklists, shared next-action service, and role-specific dashboard tests.
- Reason: employees, managers, ops managers, HR, and admins need a concise "what needs my attention now" surface instead of reading separate dashboard widgets to infer priorities.
- Change:
  - added `RoleActionService` to build capped role-specific action lists
  - added a dashboard `Next Actions` section with direct links and role-specific empty state
  - surfaced employee ticket/update actions, manager department actions, ops site pressure, HR approvals/sensitive cases/role requests, and admin configuration health
  - added feature tests proving the dashboard action section renders correct work for employee, manager, ops manager, HR, and admin
- Validation:
  - `php artisan test tests/Feature/DashboardRoleActionsTest.php tests/Feature/DashboardTrendPanelsTest.php tests/Feature/LayoutNavigationTest.php`
  - `npm run build`
  - `php artisan test`

## 2026-05-06 - RS-03 employee ticket self-service tabs
- Scope: employee ticket index controller filters, ticket index Blade UI, and ticket index feature tests.
- Reason: employees need a clearer way to track reported issues by what needs their action versus what is waiting on the team.
- Change:
  - added employee-only ticket flow tabs for `Needs me`, `In progress`, `Waiting on team`, and `Resolved`
  - added queue filtering that preserves existing search/type filters and does not affect manager ticket filters
  - added feature tests for tab rendering, `Needs me` filtering, and manager exclusion
  - updated the strategy ledger to mark RS-03 as in progress
- Validation:
  - `php artisan test tests/Feature/Tickets/TicketIndexViewTest.php tests/Feature/Tickets/TicketFiltersTest.php tests/Feature/Tickets/TicketLifecycleTransparencyTest.php`
  - `npm run build`
  - `php artisan test`

## 2026-05-06 - RS-03 guided reporting and unified help search
- Scope: ticket report guidance, knowledge search controller/view, quick-link and announcement search grouping, and related feature tests.
- Reason: employees need a lower-friction path when they do not know the right ticket category, and help search should find operational answers across snippets, quick links, and announcements.
- Change:
  - added a three-question guided report recommender that suggests existing ticket templates
  - grouped knowledge search results into snippets, quick links, announcements, and a guided-report fallback
  - kept quick-link results behind existing category visibility rules
  - added feature tests for guided recommendations, grouped help results, and hidden manager-only links
  - updated the strategy ledger to mark RS-03 complete
- Validation:
  - `php artisan test tests/Feature/Tickets/FastIssueReportingTest.php tests/Feature/KnowledgeSnippetSearchTest.php`
  - `npm run build`
  - `php artisan test`

## 2026-05-06 - RS-04 repeat issue and breach prevention
- Scope: manager/ops dashboard prevention surface, support triage prevention panel, repeat issue clustering service, ticket repeat filters, and dashboard/triage feature tests.
- Reason: managers and ops managers need to spot recurring blockers, aging tickets, and breach pressure before they become broader shift problems.
- Change:
  - added `OperationalPreventionService` for scoped repeat clusters and breach/aging pressure
  - added dashboard `Prevention Watch` for managers and ops managers, including site-wide ops pressure
  - added triage `Prevention Pressure` with repeat cluster links for faster intervention
  - added exact `template_key` and `location` ticket filters so repeat-cluster links open the relevant queue
  - added regression tests for manager scoping, ops site-wide prevention, and triage repeat pressure
- Validation:
  - `php artisan test tests/Feature/DashboardTrendPanelsTest.php tests/Feature/Tickets/TriageBoardTest.php`
  - `npm run build`
  - `php artisan test`
  - `git diff --check` (line-ending warnings only)

## 2026-05-06 - RS-05 HR-specific workspace
- Scope: HR dashboard workspace, shared HR queue service, sensitive ticket detail guidance, and HR dashboard regression tests.
- Reason: HR needed a daily work surface distinct from manager and operations dashboards, with privacy-sensitive people work grouped separately from generic ticket queues.
- Change:
  - added `HrWorkspaceService` for HR approvals, sensitive cases, people tickets, role requests, and policy acknowledgement exceptions
  - added dashboard `People Operations Queue` visible to HR users only
  - linked each HR queue back to existing approval, ticket, governance, and announcement workbench routes
  - added a sensitive-case handling banner on ticket detail to separate requester-facing updates from private HR notes
  - added feature tests for HR workspace visibility and sensitive ticket guidance
- Validation:
  - `php artisan test tests/Feature/DashboardHrWorkspaceTest.php tests/Feature/DashboardRoleActionsTest.php tests/Feature/Tickets/TicketApprovalQueueTest.php tests/Feature/Tickets/TicketAttachmentVisibilityTest.php tests/Feature/Announcements/AnnouncementReadFlowTest.php`
  - `npm run build`
  - `php artisan test`
  - `git diff --check` (line-ending warnings only)

## 2026-05-06 - RS-06 admin readiness and system health
- Scope: admin governance readiness panel, shared readiness service, readiness Artisan command, and governance feature tests.
- Reason: admins need a production/demo readiness checklist without reading `.env`, queue config, scheduler assumptions, or operational data tables directly.
- Change:
  - added `SystemReadinessService` for debug mode, demo boundary, prototype footer, queue, mailer, failed jobs, storage link, writable storage, missing departments, missing manager relationships, metrics freshness, and demo sample freshness
  - added `site:readiness-check` with optional `--fail-on-warning`
  - added an admin-only `System Health Checklist` section on the governance hub
  - kept the checklist hidden from manager governance users
  - added feature coverage for admin readiness visibility, manager exclusion, and command output
- Validation:
  - `php artisan test tests/Feature/Governance/GovernancePageTest.php tests/Feature/DashboardRoleActionsTest.php`
  - `npm run build`
  - `php artisan test`
  - `git diff --check` (line-ending warnings only)

## 2026-05-06 - Manager Filament panel login fix
- Scope: Filament admin panel access authorization and auth regression tests.
- Reason: managers could authenticate through Laravel but receive a Filament `403` on the admin panel in non-local environments because the user model did not explicitly implement Filament panel access.
- Change:
  - implemented `FilamentUser::canAccessPanel()` on `User`
  - limited admin panel access to seeded manager/admin roles in the same shape as the existing panel role middleware
  - added tests for manager credential login and manager access to manager-only/admin-panel pages
- Validation:
  - `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/DemoLoginTest.php`
  - `php artisan test tests/Feature/Analytics/SavedAnalyticsViewTest.php tests/Feature/Announcements/AnnouncementCreationPermissionsTest.php tests/Feature/Tickets/TriageBoardTest.php`

## 2026-05-06 - Demo login role coverage fix
- Scope: login demo presets, demo-login route handling, seeded demo accounts, README credentials, and auth regression tests.
- Reason: the completed role strategy now treats employee, manager, ops manager, HR, and admin as first-class role workflows, but quick demo login only exposed employee/manager and the local database had no ops-manager demo account.
- Change:
  - expanded quick demo access and the custom demo role selector to employee, manager, ops manager, HR, and admin
  - allowed demo login for all five role values while keeping department filtering scoped to employee/manager demos
  - added a friendly GET redirect for `/demo-login` so direct navigation no longer lands on an empty method/CSRF dead end
  - added `ops@example.com` / `password` as the seeded ops-manager account and updated the local SQLite database without a reset
  - updated auth tests and README demo credentials
- Validation:
  - `php artisan migrate:status`
  - `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/DemoLoginTest.php`
  - `php artisan test tests/Feature/DashboardRoleActionsTest.php tests/Feature/DashboardHrWorkspaceTest.php tests/Feature/Governance/GovernancePageTest.php tests/Feature/LayoutNavigationTest.php`

## 2026-06-17 - Authenticated home routing fix
- Scope: public home route and dashboard routing regression tests.
- Reason: the application root was rendering the dashboard controller without auth middleware, which made the start URL behave differently from `/dashboard` and could leave users thinking login routing was broken.
- Change:
  - made `/` an authenticated dashboard route for signed-in users
  - allowed guests hitting `/` to flow through Laravel's auth redirect to `/login`
  - replaced the stale public-dashboard placeholder test with explicit guest and authenticated routing assertions
- Validation:
  - `php artisan test`
  - `npm run build`
  - targeted Playwright smoke for guest login redirect, demo login, and authenticated dashboard shell

## 2026-06-17 - Performance sample rollup pipeline
- Scope: performance sample storage, dashboard chart queries, scheduled rollup command, readiness checks, and analytics tests.
- Reason: raw 15-minute performance and quality samples grow quickly at production scale, so dashboard charts need compact pre-aggregated data rather than scanning raw telemetry indefinitely.
- Change:
  - added `performance_rollups` for hourly and daily aggregates
  - added `PerformanceRollupService` and `performance:rollup-samples` for bounded day-by-day aggregation and optional raw-sample pruning
  - updated demo backfill to generate rollups after sample creation
  - routed dashboard 7-day and 24-hour chart reads through rollups while preserving raw 15-minute samples for the last-3-hour live window
  - added readiness reporting for missing or stale rollups
  - documented the data-processing path in the Laravel README
- Validation:
  - `php artisan test tests/Feature/Analytics/PerformanceRollupServiceTest.php tests/Feature/Analytics/DemoBackfillOpsDataCommandTest.php tests/Feature/DashboardTrendPanelsTest.php`
  - `php artisan migrate --force`
  - `php artisan performance:rollup-samples --days=60`
  - `php artisan test`
  - `npm run build`
  - `php artisan site:readiness-check` (rollup check OK; expected local critical/warnings remain for APP_DEBUG, demo mode, log mailer, and storage link)
