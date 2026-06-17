# Data Flow And Processing Documentation

This document explains how data moves through Site Bulletin, with particular attention to high-volume operational data and the processing steps used to keep the application responsive.

## 1. Data Flow Principles

The application follows these data-flow principles:

1. User input is validated before domain changes are made.
2. Role and department scope are applied before sensitive records are queried.
3. Business workflows are recorded as database state, not only as UI state.
4. Time-based operational metrics are pre-aggregated for repeated dashboard reads.
5. Audit and notification side effects are created from domain events or service actions.
6. Raw operational data is retained only where detailed short-window analysis is needed.

## 2. Core Data Domains

### 2.1 Identity And Organisation

Main tables:

- `users`
- `departments`
- `department_user`
- `manager_relationships`

Main flow:

1. A user logs in through Laravel authentication or demo login.
2. The user model exposes role helpers such as `hasRole`, `isEmployee`, `isManager`, `isHr`, and `isOpsManager`.
3. Department relationships are resolved through:
   - `primary_department_id`
   - `departments` many-to-many relationship
   - `managedDepartments`
   - `managerRelationships`
4. `RoleScopeService` converts role and department membership into reusable query constraints.

Why this matters:

- almost every feature depends on the user's role and department scope
- centralising scope rules prevents inconsistent authorization logic across controllers

### 2.2 Ticketing

Main tables:

- `tickets`
- `ticket_comments`
- `ticket_attachments`
- `ticket_status_changes`
- `ticket_approvals`
- `s_l_a_settings`

Ticket creation flow:

1. User opens `/tickets/report`.
2. `ReportTicketController@create` renders templates and form data.
3. User submits form to `ReportTicketController@store`.
4. `StoreTicketRequest` validates form data.
5. Controller resolves requester, affected user, department, template, priority, and category.
6. Ticket is stored.
7. Template answers are stored in ticket fields where relevant.
8. Approval records may be generated for workflows such as shift swap or missed punch.
9. Initial SLA and status state is established.
10. User is redirected to the new ticket detail page.

Ticket view flow:

1. User requests ticket index or ticket detail.
2. `TicketViewController` builds a query.
3. Query is scoped by role:
   - employee: own/requested/created-for tickets
   - manager: managed departments
   - ops manager/HR/admin: broader access
4. `TicketPolicy` protects detail-level access.
5. Blade view renders lifecycle, comments, attachments, approvals, and sensitive-case guidance.

Status update flow:

1. Authorised user submits status update.
2. `TicketStatusRequest` validates requested transition.
3. `TicketStatusController` updates ticket status.
4. `ticket_status_changes` records the transition.
5. `SLAService` recalculates breach state.
6. `AuditLogger` and notifications may record side effects.

Approval flow:

1. Ticket template creates approval rows.
2. Manager/HR/ops users open approval workbench.
3. `TicketApprovalQueueController` scopes approval records.
4. User submits approval decision.
5. `TicketApprovalController` records decision and note.
6. Ticket state may progress, pause, request more info, or resolve depending on the approval chain.

### 2.3 SLA Data Processing

Main classes:

- `SLAService`
- `SlaAutomationService`
- `RecalculateTicketSLA`

Processing flow:

1. Ticket priority maps to first-response and resolution target minutes.
2. Status history is inspected to find first response.
3. Open and closed statuses are evaluated against configured SLA targets.
4. Breach flags are stored on the ticket:
   - `sla_first_response_breached`
   - `sla_resolution_breached`
5. Scheduled recalculation handles long-running tickets and stale flags.
6. Automation can notify stakeholders and record audit events.

Reasoning:

- storing breach flags makes dashboard and filter queries faster
- status history remains the auditable source for how the flag was derived

### 2.4 Announcements And Acknowledgements

Main tables:

- `announcements`
- `announcement_reads`
- `notifications`

Flow:

1. Manager, HR, ops manager, or admin creates announcement.
2. `AnnouncementController` validates audience and department scope.
3. `AnnouncementObserver` can trigger notifications.
4. Users see announcements filtered by `Announcement::visibleTo`.
5. Opening announcement marks it read.
6. Acknowledgement stores:
   - read time
   - acknowledgement value
   - acknowledgement time

