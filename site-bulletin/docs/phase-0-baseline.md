# Phase 0 – Baseline Inventory

This document captures the current application shape before we begin schema and feature work. It will serve as a checklist to validate migrations, seed data, admin tooling, and authorization once later phases land.

## Schema & Migrations

- Core Laravel tables plus domain-specific migrations live in `database/migrations/`. Current domain tables (in creation order) include users, categories, links, announcements, tickets, ticket comments/attachments/status changes, SLA settings, performance snapshots, and duplicate-ticket support.
- Tickets track requester/assignee/category/priority/status plus duplicate relationships (`2025_10_29_155454_create_tickets_table.php`, `2025_11_02_153000_add_duplicate_of_id_to_tickets_table.php`).
- Categories include `is_sensitive` to hide items from non managers (`2025_11_02_150500_add_is_sensitive_to_categories_table.php`).
- No department or messaging tables exist yet; all role information sits on `users.role`.

## Seed Data & Factories

- `database/seeders/DatabaseSeeder.php` provisions one admin, manager, and employee, builds sample categories/links, seeds announcements, SLA defaults, ~20 tickets with status trails/comments/attachments, and creates six weeks of performance snapshots per user.
- Supporting seeders/factories exist for each model under `database/seeders/` and `database/factories/`, enabling targeted refreshes if needed.
- Notifications are not persisted; demo data relies entirely on seeded tickets and performance snapshots.

## Admin / Filament Resources

- Filament resource directories exist for announcements, categories, links, tickets (and child records), SLA settings, performance snapshots, and users (`app/Filament/Resources/**`).
- Navigation groups are split between “Content” (announcements/links) and “Configuration” (users, SLA settings). Only admins can access configuration resources; other resources inherit default policies (currently restrictive).
- User resource (`Users\UserResource`) only manages `name`, `email`, `password`, and `role`, reflecting the simple schema.

## Authorization & Roles

- `App\Models\User` defines `role` plus helpers `isAdmin/isManager/isEmployee`. There is no concept of departments or sub-roles.
- `App\Policies\TicketPolicy` gives employees/managers ticket abilities; managers cannot act on sensitive tickets (those belonging to sensitive categories).
- `AppServiceProvider` registers gates granting admins blanket access and defines coarse abilities: manage public content (manager/admin), manage SLA (admin), view analytics (manager/admin), manage users (admin).

## Public Experience

- Dashboard controller pulls active announcements (global) and quick-link categories, filtering sensitive categories for non managers. Performance snapshots display only for employees.
- Ticket module supports employee submissions and manager triage but lacks department context, messaging, or on-behalf-of creation.
- Analytics view (manager/admin) surfaces ticket metrics but no audit log UI.

## Gaps Identified for Later Phases

- No department tables, manager hierarchies, or HR role separation.
- Announcements/links cannot target audiences.
- Messaging layer absent; only ticket comments exist.
- Managers cannot create tickets on behalf of others due to route middleware limits.
- Admin tooling lacks audit log viewer and promotion/demotion flows beyond manual role edits.
- UI currently limited to dashboard/ticket views; no broader portal pages as seen in the reference site.

This snapshot should be updated after each milestone if migrations or structural assumptions change.
