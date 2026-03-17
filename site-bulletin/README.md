# Site Bulletin

## Overview
Site Bulletin is a Laravel-based internal operations portal that simulates the communication, ticketing, analytics, and governance workflows of a fulfillment-centre environment. It is the primary implementation in this repository and the production behavior source of truth.

The app supports role-based experiences for:
- `employee`
- `manager`
- `ops_manager`
- `hr`
- `admin`

## Core Functionality

### Dashboard
- Role-aware dashboard layouts for employees and leadership users.
- Employee view includes:
  - `My Work Today`
  - performance and quality trend charts
  - unread announcement/message summaries
  - ticket focus and shift context
- Manager view includes:
  - department operational performance
  - attention queue
  - SLA health
  - breach drilldown
  - ticket type breakdown
  - benchmark comparison

### Performance Simulation
- Employee productivity and quality are stored as deterministic 15-minute `performance_samples`.
- Weekly `performance_snapshots` are derived from those samples.
- Dashboard trend windows use the same base source:
  - `Last 7 days` = daily aggregation
  - `Last 24 hours` = intraday aggregation
  - `Last 3 hours` = short-window 15-minute points
- Demo data can be refreshed to current local time with:
  - `php artisan demo:backfill-ops-data`

### Announcements
- Audience targeting for `all`, department-scoped, and leadership-focused content.
- Priority levels with unread and high-priority visibility.
- Read tracking and drill-down detail pages.
- Dashboard news widgets and announcement center views.

### Messaging
- Messenger-style inbox and conversation UI.
- Direct, department, and announcement-linked conversations.
- Unread tracking and role-aware creation permissions.
- Mobile drill-in thread behavior with back navigation.
- File attachments and inline image previews.

### Ticketing and SLA
- Ticket reporting for operational, HR, and support scenarios.
- On-behalf ticket creation for leadership roles.
- Status tracking with status-change history.
- SLA evaluation for first response and active resolution time.
- Simulated rolling SLA activity for realistic demo dashboards.

### Governance
- Audit and governance activity feeds.
- Role change requests and approval workflow.
- Governance hub and policy-related navigation.

### Quick Links and Knowledge
- Quick-link categories seeded from `data/cwl1informationportal/`.
- Role-aware resource visibility.
- Knowledge and portal-reference areas aligned with the implementation guide.

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@example.com` | `password` |
| Manager | `manager@example.com` | `password` |
| HR | `hr@example.com` | `password` |
| Employee | `employee@example.com` | `password` |

## Local Setup

### Prerequisites
- PHP 8.2+
- Composer
- Node.js and npm
- SQLite, MySQL, or PostgreSQL

### Install
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Database
If you want a fresh local environment:
```bash
php artisan migrate:fresh --seed
```

If you already have a local database and only need current demo data:
```bash
php artisan migrate
php artisan demo:backfill-ops-data --refresh
```

### Run
Start the backend:
```bash
php artisan serve --host=localhost --port=8000
```

Start the frontend dev server in another terminal:
```bash
npm run dev
```

Development URLs normally are:
- app: `http://localhost:8000`
- Vite assets: `http://localhost:5173`

### Production-style assets
If you do not want Vite dev mode:
```bash
npm run build
```

## Testing
Run the full Laravel test suite:
```bash
php artisan test
```

Useful focused suites:
```bash
php artisan test tests/Feature/Messaging
php artisan test tests/Feature/DashboardTrendPanelsTest.php
php artisan test tests/Feature/Analytics
```

## Scheduled Jobs
For realistic local automation, Laravel scheduler should run:
```bash
php artisan schedule:run
```

Relevant scheduled commands include:
- `demo:backfill-ops-data`
- `tickets:recalculate-sla`
- `analytics:recalculate-departments`
- `analytics:send-digest`

For a normal cron-based setup:
```bash
* * * * * php /path/to/site-bulletin/artisan schedule:run
```

## Demo Data Model

### Performance
- `performance_samples`: 15-minute base source of truth
- `performance_snapshots`: weekly derived rollups

### SLA
- simulated ticket activity uses deterministic `simulation_key` records
- status-change history drives SLA calculations
- department metrics are recalculated from the ticket/message domain

## Repository Context
- Laravel app: [`site-bulletin/`](c:\Users\kadet\Documents\2526_CSF301_Project Specification and Development\site-bulletin)
- Seeder reference data: [`data/cwl1informationportal/`](c:\Users\kadet\Documents\2526_CSF301_Project Specification and Development\data\cwl1informationportal)

## Notes
- The repository root only keeps governance and repo-level coordination files.
- Product-level documentation now lives here in `site-bulletin/README.md`.
