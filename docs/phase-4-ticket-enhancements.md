# Phase 4 – Ticketing Enhancements

Objectives:
- Allow managers/HR to create or escalate tickets on behalf of employees and departments.
- Capture department context on tickets for better filtering/analytics.
- Lay groundwork for SLA alerts and messaging tie-ins.

## Planned Changes

### Schema
- Add `created_for_id` (nullable) to `tickets` referencing `users.id`. Represents the employee the ticket affects when raised by a manager/HR.
- Add `department_id` (nullable) to `tickets` to associate issues with a team; default to requester's primary department when available.
- Optional: add `escalation_level` enum/string for future use.

### Policies & Authorization
- Update `TicketPolicy::create` so managers/HR can raise tickets even when not employees.
- Restrict department field to departments the manager oversees (unless HR/admin).
- Ensure assignment/visibility respects sensitive categories + department context.

### Controllers
- `ReportTicketController@create` / `store`
  - Accept optional `created_for_id` and `department_id`.
  - When manager selects an employee, default department accordingly.
  - Allow HR/managers to browse employee list; employees keep original behavior.
- Add helper to auto-populate department when employee creates.
- Optionally send notification if ticket is raised on behalf of someone.

### UI
- Modify ticket report form:
  - If user can act on behalf, show dropdown for employee + department.
  - Display info badge indicating requester vs affected employee.
- Ticket detail header should show "Opened by {manager} for {employee}" when applicable.
- Ticket list filters: add department filter if field exists.

### SLA Alerts (initial steps)
- Introduce computed flags on ticket model for SLA breach (`sla_first_response_breached`, etc.) using existing service to power future alerts.
- Optional: when breach occurs, push message to conversation (phase 3 integration) – placeholder for now.

### Seed Data + Tests
- Seeder creates example tickets raised by manager on behalf of employee with department set.
- Smoke tests/feature tests for create flow and visibility.

Implementation order: migrations -> model updates -> policy/controller changes -> form/UI -> seeds/tests.
