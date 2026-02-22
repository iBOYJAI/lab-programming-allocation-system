# COMPLETE PROJECT REPORT
# Lab Programming Allocation System (LPAS)

**Student Name:** SRINATH  
**Roll No.:** 23AI152  
**College:** Gobi Arts & Science College, Gobichettypalayam  
**Department:** B.Sc. CS (AI & DS)  
**Guide:** Dr. M. Ramalingam, M.Sc.(CS)., M.C.A., Ph.D. (HOD of the AI & DS)

---

## 1. README — Project Overview (Display)

The following section presents the project overview as documented in the system README.

### 1.1 Core Objectives and Problem Statement

The Lab Programming Allocation System (LPAS) is an **offline-first** web application for managing programming examinations in academic labs. It addresses:

- **Automated Spatial Allocation:** Unique question sets based on physical seating (no two adjacent students get the same questions).
- **Administrative Monitoring:** Real-time dashboard for staff to track progress and submission status.
- **Submission Centralization:** All source code stored in a secure database (no pen drives or manual files).
- **Offline Reliability:** Runs 100% on LAN/Intranet.

### 1.2 Technical Features

| Feature | Description |
|--------|--------------|
| **Smart Allocation Algorithm** | `includes/allocation_algorithm.php` — collision detection for adjacent seats (±1, row ±10). |
| **Security & Lockdown** | Disables copy/paste, right-click, F12; RBAC for Admin, Staff, Student; Bcrypt passwords. |
| **Code Editor** | CodeMirror — syntax highlighting, line numbers, local draft persistence. |

### 1.3 Technology Stack

- **Backend:** PHP 8.2, MySQL 8.0 (PDO, ACID).
- **Frontend:** HTML5, CSS3, Bootstrap 5.3, Vanilla JavaScript (ES6+), Chart.js.

### 1.4 Directory Structure

- `/admin` — Administrative management.
- `/staff` — Invigilation and evaluation.
- `/student` — Exam and code submission.
- `/includes` — Core logic, security, allocation engine.
- `/config` — Database configuration.
- `/database` — SQL schema and migrations.

---

## 2. Screen Formats (Images Display)

Screenshots are stored under `documentation/screenshots/viewport/` (viewport) and `documentation/screenshots/fullheight/` (full page). Below are the key screens with image references.

### 2.1 Common Pages

#### Figure 2.1 — Landing Page
![Landing Page](documentation/screenshots/viewport/01_Common/01_Landing_Page.png)  
*Entry point: system overview and Login.*

#### Figure 2.2 — Login Page
![Login Page](documentation/screenshots/viewport/01_Common/02_Login_Page.png)  
*Unified authentication for Admin, Staff, and Student.*

### 2.2 Admin Module

#### Figure 2.3 — Admin Dashboard
![Admin Dashboard](documentation/screenshots/viewport/02_Admin/03_Admin_Dashboard.png)  
*Statistics cards and charts.*

#### Figure 2.4 — Manage Students
![Manage Students](documentation/screenshots/viewport/02_Admin/04_Manage_Students.png)  
*CRUD and CSV import for students.*

#### Figure 2.5 — Manage Labs
![Manage Labs](documentation/screenshots/viewport/02_Admin/05_Manage_Labs.png)  
*Lab configuration (name, systems, layout).*

#### Figure 2.6 — Manage Questions
![Manage Questions](documentation/screenshots/viewport/02_Admin/06_Manage_Questions.png)  
*Question bank by subject and difficulty.*

#### Figure 2.7 — Manage Staff
![Manage Staff](documentation/screenshots/viewport/02_Admin/07_Manage_Staff.png)  
*Staff accounts and assignments.*

#### Figure 2.8 — Manage Subjects
![Manage Subjects](documentation/screenshots/viewport/02_Admin/08_Manage_Subjects.png)  
*Subject configuration and lab link.*

### 2.3 Staff Module

#### Figure 2.9 — Staff Dashboard
![Staff Dashboard](documentation/screenshots/viewport/03_Staff/09_Staff_Dashboard.png)  
*Assigned sessions and quick actions.*

