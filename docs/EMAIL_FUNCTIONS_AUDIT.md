# WorkConnect — Email Functions Audit

This document lists every place in the system that sends email, whether it is working, and what was verified or fixed.

**Configuration:** All SMTP email uses `Employer/email_config.php` (Gmail SMTP). Ensure `vendor/autoload.php` exists (run `composer install`) so PHPMailer is available. Without it, scripts fall back to PHP `mail()` which often fails on shared hosting.

---

## 1. Password reset (Employee)

| File | Trigger | Recipient | Status |
|------|---------|-----------|--------|
| **Employee/forgot_password_phpmailer.php** | Employee “Forgot password” on login | Employee email | ✅ **Working** – Used by Employee login page. Uses PHPMailer when available, fallback `mail()`. |
| **Employee/forgot_password_handler.php** | Same flow (alternative endpoint) | Employee email | ✅ **Fixed** – Now uses PHPMailer when available (previously only `mail()`). |
| **Employee/forgot_password.php** | Standalone endpoint (duplicate) | Employee email | ✅ **Fixed** – Now uses PHPMailer when available (previously only `mail()`). |

**Note:** The Employee login form calls `forgot_password_phpmailer.php`. The other two files are alternative/legacy endpoints; all three now use SMTP when Composer/PHPMailer is installed.

---

## 2. Password reset (Company)

| File | Trigger | Recipient | Status |
|------|---------|-----------|--------|
| **Company/forgot_password_handler.php** | Company “Forgot password” on login | Company email | ✅ **Working** – Uses PHPMailer when available, fallback `mail()`. Stores token in `company_password_resets`. Reset link points to `Company/reset_password.php`. |

---

## 3. Password reset (Employer/Admin)

| File | Trigger | Recipient | Status |
|------|---------|-----------|--------|
| *(none)* | — | — | ⚠️ **Not implemented** – Employer/Admin login has no “Forgot password” flow in the codebase. Add if required. |

---

## 4. Application / referral emails

| File | Trigger | Recipient | Status |
|------|---------|-----------|--------|
| **Employee/apply.php** (`sendSubmissionConfirmationEmail`) | Employee submits NRSP form (new submission only) | Employee email | ✅ **Working** – PHPMailer when available, fallback `mail()`. Errors logged; submission still succeeds if email fails. |
| **Company/handle_application.php** (accept) | Company accepts applicant in View Applicants | Jobseeker email | ✅ **Working** – PHPMailer or `mail()`. ✅ **Fixed** – Only sends if jobseeker has a valid email; otherwise accepts without sending and returns a clear message. |
| **Company/handle_application.php** (reject) | Company rejects applicant with reason | Jobseeker email | ✅ **Working** – Same as accept. ✅ **Fixed** – Valid-email check and clearer response message. |
| **Employer/send_jobseeker_accepted_email.php** | Employer/Admin forwards jobseeker to company (referral) | Jobseeker email | ✅ **Working** – “Your application has been forwarded to [company]”. ✅ **Fixed** – Returns error if jobseeker has no valid email instead of attempting send. |
| **Employer/send_jobseeker_rejection_email.php** | Employer/Admin rejects jobseeker with reason | Jobseeker email | ✅ **Working** – Rejection reason in body. ✅ **Fixed** – Same valid-email check. |
| **Employer/send_jobseeker_email.php** | Employer refers jobseeker (sends profile to company) | **Employer/company email** | ✅ **Working** – Email to employer with full jobseeker profile (not to jobseeker). PHPMailer or `mail()`. |
| **Employer/send_email_with_phpmailer.php** | Same as above (alternative script) | **Employer/company email** | ✅ **Working** – Same purpose; PHPMailer when available, else `mail()`. |

---

## 5. Test / debug scripts (no production flow)

| File | Purpose | Status |
|------|---------|--------|
| **test_email.php** | Test PHP `mail()` | Test only |
| **test_phpmailer_direct.php** | Test PHPMailer config and SMTP | Test only |
| **Employee/test_forgot_password_email.php** | Test Employee forgot-password email | Test only |
| **test_server_email.php** | Lists email-related endpoints | Test only |

---

## Summary of fixes applied

1. **Employee forgot_password_handler.php** – Uses PHPMailer when available (was only `mail()`).
2. **Employee forgot_password.php** – Same PHPMailer + fallback (was only `mail()`).
3. **Employer/send_jobseeker_accepted_email.php** – Validates jobseeker email before send; returns clear error if missing/invalid.
4. **Employer/send_jobseeker_rejection_email.php** – Same valid-email check.
5. **Company/handle_application.php** – Accept and reject only attempt send when jobseeker has a valid email; action still succeeds and response message indicates whether email was sent or skipped.

---

## How to verify email is working

1. Run `composer install` in project root so `vendor/autoload.php` and PHPMailer exist.
2. Ensure `Employer/email_config.php` has correct Gmail (or other) SMTP credentials and that the account allows “App passwords” if using Gmail.
3. Test Employee forgot password: Employee login → Forgot password → enter email; check inbox (and spam).
4. Test Company forgot password: Company login → Forgot password → enter email; check inbox.
5. Test application confirmation: Submit a new NRSP form as Employee; check for confirmation email.
6. Test accept/reject from Company: View Applicants → Accept or Reject; jobseeker should receive the corresponding email (if email is set and valid).
7. Optional: Run `test_phpmailer_direct.php` in browser (and fix path to `email_config.php` if needed) to test SMTP connectivity.

---

## Configuration reference

- **SMTP config:** `Employer/email_config.php` defines `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME`.
- **From address:** All emails use the same “From” as configured (e.g. WorkConnect &lt;mitch00030@gmail.com&gt;). Fallback `mail()` uses `From: WorkConnect <noreply@workconnect.com>`.
- **Security:** Do not commit real SMTP credentials to version control; use environment variables or a local-only config in production.
