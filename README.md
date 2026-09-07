# Eventrify

An **event management platform** for university clubs — a DBMS Lab Project made with **PHP + MySQL + Tailwind CSS**.

How it works:

- Visitors land on a **public events page** (`/`) — they can browse and search upcoming events **without logging in**
- Each event has a **public detail page** (`/event?id=1`)
- A **Login** button in the header takes visitors to the student login page
- Once logged in you get one of **two dashboards**:
  - **Admin Dashboard** — manage users, see reports & analytics
  - **Club Dashboard** — create events, track tasks, view overview

---

## What you need on your PC

| Tool | What it's for |
|---|---|
| **XAMPP** | MySQL database (Apache is optional) |
| **PHP 8+** | Runs the website |
| **Node.js** (optional) | Builds Tailwind CSS (a ready-made CSS file is already included, so usually not needed) |

---

## Setup Guide — follows these 5 steps

> If you are using **XAMPP**, its built-in PHP already has MySQL support, so the site works out of the box. If you use a **separately installed PHP** and it shows errors about `mysqli`, jump to the [Troubleshooting](#troubleshooting) section at the bottom.

### Step 1 — Put the project on your computer

Download / clone the repository and open the folder. For example:

- On **XAMPP**, a nice place is `C:\xampp\htdocs\eventrify` (Windows) or `~/lampp/htdocs/eventrify` (Linux).

*(You don't have to put it there — anywhere works.)*

---

### Step 2 — Start MySQL

GUI way (recommended):

1. Open the **XAMPP Control Panel**.
2. Press the **Start** button on the **MySQL** row (the green **Start** button appears).
3. Leave it running. (Do **not** need Apache.)

---

### Step 3 — Create the database

GUI way (recommended):

1. Click the **Admin** button next to MySQL in XAMPP → this opens **phpMyAdmin** in your browser.
2. Near the top, click the **Import** tab.
3. Press **Choose File**, pick **`database/database.sql`** from the project folder.
4. Press the blue **Import** button at the bottom.
5. Do the **same again** with **`database/seed.sql`**.

That's it — the database **`eventrify`** with tables and sample data is now created.

> Terminal way (if you prefer):
>
> **Windows** (PowerShell, from the project folder):
> ```powershell
> mysql -u root < database/database.sql
> mysql -u root < database/seed.sql
> ```
>
> **Linux/macOS** (from the project folder). On Linux, if `mysql` isn't on your PATH, use XAMPP's copy:
> ```bash
> mysql -u root < database/database.sql
> mysql -u root < database/seed.sql
> # or, using XAMPP's mysql:
> /opt/lampp/xampp startmysql
> /opt/lampp/bin/mysql -u root < database/database.sql
> /opt/lampp/bin/mysql -u root < database/seed.sql
> ```

---

### Step 4 — Create the `.env` file

The project needs a small config file. Look for **`.env.example`** in the project folder:

1. Make a copy of it and name the copy **`.env`**.
   - **Windows (GUI):** right-click `.env.example` → **Copy**, right-click empty space → **Paste**, then right-click the pasted file → **Rename** → type `.env`
   - **Windows (terminal):** `copy .env.example .env`
   - **Linux/macOS (GUI):** press `Ctrl+H` (Linux) or `Cmd+Shift+.` (macOS) to show hidden files, right-click the file → **Copy** → **Paste**, then rename it to `.env`
   - **Linux/macOS (terminal):** `cp .env.example .env`
2. Open `.env` in a text editor. For **XAMPP** it should already be:

```bash
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=eventrify
DB_USER=root
DB_PASSWORD=
APP_URL=http://localhost:8000
```

> Just leave it as-is. These are XAMPP's default values (user `root`, no password). Only change `DB_PASSWORD` if your MySQL has a password.

---

### Step 5 — Run the website

Open a terminal (Command Prompt / PowerShell on Windows) **inside the project folder** and run:

**Windows** (if `php` works on your PATH):
```powershell
php -S localhost:8000 -t public
```
**Windows** (using XAMPP's PHP directly, most reliable):
```powershell
C:\xampp\php\php.exe -S localhost:8000 -t public
```

**Linux/macOS** (if `php` works on your PATH):
```bash
php -S localhost:8000 -t public
```
**Linux** (using XAMPP's PHP directly, most reliable):
```bash
/opt/lampp/bin/php -S localhost:8000 -t public
```

Then open your browser and go to:

```
http://localhost:8000
```

You should see the **public events landing page**. 🎉

> Tip: keep this terminal open — that **is** your website server. Press `Ctrl+C` to stop it.

---

## Demo Accounts

| Role | Email | Password | Logs you into |
|---|---|---|---|
| Admin | `admin@eventrify.com` | `admin123` | Admin Dashboard |
| Club Admin | `club@eventrify.com` | `club123` | Club Dashboard |
| User | `john@example.com` | `user123` | Club Dashboard |

Or create a fresh account at the **Register** page.

---

## Pages / URLs

| URL | What it is |
|---|---|
| `/` | Public landing page — explore & search all events |
| `/event?id=1` | Public event detail page |
| `/login`, `/register` | Sign in / create account |
| `/admin` | Admin dashboard |
| `/admin/users` (+ create/edit) | Manage users |
| `/admin/reports` | Reports & analytics |
| `/admin/settings` | Platform settings |
| `/club` | Club dashboard overview |
| `/club/events` (+ create/edit) | Manage events |
| `/club/tasks` | Task list |
| `/club/settings` | Workspace settings |

---

## Project Structure (short version)

```text
eventrify/
├── public/          # what the browser can access (start here)
│   └── index.php    # tiny router → loads pages
├── app/             # application code
│   ├── config/      # app.php, database.php, .env loader
│   ├── helpers/     # auth, redirect, small functions
│   ├── layouts/     # dashboard-a + dashboard-b + landing (header/sidebar/navbar/footer)
│   └── components/  # reusable UI (table, alert, modal, pagination, button)
├── pages/           # each page's logic
│   ├── landing/     # public pages (event listing, event detail)
│   ├── auth/        # login, register, logout
│   ├── dashboard-a/ # admin pages
│   └── dashboard-b/ # club pages
├── src/css/         # Tailwind source (only if you edit styles)
├── database/        # database.sql + seed.sql
└── .env             # your local settings (don't share this file)
```

---

## Tailwind CSS (only needed if you edit styles)

The pre-built CSS (`public/assets/css/app.css`) is already included, so **you can skip this**.

If you want to change the design (commands are the same on Windows and Linux):

```bash
npm install
npm run dev     # watches changes and rebuilds CSS automatically
```

Run this **in a second terminal** next to the `php -S` server. On Windows use Command Prompt/PowerShell, on Linux use a terminal — the commands are identical.

---

## Troubleshooting

**"mysqli PHP extension is not enabled"**

You are using a PHP that doesn't have MySQL support. Fixes:

- **Easiest — use XAMPP's PHP.** Run the site with:
  - Windows: `C:\xampp\php\php.exe -S localhost:8000 -t public`
  - Linux: `/opt/lampp/bin/php -S localhost:8000 -t public`
- **Or install MySQL support for your system PHP:**
  - Linux (Ubuntu/Debian):
    ```bash
    sudo apt install php8.3-mysql
    ```
  - Windows: open your PHP folder, find `php.ini`, remove the `;` in front of `extension=mysqli`, save, and restart the server.
  - macOS (Homebrew): `brew install php-mysql` or `brew reinstall php`

**"No such file or directory" when connecting**

MySQL couldn't be reached over the missing Unix socket (common on Linux when mixing XAMPP MySQL with system PHP). Fix: use `DB_HOST=127.0.0.1` in `.env` (already the default) and make sure **MySQL is started** in XAMPP.

**"Database connection failed"**

`.env` values are wrong, or XAMPP MySQL isn't running. Open XAMPP → press **Start** on MySQL, double-check `DB_USER` / `DB_PASSWORD`.

**White page / no styling**

Run `npm run build` once to generate the CSS, or re-clone the repo (the `public/assets/css/app.css` file must exist).

**Port 8000 already in use**

Use another port and update `APP_URL`:
- Windows / Linux: `php -S localhost:8080 -t public`
- For XAMPP's PHP on Windows: `C:\xampp\php\php.exe -S localhost:8080 -t public`
- For XAMPP's PHP on Linux: `/opt/lampp/bin/php -S localhost:8080 -t public`

Then set `APP_URL=http://localhost:8080` in `.env`.

---

## Skills you'll practice

- PHP + MySQL CRUD (create, read, update, delete)
- HTML / CSS / Tailwind UI
- Login & registration with sessions
- Two dashboard designs in one project
- Database design with foreign keys (users, events, registrations, tasks)