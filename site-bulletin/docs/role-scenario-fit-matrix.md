# Role Scenario Fit Matrix

Date: 2026-05-05

Purpose: define what each user type should ideally want from Site Bulletin, compare that ideal with the current Laravel implementation, and identify improvement candidates that can be agreed before implementation.

Implementation strategy: [`role-scenario-implementation-strategy.md`](role-scenario-implementation-strategy.md)

Status terms:
- Strong: current app appears to support the scenario well.
- Partial: meaningful support exists, but the workflow is incomplete, indirect, or uneven by role.
- Gap: little or no current support was found in the implementation.

## Summary

The app already has a strong operational foundation: role-aware dashboards, ticketing, SLA tracking, messaging, announcements, governance, knowledge, and analytics are all present. The main opportunity is not adding more broad modules; it is tightening each role's daily workflow so the app feels like a purpose-built operations tool rather than a collection of features.

Highest-value improvement themes:
1. Make each role's home screen more explicitly action-led: "what needs my attention now?"
2. Separate manager, operations manager, HR, and admin priorities more clearly.
3. Add clearer self-service flows for employees after they report issues.
4. Turn analytics from reporting into decision support: recommended actions, owner, deadline, and escalation path.
5. Make governance and role-change work feel like a controlled workflow, not just pages and queues.

## Matrix

