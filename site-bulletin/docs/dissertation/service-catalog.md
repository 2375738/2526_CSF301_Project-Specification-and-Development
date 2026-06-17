# Service And Component Catalogue

This catalogue describes the main moving parts of the Site Bulletin application. It is organised by architectural layer and can be used to explain how responsibilities are separated across the application.

## 1. Controllers

Controllers receive HTTP requests, validate route-level intent, and delegate complex business logic to services or model policies.

### Public Dashboard Controllers

#### `DashboardController`

Purpose:

- renders the main role-aware dashboard
- delegates dashboard data construction to `DashboardDataService`

Reason for separation:

- dashboard logic is too broad for a controller because it combines announcements, messages, tickets, performance, governance, and role-specific summaries
- moving this logic into `DashboardDataService` makes it testable and easier to extend

#### `MyWorkController`

Purpose:

- renders employee-focused work summary
- redirects non-employees away from employee-only work pages

Collaborators:

- `DashboardDataService`
- authenticated user

### Ticket Controllers

#### `ReportTicketController`

Purpose:

- shows the issue reporting form
- supports templates such as scanner issue, missed punch, shift swap, facilities issue, and other guided reporting paths
- stores new tickets
- supports linking duplicate or related tickets

Important behaviours:

- uses `StoreTicketRequest`
- applies role and department validation
- creates approval records for approval-driven templates
- supports on-behalf ticket creation for leadership roles

#### `TicketViewController`

Purpose:

- lists tickets
- applies filters such as status, search, department, breach state, template, and employee self-service tabs
- renders individual ticket details

Important behaviours:

- manager views are scoped through `RoleScopeService`
- employees can see their own reported/created-for tickets
- ticket details include lifecycle and sensitive-case information

#### `TicketStatusController`

Purpose:

- handles ticket status changes
- validates status transitions through `TicketStatusRequest`
- records status history
- updates SLA fields

#### `TicketApprovalQueueController`

Purpose:

- renders the approval workbench
- scopes pending and completed approvals by role and department
- supports filtering and grouped approval status views

#### `TicketApprovalController`

Purpose:

- stores approval decisions
- records public/internal notes
- updates approval and ticket state

#### `SupportTriageBoardController`

Purpose:

- renders support/operations triage board
- groups open tickets by operational queue
- shows repeat issue and breach pressure insights

### Communication Controllers

#### `AnnouncementController`

Purpose:

- lists announcements
- creates role-authorised announcements
- renders announcement details
- marks announcements read
- stores acknowledgement values
- supports mark-all-read

Collaborators:

- `AnnouncementPolicy`
- `AnnouncementObserver`
- announcement read pivot table

#### `KnowledgeSnippetController`

Purpose:

- renders searchable knowledge snippets
- groups search results into snippets, quick links, announcements, and guided report recommendations
- hides manager-only material from employees

#### `ConversationController`

Purpose:

- lists conversations
- filters and searches messages
- creates direct or department conversations
- opens conversation detail
- locks/unlocks conversations

#### `MessageController`

Purpose:

- stores messages
- validates message body and attachments
- updates read/unread state through message observers

#### `MessageAttachmentController`

Purpose:

- downloads message attachments
- previews inline image attachments where safe

### Governance And Analytics Controllers

#### `GovernanceController`

Purpose:

- renders governance hub
- renders policy, organisation, and escalation pages
- injects admin readiness data for admins

#### `RoleChangeRequestController`

Purpose:

- lists role-change requests
- renders role-change request form
- stores role-change requests with department and target-user validation

#### `AnalyticsController`

Purpose:

- renders analytics dashboard
- stores saved analytics views
- exports analytics CSV streams

Collaborators:

- `DepartmentAnalyticsService`
- `AnalyticsExportService`
- `SLAService`
- `RoleScopeService`

## 2. Service Layer

The service layer contains business logic that is shared, computational, or too complex for controllers.

### `DashboardDataService`

Purpose:

- builds all data needed by the role-aware dashboard
- chooses role-specific sections
- composes dashboard summaries from announcements, categories, tickets, metrics, performance, messages, governance logs, and actions

Key responsibilities:

- employee work summary
- manager department overview
- department trend panels
- prevention watch integration
- HR workspace integration
- next actions integration
- performance chart series

Key collaborators:

- `PerformanceService`
- `DepartmentAnalyticsService`
- `DemoOperationsSimulationService`
- `RoleScopeService`
- `RoleActionService`
- `OperationalPreventionService`
- `HrWorkspaceService`
- `PerformanceRollupService`

### `PerformanceRollupService`

Purpose:

- aggregates high-volume raw 15-minute performance samples into hourly and daily rollups
- supplies compact chart series to dashboards
- optionally prunes old raw samples after rollup

Why it exists:

- raw performance telemetry grows quickly in a production-style system
- charting should not scan raw samples indefinitely
- rollups preserve useful analytics while bounding query volume

Main operations:

- `refreshRange(start, end)`
  - reads raw samples day by day
  - creates hourly rollups
  - creates daily rollups
  - upserts aggregate rows

- `seriesForUsers(userIds)`
  - returns daily 7-day series
  - returns hourly 24-hour series
  - leaves last-3-hour live series to raw samples

- `pruneRawSamplesOlderThan(days)`
  - deletes old raw samples after the aggregate record exists

### `DemoOperationsSimulationService`

Purpose:

- generates deterministic performance samples for demo/testing
- backfills 15-minute samples
- refreshes weekly snapshots
- now also refreshes performance rollups after generation

