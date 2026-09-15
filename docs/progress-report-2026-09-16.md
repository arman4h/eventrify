# Eventrify — Database Development Update Report

**Date:** 16 September 2026
**Project:** Eventrify — University Club Events Platform
**Database:** MySQL/MariaDB (`eventrify`) · 12 tables · relational schema with enforced referential integrity

## 1. Overview

Today's session focused on tightening the **data pipeline between the application layer and the relational schema**: (a) newly added registration fields now flow all the way from form input to normalized storage, and (b) the club-application workflow was completed so that *every* field collected on the form is physically stored and displayed to the admin reviewer. Work was verified against the live database, not just code review.

## 2. Schema Enhancements (DDL)

### Clubs application form → full persistence
The `clubs` table was extended to match the registration form:
- `university VARCHAR(150)`
- `club_type VARCHAR(50)`
- `established_year SMALLINT`
- `official_email VARCHAR(100)`
- `website VARCHAR(255)`, `facebook VARCHAR(255)`, `social_links VARCHAR(255)`
- `logo` upgraded `VARCHAR(255) → LONGTEXT` (stored as base64 data-URI, following the poster pattern)

The `club_users` table gained owner/applicant fields:
- `student_id VARCHAR(20)`, `position VARCHAR(50)`, `university_email VARCHAR(100)`

A proposed `club_documents` table was **designed, migrated, tested, and then dropped by requirement** when the document-upload feature was removed — demonstrating the iterative schema-revision loop.

### Event registration fields (from earlier in the day)
- `events.poster LONGTEXT` — base64 event-poster storage
- Default registration rows (Full Name, Email, Student ID, Department) are auto-inserted for **every** event via an idempotent `INSERT … SELECT … WHERE NOT EXISTS` backfill (no duplicate rows).

## 3. Data Integrity & Constraints

- **Referential integrity verified end-to-end:** deleting an `events` row cascades to `event_registrations`, `event_registration_fields`, and `registration_field_responses`; `room_requests.event_id` uses `ON DELETE SET NULL`, preserving the request record.
- **Unique constraints enforced:** `clubs.club_name UNIQUE` and `club_users.email UNIQUE` power duplicate-application rejection (tested live).
- **Enums constrain domain values:** club status (`pending/approved/rejected`), event status (`draft/published/cancelled/completed`), room-request status, report status, and club-user roles.
- **Multi-table link:** `clubs.requested_by → club_users.club_user_id` correctly tracks each club's owner applicant (demo data back-filled for clubs 1–5).

## 4. Transactional Workflow — Club Application

The club-application INSERT is now **atomic**:
1. `BEGIN TRANSACTION`
2. Insert `clubs` (all 11 columns), capture `club_id`
3. Insert owner into `club_users` (login `email` = official club email; applicant university email stored separately), capture `club_user_id`
4. Update `clubs.requested_by`
5. `COMMIT`; on any failure `ROLLBACK` (verified: a mid-transaction failure leaves zero orphan rows)

## 5. Application ↔ Database Layer

- **Dynamic event registration** inserts guest responses into `registration_field_responses` keyed to `event_registration_fields`, plus mapped columns for name/student-id; supports both guest (walk-in) and logged-in student paths.
- **Student registration** stores department short-codes with prefix validation (e.g., CSE→011, EEE→012), with legacy full names normalized in the app layer.
- **Admin Event Manage** screen exposes every event with a guarded delete handler that relies on the cascade rules above.
- **Admin Club Requests** list + review screens fetch and display the new columns (university, type, established year, contact links, owner ID/position/email, logo), and search across `club_name / description / university / club_type`.

## 6. Verification Performed

- All modified PHP files lint-clean; schema import smoke-tested into a fresh database.
- **Live HTTP tests** confirmed: full club application → values present in every new column; logo byte-exact in DB; duplicate name/email rejected; admin review renders all fields; cascade delete + auth guards pass.

## 7. Modules Still In Progress (not yet complete)

These are acknowledged as partial and are the next milestones:
- **Room Requests** — `room_requests` table exists with FK + status enum; approval/decision flows need completion.
- **Reports Management** — `reports` table seeded; full admin workflow pending.
- **Student Profile** section — linked storage ready; UI/update flows pending.
- **Club Attendance** section — not yet built.