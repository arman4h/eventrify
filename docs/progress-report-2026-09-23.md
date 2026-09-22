# Eventrify — Database Development Update Report

**Date:** 23 September 2026
**Project:** Eventrify — University Club Events Platform
**Database:** MySQL/MariaDB (`eventrify`) · 12 tables · relational schema with enforced referential integrity

## 1. Overview

This session continues from the 16 September report ([progress-report-2026-09-16.md](./progress-report-2026-09-16.md)) and resolves the items that were previously flagged as **"still in progress"**: the Room Requests workflow, Club Attendance, executive access control, and guest-registration data integrity. Every change was verified against the live database and through live HTTP flows (not just code review).

> Status of modules listed as partial on **16 September**:
> | Module | 16 Sep status | 23 Sep status |
> |---|---|---|
> | Room Requests | table + FK, decision flow pending | **Complete** — club submission with standardized time slots; admin approve/decline with review notes |
> | Reports Management | seeded, workflow pending | **Complete** — club analytics (attendance-by-event, registrations) + admin report handling |
> | Student Profile | storage ready, update flow pending | **Linked** — guest registrations now attach to the student's account on login/registration |
> | Club Attendance | not built | **Complete** — event check-in (by Student ID/email) + walk-in registration |

## 2. Data Integrity — Duplicate Registration Prevention

Registration submission (`pages/landing/event.php`) now guards every path against double bookings, considering only **active (non-cancelled)** registrations:

- **Student ID** — blocked via the existing `uq_event_student (event_id, student_id)` unique index when the user is logged in, and re-checked for the guest-style `guest_student_id`.
- **Email** — case-insensitive, whitespace-trimmed match against prior registrations for the same event.
- **Phone** — normalized comparison (`+8801…`, `8801…`, `01…` prefixes considered equivalent) via a new helper.

The queries are centralized in `eventRegistrationValueTaken(int $eventId, string $fieldLabel, string $value)` (`app/helpers/functions.php`), which joins `event_registrations → registration_field_responses → event_registration_fields` to locate the stored Email/Phone responses — no denormalized copy of the contact info is kept on the registration row.

**Verified live:** duplicate email blocked, duplicate phone blocked, duplicate Student ID blocked, and a genuinely fresh guest submission still succeeds.

## 3. Guest Registrations → Student Account Linking

A guest who registers for an event without an account, then later logs in or creates a student account, now automatically **owns** that registration:

- `linkGuestRegistrations(int $studentId, string $email)` back-fills `student_id` on every non-cancelled registration whose stored Email response matches the account email (only when `student_id` is still NULL).
- Invoked in `pages/auth/login.php` (after student login) and `pages/auth/register-student.php` (after account creation).

**Verified live:** a guest registration created before account sign-up appears on the student's registration list and event pages immediately after login.

## 4. Club Dashboard — User Management (Create User)

`pages/dashboard-b/members/index.php` was rebuilt into a functional member-management module:

- **Create User** inserts a real `club_users` row (full name, email, password hash, phone, student ID, position, role `admin`/`executive`) with a **unique-email** check.
- **Owner-only control** — the Create button and the server-side POST handler are restricted to the club owner; admins/executives cannot create users.
- **Soft-remove** (`status = 'removed'`) and **activate/deactivate** toggle while preserving login integrity and FK relationships (registrations still reference `checked_in_by`).
- Redesigned stats + role legend (Owner / Admin / Executive) and new pause/play icons.

**Verified live:** owner creates an admin → real row + working login; duplicate email rejected; disable/remove transitions applied.

## 5. Executive Access Control (Permissions)

A full per-page permission model now governs what a club executive can see:

### Schema
- `club_users.access_scope ENUM('all','limited') NOT NULL DEFAULT 'all'` — owner/admin and `all`-scoped executives have unrestricted access; `limited` executives only see granted pages.
- `club_permission_pages` now holds **7** page definitions: `club_profile, members, events, attendance, registrations, reports, room_requests`.
- `executive_permissions (club_user_id → page_id, access_level 'view'|'manage')` stores the grant set.

