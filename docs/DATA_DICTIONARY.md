# WorkConnect — Data Dictionary

This document describes every table and attribute in the WorkConnect database. For each table: **Field/Attribute Name**, **Description**, **Data Type**, **Length/Size**, and **Allowed Values**.

Database: **WorkConnect** (MySQL/MariaDB, utf8mb4).  
*Generated from schema in `setup_complete_database.php` and related migrations.*

---

## 1. employee_users

Stores employee (jobseeker) portal user accounts used for login and profile linkage.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key; unique user identifier | Integer | — | AUTO_INCREMENT, NOT NULL |
| firstname | User’s first name | Text (VARCHAR) | 100 | Not null |
| lastname | User’s last name | Text (VARCHAR) | 100 | Not null |
| email | Login and contact email; must be unique | Text (VARCHAR) | 255 | Unique, not null |
| password | Hashed password for authentication | Text (VARCHAR) | 255 | Not null |
| created_at | When the account was created | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | When the record was last updated | Timestamp | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

---

## 2. admin_accounts

Stores employer/admin portal accounts (e.g. PESO staff) for managing jobs and announcements.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key; unique admin identifier | Integer | — | AUTO_INCREMENT, NOT NULL |
| username | Login username; must be unique | Text (VARCHAR) | 100 | Unique, not null |
| password | Hashed password for authentication | Text (VARCHAR) | 255 | Not null |
| created_at | When the account was created | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | When the record was last updated | Timestamp | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

---

## 3. jobseeker

