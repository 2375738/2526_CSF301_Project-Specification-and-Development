# Phase 6 – Analytics & Automation

## Vision
Provide leadership with deeper operational insight and automate follow-up actions so the platform becomes a proactive assistant rather than a static tracker.

## Focus Areas

1. **Analytics Enhancements**
   - Expand dashboards with trends: ticket aging, SLA performance by department, messaging response times.
   - Add comparison widgets (week-over-week, department vs. site average).
   - Export improvements: schedule recurring CSV/Excel delivery to managers.

2. **Automation Hooks**
   - SLA breach automation: when tickets breach, auto-message relevant managers and log follow-up tasks.
   - Role workflow automation: when requests are approved, notify involved parties and document outcomes in audit logs automatically.
   - Escalation triggers for governance policies (e.g. repeated breaches trigger a governance review task).

3. **Self-Service Insights**
   - Allow managers to filter and save custom analytics views (e.g. favorites or quick filters).
   - Provide drill-down from dashboard cards to underlying tickets/messages.
   - Integrate analytics summaries into weekly email digests or dashboard alerts.

## Data & Domain Updates
- Introduce aggregated tables or cached views for analytics to avoid heavy queries.
- Extend SLA metrics with historical snapshots (store weekly aggregates per department).
- Add automation queue table if necessary to track pending actions and avoid duplicate triggers.

## Application Logic
- Services to generate analytics datasets (leveraging existing `SLAService`).
- Job/command to run nightly automation routines (recalculate stats, send notifications).
- APIs or endpoints for scheduled exports.

## UI Deliverables
- Dashboard analytics cards with filters and comparison toggles.
- Dedicated `/analytics/overview` or expansions to existing admin pages.
- Modal or page for saved filters/self-service analytics.

## Testing & QA
- Feature tests for analytics filters; unit tests for aggregation services.
- Integration tests ensuring automation triggers create messages/AuditLog entries.
- Performance assertions (optional) to ensure analytics queries stay within time limits.

## Next Steps
1. Validate analytics requirements with stakeholders (which metrics matter most?).
2. Design aggregation strategy (database views vs. background jobs).
3. Implement automation hooks tied to SLA breaches and role approvals.
4. Build analytics UI + exports and cover with automated tests.

## Completion Notes *(2025‑11‑05)*
- Implemented daily department metrics via dedicated schema, command, and dashboard/admin widgets.
- Added SLA breach automation service with audit logging and message notifications.
- Expanded analytics UI with trend filters and manager-focused insights; tests cover metrics command and automation flows.
