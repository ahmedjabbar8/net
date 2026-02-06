# HealthPro OS

## Overview
HealthPro OS is a hospital management system built with PHP and MariaDB. It provides a comprehensive medical workflow including patient registration, appointments, triage, consultations, lab/radiology requests, pharmacy, billing, and more. The interface is in Arabic (RTL).

## Project Architecture
- **Language**: PHP 8.2
- **Database**: MariaDB (MySQL-compatible), running locally with `--skip-grant-tables`
- **Server**: PHP built-in development server with `router.php` for clean URLs
- **Frontend**: Server-side rendered PHP with Bootstrap 5 RTL, Font Awesome, and custom Apple-inspired CSS
- **Database Name**: `HospitalSystem`

## Key Files
- `config.php` - Database connection and session initialization
- `router.php` - Clean URL routing for PHP built-in server
- `login.php` - Authentication (username/password)
- `dashboard.php` - Main dashboard after login
- `header.php` / `footer.php` - Layout templates
- `setup_database.sql` - Full database schema with seed data
- `start.sh` - Startup script (MariaDB + PHP server)
- `apple_ui.css` / `modern_ui.css` - Stylesheets

## How to Run
The `start.sh` script handles everything:
1. Starts MariaDB on port 3306 (localhost only)
2. Creates the `HospitalSystem` database if not exists
3. Starts PHP development server on `0.0.0.0:5000`

## Default Login Credentials
- **Admin**: username `admin`, password `123456`
- **Doctor**: username `doctor`, password `123456`
- **Reception**: username `reception`, password `123456`
- Other users: `nurse`, `lab`, `radio`, `pharmacy`, `accountant` (all password `123456`)

## Database
- MariaDB data stored in `~/mysql_data`
- Socket at `~/mysql_run/mysql.sock`
- Logs at `~/mysql_log/error.log`
- Schema defined in `setup_database.sql`

## Recent Changes
- 2026-02-06: Initial Replit setup - added `start.sh`, `setup_database.sql`, configured workflow