Extended profile and application data for each employee user (one-to-one with `employee_users` via `user_id`). Includes demographics, skills, work history, education, and application status.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key; unique jobseeker/application record identifier | Integer | — | AUTO_INCREMENT, NOT NULL |
| user_id | References employee_users(id) | Integer | — | NOT NULL, FK to employee_users(id) ON DELETE CASCADE |
| surname | Family name | Text (VARCHAR) | 100 | Nullable |
| firstname | Given name | Text (VARCHAR) | 100 | Nullable |
| middlename | Middle name | Text (VARCHAR) | 100 | Nullable |
| suffix | Name suffix (e.g. Jr., III) | Text (VARCHAR) | 10 | Nullable |
| dob | Date of birth | Date | — | Nullable, valid date |
| sex | Sex/gender | Text (VARCHAR) | 20 | Nullable |
| religion | Religion | Text (VARCHAR) | 100 | Nullable |
| civilstatus | Civil status | Text (VARCHAR) | 50 | Nullable |
| street | House no./street/village | Text (VARCHAR) | 255 | Nullable |
| barangay | Barangay | Text (VARCHAR) | 100 | Nullable |
| municipality | Municipality | Text (VARCHAR) | 100 | Nullable |
| province | Province | Text (VARCHAR) | 100 | Nullable |
| tin | Tax Identification Number | Text (VARCHAR) | 50 | Nullable |
| height | Height | Text (VARCHAR) | 20 | Nullable |
| contact | Contact number | Text (VARCHAR) | 50 | Nullable |
| email | Contact email | Text (VARCHAR) | 255 | Nullable |
| hasDisability | Whether the person has a disability | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| disability_speech | Speech disability flag | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| disability_hearing | Hearing disability flag | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| disability_visual | Visual disability flag | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| disability_mental | Mental disability flag | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| disability_others | Other disability flag | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| disability_other | Description of other disability | Text (TEXT) | 65,535 | Nullable |
| employed | Currently employed flag | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| employment_type_wage | Wage employment | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| employment_type_self | Self-employment | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| self_employed_specify | Self-employment specification | Text (VARCHAR) | 255 | Nullable |
| self_type_voluntary | Voluntary work | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| self_type_vendor | Vendor | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| self_type_homebased | Home-based | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| self_type_transport | Transport | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| self_type_domestic | Domestic work | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| self_type_fisherfolk | Fisherfolk | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| self_type_others | Other self-employment | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| other_jobs | Other jobs description | Text (VARCHAR) | 255 | Nullable |
| unemployed | Unemployed flag | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| unemployed_months | Months unemployed | Text (VARCHAR) | 50 | Nullable |
| unemployed_type_first | First-time jobseeker | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| unemployed_type_local | Left local job | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| unemployed_type_resigned | Resigned | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| unemployed_type_finished | Contract finished | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| unemployed_type_public | Left public sector | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| unemployed_type_retired | Retired | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| unemployed_type_terminated | Terminated | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| terminated_country | Country if terminated abroad | Text (VARCHAR) | 100 | Nullable |
| ofw | OFW status | Text (VARCHAR) | 50 | Nullable |
| ofw_country | OFW country | Text (VARCHAR) | 100 | Nullable |
| returnee | Returnee status | Text (VARCHAR) | 50 | Nullable |
| deployment_country | Deployment country | Text (VARCHAR) | 100 | Nullable |
| return_month | Return month | Text (VARCHAR) | 50 | Nullable |
| return_year | Return year | Integer | — | Nullable |
| abroad | Abroad status | Text (VARCHAR) | 50 | Nullable |
| beneficiary | Beneficiary type | Text (VARCHAR) | 50 | Nullable |
| household_id | Household identifier | Text (VARCHAR) | 50 | Nullable |
| occupation1, occupation2, occupation3 | Occupation entries | Text (VARCHAR) | 255 each | Nullable |
| fulltime | Full-time preference | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| parttime | Part-time preference | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| local1, local2, local3 | Local work entries | Text (VARCHAR) | 255 each | Nullable |
| overseas1, overseas2, overseas3 | Overseas work entries | Text (VARCHAR) | 255 each | Nullable |
| english_read, english_write, english_speak, english_understand | English proficiency flags | Tinyint (Boolean) | 1 each | 0 or 1, default 0 |
| filipino_read, filipino_write, filipino_speak, filipino_understand | Filipino proficiency flags | Tinyint (Boolean) | 1 each | 0 or 1, default 0 |
| mandarin_read, mandarin_write, mandarin_speak, mandarin_understand | Mandarin proficiency flags | Tinyint (Boolean) | 1 each | 0 or 1, default 0 |
| other_language | Other language name | Text (VARCHAR) | 100 | Nullable |
| other_read, other_write, other_speak, other_understand | Other language proficiency | Tinyint (Boolean) | 1 each | 0 or 1, default 0 |
| inschool | Currently in school | Text (VARCHAR) | 50 | Nullable |
| level | Education level | Text (VARCHAR) | 100 | Nullable |
| course | Course/degree | Text (VARCHAR) | 255 | Nullable |
| year_graduated | Year graduated | Text (VARCHAR) | 50 | Nullable |
| level_reached | Highest level reached | Text (VARCHAR) | 100 | Nullable |
| last_attended | Last school attended | Text (VARCHAR) | 100 | Nullable |
| training_course_1, 2, 3 | Training course names | Text (VARCHAR) | 255 each | Nullable |
| training_hours_1, 2, 3 | Training hours | Text (VARCHAR) | 50 each | Nullable |
| training_institution_1, 2, 3 | Training institution | Text (VARCHAR) | 255 each | Nullable |
| training_skills_1, 2, 3 | Skills from training | Text (VARCHAR) | 255 each | Nullable |
| training_cert_1, 2, 3 | Certificate (Y/N etc.) | Text (VARCHAR) | 50 each | Nullable |
| eligibility_1, eligibility_2 | Eligibility (e.g. Civil Service) | Text (VARCHAR) | 255 each | Nullable |
| eligibility_date_1, eligibility_date_2 | Eligibility date | Text (VARCHAR) | 50 each | Nullable |
| prc_1, prc_2 | PRC license | Text (VARCHAR) | 255 each | Nullable |
| prc_valid_1, prc_valid_2 | PRC validity | Text (VARCHAR) | 50 each | Nullable |
| company_name_1, 2, 3 | Work experience company name | Text (VARCHAR) | 255 each | Nullable |
| company_address_1, 2, 3 | Company address | Text (VARCHAR) | 255 each | Nullable |
| position_1, 2, 3 | Job position | Text (VARCHAR) | 255 each | Nullable |
| months_1, months_2, months_3 | Duration in months | Text (VARCHAR) | 50 each | Nullable |
| status_1, status_2, status_3 | Employment status | Text (VARCHAR) | 50 each | Nullable |
| skill_auto_mechanic through skill_painting | Skill flags (various trades) | Tinyint (Boolean) | 1 each | 0 or 1, default 0 |
| skill_others | Other skills description | Text (VARCHAR) | 255 | Nullable |
| resume_file | Stored resume file path/name | Text (VARCHAR) | 255 | Nullable |
| esignature_file | Stored e-signature file path/name | Text (VARCHAR) | 255 | Nullable |
| submission_date | Date application was submitted | Date | — | Nullable |
| submission_month | Submission month (numeric) | Integer | — | Nullable |
| submission_year | Submission year | Integer | — | Nullable |
| application_status | Current application outcome | Enum | — | 'Pending', 'Referred', 'Accepted', 'Rejected'; default 'Pending' |
| rejection_reason | Reason if status is Rejected | Text (TEXT) | 65,535 | Nullable |
| referred_to_company_id | Company user id if referred | Integer | — | Nullable, FK to company_users(id) |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | Last update time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |
| skills_array | JSON array of skills (migration) | JSON | — | Nullable |
| years_experience | Total years of experience (migration) | Integer | — | Default 0 |
| preferred_job_type | Preferred job type (migration) | Text (VARCHAR) | 100 | Nullable |
| compatibility_score | Job-match score (migration) | Decimal | (5,2) | Default 0.00 |

