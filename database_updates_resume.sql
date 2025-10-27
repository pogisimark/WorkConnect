-- Database updates for Resume/CV Builder
-- Run this in your MySQL database

-- Create resume_templates table
CREATE TABLE IF NOT EXISTS resume_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    html_structure LONGTEXT NOT NULL,
    css_styles LONGTEXT NOT NULL,
    preview_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create resumes table
CREATE TABLE IF NOT EXISTS resumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    template_id INT NOT NULL,
    personal_info JSON,
    work_experience JSON,
    education JSON,
    skills JSON,
    certifications JSON,
    additional_sections JSON,
    resume_name VARCHAR(255) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES resume_templates(id) ON DELETE RESTRICT
);

-- Insert default resume templates
INSERT INTO resume_templates (name, description, html_structure, css_styles) VALUES
('Modern', 'Clean and contemporary design with bold headers and modern typography', 
'<div class="resume-modern">
    <header class="resume-header">
        <h1 class="name">{{personal_info.firstname}} {{personal_info.lastname}}</h1>
        <div class="contact-info">
            <span>{{personal_info.email}}</span> | 
            <span>{{personal_info.phone}}</span> | 
            <span>{{personal_info.location}}</span>
        </div>
    </header>
    <section class="resume-section">
        <h2>Professional Summary</h2>
        <p>{{personal_info.summary}}</p>
    </section>
    <section class="resume-section">
        <h2>Work Experience</h2>
        {{#work_experience}}
        <div class="experience-item">
            <h3>{{job_title}} - {{company}}</h3>
            <div class="experience-meta">{{start_date}} - {{end_date}} | {{location}}</div>
            <p>{{description}}</p>
        </div>
        {{/work_experience}}
    </section>
    <section class="resume-section">
        <h2>Education</h2>
        {{#education}}
        <div class="education-item">
            <h3>{{degree}} in {{field}}</h3>
            <div class="education-meta">{{school}} | {{graduation_year}}</div>
        </div>
        {{/education}}
    </section>
    <section class="resume-section">
        <h2>Skills</h2>
        <div class="skills-list">{{skills}}</div>
    </section>
</div>',
'body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
.resume-modern { max-width: 800px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
.resume-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2c5aa0; padding-bottom: 20px; }
.name { font-size: 2.5em; color: #2c5aa0; margin: 0; font-weight: bold; }
.contact-info { font-size: 1.1em; color: #666; margin-top: 10px; }
.resume-section { margin-bottom: 25px; }
.resume-section h2 { color: #2c5aa0; font-size: 1.4em; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px; margin-bottom: 15px; }
.experience-item, .education-item { margin-bottom: 20px; }
.experience-item h3, .education-item h3 { color: #333; margin: 0 0 5px 0; font-size: 1.2em; }
.experience-meta, .education-meta { color: #666; font-style: italic; margin-bottom: 10px; }
.skills-list { display: flex; flex-wrap: wrap; gap: 10px; }
.skills-list span { background: #e3f0ff; padding: 5px 12px; border-radius: 15px; font-size: 0.9em; }'),

('Classic', 'Traditional professional layout with clean lines and formal styling',
'<div class="resume-classic">
    <header class="resume-header">
        <h1 class="name">{{personal_info.firstname}} {{personal_info.lastname}}</h1>
        <div class="contact-info">
            <div>{{personal_info.email}}</div>
            <div>{{personal_info.phone}}</div>
            <div>{{personal_info.location}}</div>
        </div>
    </header>
    <section class="resume-section">
        <h2>OBJECTIVE</h2>
        <p>{{personal_info.summary}}</p>
    </section>
    <section class="resume-section">
        <h2>EXPERIENCE</h2>
        {{#work_experience}}
        <div class="experience-item">
            <div class="experience-header">
                <span class="job-title">{{job_title}}</span>
                <span class="company">{{company}}</span>
                <span class="dates">{{start_date}} - {{end_date}}</span>
            </div>
            <div class="location">{{location}}</div>
            <p>{{description}}</p>
        </div>
        {{/work_experience}}
    </section>
    <section class="resume-section">
        <h2>EDUCATION</h2>
        {{#education}}
        <div class="education-item">
            <div class="education-header">
                <span class="degree">{{degree}} in {{field}}</span>
                <span class="school">{{school}}</span>
                <span class="year">{{graduation_year}}</span>
            </div>
        </div>
        {{/education}}
    </section>
    <section class="resume-section">
        <h2>SKILLS</h2>
        <p>{{skills}}</p>
    </section>
</div>',
'body { font-family: "Times New Roman", serif; margin: 0; padding: 20px; background: white; }
.resume-classic { max-width: 800px; margin: 0 auto; padding: 40px; }
.resume-header { margin-bottom: 30px; }
.name { font-size: 2.2em; color: #000; margin: 0; font-weight: bold; text-align: center; }
.contact-info { text-align: center; margin-top: 15px; line-height: 1.6; }
.resume-section { margin-bottom: 25px; }
.resume-section h2 { color: #000; font-size: 1.3em; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; border-bottom: 1px solid #000; padding-bottom: 3px; }
.experience-item, .education-item { margin-bottom: 20px; }
.experience-header, .education-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
.job-title, .degree { font-weight: bold; }
.company, .school { font-style: italic; }
.dates, .year { color: #666; }
.location { color: #666; font-size: 0.9em; margin-bottom: 8px; }'),

('Minimal', 'Simple and clean design focusing on content with minimal styling',
'<div class="resume-minimal">
    <header class="resume-header">
        <h1>{{personal_info.firstname}} {{personal_info.lastname}}</h1>
        <div class="contact">{{personal_info.email}} • {{personal_info.phone}} • {{personal_info.location}}</div>
    </header>
    <section>
        <h2>Summary</h2>
        <p>{{personal_info.summary}}</p>
    </section>
    <section>
        <h2>Experience</h2>
        {{#work_experience}}
        <div class="item">
            <div class="item-header">
                <strong>{{job_title}}</strong> at {{company}}
                <span class="date">{{start_date}} - {{end_date}}</span>
            </div>
            <div class="location">{{location}}</div>
            <p>{{description}}</p>
        </div>
        {{/work_experience}}
    </section>
    <section>
        <h2>Education</h2>
        {{#education}}
        <div class="item">
            <div class="item-header">
                <strong>{{degree}} in {{field}}</strong>
                <span class="date">{{graduation_year}}</span>
            </div>
            <div>{{school}}</div>
        </div>
        {{/education}}
    </section>
    <section>
        <h2>Skills</h2>
        <p>{{skills}}</p>
    </section>
</div>',
'body { font-family: "Helvetica Neue", Arial, sans-serif; margin: 0; padding: 20px; background: white; line-height: 1.6; }
.resume-minimal { max-width: 700px; margin: 0 auto; }
.resume-header { margin-bottom: 40px; }
.resume-header h1 { font-size: 2em; margin: 0; color: #333; }
.contact { color: #666; margin-top: 10px; }
section { margin-bottom: 30px; }
h2 { font-size: 1.2em; color: #333; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee; }
.item { margin-bottom: 20px; }
.item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
.date { color: #666; font-size: 0.9em; }
.location { color: #666; font-size: 0.9em; margin-bottom: 8px; }');