Audience rules:

- all users
- department-specific users
- manager/leadership audiences

### 2.5 Messaging

Main tables:

- `conversations`
- `conversation_participants`
- `messages`
- `message_attachments`
- `notifications`

Flow:

1. User creates a direct or department conversation.
2. `ConversationStoreRequest` validates request type and recipient scope.
3. Participants are attached to the conversation.
4. Messages are stored through `MessageController`.
5. Attachments are stored through message attachment handling.
6. `MessageObserver` can update conversation state and notify recipients.
7. `Conversation::unreadCountFor` calculates unread counts for navigation and dashboards.

### 2.6 Knowledge And Help Search

Main tables:

- `knowledge_snippets`
- `categories`
- `links`
- `announcements`

Flow:

1. User opens `/knowledge`.
2. Search query is applied across visible knowledge snippets.
3. Additional grouped results can include quick links, announcements, and guided report suggestions.
4. Visibility rules filter out department-only or manager-only material.

Purpose:

- reduce support load by directing employees to self-service guidance before ticket creation
- provide managers with escalation guidance not visible to employees

## 3. Performance Telemetry Data Processing

This is the most important data-processing pipeline in the current application because it converts high-volume raw operational telemetry into smaller chart-ready data.

### 3.1 Problem

The application stores productivity and quality observations every 15 minutes for each employee.

If the system has:

- 2,000 employees
- roughly 40 samples per employee per working day
- 250 working days per year

Then annual raw sample volume can approach:

`2,000 * 40 * 250 = 20,000,000 raw sample rows`

If every dashboard request scans raw rows for all employees in a department, the application will slow down as data grows. Repeated chart queries become expensive because they aggregate the same historical periods again and again.

### 3.2 Tables In The Performance Pipeline

#### `performance_samples`

Purpose:

- raw 15-minute employee telemetry

Fields:

- `user_id`
- `recorded_at`
- `units_per_hour`
- `quality_score`

Use:

- short-window live operational views
- source data for hourly and daily rollups
- source data for weekly snapshots

#### `performance_rollups`

Purpose:

- pre-aggregated chart data
- stores hourly and daily aggregates

Fields:

- `user_id`
- `bucket_type`
  - `hour`
  - `day`
- `bucket_start`
- `sample_count`
- `avg_units_per_hour`
- `avg_quality_score`

Indexes:

- unique key on `user_id`, `bucket_type`, `bucket_start`
- index on `bucket_type`, `bucket_start`

Use:

- dashboard chart reads
- long-window analytics
- raw-sample retention support

#### `performance_snapshots`

Purpose:

- weekly summary records
- used for employee trend/risk evaluation

Fields:

- `user_id`
- `week_start`
- `units_per_hour`
- `rank_percentile`
- `quality_score`

Use:

- performance risk flagging
- fallback employee trend data
- weekly comparison

### 3.3 Processing Pipeline

Raw generation or ingestion:

1. Operational event occurs or demo simulation generates a sample.
2. A row is written to `performance_samples`.
3. The row is uniquely identified by `user_id` and `recorded_at`.

Rollup generation:

1. Scheduler runs `performance:rollup-samples --days=8` hourly.
2. `RollupPerformanceSamples` command calls `PerformanceRollupService`.
3. `PerformanceRollupService` reads raw samples day by day.
4. Samples are grouped by:
   - user and hour
   - user and day
5. Aggregates are calculated:
   - sample count
   - average units per hour
   - average quality score
6. Results are upserted into `performance_rollups`.

Dashboard use:

1. Dashboard requests chart data.
2. `DashboardDataService` resolves relevant employee IDs.
3. If rollups exist, `PerformanceRollupService` supplies:
   - daily series for last seven days
   - hourly series for last 24 hours
4. `DashboardDataService` still reads raw samples for last three hours.
5. Chart data is rendered in Blade.

Retention:

1. After enough rollups exist, command may be run with `--prune-raw-after-days`.
2. Old raw rows are deleted.
3. Rollup rows remain available for dashboards and dissertation analytics.

### 3.4 Why The Rollup Pipeline Improves Scalability

Before rollups:

