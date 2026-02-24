# PHP vs TS Parity Matrix
Last updated: 2026-02-24

## Scope
Comparison between:
- Laravel app: `site-bulletin/`
- Sample guide app: `Site Bulletin Implementation Guide/`

Status labels:
- `Implemented`: behavior exists in Laravel (UI can differ).
- `Partial`: some behavior exists, but notable guide behavior is missing.
- `Missing`: behavior not present in Laravel user-facing flow.
- `Exceeds`: Laravel has broader capability than guide sample.

## Matrix
| Capability | Guide (TS) | Laravel (PHP) | Status | Notes |
|---|---|---|---|---|
| Authentication entry | Demo login with role/department selector in-app | Real Laravel auth + non-production demo role/department switcher on login screen | Implemented | Preserves secure standard auth while adding guide-style quick demo entry flow.
| App shell navigation | Sidebar + mobile bottom nav with tab focus | Collapsible desktop sidebar + mobile bottom nav + active states | Implemented | Desktop shell now includes guide-style collapsible sidebar behavior with persisted state.
| Employee dashboard | Metrics-heavy chart dashboard | Dashboard with employee trend panel + multi-metric productivity/quality visuals + snapshots + widgets | Implemented | Multi-metric trend parity now includes target context and quality-index comparison.
| Manager dashboard | KPI charts, benchmark comparisons, SLA visuals | Analytics page + manager trend panel + benchmark comparison block + dashboard widgets | Implemented | Benchmark comparison now surfaced on dashboard with department/company/industry metrics.
| Announcements center | Search/sort/filter/unread + mark-all-read | Dedicated `/announcements` with search/sort/filter/unread/mark-all-read | Implemented | Includes detail drill-down and per-user read state.
| Announcement creation (manager flow) | In-view dialog with targeting + priority | Public manager modal composer + role-aware audience constraints | Implemented | Also available via Filament admin.
| Announcement priority model | `low/medium/high/urgent` with visual states | Priority field + filtering/sorting + badges + forms | Implemented | End-to-end model/UI implemented.
| Announcement read state | Per-user read/unread state | `announcement_reads` pivot + mark read/all read + auto-read on open | Implemented | Unread counters and badges wired across views.
| News widget | Latest relevant announcements card | Dashboard `Latest News & Updates` widget + unread/high-priority signals | Implemented | Includes deep links to announcement details.
| Quick links | Role-based grouped links | Targeted categories/links with badges and restrictions | Implemented | Data-driven and role-aware.
| Messaging main UX | Preview cards + thread view + search + unread indicators | Recent chat preview cards + search + unread badges + quick open + thread view | Implemented | Guide-style behaviors now present with existing Laravel flows.
| Conversation permissions | Broadcast restrictions by role | Policy + controller enforcement | Implemented | Robust role checks in place.
| Tasks view | Unified active tickets view | Ticket list/filters/report flow | Implemented | Functional parity achieved.
| Profile settings | Editable profile + notification toggles | Editable profile + persisted email/slack toggles + dispatch enforcement | Implemented | Preferences saved and enforced in observers.
| Governance tab | Placeholder in guide | Full governance hub and audit views | Exceeds | Laravel goes beyond guide.
| In-app notifications | Badge/list + read actions | Notification bell + mark read/all + preference-aware dispatch | Implemented | Dispatch now respects user preference.
| Automation and scheduled analytics | Not present in guide | Scheduler + analytics digest + SLA recalc | Exceeds | Laravel has production-oriented automation.

## Current Snapshot
- `Implemented`: 15
- `Partial`: 0
- `Missing`: 0
- `Exceeds`: 2

## Completed Work Summary
1. Announcements parity delivered: dedicated center, detail page, read receipts, priority model, manager modal compose flow.
2. Dashboard parity improved: news widget plus employee/manager trend panels and manager benchmark comparison.
3. Messaging parity improved: preview cards, unread indicators, search, quick-open actions.
4. App shell improved: clearer global destinations and mobile bottom navigation.
5. Profile parity delivered: persisted notification preferences and notification dispatch gating.
6. Authentication parity delivered: login page now includes guide-style demo role/department quick access in non-production environments.
7. Documentation aligned to current repo structure and implementation (`README.md` updated).

## Remaining High-Value Gaps
1. Additional polish pass for consistency (copy, spacing, empty states, accessibility labels) across all primary views.

## Recommended Next Execution Order
1. Do final end-to-end UX polish and accessibility sweep.
