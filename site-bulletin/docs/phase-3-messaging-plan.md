# Phase 3 – Messaging & Engagement

Goal: provide managers and HR with a way to send department-specific announcements or 1:1 messages, and let employees reply. Messages should complement tickets—ideal for quick nudges, follow-ups, or recognition.

## Functional Scope
- **Threads** – conversation grouping. One thread per department broadcast or per manager↔employee chat. Keep participants explicit.
- **Messages** – body, attachments (optional future), read receipts.
- **Targets** – manager can start:
  - department broadcast: everyone in selected dept(s) (including managers) gets the thread but cannot reply all (optional).
  - 1:1 dialogue with employee(s).
  - HR can message all employees or cross-department groups.
- **Notifications** – badge and dashboard widget for unread counts. Optional email stub.

## Data Model
1. `conversations`
   - id, subject (nullable), type (`direct`, `department`, `announcement`), creator_id, department_id (nullable), locked (bool), timestamps.
2. `conversation_participants`
   - conversation_id, user_id, last_read_at, role (`owner`, `member`, `recipient`).
3. `messages`
   - conversation_id, sender_id, body, is_system, timestamps.
4. (Optional) `message_notifications` for queued alerts.

Indices: conversation type/department, participant user_id lookup, message created_at.

## API / Controllers
- ConversationController (list, show, create). Filter by participation + type; allow managers to create targeted threads.
- MessageController (store). Ensure participant authorization and update read state.
- Possibly a ConversationBroadcastJob to fan out new participants for department broadcasts (dup entries in pivot).

## Authorization
- Only admins/HR/managers can create department or multi-recipient threads. Employees can reply to threads they’re invited to.
- Department broadcast creation limited to departments the manager covers.
- Conversations locked to prevent employee-initiated messages to entire departments (unless toggled later).

## UI
- Dashboard widget: “Messages” card showing unread count and last few threads.
- Dedicated `/messages` page with conversation list, filters (All, Department, Direct), composer, and message view (similar to tickets comments).
- Visual indicators for department badge, manager-only threads.

## Notifications
- On new message, mark conversation as unread for other participants. Later phases can send email/push.

## Future-proofing
- Keep attachments table optional to extend later.
- Provide `archived` flag on conversations for tidy inbox.

Implementation order: schema + models -> policies -> seed sample data -> controllers/routes -> UI widget and pages -> tests/seeding adjustments.
