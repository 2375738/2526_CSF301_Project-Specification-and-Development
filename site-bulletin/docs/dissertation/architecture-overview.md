# Site Bulletin Architecture Overview

## 1. Application Purpose

Site Bulletin is a Laravel-based internal operations portal for a fulfilment-centre environment. It combines communication, ticket management, operational performance monitoring, governance, and role-based work queues into one application.

The system is designed around the following operational problems:

- employees need a central place to report issues, read updates, find knowledge, and understand their immediate work status
- managers need department-level visibility into tickets, SLA pressure, role requests, employee performance, and repeated blockers
- operations managers need site-wide visibility across departments
- HR needs a privacy-aware workspace for people-related tickets, approval work, and sensitive cases
- administrators need governance, readiness, user management, and audit visibility

The application is implemented as a conventional Laravel monolith with a Blade/Tailwind/Alpine frontend, Eloquent domain models, policies for authorization, service classes for business logic, scheduled Artisan commands, and Filament for administrative resource management.

## 2. Main Architectural Style

The system follows a layered monolithic architecture:

1. Presentation layer
   - Blade views in `resources/views`
   - Tailwind CSS and Alpine.js interactions
   - Filament admin resources under `app/Filament`

2. HTTP and routing layer
   - Route definitions in `routes/web.php` and `routes/auth.php`
   - Public-facing controllers in `app/Http/Controllers/Public`
   - Messaging controllers in `app/Http/Controllers/Messaging`
   - Analytics controller in `app/Http/Controllers/Admin`
   - Request validation objects in `app/Http/Requests`

3. Authorization layer
   - `EnsureRole` middleware for route-level role checks
   - Laravel policies for model-level decisions
   - `RoleScopeService` for reusable department-scope decisions

4. Domain and data layer
   - Eloquent models in `app/Models`
   - Database migrations in `database/migrations`
   - Seeders and factories for repeatable demo/test data

5. Service layer
   - Business logic and aggregation services in `app/Services`
   - Services keep controllers thinner and allow tests to exercise domain behaviour independently

6. Background processing layer
   - Scheduled commands in `app/Console/Commands`
   - Laravel scheduler in `app/Console/Kernel.php`
   - Commands handle demo backfill, SLA recalculation, analytics recalculation, performance rollups, readiness checks, and analytics digest export

## 3. Runtime Architecture

At runtime, Site Bulletin has these main execution paths:

- interactive HTTP requests from authenticated users
- Filament admin-panel requests for manager/admin users
- scheduled command execution for analytics and data maintenance
- notification dispatch from observers and services
- test/demo data generation through seeders and simulation services

The system is currently configured for local development with SQLite, database-backed cache, database-backed queues, and log mail. In a production deployment, the same architecture can move to MySQL or PostgreSQL, a durable queue backend, real mail transport, and scheduled cron execution.

## 4. User Roles

The application defines these core roles in `App\Enums\UserRole`:

- `employee`
  - reports issues
  - tracks own tickets
  - reads announcements and knowledge
  - views personal performance and work summary

- `manager`
  - manages assigned departments
  - views department tickets and analytics
  - approves selected ticket workflows
  - reviews role requests for managed departments
  - accesses governance and analytics views

- `ops_manager`
  - has site-wide operational visibility
  - can view and manage tickets across departments
  - can operate across the triage and approval workbenches

- `hr`
  - has people-operations workspace
  - can handle HR-sensitive tickets and approvals
  - can view role requests and governance areas

- `admin`
  - has broad authorization through `Gate::before`
  - can access governance readiness checks and Filament management resources

## 5. Bounded Contexts

Although implemented as a monolith, the application is organised into bounded contexts.

### Authentication And Demo Access

Key files:

- `AuthenticatedSessionController`
- `LoginRequest`
- `routes/auth.php`
- `resources/views/auth/login.blade.php`

Responsibilities:

- standard Laravel login/logout
- quick demo login for seeded roles
- demo login presets for employee, manager, ops manager, HR, and admin

### Dashboard And Role Workspaces

Key files:

- `DashboardController`
- `MyWorkController`
- `DashboardDataService`
- `RoleActionService`
- `HrWorkspaceService`
- `OperationalPreventionService`
- dashboard partials under `resources/views/dashboard/partials`

Responsibilities:

- role-specific home dashboard
- employee "My Work Today"
- manager department trend and attention queue
- HR people operations workspace
- admin readiness surface
- prevention watch for repeat issues and breach pressure

### Ticketing And SLA

Key files:

- `ReportTicketController`
- `TicketViewController`
- `TicketStatusController`
- `TicketApprovalController`
- `TicketApprovalQueueController`
- `SupportTriageBoardController`
- `TicketPolicy`
- `SLAService`
- `SlaAutomationService`
- `RecalculateTicketSLA`