| Role | Ideal user scenario | Current implementation fit | Evidence in current app | Gaps / friction | Candidate improvement |
|---|---|---:|---|---|---|
| Guest / unauthenticated | Understand what the portal is and sign in quickly. | Strong | `/` routes to dashboard landing; login supports demo presets when enabled. | Guest experience is mostly a sign-in gateway; that is acceptable for an internal tool. | Keep minimal. Add only environment-specific support/contact copy if needed. |
| Employee | Start a shift and immediately see today's work context, performance pace, quality, unread messages, announcements, and tickets requiring action. | Strong | Employee dashboard, `My Work Today`, `/my-work`, trend windows, notification bell, messages/news widgets. | The app shows many useful signals, but not always as a single prioritized checklist. | Add an employee "Next actions" strip: acknowledge urgent update, reply to waiting ticket, read unread manager message, review performance risk. |
| Employee | Report an issue fast without knowing the right category or support path. | Strong | `/tickets/report`, fast templates, ticket presets, category routing, on-page lifecycle language. | Good base. Possible friction if an employee has several similar templates or does not know which one applies. | Add "I am not sure" guided reporting path that asks 2-3 questions and recommends the ticket template/category. |
| Employee | Track a reported issue in plain language and know what to do next. | Strong | Ticket detail lifecycle summary, timeline, public comments, requester action labels for waiting/resolved states. | Strong. Could be improved with clearer unresolved/resolved separation on ticket index. | Add ticket index tabs: `Needs me`, `In progress`, `Waiting on team`, `Resolved`. |
| Employee | Message the right person without knowing organization structure. | Strong | Routed shortcuts: manager, support team, HR team; direct conversations; attachment support. | Shortcuts exist, but the employee may not know when to message versus open a ticket. | Add contextual prompts: "Need a fix? Report issue. Need advice? Message manager/HR." |
| Employee | Find policy, scanner, shift, HR, or site guidance quickly. | Partial | `/knowledge`, quick links, role-aware categories. | Search exists, but knowledge and quick links are separate mental models. | Merge search across knowledge snippets, quick links, and announcements into one "Find help" experience. |
| Employee | Request access/role change or raise governance concern. | Partial | Role request pages available to all authenticated roles; governance pages are leadership-only. | Employees can submit role requests but have limited visibility into approval expectations or governance routes. | Add an employee-facing "Requests" page with role/access request status, expected approver, and SLA. |
| Manager | Start the day seeing team health, breached work, waiting employees, unread conversations, pending role requests, and department performance. | Strong | Manager dashboard overview, attention queue, SLA health, performance trend, benchmark comparison. | Good coverage, but attention items are separate from recommended action and owner/deadline. | Convert attention queue items into actionable cards with suggested next step: assign, chase, approve, message, escalate. |
| Manager | Review only their managed department's tickets and approvals. | Strong | Managed department scoping, approval workbench, manager-specific queues, tests for managed/unmanaged visibility. | Some routes allow broad leadership access by role, while scoping logic varies by controller. | Add a shared department-scope service/policy and test it across tickets, analytics, role requests, announcements, and messages. |
| Manager | Create tickets on behalf of employees and track team-wide recurring blockers. | Partial | On-behalf ticket creation, ticket index filters, triage/analytics for leadership roles. | Recurring blocker detection is not explicit. | Add "repeat issues" panel: duplicate clusters, top categories this week, stations/locations with repeated reports. |
| Manager | Communicate department updates and confirm who has read/acknowledged them. | Strong | Announcement targeting, acknowledgement summary on detail, department broadcast messaging. | Creation path likely lives partly in admin/Filament and partly public announcement routes; manager publishing flow may feel less direct than reading flow. | Add manager-facing "Create update" CTA with preview audience and expected reach. |
| Manager | Coach employees using performance trend data without exposing raw surveillance-like detail. | Partial | Performance trends, quality/productivity targets, employee samples power dashboard. | App shows operational metrics, but coaching workflow, notes, and privacy boundaries are not explicit. | Add manager coaching mode: team aggregate first, individual drilldown only with reason/context and audit trail. |
| Operations Manager | See cross-department operational health, SLA pressure, triage backlog, and bottlenecks across site. | Partial | Ops manager has analytics, triage board, dashboard if managed department exists, department metrics. | Current dashboard logic is closer to department manager than site operations command center. | Add ops manager dashboard mode: site-wide queues, department comparison, aging tickets, breach forecast, staffing/performance exceptions. |
| Operations Manager | Reassign, escalate, and rebalance work across departments. | Partial | Ticket triage board for `ops_manager`, status updates, assignee changes. | Rebalancing is manual; no recommended reassignment or capacity view. | Add triage actions: bulk assign, transfer department, escalation reason, capacity indicator by team. |
| Operations Manager | Monitor automation, SLA recalculation, and demo/simulation data health. | Partial | Scheduled commands, analytics recalculation, demo data command documented. | Operational job health is not visible in app UI. | Add admin/ops system health widget: last SLA run, last analytics run, failed jobs, stale demo samples warning. |
| HR | Handle HR tickets, sensitive attachments, approvals, role changes, people-policy announcements, and governance evidence. | Strong | HR has ticket approvals, triage board access, sensitive ticket visibility, role request management, announcements/categories permissions. | HR role currently overlaps heavily with admin/ops in public navigation. | Add HR dashboard view: pending HR approvals, sensitive cases, policy acknowledgements, role changes, open people tickets. |
| HR | Keep employee-sensitive evidence private while still updating requester-facing status. | Strong | Internal attachment visibility, private comments, ticket attachment visibility tests. | Strong. Could use clearer labels in UI to prevent accidental public notes. | Add side-by-side "Public update" vs "Internal note" labels and confirmation when adding private evidence. |
| HR | Review acknowledgement of policy or site-wide updates. | Partial | Announcement acknowledgement summary visible to leadership. | No dedicated policy compliance or non-acknowledger follow-up queue. | Add HR policy compliance queue: urgent policy update, not acknowledged, clarification requested, export. |
| Admin | Configure users, departments, categories, links, announcements, SLA settings, and audit access. | Strong | Filament resources, admin gates, user/department/resources, audit logs. | Public app and Filament admin are likely two separate experiences; admin landing may not prioritize configuration health. | Add admin landing checklist: missing departments, stale categories, broken quick links, users without department/manager, scheduler status. |
| Admin | Safely manage production/demo boundary. | Partial | Config flags for demo login, demo simulation, prototype footer. | Flags exist, but no in-app visibility or deployment checklist. | Add deployment readiness command/page that checks demo flags, app debug, queue driver, scheduler, mailer, storage link. |
| Admin | Audit sensitive actions and investigate who changed what. | Partial | Audit logs, policies, governance pages. | Audit coverage may be uneven across all high-impact actions. | Add audit coverage matrix and tests for role changes, ticket privacy, announcements, user admin changes, duplicate merging. |