#### Figure 2.10 — Start Exam
![Start Exam](documentation/screenshots/viewport/03_Staff/10_Start_Exam.png)  
*Create exam session (subject, type, duration).*

#### Figure 2.11 — Monitor Exam
![Monitor Exam](documentation/screenshots/viewport/03_Staff/11_Monitor_Exam.png)  
*Real-time grid: student status (Offline / In Progress / Submitted).*

#### Figure 2.12 — Evaluate Submissions
![Evaluate Submissions](documentation/screenshots/viewport/03_Staff/12_Evaluate_Submissions.png)  
*View question + code; enter marks and remarks.*

#### Figure 2.13 — View Results
![View Results](documentation/screenshots/viewport/03_Staff/13_View_Results.png)  
*Results summary per session.*

### 2.4 Student Module

#### Figure 2.14 — Student Dashboard
![Student Dashboard](documentation/screenshots/viewport/04_Student/14_Student_Dashboard.png)  
*Waiting room and “Join Exam”.*

#### Figure 2.15 — Exam Interface
![Exam Interface](documentation/screenshots/viewport/04_Student/15_Exam_Interface.png)  
*Split view: questions + CodeMirror editor; timer and submit.*

#### Figure 2.16 — Exam History
![Exam History](documentation/screenshots/viewport/04_Student/16_Exam_History.png)  
*Past exams and marks.*

#### Figure 2.17 — Student Profile
![Student Profile](documentation/screenshots/viewport/04_Student/17_Student_Profile.png)  
*Student information.*

### 2.5 Additional Screens

#### Figure 2.18 — View Session Details
![View Session Details](documentation/screenshots/viewport/05_Additional/18_View_Session_Details.png)  
*Session overview and allocations.*

#### Figure 2.19 — View Submission
![View Submission](documentation/screenshots/viewport/05_Additional/19_View_Submission.png)  
*Single submission view.*

#### Figure 2.20 — Reports
![Reports](documentation/screenshots/viewport/05_Additional/20_Reports.png)  
*Analytics and reports.*

---

## 3. Table Specifications (Complete)

Database: **`lab_allocation_system`**. Engine: **InnoDB**. All 14 tables are defined below with every column, type, and constraint.

### 3.1 Table: `users`

**Purpose:** Super-admin login and profile.

| Column     | Data Type        | Null | Default           | Key | Extra          | Description           |
|------------|------------------|------|-------------------|-----|----------------|-----------------------|
| id         | INT              | NO   | —                 | PRI | AUTO_INCREMENT | Unique identifier     |
| username   | VARCHAR(50)      | NO   | —                 | UNI | —             | Admin login           |
| password   | VARCHAR(255)     | NO   | —                 | —   | —             | Bcrypt hash           |
| role       | ENUM('admin')    | YES  | 'admin'           | —   | —             | Role                  |
| gender     | ENUM('Male','Female') | YES | 'Male'       | —   | —             | Gender                |
| avatar_id  | INT              | YES  | 1                 | —   | —             | Avatar ref            |
| created_at | TIMESTAMP        | YES  | CURRENT_TIMESTAMP | —   | —             | Created at            |

**Indexes:** PRIMARY KEY (id), UNIQUE (username).

---

### 3.2 Table: `departments`

**Purpose:** Department master data.

| Column           | Data Type   | Null | Default           | Key | Extra          | Description     |
|------------------|------------|------|-------------------|-----|----------------|-----------------|
| id               | INT        | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID       |
| department_name  | VARCHAR(100)| NO   | —                 | UNI | —             | Name            |
| department_code  | VARCHAR(20)| NO   | —                 | UNI | —             | Code (e.g. CS)  |
| description      | TEXT       | YES  | NULL              | —   | —             | Description     |
| created_at       | TIMESTAMP  | YES  | CURRENT_TIMESTAMP | —   | —             | Created at      |

**Indexes:** PRIMARY KEY (id), UNIQUE (department_name), UNIQUE (department_code).

---

### 3.3 Table: `staff`

**Purpose:** Faculty/staff accounts.

