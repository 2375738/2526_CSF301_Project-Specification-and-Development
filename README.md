# Site Bulletin

## Overview & Real-World Context
Site Bulletin is an internal operations portal created as part of my dissertation, inspired by the communication and compliance challenges inside an Amazon fulfillment centre. In that environment, associates, process assistants, area managers, HR, and operations directors rely on fast information flows: shift briefs, safety escalations, ticketing, SLA tracking, and governance reporting. This application simulates how those workflows can be unified under one platform with strong role-based access control, analytics, and automation.

## Core Purpose
Site Bulletin aims to:
- give associates and managers a single source of truth for announcements, tickets, and performance insights;
- accelerate response to safety or customer-impacting issues via streamlined ticketing + messaging;
- provide leadership with governance oversight (audit trails, policy hub, role change approvals);
- automate SLA alerts and analytics digests to reduce manual reporting.

## Feature Summary by Capability

### 1. Roles & Departments
- Roles: `employee`, `manager`, `ops_manager`, `hr`, `admin`. Departments have managers and members.
- Permissions cascade from these roles (see “Role Matrix” below). Policies enforce access to messaging, ticketing, analytics, and governance functions.
- Filament admin pages allow HR/Admin to maintain user/dept data.

### 2. Targeted Content
- Announcements and quick links can be targeted by department or leadership audience, keeping communication relevant.
- Quick Links mirror the latest Linktree snapshot bundled with the project (`data/cwl1informationportal/`) so seeded environments match the real portal.
- Dashboard badges flag which items are department- or manager-specific.

### 3. Messaging & Engagement
- Conversations support three types: `direct`, `department`, `announcement`.
- Employees may initiate direct chats with peers or managers; only leadership roles can broadcast to departments or issue announcements.
- Unread tracking, locking, and audit-logging ensure compliance around sensitive threads.
- Messaging integrates with dashboard widgets for quick awareness.

### 4. Ticketing & SLA Management
- Managers/HR can open tickets on behalf of associates, with department context and automatic SLA evaluation.
- SLA breach flags trigger automation (see below) and power analytics dashboards.
- UI includes filters, badges, department views, and badges for SLA risk, matching typical Amazon-style ticket queues.

### 5. Governance & Compliance
- Governance hub: policy pages, org charts, escalation playbook, and quick links to requests.
- Audit logs record sensitive operations (role approvals, ticket status changes, conversation locks, SLA automation events).
- Role change request workflow captures submissions, approvals, audit trails.
- Dashboards show governance activity and recent audit entries for managers.

### 6. Analytics & Automation
- Daily department metrics (open tickets, breaches, messages, average SLA response/resolution).
- Analytics dashboard with filters, saved views, and CSV exports; employees in leadership roles can analyse trends quickly.
- Scheduled commands recalc metrics and email digests (stored/logged) so leadership receives proactive updates.
- SLA automation service notifies department managers via announcements when breaches occur, logging the action to audit trail.

## Technology Stack & Architecture
- **Framework:** Laravel 10, PHP 8+, Composer-managed dependencies.
- **Front-End:** Blade templates, Tailwind CSS (via Vite). Filament for admin UI components.
- **Database:** MySQL/PostgreSQL friendly; test suite uses SQLite in-memory. Migrations cover all schema changes per phase.
- **Queue/Automation:** Laravel scheduler, queued jobs (future-ready). Automation service objects encapsulate business logic.
- **Testing:** PHPUnit feature + unit tests for messaging, analytics, governance, authentication, automation, and SLA calculators.
- **Documentation:** `/docs/*` holds phase plans, progress log, testing templates, etc.

## Algorithms & Business Logic Highlights
- **SLA Evaluation (`App\Services\SLAService`)**: Calculates first response and resolution active minutes, respects pause states. Used across ticket lifecycle, analytics, and automation.
- **Department Metrics (`DepartmentAnalyticsService`)**: Aggregates daily open ticket counts, breaches, message activity, and averages using scheduled command + caching in `department_metrics` table.
- **Messaging Access Control**: Policies ensure employees can only create direct conversations; department broadcasts are restricted, with automatic downgrading when forged parameters are detected.
- **Automation Service**: When SLA breaches occur, look up stakeholder managers based on ticket department, create announcement conversation, update notification flags, and log via `AuditLogger`.
- **Analytics Export & Digests**: Exports use a dedicated service to build CSV rows; scheduler writes file to disk and logs a digest event (in real deployment could email or push to S3).

