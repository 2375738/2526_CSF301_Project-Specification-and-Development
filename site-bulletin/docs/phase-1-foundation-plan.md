# Phase 1 – Roles & Department Foundation

This phase lays the groundwork for all department-aware features. It introduces structured department data, clarifies global roles (employee, manager, HR, admin, operational manager), and records management hierarchies so future phases (targeted content, messaging, ticket escalation) can rely on consistent relationships.

## Schema Changes

1. **Departments**
   - Table: `departments`
   - Columns: `id`, `name`, `slug`, `description`, `color`, timestamps.
   - Purpose: single source for all department metadata, used for announcements, tickets, and targeting.

2. **User Primary Department**
   - Add nullable `primary_department_id` to `users` with FK to `departments`.
   - Represents the default department for an employee/manager; simplifies querying and aligns with the request for a “department attribute” on the employee.

3. **Department Membership & Roles**
   - Pivot table: `department_user`
   - Columns: `id`, `department_id`, `user_id`, `role` (`member`, `manager`, `hr_manager`, `observer`), `is_primary`, timestamps.
   - Captures multi-department assignments and department-specific roles (e.g., managers covering multiple departments, HR business partners).

4. **Management Hierarchy**
   - Table: `manager_relationships`
   - Columns: `id`, `manager_id`, `reports_to_id`, `relationship_type` (`direct`, `operational`, `dotted`), timestamps.
   - Supports “managers having their own operational managers” by linking managers in a flexible hierarchy. Later phases can query this chain for escalations or permission scopes.

5. **Role Enum Extension**
   - Keep `users.role` but expand enum/string values to include `employee`, `manager`, `hr`, `ops_manager`, and `admin`.
   - Introduce `App\Enums\UserRole` for type safety and centralised role checks (`isHr()`, `isOpsManager()` helpers on `User` model).

## Model & Factory Updates

- `App\Models\User`
  - Add relationships: `primaryDepartment()`, `departments()`, `managedDepartments()`, `managerRelationships()`, `reportsToMany()`.
  - New helper scopes: `scopeDepartment($query, $departmentId)`, `scopeRole(UserRole $role)`.
  - Update `$fillable` / `$casts` to handle `primary_department_id` and `role` enum.

- `App\Models\Department`
  - New model with relationships to users (`members()`, `managers()`, `hrManagers()`).

- Factories & seeders
  - Add `DepartmentFactory`.
  - Update seeders to create sample departments, link existing admin/manager/employee accounts, and demonstrate multiple managers per department.

## Authorization & Policies

- Refresh `AppServiceProvider` gates to incorporate HR/ops roles.
- Extend `TicketPolicy` (and others, if needed) to account for department-based permissions (e.g., managers can only triage tickets for departments they manage—enforced in later phases but helpers land here).
- Provide reusable policy helpers for “user manages department X or reports to manager Y”.

## Filament / Admin UX

- Update `Users\UserResource` form/table to manage `primary_department_id`, assign secondary departments (multi-select), and set global role.
- Add new `DepartmentResource` (CRUD) so admins can maintain department metadata.
- Admins can assign/remove managers for a department via relation managers or custom actions.

## Data Migration Notes

- Migration order: create departments → add column to users → create pivot and hierarchy tables.
- Backfill existing users by creating default departments (e.g., “Operations”) and linking seeded users via `department_user`.
- Ensure nullable defaults so existing data keeps working until seeds/assignments run.

## Testing Considerations

- Feature tests to verify:
  - Users resolve their primary department correctly and membership queries return expected IDs.
  - Manager hierarchy queries return the correct parent managers.
  - Filament forms save/read the new attributes.
- Database tests for migrations to confirm FK constraints and cascading deletes behave as expected.

With this foundation in place, Phase 2 (targeted content) can scope announcements/links by department, and later phases (messaging, ticket escalation) can leverage the same relationships without additional schema work.
