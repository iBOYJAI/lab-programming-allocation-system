# Database Setup

## One-time setup (recommended)

From the **project root** folder, run:

- **START.bat** — Imports the database and opens the app in the browser.

Or manually:

```bash
mysql -u root < database/lab_allocation_system.sql
```

## SQL file

- **lab_allocation_system.sql** — Full setup: creates database `lab_allocation_system`, all tables (including `departments`), and seed data. Run this file once when copying the project to a new system so Department Management and the rest of the app work without errors.

## Optional migration (existing databases only)

If you already had the database **without** the `departments` table, run once:

- **migrate_departments.php** in the browser, or `php database/migrate_departments.php` from project root.  
  This creates the `departments` table, adds `department_id` columns, seeds default departments, and **maps existing staff/students/subjects** to departments so Department Management shows correct counts.

If departments table already exists but **counts are still 0**, run once:

- **map_departments_data.php** — maps existing staff (by department name), students (by department name), and subjects (by subject code prefix CS/IT/AI) to departments.