---

## 4. company_users

Stores company portal accounts (employers/companies that receive referrals and post jobs).

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key; unique company identifier | Integer | — | AUTO_INCREMENT, NOT NULL |
| company_name | Legal or display name of the company | Text (VARCHAR) | 255 | Not null |
| email | Login and contact email; must be unique | Text (VARCHAR) | 255 | Unique, not null |
| password | Hashed password for authentication | Text (VARCHAR) | 255 | Not null |
| logo | Path/filename of company logo image | Text (VARCHAR) | 255 | Nullable |
| description | Company description | Text (TEXT) | 65,535 | Nullable |
| website | Company website URL | Text (VARCHAR) | 255 | Nullable |
| address | Physical address | Text (TEXT) | 65,535 | Nullable |
| phone | Contact phone | Text (VARCHAR) | 50 | Nullable |
| created_at | When the account was created | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | When the record was last updated | Timestamp | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

---

## 5. skill_registry

Registry of skills survey data (e.g. barangay-level) for analytics and matching.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| barangay | Barangay name | Text (VARCHAR) | 100 | Not null |
| city | City/municipality | Text (VARCHAR) | 100 | Default 'Norzagaray' |
| survey_date | Date of survey | Date | — | Nullable |
| printed_name | Respondent printed name | Text (VARCHAR) | 255 | Nullable |
| dob | Date of birth | Date | — | Nullable |
| ftjs | First-time jobseeker (etc.) | Text (VARCHAR) | 50 | Nullable |
| covid | COVID-related field | Text (VARCHAR) | 50 | Nullable |
| marital | Marital status | Text (VARCHAR) | 50 | Nullable |
| address | Address | Text (TEXT) | 65,535 | Nullable |
| contact | Contact number | Text (VARCHAR) | 50 | Nullable |
| education | Education level | Text (VARCHAR) | 255 | Nullable |
| age | Age | Text (VARCHAR) | 10 | Nullable |
| sex | Sex | Text (VARCHAR) | 20 | Nullable |
| we_position | Work experience position | Text (VARCHAR) | 255 | Nullable |
| we_months | Work experience months | Text (VARCHAR) | 50 | Nullable |
| se_business | Self-employment business | Text (VARCHAR) | 255 | Nullable |
| se_months | Self-employment months | Text (VARCHAR) | 50 | Nullable |
| ue | Unemployment (etc.) | Text (VARCHAR) | 50 | Nullable |
| skills | Skills (text or list) | Text (TEXT) | 65,535 | Nullable |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | Last update time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

---

## 6. notifications