| Column        | Data Type        | Null | Default           | Key | Extra          | Description      |
|---------------|------------------|------|-------------------|-----|----------------|------------------|
| id            | INT              | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID        |
| staff_id      | VARCHAR(20)      | NO   | —                 | UNI | —             | Login ID         |
| name          | VARCHAR(100)     | NO   | —                 | —   | —             | Full name        |
| email         | VARCHAR(100)     | YES  | NULL              | UNI | —             | Email            |
| phone         | VARCHAR(20)      | YES  | NULL              | —   | —             | Phone            |
| gender        | ENUM('Male','Female','Other') | YES | 'Male' | —   | —             | Gender           |
| avatar_id     | INT              | YES  | 1                 | —   | —             | Avatar           |
| status        | ENUM('Active','Inactive') | YES | 'Active'   | —   | —             | Status           |
| password      | VARCHAR(255)     | NO   | —                 | —   | —             | Bcrypt hash      |
| department    | VARCHAR(100)     | YES  | NULL              | —   | —             | Department name  |
| department_id | INT              | YES  | NULL              | MUL | —             | FK → departments |
| created_by    | INT              | YES  | NULL              | MUL | —             | FK → users       |
| created_at    | TIMESTAMP        | YES  | CURRENT_TIMESTAMP | —   | —             | Created at       |

**Indexes:** PRIMARY KEY (id), UNIQUE (staff_id), UNIQUE (email), FK (department_id), FK (created_by).

---

### 3.4 Table: `students`

**Purpose:** Student records and seat mapping.

| Column        | Data Type        | Null | Default           | Key | Extra          | Description      |
|---------------|------------------|------|-------------------|-----|----------------|------------------|
| id            | INT              | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID        |
| roll_number   | VARCHAR(20)      | NO   | —                 | UNI | —             | Roll No (login)  |
| name          | VARCHAR(100)     | NO   | —                 | —   | —             | Full name        |
| email         | VARCHAR(100)     | YES  | NULL              | —   | —             | Email            |
| phone         | VARCHAR(20)      | YES  | NULL              | —   | —             | Phone            |
| gender        | ENUM('Male','Female','Other') | YES | 'Male' | —   | —             | Gender           |
| avatar_id     | INT              | YES  | 1                 | —   | —             | Avatar           |
| department    | VARCHAR(100)     | YES  | NULL              | —   | —             | Department       |
| department_id | INT              | YES  | NULL              | MUL | —             | FK → departments |
| semester      | INT              | YES  | NULL              | —   | —             | Semester         |
| system_no     | INT              | YES  | NULL              | —   | —             | Lab seat no      |
| password      | VARCHAR(255)     | NO   | —                 | —   | —             | Bcrypt hash      |
| created_at    | TIMESTAMP        | YES  | CURRENT_TIMESTAMP | —   | —             | Created at       |

**Indexes:** PRIMARY KEY (id), UNIQUE (roll_number), FK (department_id).

---

### 3.5 Table: `labs`

**Purpose:** Lab infrastructure.

| Column         | Data Type   | Null | Default           | Key | Extra          | Description   |
|----------------|------------|------|-------------------|-----|----------------|---------------|
| id             | INT        | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID     |
| lab_name       | VARCHAR(100)| NO   | —                 | —   | —             | Lab name      |
| total_systems  | INT        | YES  | 70                | —   | —             | No. of PCs    |
| location       | VARCHAR(200)| YES  | NULL              | —   | —             | Location      |
| seating_layout | TEXT       | YES  | NULL              | —   | —             | Layout        |
| created_at     | TIMESTAMP  | YES  | CURRENT_TIMESTAMP | —   | —             | Created at    |

**Indexes:** PRIMARY KEY (id).

---

### 3.6 Table: `subjects`

**Purpose:** Subjects linked to labs and departments.

| Column        | Data Type   | Null | Default           | Key | Extra          | Description   |
|---------------|------------|------|-------------------|-----|----------------|---------------|
| id            | INT        | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID     |
| subject_code  | VARCHAR(20)| NO   | —                 | UNI | —             | Code          |
| subject_name  | VARCHAR(100)| NO   | —                 | —   | —             | Name          |
| language      | VARCHAR(50)| NO   | —                 | —   | —             | e.g. C, Python|
| lab_id        | INT        | YES  | NULL              | MUL | —             | FK → labs     |
| department_id | INT        | YES  | NULL              | MUL | —             | FK → departments |
| created_at    | TIMESTAMP  | YES  | CURRENT_TIMESTAMP | —   | —             | Created at    |