## Role Matrix & Permissions
| Feature/Area              | Employee | Manager/Ops Manager | HR | Admin |
|---------------------------|:--------:|:-------------------:|:--:|:-----:|
| Dashboard (basic cards)   | ✅       | ✅                  | ✅ | ✅    |
| Direct messaging          | ✅ (direct only) | ✅ | ✅ | ✅ |
| Department/Announcement messaging | ❌ | ✅ | ✅ | ✅ |
| Tickets (self)            | ✅       | ✅ (on behalf)      | ✅ | ✅    |
| Ticket SLA automation logs | View via dashboard widget | ✅ | ✅ | ✅ |
| Governance hub            | Read-only policies | Full access to audit list; role requests creation | Full access + approvals | Full access |
| Role change approvals     | ❌       | ❌                  | ✅ (approve) | ✅ |
| Analytics dashboard       | ❌       | ✅ (saved views)    | ✅ | ✅ |
| Admin Filament panels     | ❌       | ❌                  | partial (users/depts) | ✅ full |

## Typical Use Cases
1. **Associate raises issue**: Employee reports ticket → SLA evaluated → managers receive alert if breach occurs → resolution tracked with history.
2. **Manager broadcast**: Area manager sends department safety reminder; employees receive read-only announcement thread.
3. **HR governance oversight**: HR reviews audit logs, policies, and approves role change request for new process assistant.
4. **Operations weekly review**: Ops manager loads saved analytics view (e.g., “Inbound 14-day trend”), downloads CSV, and cross-references SLA breaches before stand-up.
5. **Automation pipeline**: Overnight jobs recalc SLA/ticket metrics, generate digest, log output. Next morning managers view aggregated data on dashboard.

## Technology Setup & Running Locally
1. **Clone & install**  
   ```bash
   git clone <repo-url>
   composer install
   npm install
   npm run build # or npm run dev
   ```
2. **Configure environment**  
   Copy `.env.example` → `.env`; set DB credentials, `APP_KEY`, queue/mail if required.
3. **Database**  
   ```bash
   php artisan migrate --seed
   ```
   Seeders create sample departments, users, governance data, tickets, conversations, metrics.
4. **Serve**  
   ```bash
   php artisan serve
   ```
5. **Tests**  
   ```bash
   php artisan test
   ```
   Full suite verifies messaging policies, SLA automation, analytics commands, etc.
6. **Scheduled Tasks**  
   Register cron `* * * * * php /path/to/artisan schedule:run` to execute daily SLA recalcs and analytics digests.

## Seeded Accounts
| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Ops Manager | manager@example.com | password |
| HR | hr@example.com | password |
| Employee | employee@example.com | password |

Login as different roles to experience UI/permissions (e.g., employee sees direct messaging and ticket submission; manager sees analytics, governance, and departmental messaging).

## Documentation Trail
- `docs/progress-log.md` – Detailed history of each phase, completed dates, follow-ups.
- `docs/phase-6-analytics-automation-plan.md` – Planning notes plus completion addendum.
- Other docs in `/docs` capture phase plans, testing templates, risk register, etc.

## Future Enhancements (Backlog)
- Messaging file attachments & push/email notifications.
- Visualization charts for analytics dashboard, automated email digests to actual recipients, deeper automation triggers on repeated SLA breaches.
- Integration with external systems (e.g., Amazon’s Ops ticketing or S3 for export storage) for production-scale deployments.

---
Site Bulletin demonstrates how an Amazon fulfilment team could integrate communication, ticketing, governance, and analytics in one Laravel platform—balancing associate usability with leadership insight and automation. Use the seeded data to explore workflows end-to-end and adapt the code for additional dissertation experiments or production pilots.
