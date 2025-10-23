# Copilot Instructions for WorkConnect

## Project Overview
- **WorkConnect** is a PHP-based web application for managing jobseekers, employers, and skills registries for a local government unit (LGU).
- The codebase is organized by user type: `Employee/` (jobseeker-facing) and `Employer/` (admin/PESO-facing). Shared assets are in `assets/`.
- Data is stored in a MySQL database (see `Employer/db.php` for connection details).

## Key Components
- **Employee/**: Contains HTML forms and `apply.php` for jobseeker registration. Handles file uploads (resumes) to `uploads/resumes/`.
- **Employer/**: Contains dashboards, jobseeker/skill registry management, and API endpoints (e.g., `dashboard_stats.php`, `skill_registry.php`, `jobseekers.php`).
- **assets/**: CSS and images for UI. No build step; static assets are referenced directly.
- **uploads/resumes/**: Stores uploaded resume files. Ensure this directory is writable.

## Data Flow & APIs
- All data is stored in a remote MySQL DB. Most PHP files in `Employer/` act as REST-like endpoints returning JSON.
- `skill_registry.php` supports GET (filter by barangay/month/year), POST (add), and PUT (update) for skill records.
- `jobseekers.php` returns all jobseeker data as JSON, including computed age.
- `apply.php` (Employee) processes jobseeker registration and resume upload.

## Conventions & Patterns
- **No framework**: All code is custom PHP, procedural style, minimal OOP.
- **DB access**: Use `db.php` for connection. Most queries use `mysqli` prepared statements (except `apply.php`, which uses string interpolation—be cautious of SQL injection risk).
- **API responses**: JSON for most Employer endpoints; HTML or plain text for Employee-facing pages.
- **Sanitization**: Use `sanitize()` (Employer) or `real_escape_string()` (Employee) for user input.
- **File uploads**: Only image files are accepted for resumes (see `apply.php`).

## Developer Workflows
- **No build step**: Edit PHP/HTML/CSS directly. No npm, composer, or asset pipeline.
- **Testing**: No automated tests. Manual testing via browser and API tools (e.g., Postman) is standard.
- **Debugging**: Use `error_log()` or inline `echo`/`var_dump()` for debugging. Check PHP error logs.
- **Local dev**: Use XAMPP or similar LAMP stack. Place project in `htdocs` and access via `http://localhost/WorkConnect/`.

## Integration Points
- **Database**: All data is in MySQL (see `db.php`).
- **Uploads**: Resumes are stored in `uploads/resumes/` and referenced by filename in the DB.
- **No external APIs**: All logic is internal; no third-party integrations.

## Examples
- To add a new skill record: POST JSON to `Employer/skill_registry.php`.
- To fetch jobseeker data: GET `Employer/jobseekers.php`.
- To register a jobseeker: POST form data (with file) to `Employee/apply.php`.

---
**For AI agents:**
- Always check for existing DB connection code in `db.php` before adding new DB logic.
- Follow the directory structure for user-facing vs. admin features.
- Use prepared statements for new DB queries (see `skill_registry.php` for pattern).
- When adding new endpoints, return JSON for Employer/admin, HTML for Employee/jobseeker.
- Keep code procedural and simple; avoid introducing frameworks or complex OOP unless discussed.