**Indexes:** PRIMARY KEY (id), UNIQUE (subject_code), FK (lab_id), FK (department_id).

---

### 3.7 Table: `student_subjects`

**Purpose:** Student–subject enrollment (many-to-many).

| Column      | Data Type   | Null | Default           | Key | Extra          | Description   |
|-------------|------------|------|-------------------|-----|----------------|---------------|
| id          | INT        | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID     |
| student_id  | INT        | NO   | —                 | MUL | —             | FK → students |
| subject_id  | INT        | NO   | —                 | MUL | —             | FK → subjects |
| enrolled_at | TIMESTAMP  | YES  | CURRENT_TIMESTAMP | —   | —             | Enrolled at   |

**Indexes:** PRIMARY KEY (id), UNIQUE (student_id, subject_id), FK (student_id), FK (subject_id).

---

### 3.8 Table: `questions`

**Purpose:** Question bank per subject.

| Column           | Data Type        | Null | Default           | Key | Extra          | Description   |
|------------------|------------------|------|-------------------|-----|----------------|---------------|
| id               | INT              | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID     |
| subject_id       | INT              | NO   | —                 | MUL | —             | FK → subjects |
| question_title   | VARCHAR(200)     | NO   | —                 | —   | —             | Title         |
| question_text    | TEXT             | NO   | —                 | —   | —             | Description   |
| difficulty       | ENUM('Easy','Medium','Hard') | YES | 'Medium' | —   | —             | Difficulty   |
| pattern_category | VARCHAR(50)      | YES  | NULL              | —   | —             | Category      |
| sample_input     | TEXT             | YES  | NULL              | —   | —             | Sample input  |
| sample_output    | TEXT             | YES  | NULL              | —   | —             | Sample output |
| expected_approach| TEXT             | YES  | NULL              | —   | —             | Approach      |
| created_by       | INT              | YES  | NULL              | —   | —             | Creator       |
| created_at       | TIMESTAMP        | YES  | CURRENT_TIMESTAMP | —   | —             | Created at    |

**Indexes:** PRIMARY KEY (id), FK (subject_id).

---

### 3.9 Table: `staff_assignments`

**Purpose:** Staff–lab–subject assignment (many-to-many).

| Column      | Data Type   | Null | Default           | Key | Extra          | Description   |
|-------------|------------|------|-------------------|-----|----------------|---------------|
| id          | INT        | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID     |
| staff_id    | INT        | NO   | —                 | MUL | —             | FK → staff    |
| lab_id      | INT        | NO   | —                 | MUL | —             | FK → labs     |
| subject_id  | INT        | NO   | —                 | MUL | —             | FK → subjects |
| assigned_at | TIMESTAMP  | YES  | CURRENT_TIMESTAMP | —   | —             | Assigned at   |

**Indexes:** PRIMARY KEY (id), UNIQUE (staff_id, lab_id, subject_id), FK (staff_id), FK (lab_id), FK (subject_id).

---

### 3.10 Table: `exam_sessions`

**Purpose:** Exam session (subject, staff, lab, duration, status).

| Column                | Data Type        | Null | Default           | Key | Extra          | Description   |
|-----------------------|------------------|------|-------------------|-----|----------------|---------------|
| id                    | INT              | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID     |
| session_code          | VARCHAR(50)      | NO   | —                 | UNI | —             | Session code  |
| subject_id            | INT              | NO   | —                 | MUL | —             | FK → subjects |
| staff_id              | INT              | NO   | —                 | MUL | —             | FK → staff    |
| lab_id                | INT              | NO   | —                 | MUL | —             | FK → labs     |
| test_type             | ENUM('Practice','Weekly Test','Monthly Test','Internal Exam') | NO | — | — | —             | Test type     |
| duration_minutes      | INT              | YES  | 0                 | —   | —             | Duration      |
| current_submission_key| VARCHAR(6)       | YES  | '000000'          | —   | —             | Submit key    |
| key_updated_at        | TIMESTAMP        | YES  | NULL              | —   | —             | Key updated   |
| started_at            | TIMESTAMP        | YES  | CURRENT_TIMESTAMP | —   | —             | Started at    |
| ended_at              | TIMESTAMP        | YES  | NULL              | —   | —             | Ended at      |
| status                | ENUM('Active','Completed','Cancelled') | YES | 'Active' | —   | —             | Status        |