## Cross-Role Journey Checks

| Journey | Ideal outcome | Current fit | Improvement candidate |
|---|---|---:|---|
| Urgent site update | Leadership posts targeted update; employee sees it immediately; acknowledgement is tracked; non-acknowledgers can be chased. | Partial | Add acknowledgement follow-up queue and escalation messaging. |
| Broken scanner at station | Employee reports from template; manager/ops triages; requester sees plain-language status; repeated station issues are detected. | Partial | Add repeat-issue clustering by location/category/template. |
| Shift swap request | Employee submits request; manager approves; HR finalizes; employee sees exact stage. | Strong | Add due date / expected review time for each approval step. |
| Sensitive HR issue | Employee reports privately; HR sees internal evidence; requester gets safe public updates. | Strong | Add stronger UI separation for public/internal updates. |
| Department performance dip | Manager sees quality/productivity drop; knows affected period; can review tickets/messages causing blockers. | Partial | Connect performance dip panels to related ticket categories, announcements, and staffing context. |
| Site-wide SLA pressure | Ops manager sees breach forecast and aging queues; reassigns or escalates before breach. | Partial | Add breach forecast, capacity, and bulk triage actions. |
| Role/access change | User requests role/access; manager/HR approves; admin/audit trail records outcome. | Partial | Add requester status tracking and clearer approver chain. |

## Suggested Priority Backlog

### P1: Action-Led Role Home Screens
Create explicit "Next actions" for employee, manager, HR, ops manager, and admin. The app has the data; the improvement is surfacing it as a role-specific worklist.

Candidate acceptance criteria:
- Employee sees no more than 4 next actions.
- Manager sees attention items with action labels and routes.
- HR sees HR approvals, sensitive tickets, role requests, and policy acknowledgement exceptions.
- Ops manager sees cross-department SLA/triage pressure.
- Admin sees configuration/system health.

### P1: Unified Scope And Permission Model
Centralize department scoping and leadership role rules so every controller applies the same boundaries.

Candidate acceptance criteria:
- One service/policy answers "which departments can this user manage/view?"
- Tickets, analytics, role requests, broadcasts, and audit logs use it.
- Tests cover employee, manager, ops manager, HR, and admin scoping.

### P2: Unified Help/Search Experience
Merge quick links, knowledge snippets, and announcements into a single "Find help" search surface.

Candidate acceptance criteria:
- Search returns grouped results from knowledge, quick links, and announcements.
- Results respect role/department visibility.
- Employee can start a ticket from a relevant result.

### P2: Repeat Issue And Breach Prevention
Move from reactive tickets to operational prevention.

Candidate acceptance criteria:
- Ticket index/analytics shows repeated category/location/template clusters.
- Ops/manager dashboard highlights tickets likely to breach soon.
- Suggested action exists for each cluster or forecast item.

### P2: HR-Specific Workspace
Separate HR's day-to-day flow from generic leadership/admin navigation.

Candidate acceptance criteria:
- HR dashboard shows pending approvals, sensitive cases, role changes, policy acknowledgements, and people-ticket SLA.
- HR can filter by clarification requested / waiting employee / awaiting manager.

### P3: Deployment Readiness And System Health
Make production readiness visible.

Candidate acceptance criteria:
- Command or page checks demo flags, debug mode, scheduler, queue, mail, storage, stale samples, and failed jobs.
- Admin receives warning if production-facing unsafe settings are detected.

## Open Consensus Questions

1. Should `ops_manager` be a site-wide command-center role, or just a manager with broader permissions?
2. Should HR have a distinct dashboard, or should HR continue using the generic leadership dashboard plus approvals?
3. Should employees see performance metrics as coaching feedback, or should the app keep performance detail minimal and focus on tickets/messages?
4. Should quick links and knowledge be merged into one search experience, or remain separate for clarity?
5. Should role/access requests be treated as a formal approval workflow with SLA and status, similar to tickets?
6. Should duplicate/repeat issue detection become a first-class feature in ticketing and analytics?