In-app notifications for employee users (e.g. application updates, follow-up replies).

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| user_id | Employee user who receives the notification | Integer | — | NOT NULL (references employee_users.id in app) |
| title | Notification title | Text (VARCHAR) | 255 | Not null |
| message | Notification body | Text (TEXT) | 65,535 | Not null |
| is_read | Whether the user has read it | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| type | Optional category (e.g. follow_up) | Text (VARCHAR) | 50 | Nullable |
| created_at | When the notification was created | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |

---

## 7. password_resets

One-time tokens for employee “forgot password” flow.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| user_id | Employee user id | Integer | — | NOT NULL |
| email | Email address for the reset | Text (VARCHAR) | 255 | Not null |
| token | Unique reset token (hash) | Text (VARCHAR) | 255 | Unique, not null |
| expires_at | When the token expires | DateTime | — | Not null |
| created_at | When the token was created | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |

---

## 8. company_password_resets

One-time tokens for company portal “forgot password” flow.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| user_id | Company user id | Integer | — | NOT NULL, FK to company_users(id) ON DELETE CASCADE |
| email | Email for the reset | Text (VARCHAR) | 255 | Not null |
| token | Unique reset token (hash) | Text (VARCHAR) | 255 | Unique, not null |
| expires_at | When the token expires | DateTime | — | Not null |
| created_at | When the token was created | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |

---

## 9. job_postings

Job vacancies; may be linked to a company via company_id.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| title | Job title | Text (VARCHAR) | 255 | Not null |
| company | Display name of company | Text (VARCHAR) | 255 | Not null |
| description | Job description | Text (TEXT) | 65,535 | Not null |
| requirements | Job requirements | Text (TEXT) | 65,535 | Not null |
| salary_range | Salary range (e.g. text) | Text (VARCHAR) | 100 | Nullable |
| location | Job location | Text (VARCHAR) | 255 | Not null |
| job_type | Type of employment | Enum | — | 'Full-time', 'Part-time', 'Contract', 'Internship'; default 'Full-time' |
| industry | Industry category | Text (VARCHAR) | 100 | Nullable |
| status | Posting status | Enum | — | 'Active', 'Closed', 'Draft'; default 'Active' |
| company_id | Link to company_users (if any) | Integer | — | Nullable, FK to company_users(id) ON DELETE SET NULL |
| created_at | When the posting was created | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | Last update time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

---

## 10. user_preferences

Job preferences per employee user for matching/recommendations.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| user_id | Employee user id | Integer | — | NOT NULL, FK to employee_users(id) ON DELETE CASCADE |
| preferred_locations | Preferred job locations | JSON | — | Nullable (array of strings) |
| preferred_job_types | Preferred job types | JSON | — | Nullable (array of strings) |
| min_salary | Minimum desired salary | Decimal | (10,2) | Nullable |
| preferred_industries | Preferred industries | JSON | — | Nullable (array of strings) |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | Last update time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

---

## 11. job_applications_extended

Tracks each jobseeker’s application to a job posting (extended status and notes).

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| jobseeker_id | Jobseeker record id | Integer | — | NOT NULL, FK to jobseeker(id) ON DELETE CASCADE |
| job_posting_id | Job posting id | Integer | — | NOT NULL, FK to job_postings(id) ON DELETE CASCADE |
| compatibility_score | Match score (e.g. 0–100) | Decimal | (5,2) | Default 0.00 |
| applied_date | When the application was submitted | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| viewed_date | When employer viewed the application | Timestamp | — | Nullable |
| status | Application status in pipeline | Enum | — | 'Applied', 'Viewed', 'Interview', 'Accepted', 'Rejected'; default 'Applied' |
| notes | Employer/internal notes | Text (TEXT) | 65,535 | Nullable |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |

---

## 12. follow_up_requests

