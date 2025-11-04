# Phase 5 – Governance & Portal Enhancements

## Goal
Give senior staff the tools to monitor sensitive activity, streamline admin approvals, and extend the public-facing portal with governance-focused pages inspired by our reference site.

## Core Outcomes
1. **Audit log visibility** for HR/Admin roles within the Filament admin and a public-facing read-only view for managers with limited scope.
2. **Admin role workflows** to control escalations (role change requests, department manager assignments, SLA overrides).
3. **Governance portal pages** that surface policies, org charts, and escalation playbooks for employees.

---

## Functional Scope

### 1. Audit Logs
- Persist key events: role changes, ticket status updates, SLA breaches, message thread locks/unlocks, and sensitive announcement edits.
- Provide Filament admin resource with advanced filters (date range, actor, event type, target model).
- Create manager-facing dashboard widget highlighting recent audit events relevant to their departments.
- Ensure audit data is searchable without exposing private details to non-authorised users.

### 2. Admin Workflows
- Introduce requests queue for employees/managers to propose role changes or department realignments.
- Provide approval UI for HR/Admin that records decisions and triggers notifications.
- Extend Ticket SLA settings to require approval before thresholds change; log each change.
- Add guardrails to prevent direct database edits by locking fields behind approval status.

### 3. Governance Portal Pages
- Build public portal pages under `/governance`:
  - **Policies & Procedures:** Markdown-backed content with version history.
  - **Org Structure:** Visual hierarchy of departments/managers using existing department data.
  - **Escalation Playbook:** Step-by-step flows referencing ticket categories and messaging channels.
- Include quick actions for managers (start audit report, request role change).
- Add breadcrumbs and navigation items consistent with the current UI system.

---

## Data Model Changes
- `audit_logs` table capturing: actor, event type, auditable model (morph), payload, ip, user agent, occurred_at.
- `role_change_requests` table with requested role, status (`pending`, `approved`, `rejected`), approver_id, decision notes.
- Optionally extend `departments` for governance metadata (policy links, manager notes).

---

## Application Logic & Services
- Service class to record audit events and integrate with existing notification service.
- Policy updates ensuring only authorised roles access audit and workflow resources.
- Queueable jobs for approval notifications (email/log stubs) to keep architecture extensible.

---

## UI/UX Deliverables
- Filament resources for audit logs and role change requests with custom filters/actions.
- Dashboard cards summarising pending approvals and recent audit events.
- New `/governance/*` Blade views with responsive layouts, linked from primary navigation.

---

## Testing Plan
- Feature tests for audit log visibility (admin vs manager vs employee).
- Feature tests for role change request submission and approval flows.
- Policy tests covering new permissions.
- View/route smoke tests for governance pages (authorisation + content rendering).

---

## Work Log Expectations
- Use `docs/progress-log.md` to record milestone updates.
- Capture manual verification notes (screenshots or descriptions) in commit messages or supporting docs.
- Keep the `Phase Implementation Playbook` checklist in mind before marking the phase complete.

---

## Next Steps
1. Create migrations for `audit_logs` and `role_change_requests`.
2. Stub AuditLogger service and integrate with critical events.
3. Build Filament resources + policies.
4. Ship governance portal pages with seeded content.
5. Ensure automated test suite covers new workflows.