**Indexes:** PRIMARY KEY (id), UNIQUE (session_code), FK (subject_id), FK (staff_id), FK (lab_id).

---

### 3.11 Table: `student_allocations`

**Purpose:** Allocated questions per student per session (allocation algorithm output).

| Column         | Data Type        | Null | Default           | Key | Extra          | Description   |
|----------------|------------------|------|-------------------|-----|----------------|---------------|
| id             | INT              | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID     |
| session_id     | INT              | NO   | —                 | MUL | —             | FK → exam_sessions |
| student_id     | INT              | NO   | —                 | MUL | —             | FK → students |
| system_no      | INT              | NO   | —                 | —   | —             | Seat no       |
| question_1_id  | INT              | NO   | —                 | MUL | —             | FK → questions (Q1) |
| question_2_id  | INT              | NO   | —                 | MUL | —             | FK → questions (Q2) |
| allocated_at   | TIMESTAMP        | YES  | CURRENT_TIMESTAMP | —   | —             | Allocated at  |
| status_q1      | ENUM('Not Started','In Progress','Submitted') | YES | 'Not Started' | — | —             | Q1 status     |
| status_q2      | ENUM('Not Started','In Progress','Submitted') | YES | 'Not Started' | — | —             | Q2 status     |
| can_resubmit_q1| TINYINT(1)       | YES  | 0                 | —   | —             | Resubmit Q1   |
| can_resubmit_q2| TINYINT(1)       | YES  | 0                 | —   | —             | Resubmit Q2   |
| is_completed   | TINYINT(1)       | YES  | 0                 | —   | —             | Completed     |
| completed_at   | TIMESTAMP        | YES  | NULL              | —   | —             | Completed at  |
| draft_code_q1  | LONGTEXT         | YES  | NULL              | —   | —             | Draft Q1      |
| draft_code_q2  | LONGTEXT         | YES  | NULL              | —   | —             | Draft Q2      |

**Indexes:** PRIMARY KEY (id), UNIQUE (session_id, student_id), FK (session_id), FK (student_id), FK (question_1_id), FK (question_2_id).

---

### 3.12 Table: `submissions`

**Purpose:** Submitted code and evaluation.

| Column        | Data Type   | Null | Default           | Key | Extra          | Description   |
|---------------|------------|------|-------------------|-----|----------------|---------------|
| id            | INT        | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID     |
| allocation_id | INT        | NO   | —                 | MUL | —             | FK → student_allocations |
| question_id   | INT        | NO   | —                 | MUL | —             | FK → questions |
| submitted_code| TEXT       | NO   | —                 | —   | —             | Source code   |
| submitted_at  | TIMESTAMP  | YES  | CURRENT_TIMESTAMP | —   | —             | Submitted at  |
| marks         | DECIMAL(5,2)| YES | NULL              | —   | —             | Marks         |
| remarks       | TEXT       | YES  | NULL              | —   | —             | Remarks       |
| evaluated_by  | INT        | YES  | NULL              | —   | —             | Evaluator     |
| evaluated_at  | TIMESTAMP  | YES  | NULL              | —   | —             | Evaluated at  |

**Indexes:** PRIMARY KEY (id), FK (allocation_id), FK (question_id).

---

### 3.13 Table: `results`

**Purpose:** Session-level result per student.

| Column       | Data Type   | Null | Default           | Key | Extra          | Description   |
|--------------|------------|------|-------------------|-----|----------------|---------------|
| id           | INT        | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID     |
| session_id   | INT        | NO   | —                 | MUL | —             | FK → exam_sessions |
| student_id   | INT        | NO   | —                 | MUL | —             | FK → students |
| total_marks  | DECIMAL(5,2)| YES  | 0                 | —   | —             | Total marks   |
| remarks      | TEXT       | YES  | NULL              | —   | —             | Remarks       |
| evaluated_by | INT        | YES  | NULL              | —   | —             | Evaluator     |
| evaluated_at | TIMESTAMP  | YES  | NULL              | —   | —             | Evaluated at  |

