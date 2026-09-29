# Progress Report — 29 September 2026

## Summary

A correctness and hardening pass over Eventrify. No database DDL was changed; the schema and
seed data remain the contract. This round focused on things that were silently wrong:
validation that accepted hostile input, a crash on oversized input, permission redirects that
404'd, and a landing page showing invented numbers.

---

## Bugs found and fixed

### 1. HTTP 500 on oversized input (student registration)

`register-student.php` had no length validation. A 4000-character name reached the `INSERT`
and raised `Data too long for column 'full_name'`, which surfaced as a fatal error page.

Because `mysqli` runs in exception mode, `execute()` throws rather than returning `false`, so
the page's `else` branch — which showed a friendly message — was unreachable.

Fixed by validating against the real column widths *before* the insert, and wrapping the write
in a `try`/`catch` that logs and shows a retry message.

**Affected pages converted to validate length first:** `register-student`, `register-club`,
`events/create`, `events/edit`, `edit-profile`, `landing/event`.

### 2. Weak password policy on the two registration forms

Both registration pages hand-rolled their own rules and accepted passwords that the rest of the
application rejects. `12345678` was accepted, and a password containing the applicant's name
was accepted as long as the name had a space in it.

Both now use the shared library in `app/helpers/validation.php` (`vPassword`,
`vPasswordNotSimilar`), so registration matches the admin settings page.

`vPasswordNotSimilar` was also made more robust:

- it now compares with case **and separators** removed, so `arifhossain1` is caught against
  the name `Arif Hossain`
- it ignores values shorter than 4 characters, so a one-letter name cannot reject nearly every
  password

### 3. `mysqli` failures produced 500s instead of messages

Seven pages branched on `if ($stmt->execute())` expecting `false` on failure. In exception mode
they get an uncaught `mysqli_sql_exception` instead.

Added `dbExec()` in `app/config/database.php`, which logs and returns `false`, and switched
those call sites to it. The room-request batch insert is now wrapped in `try`/`catch` with a
rollback, so a failed booking can no longer be left half-written.

### 4. Permission denial redirected to a 404

`requireClubManage('club_profile')` sent a view-only executive to `/club/club-profile`, which
is not a route — the settings page is at `/club/settings`. The user got a 404 instead of being
told they lacked permission.

Added `clubPageUrl()`, which maps a permission key to its real route, and used it in
`requireClubManage()`.

### 5. Attendance write controls visible to read-only executives

Every other club section hid its write controls from a `view` executive. The attendance page
still showed the participant search box, the **Confirm Check-in** button, and the per-row
**Check In** button. The server correctly refused the POST, so this was a UI inconsistency
rather than a data leak, but it invited clicks that could never work.

All three are now gated with `clubCanManage('attendance')`, and a view-only executive sees a
note explaining they can list participants but not check them in.

### 6. Seeded room names did not match the room catalogue

Four seeded `room_requests` used short names (`Ground Floor Hall`, `Auditorium`, `Open Ground`,
`Room 501`) while the catalogue uses fully-qualified names
(`Academic Building 3, Ground Floor Hall`, `Auditorium, Admin Building`, …).

Availability and conflict detection match on exact names, so **the seeded bookings were not
blocking anything**. The approved auditorium request looked like it should have blocked a slot
and did not.

Corrected in `database/finalschema.sql` and in the live database. Verified: the approved
request now blocks its slot and reports the holding club correctly.

### 7. Room recommendation ignored the slots the user had ticked

The recommender always recommended a two-slot run. A user who had ticked one slot and pressed
**Recommend a room** was shown a two-slot recommendation, which the page then ticked for them.

The page now sends the number of ticked slots, and the endpoint prefers that count. An event
with its own schedule still overrides it, because the event's window is what actually has to be
covered. An out-of-range value is clamped.

Verified: 1 ticked → 1 slot, 2 ticked → 2 slots, none ticked → falls back to the maximum of 2.

### 8. Landing page showed invented numbers

The hero and the "for clubs" panel were hardcoded mockups, including a `UIU Robotics Club
Dashboard` with 12 events, 486 participants, and 92% attendance, none of which came from the
database.

Both panels are now data-driven: the hero shows the next published events to start, with live
seat counts, and the panel shows real platform figures (live events, participants, approved
clubs) with links to real events. Empty states are handled.

---

## Also completed

- **13 malformed CSRF form tags** repaired across 11 files. Every form now emits
  `<?= csrfField() ?>` correctly, so POSTs are no longer rejected with HTTP 419.
- **All club write handlers** now call `requireClubManage()`, so a `view` executive cannot
  write by hand-crafting a request.
- **`bind_param()` by-reference bugs** fixed where `currentUserId()` was passed directly;
  a static audit script was added and its one remaining warning reviewed as a safe
  `COALESCE(event_id, 0)` comparison.
- **Club settings** persist to `clubs` and are validated; **admin settings** persist to
  `system_admins`, including password change, and the active session is refreshed.
- **Deleted report fixture `#3`**, which was leftover test data rather than seed data. The
  canonical report count is 2.
- **Removed `m.jar`**, a stray curl cookie file containing an old `PHPSESSID`, and added
  `*.jar` to `.gitignore`.

---

## Verification

| Check | Result |
|---|---|
| `php -l` across all files | 77 files, 0 failures |
| `bind_param` type audit | 1 finding, reviewed and safe |
| Malformed CSRF tags | 0 remaining |
| Authenticated route sweep | 60 routes, 0 unexpected failures |
| Registration validation | 19 cases, all rejected as expected |
| Club registration validation | 17 cases, all rejected as expected |
| Event create / edit validation | 17 cases, all rejected as expected |
| Profile, room booking, permissions | verified by request and by response |
| PHP warnings / notices / fatals | 0 |

The database was returned to its seeded baseline after testing: 4 students, 6 club users,
5 clubs, 7 events, 8 registrations, 32 registration fields, 4 room requests, 2 reports,
7 permission pages, 0 executive permissions, 1 administrator.