Data produced:

- `performance_samples`
- `performance_snapshots`
- `performance_rollups`

### `DepartmentAnalyticsService`

Purpose:

- calculates department-level operational metrics from tickets
- ensures recent metrics exist for dashboard and analytics views

Data produced:

- `department_metrics`

### `PerformanceService`

Purpose:

- evaluates employee performance snapshots
- identifies risk flags based on recent snapshot trends

### `RoleScopeService`

Purpose:

- centralises role and department visibility
- avoids repeated ad hoc role checks across controllers

Core rules:

- employee: own departments
- manager: managed departments
- ops manager: all departments
- HR: all departments for people/workflow contexts
- admin: all departments

Key methods:

- `viewableDepartmentIds`
- `manageableDepartmentIds`
- `canViewDepartment`
- `canManageDepartment`
- query scope helpers

### `RoleActionService`

Purpose:

- builds dashboard "Next Actions" for each role
- prioritises immediate work by role

Examples:

- employee: reply to waiting ticket, read urgent announcement
- manager: clear breached tickets, review approvals, respond to messages
- ops manager: balance unassigned work, reduce site SLA pressure
- HR: complete HR approvals, review sensitive people cases
- admin: readiness and configuration checks

### `OperationalPreventionService`

Purpose:

- identifies repeated blockers and breach/aging pressure
- helps managers move from reactive ticket handling to prevention

Outputs:

- repeat issue clusters
- breach and aging risks
- scoped department or site-wide insights

### `HrWorkspaceService`

Purpose:

- builds HR-specific work queues
- separates sensitive people work from general operations queues

Outputs:

- approvals waiting now
- sensitive cases
- role requests
- policy/acknowledgement exceptions

### `SLAService`

Purpose:

- calculates SLA targets
- evaluates first-response and resolution breaches
- supports ticket SLA recalculation

Inputs:

- ticket priority
- ticket status history
- configured SLA settings

Outputs:

- breach flags
- first response timing
- resolution timing

### `SlaAutomationService`

Purpose:

- handles escalation/notification behaviour when SLA breaches are identified
- notifies stakeholders and records audit information

### `AnalyticsExportService`

Purpose:

- exports analytics data as CSV
- scopes export data by user permissions

### `AuditLogger`

Purpose:

- records auditable events in a consistent shape
- supports governance review and traceability

### `NotificationService`

Purpose:

- dispatches notifications while respecting user preferences
- supports message, announcement, and ticket update notifications

### `SystemReadinessService`

Purpose:

- produces a deployment/demo readiness report

Checks include:

- debug mode
- demo login/simulation boundary
- prototype footer flag
- queue driver
- mailer
- failed jobs
- storage link
- writable storage
- users without departments
- employees without manager relationships
- department metrics freshness
- demo sample freshness
- performance rollup freshness

## 3. Policies And Middleware

### `EnsureRole`

Purpose:

- route-level role middleware
- aborts unauthorised role access

### `TicketPolicy`

Purpose:

- controls who can view, comment on, upload to, or manage tickets
- enforces employee ownership and manager department scope

### `ConversationPolicy`

Purpose:

- controls access to conversations and replies
- ensures only participants or authorised users can interact

### `AuditLogPolicy`

Purpose:

- controls governance audit visibility
- managers see scoped activity
- HR/ops/admin have broader visibility

### `RoleChangeRequestPolicy`

Purpose:

- controls role request visibility and decisions
- prevents managers from approving users outside their scope

## 4. Form Request Objects

Form requests validate incoming data before controllers run business logic.

- `LoginRequest`
  - validates authentication credentials and rate limiting

- `StoreTicketRequest`
  - validates issue reporting, category, priority, department, and template answers

- `TicketStatusRequest`
  - validates ticket status transitions and status-specific requirements

- `TicketCommentRequest`
  - validates ticket comment body and visibility rules

- `TicketAttachmentRequest`
  - validates file uploads

- `ConversationStoreRequest`
  - validates direct/department conversation creation

- `MessageStoreRequest`
  - validates message body and attachments

- `RoleChangeRequestStoreRequest`
  - validates requested role, target user, department, and justification

- `ProfileUpdateRequest`
  - validates profile and notification preferences

## 5. Scheduled Commands

### `demo:backfill-ops-data`

Purpose:

- generates deterministic demo performance data and rolling SLA simulation data

Outputs:

- raw performance samples
- performance snapshots
- performance rollups
- simulated tickets and ticket status history

### `performance:rollup-samples`

Purpose:

- converts raw 15-minute telemetry into hourly and daily aggregates
- optionally prunes old raw samples

Importance:

- this is the main scalable data-processing feature for performance charts

### `tickets:recalculate-sla`

Purpose:

- recalculates SLA flags for tickets in batches

### `analytics:recalculate-departments`

Purpose:

- updates department metrics used by manager/analytics dashboards

### `analytics:send-digest`

Purpose:

- generates scheduled analytics digest output

### `site:readiness-check`

Purpose:

- reports production/demo readiness risks
- supports admin governance view and command-line checks

## 6. Filament Admin Resources

Filament resources provide administrative management surfaces for:

- users
- departments
- categories
- links
- announcements
- tickets
- ticket attachments
- ticket comments
- ticket status changes
- SLA settings
- role change requests
- audit logs
- performance snapshots

Access is constrained through `User::canAccessPanel()` and the admin panel role middleware.
