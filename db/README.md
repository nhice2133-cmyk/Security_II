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

## First Admin Account

The SQL file does **not** ship any default credentials.

After deploying, use the Super Admin creation flow in the application to create
the first privileged account. You will be prompted to set a strong password and
security questions on first login.

> **Never** hard-code or commit default admin passwords into source files or documentation.

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

## Notes

- The schema uses UTF8MB4 encoding for full Unicode support
- All foreign keys use CASCADE delete for data integrity
- Indexes are created on frequently queried columns for performance