### Application layer
- New guards in `app/helpers/functions.php`: `clubCanAccess(string $pageKey): bool` and `requireClubAccess(string $pageKey): void` (redirects with a flash message when access is denied, after the usual approved-club check).
- **Every club dashboard page** now calls `requireClubAccess('<page_key>')` instead of the blanket `requireApprovedClub()`.
- **Navigation** (`sidebar.php`, `navbar.php`) hides un-granted menu items/dropdowns; the club overview hides quick actions, "View all", and per-event Manage/Edit buttons the user cannot access.
- The Create User form exposes a **Dashboard Access** selector: "All dashboard sections" vs "Specific dashboard sections" (checkbox list, stored as `executive_permissions` rows).

**Verified live:** an executive granted only *events + attendance* — sees only Overview/Events/Attendance; direct URLs to `/club/registrations`, `/club/members`, `/club/room-requests`, `/club/reports`, `/club/settings` return a redirect with *"You do not have access to this section."*; the owner and an `all`-scope executive see every section.

## 6. Room Requests — Completed Workflow

The module flagged as partial on 16 September is now end-to-end:

- **Standardized time slots** — the free-text start/end time inputs are replaced by a fixed 6-slot dropdown (`8:30–9:50, 9:51–11:10, 11:11–12:30, 12:31–1:40, 1:50–3:10, 3:11–4:30`), backed by `roomTimeSlots()` / `roomSlotLabel()` helpers. Stored as `start_time`/`end_time` TIME columns.
- **`preferred_building` removed** from the schema and both club/admin forms — location preference is expressed through `preferred_room` only (column dropped from the live database and the DDL).
- **Admin decision flow** (`pages/dashboard-a/room-requests/`) — admin **approves** (room marked allocated) or **declines** a pending request, with optional review notes; writes `reviewed_by`, `reviewed_at` and transitions status `pending → approved | declined` (guarded so only `pending` rows can be decided).
- Club dashboard lists the club's own requests with their status.

## 7. Club Attendance — Completed Module

- **Check-in** (`pages/dashboard-b/attendance/index.php`) — pick a published event, search by Student ID or email, and mark attendees; writes `status='attended'`, `checked_in_at`, `check_in_method='manual'`, `checked_in_by`; attended counts tracked per event.
- **Walk-in registration** (`attendance/walkin.php`) — registers an attendee directly at the venue (guest path with `is_walkin=true`) so attendance and registration stay consistent.
- **Reports page** aggregates attendance-per-event and registration counts for the club.

## 8. Schema Consolidation

- `database/finalschema.sql` — a single consolidated DDL for all **12 tables**, kept in sync with the live schema (includes `access_scope`, `room_requests` without `preferred_building`, the 7 permission-page rows, and the guest/attendance columns on `event_registrations`).
- `database/migrate.sql` — extended with the idempotent additions: `ALTER TABLE club_users ADD COLUMN IF NOT EXISTS access_scope …` and an `INSERT … WHERE NOT EXISTS` seed for all permission pages.
- No re-seeding of demo data; the live database was left in a clean state (1 approved club, 2 club users, 1 published event, sample registrations, 0 room requests).

## 9. Verification Performed

- All modified PHP files lint-clean.
- **Live HTTP tests** confirmed: duplicate email/phone/ID blocked; guest→login linking; owner-only user creation; `limited` executive cannot view or reach unauthorized sections while owner/`all`-scope users keep full access; room-request slot submission + admin approve/decline round-trip.
- Post-test cleanup removed all temporary users/requests; the database is back to its clean baseline.

## 10. Still In Progress (next milestones)

- **QR-based check-in** — `check_in_method` enum already supports `'qr'`; the QR scanner UI is not yet wired.
- **Student profile updates** — profile display exists; self-service edit/update flows pending.
- **Club tasks/projects modules** — directories exist but remain scaffold-only.