Jobseeker-initiated follow-up requests to admin; admin can answer; soft-delete per side.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| jobseeker_id | Jobseeker who requested follow-up | Integer | — | NOT NULL, FK to jobseeker(id) ON DELETE CASCADE |
| message | Jobseeker’s message | Text (TEXT) | 65,535 | Nullable |
| status | Whether admin has responded | Enum | — | 'pending', 'answered'; default 'pending' |
| admin_response | Admin’s reply text | Text (TEXT) | 65,535 | Nullable |
| responded_at | When admin responded | DateTime | — | Nullable |
| created_at | When the request was created | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| hidden_by_jobseeker | Soft-delete by jobseeker | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| hidden_by_admin | Soft-delete by admin | Tinyint (Boolean) | 1 | 0 or 1, default 0 |

---

## 13. admin_company_follow_up

Admin-initiated follow-up requests to a company; company can respond; soft-delete per side.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| company_id | Company user id | Integer | — | NOT NULL, FK to company_users(id) ON DELETE CASCADE |
| message | Admin’s message/question | Text (TEXT) | 65,535 | Nullable |
| status | Whether company has responded | Enum | — | 'pending', 'answered'; default 'pending' |
| company_response | Company’s reply text | Text (TEXT) | 65,535 | Nullable |
| responded_at | When company responded | DateTime | — | Nullable |
| created_at | When the request was created | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| hidden_by_admin | Soft-delete by admin | Tinyint (Boolean) | 1 | 0 or 1, default 0 |
| hidden_by_company | Soft-delete by company | Tinyint (Boolean) | 1 | 0 or 1, default 0 |

---

## 14. resume_templates

Resume layout templates (HTML + CSS) for building and exporting resumes.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| name | Template name | Text (VARCHAR) | 100 | Not null |
| description | Short description | Text (TEXT) | 65,535 | Nullable |
| html_structure | HTML structure with placeholders | Long text | 4,294,967,295 | Not null |
| css_styles | CSS styles | Long text | 4,294,967,295 | Not null |
| preview_image | Preview image path | Text (VARCHAR) | 255 | Nullable |
| is_active | Whether template is available | Boolean | — | Default TRUE |
| created_at | When the template was created | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |

---

## 15. resumes

Resume records (legacy/schema) with JSON sections; one per user/template combination.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| user_id | Employee user id | Integer | — | NOT NULL, FK to employee_users(id) ON DELETE CASCADE |
| template_id | Resume template id | Integer | — | NOT NULL, FK to resume_templates(id) ON DELETE RESTRICT |
| personal_info | Personal info JSON | JSON | — | Nullable |
| work_experience | Work experience JSON | JSON | — | Nullable |
| education | Education JSON | JSON | — | Nullable |
| skills | Skills JSON | JSON | — | Nullable |
| certifications | Certifications JSON | JSON | — | Nullable |
| additional_sections | Other sections JSON | JSON | — | Nullable |
| resume_name | Display name of resume | Text (VARCHAR) | 255 | Not null |
| is_default | Whether this is the user’s default resume | Boolean | — | Default FALSE |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | Last update time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

---

## 16. application_analytics

Aggregated application statistics per employee user.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| user_id | Employee user id | Integer | — | NOT NULL, FK to employee_users(id) ON DELETE CASCADE |
| total_applications | Total applications submitted | Integer | — | Default 0 |
| pending_count | Count in Pending status | Integer | — | Default 0 |
| accepted_count | Count accepted | Integer | — | Default 0 |
| rejected_count | Count rejected | Integer | — | Default 0 |
| response_rate | Percentage of applications with response | Decimal | (5,2) | Default 0.00 |
| avg_response_time_days | Average response time in days | Decimal | (5,2) | Default 0.00 |
| success_rate | Acceptance rate (e.g. percentage) | Decimal | (5,2) | Default 0.00 |
| last_calculated | When stats were last computed | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | Last update time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

---

## 17. application_timeline

Events for an application (submitted, viewed, interview, accepted/rejected).

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| application_id | Jobseeker record id (application) | Integer | — | NOT NULL, FK to jobseeker(id) ON DELETE CASCADE |
| user_id | Employee user id | Integer | — | NOT NULL, FK to employee_users(id) ON DELETE CASCADE |
| event_type | Type of event | Enum | — | 'submitted', 'viewed', 'interview_scheduled', 'interview_completed', 'accepted', 'rejected'; NOT NULL |
| event_date | When the event occurred | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| notes | Optional notes for the event | Text (TEXT) | 65,535 | Nullable |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |

