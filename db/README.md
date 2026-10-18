# Database Setup

This folder contains SQL files for setting up the authentication system database.

## Files

- **auth_system.sql** - Schema-only database structure (no production data)

## Installation

### Option 1: Using phpMyAdmin
1. Open phpMyAdmin in your browser
2. Click on **Import** tab
3. Select `auth_system.sql` file
4. Click **Go** to execute

### Option 2: Using MySQL Command Line
```bash
mysql -u your_app_user -p < auth_system.sql
```

### Option 3: Using XAMPP MySQL
1. Open XAMPP Control Panel
2. Start MySQL service
3. Open MySQL command line or phpMyAdmin
4. Execute the SQL file

## Database Configuration

After importing the schema, update the database credentials in `php/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_app_db_user');  // Do NOT use root in production
define('DB_PASS', '');                  // Set via .env or environment variable
define('DB_NAME', 'auth_system');
```

> **Important:** Do NOT use `root` as the database user in production.
> Create a dedicated database account with only the required privileges (SELECT, INSERT, UPDATE, DELETE on the `auth_system` database).

## Seeded Accounts

The `auth_system.sql` file comes configured with exactly 3 accounts:

| Role | Username | Password | Email | ID Number |
|---|---|---|---|---|
| **Super Admin** (`super_admin`) | `superadmin` | `Admin@12345!` | `superadmin@example.com` | `EMP-001` |
| **Administrator** (`admin`) | `admin` | `Admin@12345!` | `admin@example.com` | `2026-0001` |
| **Regular User** (`user`) | `user` | `User@1234` | `user@example.com` | `2026-0002` |

### Security Recovery Questions (All 3 Accounts)
All 3 accounts have the default security questions pre-configured with the answer `admin`:
- **Q1:** Who is your best friend in Elementary? -> `admin`
- **Q2:** What is the name of your favorite pet? -> `admin`
- **Q3:** Who is your favorite teacher in high school? -> `admin`

## Database Structure

### Tables

1. **users** — User account information
2. **login_logs** — Session/login audit trail
3. **login_attempts** — Brute-force throttling
4. **registration_attempts** — Registration rate limiting per IP
5. **user_security_questions** — Security questions for account recovery
6. **password_reset_tokens** — (Legacy) password reset token management
7. **otps** — OTP codes for password recovery flow
8. **delete_requests** — Admin-initiated deletion request queue
9. **super_admin_lock** — Singleton lock for single-active Super Admin enforcement
10. **superadmin_handover_settings** — Superadmin handover deactivation and reactivation tracking
11. **superadmin_succession_logs** — Superadmin succession audit log

## Notes

- The schema uses UTF8MB4 encoding for full Unicode support
- All foreign keys use CASCADE delete for data integrity
- Indexes are created on frequently queried columns for performance
