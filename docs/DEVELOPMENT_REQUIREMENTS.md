# WorkConnect — Development Hardware & Software Requirements

This document describes the hardware and software needed by developers to design, build, test, and deploy the **WorkConnect** system (job-matching and recruitment platform with Employee, Employer, and Company roles).

---

## 1. Development Hardware Requirements

The following specifications support comfortable development, local testing, and deployment tasks.

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **Processor** | 64-bit, 2 cores | 64-bit, 4+ cores |
| **RAM** | 4 GB | 8 GB or more |
| **Storage** | 10 GB free (OS + stack + project) | 20+ GB SSD (faster PHP/DB and upload handling) |
| **Display** | 1280×720 | 1920×1080 or higher (for UI and multi-window work) |
| **Network** | Broadband for Git, Composer, and API testing | Stable connection for RDS/remote DB and email testing |

**Notes:**

- **Design & build:** A multi-core CPU and 8 GB RAM improve responsiveness when running Apache, MySQL, and an IDE together.
- **Testing:** Running the full stack (web server + MySQL + browser) benefits from the recommended RAM. Multiple browser tabs or devices for role-based testing do not require extra hardware beyond the above.
- **Deploy:** If deploying from the same machine (e.g., Git push, CI, or manual upload), the minimum specs are sufficient; recommended specs reduce build/upload time.
- **Optional:** A second monitor helps when comparing UI (Employee/Employer/Company) or debugging. No special GPU is required.

---

## 2. Software Requirements

### 2.1 Operating System

- **Windows:** 10 or 11 (64-bit) — e.g. for XAMPP and local MySQL.
- **macOS:** 10.15 (Catalina) or later.
- **Linux:** Any modern distribution with PHP 7.4+ and MySQL/MariaDB (e.g. Ubuntu 20.04 LTS or later).

### 2.2 Core Development Stack

| Software | Version | Purpose |
|----------|---------|---------|
| **PHP** | 7.4 or 8.x (8.1+ recommended) | Server-side application and APIs |
| **MySQL** | 5.7+ or 8.0+ | Database (local dev and/or AWS RDS) |
| **Web server** | Apache 2.4+ (or Nginx 1.18+) | Serves PHP and static assets |
| **Composer** | 2.x | Dependency management (PHPMailer, TCPDF) |
| **Git** | 2.x | Version control and push to GitHub |

### 2.3 PHP Extensions (required)

These must be enabled in `php.ini` for the application and its dependencies to work:

| Extension | Purpose |
|-----------|---------|
| **mysqli** | Database connectivity |
| **mbstring** | Multibyte strings (PHPMailer, UTF-8 content) |
| **openssl** | TLS/SSL for SMTP and secure connections |
| **json** | JSON APIs and config |
| **fileinfo** | File type validation (resumes, images) |
| **gd** or **imagick** | Image handling (e-signatures, company logos) |
| **curl** | HTTP requests (e.g. external APIs or mail) |
| **zip** | Optional; useful for exports or packages |

**Checking extensions (CLI):**  
`php -m`  
Or in browser: use a single `phpinfo()` page and ensure the above appear.

### 2.4 Application Dependencies (Composer)

Installed via `composer install` from the project root:

| Package | Version (in project) | Purpose |
|---------|----------------------|---------|
| **phpmailer/phpmailer** | ^6.8 | Transactional email (applications, password reset, notifications) |
| **tecnickcom/tcpdf** | ^6.10 | PDF generation (resumes, job postings export) |

Project path: `composer.json` in repository root. Run:

```bash
composer install
```

so that `vendor/autoload.php` exists; the app and scripts require it for PHPMailer and TCPDF.

### 2.5 Database

- **Engine:** MySQL 5.7+ or MariaDB 10.3+ (UTF-8 support).
- **Character set:** `utf8mb4` and collation `utf8mb4_unicode_ci` (as in `setup_complete_database.php`).
- **Access:**  
  - **Local:** MySQL/MariaDB via XAMPP, WAMP, or native install.  
  - **Remote:** Optional use of AWS RDS (or similar) for shared dev/staging; connection details in `db.php` (and related config) per environment.

Schema and migrations: use `setup_complete_database.php` for full schema (28 tables). Optional migration scripts (e.g. `add_admin_company_follow_up_table.php`, `add_follow_up_hidden_columns.php`) as needed for existing databases.

### 2.6 Browsers (for testing)

- **Primary:** Latest Chrome or Edge (for dev tools and consistent behavior).
- **Also test:** Firefox and Safari (or current mobile browsers) for cross-browser checks on login, applications, and file uploads.

### 2.7 Optional / Convenience

| Software | Purpose |
|----------|---------|
| **XAMPP** (or WAMP, MAMP) | All-in-one Apache + MySQL + PHP for Windows/macOS. |
| **VS Code / Cursor / PhpStorm** | Editing PHP, JS, and config files. |
| **MySQL Workbench or DBeaver** | Inspecting and querying the database. |
| **Postman or similar** | Testing JSON APIs (e.g. apply, follow-up, announcements). |

### 2.8 Deployment (target environment)

- **Web server:** Apache or Nginx with PHP-FPM, matching the PHP and extension requirements above.
- **Database:** MySQL 5.7+ or 8.0+ (or compatible RDS) with `utf8mb4`.
- **PHP:** Same major/minor as used in development (e.g. 8.1) and the same extensions enabled.
- **Composer:** Run `composer install --no-dev` on the server (or deploy `vendor/` from a build that used that flag).
- **Writable directories:** `uploads/esignatures/`, `uploads/resumes/`, and `assets/uploads/` (or configured paths) must be writable by the web server user.
- **Git:** Optional on server; alternatively deploy via CI/CD or FTP/SFTP, keeping `vendor/` and upload dirs out of public document root where appropriate.

---

## Summary

- **Hardware:** 64-bit CPU (4+ cores recommended), 8 GB RAM recommended, 20+ GB free SSD, standard display and network.
- **Software:** PHP 7.4+ (8.1+ recommended), MySQL 5.7+/8.0+, Apache (or Nginx), Composer, Git; required PHP extensions include mysqli, mbstring, openssl, json, fileinfo, gd (or imagick).
- **Project:** Run `composer install`, configure DB (local or RDS), run `setup_complete_database.php` (or migrations), and ensure upload directories are writable.

These requirements align with the current WorkConnect codebase (PHP, MySQL, PHPMailer, TCPDF, file uploads, and multi-role web UI).