---

## 18. analytics_insights

Stored insights and recommendations per user (e.g. success rate, skill gap).

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| user_id | Employee user id | Integer | — | NOT NULL, FK to employee_users(id) ON DELETE CASCADE |
| insight_type | Category of insight | Enum | — | 'success_rate', 'response_time', 'skill_gap', 'timing', 'profile_completeness'; NOT NULL |
| insight_text | Human-readable insight text | Text (TEXT) | 65,535 | Not null |
| insight_value | Numeric value if applicable | Decimal | (10,2) | Nullable |
| recommendation | Recommendation text | Text (TEXT) | 65,535 | Nullable |
| is_active | Whether to show this insight | Boolean | — | Default TRUE |
| created_at | When the insight was created | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |

---

## 19. monthly_analytics

Per-user, per-month application counts and averages.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| user_id | Employee user id | Integer | — | NOT NULL, FK to employee_users(id) ON DELETE CASCADE |
| year | Year (e.g. 2025) | Integer | — | Not null |
| month | Month (1–12) | Integer | — | Not null |
| applications_submitted | Applications submitted that month | Integer | — | Default 0 |
| applications_accepted | Accepted that month | Integer | — | Default 0 |
| applications_rejected | Rejected that month | Integer | — | Default 0 |
| avg_response_time | Average response time (e.g. days) | Decimal | (5,2) | Default 0.00 |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | Last update time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |
| *(unique)* | One row per user per year-month | — | — | UNIQUE (user_id, year, month) |

---

## 20. announcements

Announcements created by admins; can be draft, published, or archived.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| title | Announcement title | Text (VARCHAR) | 255 | Not null |
| category | Category/category name | Text (VARCHAR) | 100 | Not null |
| description | Announcement body/content | Text (TEXT) | 65,535 | Not null |
| status | Publication status | Enum | — | 'draft', 'published', 'archived'; default 'draft' |
| date_posted | When it was posted (if published) | DateTime | — | DEFAULT CURRENT_TIMESTAMP |
| expiration_date | Optional expiration date | Date | — | Nullable |
| created_by | Admin who created it | Integer | — | NOT NULL, FK to admin_accounts(id) |
| created_at | Record creation time | DateTime | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | Last update time | DateTime | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

---

## 21. announcement_attachments

Files attached to an announcement.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| announcement_id | Parent announcement | Integer | — | NOT NULL, FK to announcements(id) ON DELETE CASCADE |
| file_name | Original file name | Text (VARCHAR) | 255 | Not null |
| file_path | Server path or URL to file | Text (VARCHAR) | 500 | Not null |
| file_type | MIME type or extension | Text (VARCHAR) | 100 | Not null |
| file_size | File size in bytes | Integer | — | Not null |
| uploaded_at | When the file was uploaded | DateTime | — | DEFAULT CURRENT_TIMESTAMP |

---

## 22. announcement_tags

Tags/labels for announcements (e.g. for filtering).

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| announcement_id | Parent announcement | Integer | — | NOT NULL, FK to announcements(id) ON DELETE CASCADE |
| tag_name | Tag label | Text (VARCHAR) | 50 | Not null |

---

## 23. announcement_views

Records each view of an announcement (for analytics).

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| announcement_id | Announcement that was viewed | Integer | — | NOT NULL, FK to announcements(id) ON DELETE CASCADE |
| user_id | Jobseeker user_id (if logged in) | Integer | — | Nullable, FK to jobseeker(user_id) ON DELETE SET NULL |
| viewed_at | When the view occurred | DateTime | — | DEFAULT CURRENT_TIMESTAMP |
| ip_address | Viewer IP (e.g. for anonymous) | Text (VARCHAR) | 45 | Nullable (IPv6-safe) |

---

## 24. announcement_clicks

