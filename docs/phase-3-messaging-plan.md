# Phase 3 – Messaging & Engagement

Goal: provide managers and HR with a way to send department-specific announcements or 1:1 messages, and let employees reply. Messages should complement tickets—ideal for quick nudges, follow-ups, or recognition.

## Functional Scope
- **Threads** – conversation grouping. One thread per department broadcast or per manager↔employee chat. Keep participants explicit.
- **Messages** – body, attachments (optional future), read receipts.
- **Targets** – manager can start:
  - department broadcast: everyone in selected dept(s) gets the thread but cannot reply all (optional).
  - 1:1 dialogue with employee(s).
  - HR can message all employees or cross-department groups.
- **Notifications** – badge and dashboard widget for unread counts. Optional email stub.

## Data Model
1. `conversations`
   - id, subject (nullable), type (`direct`, `department`, `announcement`), creator_id, department_id (nullable), locked (bool), timestamps.
2. `conversation_participants`
   - id, conversation_id, user_id, last_read_at, role (`owner`, `member`, `recipient`), timestamps.
3. `messages`
   - id, conversation_id, sender_id, body, is_system, timestamps.
4. (Optional) `message_notifications` for queued alerts or future push/email.

Indices: conversation type/department, participant user_id lookup, message created_at.

## API / Controllers
- `ConversationController` (list/show/create). Filter by participation + type; allow managers to create targeted threads.
- `ConversationParticipantController` (optional) to add/remove participants.
- `MessageController` (store). Ensure authorization and update read state.
- Possibly background job to fan out department broadcasts by inserting participants.

## Authorization
- Only admins/HR/managers can create department or multi-recipient threads. Employees can reply only if they are participants.
- Department broadcasts limited to departments the manager manages (use department pivot from Phase 1).
- Conversations can be locked (no replies) for one-way announcements.

## UI
- Dashboard widget: new “Messages” card showing unread count + last few threads.
- `/messages` page with conversation list (filters: All / Direct / Department), composer, and message view.
- Show badges for department name, manager-only threads, locked threads.

## Notifications
- On new message, mark conversation as unread for other participants. Later phases can add email/push notifications.

## Implementation Order
1. Migrations + models (`Conversation`, `ConversationParticipant`, `Message`).
2. Policies + scopes for role-based creation.
3. Seed sample conversations/messages.
4. Controllers/routes/API endpoints.
5. UI components (dashboard widget + inbox page).
6. Basic tests to ensure visibility and creation rules.
