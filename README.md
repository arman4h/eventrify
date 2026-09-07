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
| **XAMPP** | Apache (serves phpMyAdmin) + MySQL (the database) — run both |
| **PHP 8+** | Runs the website via its own command |
| **Node.js + npm** | Installs and builds **Tailwind CSS** (required — the site's styling) |

---

## Setup Guide

> You will run **Apache and MySQL normally** from XAMPP (Apache is used for phpMyAdmin), and separately run the PHP project **with its own command on a different port** — so they never conflict. If you use a separately installed PHP and it shows errors about `mysqli`, jump to the [Troubleshooting](#troubleshooting) section at the bottom.

### Step 1 — Put the project on your computer

Download / clone the repository anywhere you like and open the folder.

> Do **not** put it inside `C:\xampp\htdocs\eventrify` / `htdocs` — you will run the project with your own PHP command instead, so it works from any folder.

---

### Step 2 — Install Tailwind CSS (must do before running)

The project uses **Tailwind CSS** for all styling. You need to install its packages and build the CSS file **before** starting the site.

1. Make sure **Node.js** is installed:
   - Windows / Linux / macOS: check in a terminal with:
     ```bash
     node --version
     npm --version
     ```
   - If it says "command not found", download Node.js from <https://nodejs.org> (the LTS version), install it, and re-open your terminal.

2. From the **project folder**, install the Tailwind packages:
   ```bash
   npm install
   ```
   (Windows / Linux / macOS — same command.)

3. Build the CSS file once:
   ```bash
   npm run build
   ```
   This creates `public/assets/css/app.css` from `src/css/app.css`.

4. Verify the file exists:
   - Look for `public/assets/css/app.css` in the project — if it's there, Tailwind is ready.


---

### Step 3 — Start Apache and MySQL

GUI way (recommended):

1. Open the **XAMPP Control Panel**.
2. Press the **Start** button on the **Apache** row and on the **MySQL** row (both turn green).
3. Leave both running.

> **Apache** is needed so that **phpMyAdmin** works (the SQL dashboard). **MySQL** is the database the project connects to.

---

### Step 4 — Create the database

GUI way (recommended):

1. Click the **Admin** button next to the MySQL row in XAMPP → this opens **phpMyAdmin** in your browser (requires Apache to be running).
2. Near the top, click the **Import** tab.
3. Press **Choose File**, pick **`database/database.sql`** from the project folder.
4. Press the blue **Import** button at the bottom.
5. Do the **same again** with **`database/seed.sql`**.

That's it — the database **`eventrify`** with tables and sample data is now created.

> Terminal way (if you prefer - ):
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

### Step 5 — Create the `.env` file

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

### Step 6 — Run the website

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
>
> This runs **alongside Apache** which stays on port 80 — so at the same time you can open phpMyAdmin at `http://localhost/phpmyadmin` and the project at `http://localhost:8000` without any conflict.

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
├── src/css/         # Tailwind source CSS (input — builds public/assets/css/app.css)
├── database/        # database.sql + seed.sql
└── .env             # your local settings (don't share this file)
```

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

**White page / no styling / "npm not found"**

Tailwind wasn't installed/built yet. From the project folder run `npm install`, then `npm run build` to generate `public/assets/css/app.css`, and restart the `php -S` server. If `npm` is missing, install Node.js first (see Step 2).

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