**Indexes:** PRIMARY KEY (id), FK (session_id), FK (student_id).

---

### 3.14 Table: `activity_logs`

**Purpose:** Audit trail (login, logout, actions).

| Column         | Data Type        | Null | Default           | Key | Extra          | Description   |
|----------------|------------------|------|-------------------|-----|----------------|---------------|
| id             | INT              | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID     |
| user_id        | INT              | YES  | NULL              | —   | —             | User ref      |
| user_role      | ENUM('admin','staff','student') | NO | —                 | —   | —             | Role          |
| activity_type  | VARCHAR(50)      | NO   | —                 | —   | —             | Activity type |
| description    | TEXT             | YES  | NULL              | —   | —             | Description   |
| ip_address     | VARCHAR(45)      | YES  | NULL              | —   | —             | IP            |
| created_at     | TIMESTAMP        | YES  | CURRENT_TIMESTAMP | —   | —             | Created at    |

**Indexes:** PRIMARY KEY (id).

---

### 3.15 Table: `notifications`

**Purpose:** In-app notifications.

| Column     | Data Type        | Null | Default           | Key | Extra          | Description   |
|------------|------------------|------|-------------------|-----|----------------|---------------|
| id         | INT              | NO   | —                 | PRI | AUTO_INCREMENT | Unique ID     |
| user_id    | INT              | YES  | NULL              | MUL | —             | FK → users    |
| user_role  | ENUM('admin','staff','student') | YES | NULL              | —   | —             | Role          |
| title      | VARCHAR(100)     | NO   | —                 | —   | —             | Title         |
| message    | TEXT             | NO   | —                 | —   | —             | Message       |
| link       | VARCHAR(255)     | YES  | NULL              | —   | —             | Link          |
| is_read    | TINYINT(1)       | YES  | 0                 | —   | —             | Read flag     |
| icon       | VARCHAR(50)      | YES  | 'bi-bell'         | —   | —             | Icon          |
| created_at | TIMESTAMP        | YES  | CURRENT_TIMESTAMP | —   | —             | Created at    |

**Indexes:** PRIMARY KEY (id), FK (user_id).

---

## 3.5 MODULE SPECIFICATION

Core modules of the Lab Programming Allocation System are defined below. Each module is described by purpose, inputs, outputs, and main functions.

---

### MODULE 1: Authentication Module

**Location:** `auth.php`, `includes/security.php`, `admin/logout.php`, `staff/logout.php`, `student/logout.php`

**Purpose:**  
Unified identity verification for Admin, Staff, and Student. Ensures only authenticated users access role-specific pages and provides secure logout.

**Inputs:**
- Credential (username / staff_id / roll_number)
- Password (plain text; verified against stored hash)
- CSRF token (from session)

**Outputs:**
- Session variables: `user_id`, `user_role`, `username` or `staff_id`/`roll_number`, role-specific data
- Redirect to role dashboard or error message on failure

**Functions / Behaviour:**
- **Login:** Check `users` → `staff` → `students` in order; verify password with `password_verify()`; regenerate session ID; set session; log activity; redirect.
- **Logout:** Destroy session; redirect to index or auth.
- **CSRF:** `generateCSRFToken()`, `validateCSRFToken()` on every POST.
- **Session check:** `checkSession()` used by `requireRole()` in protected pages.

**Dependencies:** `config/database.php`, `includes/security.php`, `includes/functions.php`.

---

### MODULE 2: Admin Module

**Location:** `admin/` (dashboard, manage_students, manage_labs, manage_questions, manage_staff, manage_subjects, manage_departments, view_*, settings, logs, notifications)

**Purpose:**  
Central administration: labs, departments, staff, students, subjects, question bank, session overview, system settings, and audit logs.

**Sub-modules:**