Responsibilities:

- ticket reporting
- on-behalf ticket creation
- employee self-service ticket queues
- status transitions
- SLA calculation and breach flags
- approval workflows
- triage board
- duplicate issue linking and repeat issue analysis

### Announcements And Knowledge

Key files:

- `AnnouncementController`
- `KnowledgeSnippetController`
- `AnnouncementObserver`
- `AnnouncementPolicy`
- `Category`, `Link`, `KnowledgeSnippet`, `Announcement`

Responsibilities:

- targeted announcements
- read and acknowledgement receipts
- quick links
- searchable knowledge snippets
- grouped help search across knowledge, quick links, announcements, and report guidance

### Messaging And Notifications

Key files:

- `ConversationController`
- `MessageController`
- `MessageAttachmentController`
- `NotificationController`
- `MessageObserver`
- `NotificationService`
- notification classes under `app/Notifications`

Responsibilities:

- direct conversations
- department broadcasts
- attachment messages
- unread tracking
- actionable notification dropdown
- notification preferences

### Analytics And Performance Telemetry

Key files:

- `AnalyticsController`
- `AnalyticsExportService`
- `DepartmentAnalyticsService`
- `DemoOperationsSimulationService`
- `PerformanceRollupService`
- `PerformanceService`
- `RecalculateDepartmentMetrics`
- `RollupPerformanceSamples`
- `BackfillDemoOperationsData`

Responsibilities:

- department analytics dashboard
- CSV export
- saved analytics views
- performance simulation
- raw 15-minute performance samples
- hourly/daily rollups
- weekly snapshots
- scalable chart data preparation

### Governance And Readiness

Key files:

- `GovernanceController`
- `RoleChangeRequestController`
- `SystemReadinessService`
- `SystemReadinessCheck`
- `AuditLogger`
- `AuditLogPolicy`
- `RoleChangeRequestPolicy`

Responsibilities:

- governance hub
- policy and escalation pages
- role-change workflow
- audit visibility
- operational readiness checks for production/demo boundary, queue, mailer, storage, metrics, and rollups

## 6. Primary Request Lifecycle

Typical authenticated dashboard request:

1. Browser sends request to `/` or `/dashboard`.
2. Laravel route resolves to `DashboardController`.
3. `auth` middleware confirms the session user.
4. `DashboardController` delegates to `DashboardDataService`.
5. `DashboardDataService` uses the authenticated user role to decide which data to fetch.
6. Domain services build role-specific data:
   - `RoleActionService` builds next actions.
   - `HrWorkspaceService` builds HR-only queues.
   - `OperationalPreventionService` builds repeat issue and breach pressure signals.
   - `PerformanceRollupService` supplies scalable chart data.
7. Eloquent models query the database through scoped relationships and policies.
8. Blade views render the dashboard and navigation shell.

## 7. Authorization Strategy

Authorization is enforced at multiple levels:

- route middleware:
  - `auth` protects the main app
  - `role:*` protects workbenches such as analytics, triage, and approval queues

- policies:
  - `TicketPolicy` controls ticket view/comment/upload permissions
  - `ConversationPolicy` controls messaging access
  - `AuditLogPolicy` controls governance visibility
  - `RoleChangeRequestPolicy` controls role request decisions

- service-level scoping:
  - `RoleScopeService` centralises department visibility and manageability
  - managers are limited to managed departments
  - ops manager, HR, and admin can operate site-wide where appropriate

This layered approach prevents a controller from accidentally exposing data just because a route is protected.

## 8. Data Architecture Summary

The data model is centred on these groups:

- identity and organisation:
  - `users`
  - `departments`
  - `department_user`
  - `manager_relationships`

- communication:
  - `announcements`
  - `announcement_reads`
  - `conversations`
  - `conversation_participants`
  - `messages`
  - `message_attachments`
  - `notifications`

- ticketing:
  - `tickets`
  - `ticket_comments`
  - `ticket_attachments`
  - `ticket_status_changes`
  - `ticket_approvals`
  - `s_l_a_settings`

- analytics and performance:
  - `department_metrics`
  - `performance_samples`
  - `performance_rollups`
  - `performance_snapshots`
  - `saved_analytics_views`

- governance:
  - `audit_logs`
  - `role_change_requests`

## 9. Complexity Features For Dissertation Discussion

The implementation includes several complexity points suitable for dissertation analysis:

- multi-role authorization with different department scopes
- model policies plus reusable scope services
- ticket lifecycle state management
- SLA evaluation and recalculation
- approval chains for ticket templates
- notification preferences and actionable alerts
- department analytics and export
- performance telemetry simulation
- rollup-based data processing for high-volume chart data
- readiness checks that expose operational risk before deployment
- test coverage across authorization, workflows, dashboards, analytics, messaging, governance, and data processing
