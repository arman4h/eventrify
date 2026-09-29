# Running Eventrify

## Quick start (XAMPP)

1. Start **Apache** and **MySQL** from the XAMPP control panel.
   Apache is only needed for phpMyAdmin — the site runs on PHP's own server.
2. Open a terminal in VS Code in the project folder.
3. Build the CSS once:
   ```bash
   npm install
   npm run build
   ```
   Use `npm run dev` instead of `build` to keep rebuilding while you edit styles.
4. Start the site:
   ```bash
   php -S localhost:8000 -t public
   ```
   (On Windows with XAMPP's PHP: `C:\xampp\php\php.exe -S localhost:8000 -t public`)
5. Visit <http://localhost:8000>.

## Database

Import the schema and demo data through phpMyAdmin, or from a terminal:

```bash
mysql -u root -p -e "CREATE DATABASE eventrify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p eventrify < database/finalschema.sql
```

Then copy `.env.example` to `.env` and set `DB_NAME`, `DB_USER`, and `DB_PASSWORD` to match
your local MySQL.

## Demo accounts

| Role | Email | Password | Sign-in page |
|---|---|---|---|
| Student | `arman4hn@gmail.com` | `arman123` | `/login` |
| Club owner | `mahi@gmail.com` | `mahi123` | `/club/login` |
| System admin | `admin@eventrify.com` | `admin123` | `/admin/login` |

## Club registration flow

1. The applicant fills in the club and their own details at `/club/register`, and sets a password.
2. On submit they are signed in and sent to `/club`.
3. Because the club's status is `pending`, the dashboard shows an **Account Not Active Yet**
   screen (pending-review badge and application ID). Every other club page redirects back to `/club`.
4. An administrator approves the request at `/admin/club-requests`, which sets the club to `approved`.
5. The club is now fully unlocked. Members sign in at `/club/login` with the **official club
   email** and the password they chose.