| Sub-module | File(s) | Purpose | Main operations |
|------------|--------|--------|------------------|
| **Dashboard** | dashboard.php | Overview and charts | Display counts (labs, staff, students, subjects, questions, active sessions); Chart.js analytics. |
| **Manage Students** | manage_students.php | Student CRUD + bulk | Add, edit, delete; CSV import; list with search. |
| **Manage Labs** | manage_labs.php | Lab configuration | Create/edit labs; set name, total_systems, location, seating_layout. |
| **Manage Questions** | manage_questions.php | Question bank | Add/edit questions per subject; title, text, difficulty, sample I/O, expected_approach. |
| **Manage Staff** | manage_staff.php | Staff accounts | Add/edit staff; assign department; link to labs/subjects. |
| **Manage Subjects** | manage_subjects.php | Subject configuration | Create subjects; set code, name, language, lab_id, department_id; enroll students. |
| **Manage Departments** | manage_departments.php | Department master | CRUD for departments (name, code, description). |
| **View Session/Result/Student** | view_session_details.php, view_result_details.php, view_student.php | Inspection | View session allocations, result details, student profile. |
| **Settings & Logs** | settings.php, logs.php | Config and audit | System settings; view activity_logs. |

**Inputs:** Form data (POST/GET), session (admin role).  
**Outputs:** HTML dashboards, success/error messages, redirects.  
**Dependencies:** Database, `includes/security.php`, `includes/functions.php`, Chart.js.

---

### MODULE 3: Staff Module

**Location:** `staff/` (dashboard, start_exam, process_start_exam, monitor_exam, evaluate, view_submission, view_result, reports, manage_*, profile, logout)

**Purpose:**  
Operational control of exams: create sessions, trigger allocation, monitor live status, evaluate submissions, and view results/reports.

**Sub-modules:**

| Sub-module | File(s) | Purpose | Main operations |
|------------|--------|--------|------------------|
| **Dashboard** | dashboard.php | Staff home | List assigned labs/subjects; upcoming and active sessions; quick links. |
| **Start Exam** | start_exam.php, process_start_exam.php | Session creation | Select subject, lab, test type, duration; start session; call allocation for enrolled students. |
| **Monitor Exam** | monitor_exam.php | Live grid | Display students by seat; status (Not Started / In Progress / Submitted); AJAX refresh. |
| **Evaluate** | evaluate.php, view_submission.php | Mark submissions | List submissions; view question + code; enter marks and remarks; save. |
| **View Result / Reports** | view_result.php, reports.php | Results and analytics | View session results; reports and statistics. |
| **Manage Students/Questions/Subjects** | manage_students.php, manage_questions.php, manage_subjects.php | Scoped management | Manage only within assigned labs/subjects. |

**Inputs:** Session (staff_id, assignments), POST (session params, marks, remarks).  
**Outputs:** HTML pages, JSON for monitor refresh, success/error feedback.  
**Dependencies:** `includes/allocation_algorithm.php`, database, security, functions.

---

### MODULE 4: Student Module

**Location:** `student/` (dashboard, exam, subject_details, submit_code, save_draft, finish_exam, history, profile, logout, heartbeat, update_exam_progress)

**Purpose:**  
Secure exam entry, display allocated questions, provide code editor, save drafts, submit code, and view history and profile.

**Sub-modules:**

| Sub-module | File(s) | Purpose | Main operations |
|------------|--------|--------|------------------|
| **Dashboard** | dashboard.php | Waiting room | Show profile; “Join Exam” when active session exists; list enrolled subjects. |
| **Exam Interface** | exam.php | Main exam screen | Load allocation; show two questions + CodeMirror editors; timer; draft save; submit. |
| **Submit / Draft** | submit_code.php, save_draft.php | Persist code | Submit final code (insert submissions, update allocation status); save draft to student_allocations. |
| **Finish Exam** | finish_exam.php | End exam | Set is_completed, completed_at; redirect to history or dashboard. |
| **History** | history.php | Past results | List past sessions; questions attempted; marks and remarks. |
| **Profile** | profile.php | Student info | Display and optionally edit profile. |
| **Heartbeat / Progress** | heartbeat.php, update_exam_progress.php | Live tracking | Update status (In Progress); used by staff monitor. |

**Inputs:** Session (student_id, roll_number), GET/POST (session join, code, draft, finish).  
**Outputs:** HTML (dashboard, exam, history, profile), JSON for AJAX.  
**Dependencies:** CodeMirror, database, security, allocation (read-only for student).