- chart requests query raw samples
- PHP groups samples in memory
- query size grows with number of users and time range
- each request repeats the same aggregation work

After rollups:

- scheduled command performs aggregation once
- dashboard reads compact hourly/daily records
- historical chart reads are bounded
- raw samples are only needed for short live windows
- old raw data can be pruned without losing historical chart visibility

### 3.5 Example Volume Reduction

Assume one employee has 40 raw samples per day.

Daily raw storage:

- 40 rows per employee per day

Hourly rollup storage:

- roughly 10 working-hour buckets per employee per day

Daily rollup storage:

- 1 row per employee per day

For a department of 100 employees over 7 days:

- raw samples: about `100 * 40 * 7 = 28,000` rows
- daily rollups: `100 * 7 = 700` rows before weighted grouping
- dashboard grouped daily series: 7 final chart points

The dashboard no longer needs to repeatedly scan tens of thousands of raw rows for a standard seven-day view.

## 4. Department Analytics Data Flow

Main tables:

- `department_metrics`
- `tickets`
- `ticket_status_changes`

Flow:

1. Scheduled command runs `analytics:recalculate-departments`.
2. `DepartmentAnalyticsService` recalculates metrics per department and date.
3. Metrics are stored in `department_metrics`.
4. Dashboard and analytics views read precomputed metrics.

Metrics include:

- average resolution minutes
- open tickets
- SLA breaches
- department trend values

Reason:

- managers need repeated dashboard reads
- recalculating department metrics directly from tickets on each request would become expensive

## 5. Dashboard Data Flow

Dashboard request:

1. User accesses `/` or `/dashboard`.
2. `DashboardController` delegates to `DashboardDataService`.
3. `DashboardDataService` identifies user role.
4. It builds only the sections required for that role.

Employee dashboard:

- announcements
- message preview
- personal tickets
- work summary
- performance chart
- next actions

Manager dashboard:

- department trend overview
- attention queue
- SLA health
- prevention watch
- benchmark comparison
- role requests
- next actions

Ops manager dashboard:

- site-wide pressure
- site-wide prevention watch
- approvals and triage access

HR dashboard:

- people operations queue
- sensitive cases
- approvals
- role requests

Admin dashboard:

- readiness and system health
- governance and configuration actions

## 6. Navigation Data Flow

Navigation is generated through `NavigationViewData`.

Flow:

1. Layout composer in `AppServiceProvider` injects navigation data into `layouts.app`.
2. `NavigationViewData` checks authenticated user.
3. It builds desktop and mobile nav items.
4. Badge counts are calculated for relevant items.
5. Blade layout renders role-aware navigation.

Benefits:

- layout file stays less query-heavy
- mobile navigation is derived from named items
- role visibility is centralised

## 7. Audit Data Flow

Main table:

- `audit_logs`

Flow:

1. A significant domain action occurs, such as status change, approval, lock toggle, or role request decision.
2. `AuditLogger` writes actor, event type, auditable model, and payload.
3. Governance views use policies and role scope to decide which audit rows are visible.

Purpose:

- traceability
- governance evidence
- dissertation discussion of accountability and data provenance

## 8. Readiness Data Flow

Main class:

- `SystemReadinessService`

Flow:

1. Admin opens governance dashboard or command is run.
2. Service executes checks.
3. Each check returns status, label, detail, and action.
4. UI or command renders summary.

Checks:

- application debug mode
- demo mode boundary
- prototype footer
- queue driver
- mailer
- failed jobs
- public storage link
- writable storage
- users without departments
- employees without managers
- department metric freshness
- demo sample freshness
- performance rollup freshness

## 9. Suggested Dissertation Framing

The data-processing argument can be framed as:

"The system originally captured operational telemetry as raw 15-minute records. This is appropriate for short-window monitoring but unsuitable as the sole source for repeated dashboard analytics at production scale. To address this, a scheduled aggregation pipeline was introduced. Raw samples are transformed into hourly and daily rollups, allowing dashboards to query compact aggregate records while retaining short-window raw data for live operational detail. This creates a multi-resolution analytics model: raw data for recent precision, hourly data for intraday trend analysis, daily data for weekly dashboards, and weekly snapshots for longer-term employee risk evaluation."
