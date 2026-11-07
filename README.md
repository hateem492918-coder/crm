# Kahloon CRM — MVP foundation

A small, local-first PHP 8.3+ / MySQL CRM foundation. This first release provides session-based authentication, a dashboard, customer and lead listing/creation/editing/archiving, server-side validation, CSRF protection, PDO prepared statements, and role scaffolding. Google Sheets, tasks, opportunities, granular permissions, and reporting exports are intentionally left for later releases.

## Requirements

- PHP 8.3+ with PDO MySQL
- MySQL 8+
- Apache with `mod_rewrite` (or configure the document root to `public/`)

## Install on XAMPP

1. Copy the project into `C:\xampp\htdocs\crm`.
2. Create and select a database, then import `database/schema.sql` using phpMyAdmin.
3. Copy `config/config.example.php` to `config/config.php` and set the database connection details. Keep `config/` outside the web document root in production.
4. From a terminal in the project root, create the first administrator:

   ```sh
   php bin/create-admin.php
   ```

   Follow the prompts. The password is stored using PHP's `password_hash()`.
5. Visit `http://localhost/crm/public/` and sign in. For a cleaner URL, point an Apache virtual host's document root at the project's `public/` directory.

## Security and operations

- Configure a dedicated MySQL account with only the permissions the app needs; do not use `root` in the application.
- Use HTTPS in production and protect `config/config.php` and database backups.
- The first release is a foundation, not a production security certification. Add deployment-specific rate limiting, password reset, granular permission checks, backup/restore procedures, and audit review before handling real customer data.
- The app uses CSRF tokens for state-changing forms, session ID regeneration on login, secure cookie flags when HTTPS is enabled, output escaping, and prepared statements.

## Project layout

- `app/bootstrap.php` — configuration, session, database, and shared helpers
- `public/index.php` — request routing and CRM pages
- `public/assets/app.css` — responsive styling
- `database/schema.sql` — initial relational schema
- `bin/create-admin.php` — interactive initial administrator setup

## Current scope

The MVP currently implements authentication and customer/lead workflows. The supplied blueprint's Google Sheets synchronization, conflict review, task/follow-up workflow, opportunities, user administration UI, and advanced reports are not yet implemented; schema and roadmap should be extended as those releases are built.