---

### MODULE 5: Allocation Engine (Core Algorithm)

**Location:** `includes/allocation_algorithm.php`

**Purpose:**  
Assign exactly two distinct questions per student per session such that no two adjacent students (by `system_no`) receive the same question set. Ensures fairness and supports invigilation.

**Inputs:**
- `session_id` (exam session)
- `subject_id` (subject whose questions are used)
- List of students with `id`, `roll_number`, `name`, `system_no` (enrolled in subject and eligible)

**Outputs:**
- Rows inserted into `student_allocations`: `session_id`, `student_id`, `system_no`, `question_1_id`, `question_2_id`, status defaults.
- Success/failure and error message (e.g. insufficient questions).

**Algorithm (summary):**
1. Fetch all question IDs for the subject (minimum 15).
2. Sort students by `system_no`.
3. For each student: seed RNG with `system_no` + `session_id`; shuffle question pool; pick two distinct questions; check “neighbours” (system_no ± 1, ± 10); if collision, retry with next pair (up to 50 attempts); then insert allocation.
4. Use transaction for atomicity.

**Dependencies:** `config/database.php`, PDO.

---

### MODULE 6: Security Module

**Location:** `includes/security.php`, used across all role areas

**Purpose:**  
Session safety, CSRF protection, role-based access, and password hashing.

**Functions:**
- **startSecureSession():** Start session with secure params; regenerate ID when appropriate.
- **generateCSRFToken() / validateCSRFToken():** Generate and validate CSRF token for forms.
- **requireRole(['admin'|'staff'|'student']):** Redirect to login if not logged in or wrong role.
- **hashPassword() / verifyPassword():** Bcrypt wrap for storage and verification.
- **logoutUser():** Destroy session and optional activity log.
- **checkSession():** Return whether a valid session exists.

**Inputs:** Session, POST token, role requirement.  
**Outputs:** Redirects, boolean validation results.  
**Dependencies:** None (core).

---

### MODULE 7: Configuration and Database Module

**Location:** `config/database.php`, `includes/functions.php`

**Purpose:**  
Single database connection and common query/helper functions.

**Functions:**
- **getDbConnection()** or equivalent: Return PDO instance for `lab_allocation_system`.
- **dbQuery($sql, $params):** Execute prepared statement and return results.
- **logActivity($user_id, $role, $type, $description):** Insert into `activity_logs`.
- **redirect($url):** Header redirect.
- Other helpers (e.g. get active session for student, get subject list).

**Inputs:** SQL, parameters, connection config.  
**Outputs:** PDO connection, result sets, redirects.  
**Dependencies:** PHP PDO extension, MySQL.

---

### MODULE 8: Presentation and Shared UI

**Location:** `index.php`, `auth.php`, `includes/header.php`, `includes/footer.php`, `includes/sidebar_*.php`, `includes/navbar_*.php`, `includes/topbar_*.php`

**Purpose:**  
Landing page, login form, and consistent layout (header, footer, sidebar, navbar) for Admin, Staff, and Student.

**Behaviour:**
- **index.php:** Show landing; if already logged in, redirect by role.
- **auth.php:** Display login form (credential, password, CSRF); handle POST; redirect on success.
- **Includes:** Role-specific menus and layout; Bootstrap 5; icons; Notion-style styling.

**Inputs:** Session, GET/POST.  
**Outputs:** HTML pages.  
**Dependencies:** Bootstrap, Bootstrap Icons, CSS/JS assets.

---

## Summary

- **Section 1** — README-style overview (objectives, features, stack, directory structure).  
- **Section 2** — Screen formats with embedded screenshot images (viewport) for all 20 screens.  
- **Section 3** — Complete table specifications for all 14 database tables (every column defined).  
- **Section 3.5** — Module specification for 8 core modules (Authentication, Admin, Staff, Student, Allocation Engine, Security, Config/DB, Presentation/Shared UI), each with purpose, inputs, outputs, and behaviour.

This document serves as the **Complete Project Report** for the Lab Programming Allocation System, including images, README content, full table definitions, and detailed module specifications.