Records clicks on elements/links within announcements (for analytics).

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| announcement_id | Announcement that was clicked | Integer | — | NOT NULL, FK to announcements(id) ON DELETE CASCADE |
| user_id | Jobseeker user_id (if logged in) | Integer | — | Nullable, FK to jobseeker(user_id) ON DELETE SET NULL |
| clicked_at | When the click occurred | DateTime | — | DEFAULT CURRENT_TIMESTAMP |
| click_type | Type of click (e.g. link, button) | Text (VARCHAR) | 50 | Not null |

---

## 25. resumes_new

New resume builder schema: one header record per resume per user.

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| user_id | Employee user id | Integer | — | NOT NULL, FK to employee_users(id) ON DELETE CASCADE |
| template_id | Resume template id | Integer | — | NOT NULL, FK to resume_templates(id) ON DELETE RESTRICT |
| resume_name | Display name of resume | Text (VARCHAR) | 255 | Not null |
| firstname | First name on resume | Text (VARCHAR) | 100 | Not null |
| lastname | Last name on resume | Text (VARCHAR) | 100 | Not null |
| email | Email on resume | Text (VARCHAR) | 255 | Not null |
| phone | Phone number | Text (VARCHAR) | 50 | Nullable |
| location | Location/address | Text (VARCHAR) | 255 | Nullable |
| linkedin | LinkedIn URL | Text (VARCHAR) | 255 | Nullable |
| summary | Professional summary | Text (TEXT) | 65,535 | Nullable |
| profile_image | Profile image path | Text (VARCHAR) | 255 | Nullable |
| skills | Skills (text or structured) | Text (TEXT) | 65,535 | Nullable |
| languages | Languages | Text (TEXT) | 65,535 | Nullable |
| is_default | Whether this is the user’s default resume | Boolean | — | Default FALSE |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |
| updated_at | Last update time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

---

## 26. resume_work_experience

Work experience entries linked to a resume (resumes_new).

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| resume_id | Parent resume (resumes_new.id) | Integer | — | NOT NULL, FK to resumes_new(id) ON DELETE CASCADE |
| job_title | Job title | Text (VARCHAR) | 255 | Not null |
| company | Company name | Text (VARCHAR) | 255 | Not null |
| start_date | Start date (string for flexibility) | Text (VARCHAR) | 50 | Nullable |
| end_date | End date | Text (VARCHAR) | 50 | Nullable |
| location | Job location | Text (VARCHAR) | 255 | Nullable |
| description | Role description | Text (TEXT) | 65,535 | Nullable |
| sort_order | Display order | Integer | — | Default 0 |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |

---

## 27. resume_education

Education entries linked to a resume (resumes_new).

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| resume_id | Parent resume (resumes_new.id) | Integer | — | NOT NULL, FK to resumes_new(id) ON DELETE CASCADE |
| degree | Degree name | Text (VARCHAR) | 255 | Not null |
| field | Field of study | Text (VARCHAR) | 255 | Not null |
| school | School/institution name | Text (VARCHAR) | 255 | Not null |
| graduation_year | Year of graduation | Text (VARCHAR) | 10 | Nullable |
| gpa | GPA (if applicable) | Text (VARCHAR) | 20 | Nullable |
| sort_order | Display order | Integer | — | Default 0 |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |

---

## 28. resume_certifications

Certification entries linked to a resume (resumes_new).

| Field/Attribute Name | Description | Data Type | Length/Size | Allowed Values |
|----------------------|-------------|-----------|-------------|----------------|
| id | Primary key | Integer | — | AUTO_INCREMENT, NOT NULL |
| resume_id | Parent resume (resumes_new.id) | Integer | — | NOT NULL, FK to resumes_new(id) ON DELETE CASCADE |
| name | Certification name | Text (VARCHAR) | 255 | Not null |
| organization | Issuing organization | Text (VARCHAR) | 255 | Nullable |
| issue_date | Date issued | Text (VARCHAR) | 50 | Nullable |
| expiry_date | Expiration date | Text (VARCHAR) | 50 | Nullable |
| sort_order | Display order | Integer | — | Default 0 |
| created_at | Record creation time | Timestamp | — | DEFAULT CURRENT_TIMESTAMP |

---

*End of Data Dictionary. For schema creation, see `setup_complete_database.php`.*
