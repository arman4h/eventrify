# Eventrify

An **event management platform for university clubs** — a DBMS lab project built with
**PHP 8 (procedural) + MySQL/MariaDB + Tailwind CSS**.

Eventrify lets students discover and register for club events, gives clubs a dashboard to
publish events and run attendance, and gives the system administrator one place to approve
clubs, review room bookings, and resolve student reports.

---

## Contents

- [Features](#features)
- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Setup](#setup)
- [Database](#database)
- [Demo accounts](#demo-accounts)
- [Roles and permissions](#roles-and-permissions)
- [Routes](#routes)
- [Project layout](#project-layout)
- [Troubleshooting](#troubleshooting)

---

## Features

### Public

- Landing page with live statistics and the next events to start
- Event explorer with search and filtering
- Public event detail page with a **custom registration form** built by the club
- Registration works signed in (linked to a student) or as a guest

### Student

- Register, log in, edit profile
- Browse and register for events
- Personal registration history with check-in status
- File a report against a specific event or club, with an admin reply thread

### Club

- Request club access; the dashboard unlocks once an admin approves the club
- Create and edit events, with a drag-free registration-question builder
- Custom registration fields per event (text, email, dropdown, and more)
- Registration management: cancel, waitlist, promote
- Attendance: search by student ID, check in, and walk-in registration
- **Room booking** with a live availability grid, conflict detection, and a room recommender
- Manage club members, and set per-page `view` / `manage` permissions for executives
- Club settings (name, university, type, established year, description, contact links, logo URL)

### System admin

- Approve or reject club registration requests
- Overview of all clubs, events, and users
- Batch approve / decline room requests
- Read and reply to student reports, with a status workflow
- Edit the administrator's own name, email, and password

---

## Tech stack

| Layer | Choice |
|---|---|
| Language | PHP 8 (procedural, no framework) |
| Database | MySQL 8 / MariaDB via `mysqli` (prepared statements throughout) |
| Templating | Plain PHP includes |
| CSS | Tailwind CSS 3, compiled to a single static file |
| Auth | Session cookies, `password_hash` / `password_verify` (bcrypt) |

There is no JavaScript framework and no build step for the PHP code — only Tailwind is compiled.

---

## Requirements

| Tool | Why |
|---|---|
| PHP 8.0+ with the `mysqli` extension | Runs the app and talks to MySQL |
| MySQL 8.0+ or MariaDB 10.4+ | The database |
| Node.js + npm | Only to install and build Tailwind CSS |

> The `mbstring` extension is **not** required — the code uses its own `strLength()` helper.

---

## Setup

### 1. Get the code

Clone or download the repository. Do **not** put it inside `htdocs`; you will run it with
PHP's own server on its own port.

### 2. Build the CSS

```bash
npm install
npm run build      # writes public/assets/css/app.css
```

While working on styles, use `npm run dev` to rebuild on save.

### 3. Configure the database

Copy `.env.example` to `.env` and edit it:

```ini
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=eventrify
DB_USER=root
DB_PASSWORD=

APP_NAME=Eventrify
APP_URL=http://localhost:8000
APP_ENV=development
```

### 4. Create and seed the database

`database/finalschema.sql` is the single source of truth: it creates every table, the
foreign keys, the indexes, and the demo data.

```bash
mysql -u root -p -e "CREATE DATABASE eventrify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p eventrify < database/finalschema.sql
```

### 5. Run it

```bash
php -S localhost:8000 -t public
```

Open <http://localhost:8000>.

---

## Database

`database/finalschema.sql` contains:

- **Schema** — 12 tables with foreign keys and indexes
- **Seed data** — students, clubs, club users, events, registrations, registration fields,
  room requests, reports, permission pages, and one system administrator

Tables:

| Table | Holds |
|---|---|
| `students` | Student accounts and profiles |
| `clubs` | Club records, public contact details, approval status |
| `club_users` | Club members — owner, admin, or executive |
| `club_permission_pages` | The dashboard sections a club can grant access to |
| `executive_permissions` | Per-executive, per-section `view` / `manage` grant |
| `events` | Events, including venue, schedule, capacity, and lifecycle status |
| `event_registration_fields` | Custom registration questions per event |
| `event_registrations` | Who registered, waitlist position, and check-in state |
| `registration_field_responses` | A participant's answers to the custom questions |
| `room_requests` | One row **per booked slot** |
| `reports` | Student reports and the admin's replies |
| `system_admins` | System administrator accounts |

### Room requests are slot-based

The campus day is divided into six fixed slots. A booking is one or two *adjacent* slots, and
each slot is stored as its own `room_requests` row that shares the same `created_at` value —
that shared timestamp is what groups the rows back into one booking. A slot held by a
`pending` or `approved` request from **another** club blocks that room; a request from your
own club does not block you.

---

## Demo accounts

| Role | Email | Password |
|---|---|---|
| Student | `arman4hn@gmail.com` | `arman123` |
| Club owner | `mahi@gmail.com` | `mahi123` |
| System admin | `admin@eventrify.com` | `admin123` |

Sign-in pages: `/login`, `/club/login`, `/admin/login`.

---

## Roles and permissions

**System admin** — full access to every admin page.

**Club owner / admin** — full access to their own club's dashboard.

**Club executive** — access is granted per dashboard section, at one of two levels:

- `view` — can open the section, but every write is refused server-side, not just hidden
- `manage` — can make changes

An executive with `access_scope = 'limited'` is restricted to their explicit grants. A club
owner or admin always has `manage` everywhere.

Both levels are enforced twice on purpose: the buttons are hidden in the UI, **and**
`requireClubManage()` re-checks the level at the top of every POST handler so a hand-crafted
request cannot bypass it.

---

## Routes

**Public**

| Path | Page |
|---|---|
| `/` | Landing |
| `/events` | Event explorer |
| `/event?event_id=2` | Event detail and registration |
| `/about`, `/help` | Static pages |

**Auth** — `/login`, `/register-student`, `/logout`, `/club/login`, `/club/register`,
`/club/access-request`, `/admin/login`

**Student** — `/student`, `/student/profile`, `/student/profile/edit`,
`/student/registrations`, `/student/reports`, `/student/report`

**Club** — `/club`, `/club/events`, `/club/events/create`, `/club/events/edit`,
`/club/events/manage`, `/club/events/delete`, `/club/registrations`, `/club/attendance`,
`/club/attendance/walkin`, `/club/members`, `/club/room-requests`, `/club/reports`,
`/club/settings`

**Admin** — `/admin`, `/admin/club-requests`, `/admin/club-requests/review?club_id=1`,
`/admin/clubs`, `/admin/events`, `/admin/events/delete`, `/admin/users`,
`/admin/room-requests`, `/admin/reports`, `/admin/settings`

Unknown paths render the 404 page.

---

## Project layout

```
app/
  config/       app bootstrap, .env loader, database connection
  helpers/      auth, security, validation, rooms, reporting, shared functions
  layouts/      landing, dashboard-s, dashboard-a, dashboard-b shells
  components/   reusable markup (alerts, and similar)
  views/
database/
  finalschema.sql   the authoritative schema + seed data
pages/          one directory per dashboard, mirroring the routes
public/         document root; index.php is the router, assets/ is served from here
src/css/        Tailwind source, compiled into public/assets/css/app.css
docs/           progress reports
```

### Security notes

- Every state-changing request is verified against the session CSRF token in `public/index.php`
- All SQL goes through prepared statements with explicit bind types
- Passwords are bcrypt hashes; sessions are regenerated on login
- Output is escaped through the `e()` helper
- Failed logins are throttled and reported with HTTP 419
- `execOk()` / `dbExec()` convert database failures into user-facing messages instead of 500s

---

## Troubleshooting

**"The mysqli PHP extension is not enabled"** — enable `extension=mysqli` in your `php.ini`,
then restart the server.

**"Can't connect to MySQL"** — start MySQL, then check `DB_HOST`, `DB_PORT`, `DB_USER`, and
`DB_PASSWORD` in `.env`.

**The page has no styling** — run `npm install && npm run build`.

**"Unknown database"** — create it and import `database/finalschema.sql`.

**Port 8000 already in use** — run `php -S localhost:8080 -t public` and set `APP_URL` to match.
