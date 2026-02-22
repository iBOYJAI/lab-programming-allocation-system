---

**Lab Programming Allocation System**  
*SRINATH | 23AI152 | B.Sc. CS (AI & DS) | Gobi Arts & Science College, Gobichettypalayam*

---

# APPENDIX B  
# DATA DICTIONARY TABLES

This appendix documents the database tables used in the Lab Programming Allocation System (`lab_allocation_system`).

---

## B.1 Core Tables

### Table: `users`
Stores super-admin login credentials.

| Column       | Type         | Constraint   | Description           |
|:-------------|:-------------|:-------------|:----------------------|
| id           | INT          | PK, AI       | Unique identifier     |
| username     | VARCHAR(50)  | UNIQUE, NOT NULL | Admin username    |
| password     | VARCHAR(255) | NOT NULL     | Bcrypt hashed password |
| role         | ENUM         | DEFAULT 'admin' | Role identifier    |
| gender       | ENUM         | Male/Female  | Optional              |
| avatar_id    | INT          | DEFAULT 1    | Profile avatar        |
| created_at   | TIMESTAMP    | DEFAULT      | Record creation time  |

---

### Table: `departments`
Department master data.

| Column           | Type         | Constraint | Description     |
|:-----------------|:-------------|:-----------|:----------------|
| id               | INT          | PK, AI     | Unique ID       |
| department_name  | VARCHAR(100) | UNIQUE, NOT NULL | Name        |
| department_code  | VARCHAR(20)  | UNIQUE, NOT NULL | Code        |
| description      | TEXT         |            | Optional        |
| created_at       | TIMESTAMP    | DEFAULT    | Creation time   |

---

### Table: `staff`
Faculty/staff accounts for conducting exams.

| Column        | Type         | Constraint | Description        |
|:--------------|:-------------|:-----------|:-------------------|
| id            | INT          | PK, AI     | Unique ID          |
| staff_id      | VARCHAR(20)  | UNIQUE, NOT NULL | Staff ID (login) |
| name          | VARCHAR(100) | NOT NULL   | Full name          |
| email         | VARCHAR(100) | UNIQUE     | Email              |
| phone         | VARCHAR(20)  |            | Phone              |
| password      | VARCHAR(255) | NOT NULL   | Hashed password    |
| department    | VARCHAR(100) |            | Department name    |
| department_id | INT          | FK→departments | Department ref  |
| status        | ENUM         | Active/Inactive | Account status  |
| created_at    | TIMESTAMP    | DEFAULT    | Creation time      |

---

### Table: `students`
Student records and seat mapping.

| Column        | Type         | Constraint | Description        |
|:--------------|:-------------|:-----------|:-------------------|
| id            | INT          | PK, AI     | Unique ID          |
| roll_number   | VARCHAR(20)  | UNIQUE, NOT NULL | Roll No (login)  |
| name          | VARCHAR(100) | NOT NULL   | Full name          |
| email         | VARCHAR(100) |            | Email              |
| system_no     | INT          |            | Lab seat/system no |
| department    | VARCHAR(100) |            | Department         |
| department_id | INT          | FK→departments | Department ref  |
| semester      | INT          |            | Semester           |
| password      | VARCHAR(255) | NOT NULL   | Hashed password    |
| created_at    | TIMESTAMP    | DEFAULT    | Creation time      |

---

### Table: `labs`
Physical lab configuration.

| Column         | Type         | Constraint | Description      |
|:---------------|:-------------|:-----------|:-----------------|
| id             | INT          | PK, AI     | Unique ID        |
| lab_name       | VARCHAR(100) | NOT NULL   | Lab name         |
| total_systems  | INT          | DEFAULT 70 | Number of systems |
| location       | VARCHAR(200) |            | Location         |
| seating_layout | TEXT         |            | Layout (rows/cols) |
| created_at     | TIMESTAMP    | DEFAULT    | Creation time    |

---

### Table: `subjects`
Subjects linked to labs.

| Column        | Type         | Constraint | Description   |
|:--------------|:-------------|:-----------|:--------------|
| id            | INT          | PK, AI     | Unique ID     |
| subject_code  | VARCHAR(20)  | UNIQUE, NOT NULL | Code     |
| subject_name  | VARCHAR(100) | NOT NULL   | Name          |
| language      | VARCHAR(50)  | NOT NULL   | e.g. C, Python |
| lab_id        | INT          | FK→labs    | Lab reference  |
| department_id | INT          | FK→departments | Department  |
| created_at    | TIMESTAMP    | DEFAULT    | Creation time  |

---

### Table: `questions`
Question bank per subject.

| Column         | Type         | Constraint | Description     |
|:---------------|:-------------|:-----------|:----------------|
| id             | INT          | PK, AI     | Unique ID       |
| subject_id     | INT          | FK→subjects, NOT NULL | Subject   |
| question_title | VARCHAR(200) | NOT NULL  | Title           |
| question_text  | TEXT         | NOT NULL   | Full description |
| difficulty     | ENUM         | Easy/Medium/Hard | Level   |
| pattern_category | VARCHAR(50) |          | Category        |
| sample_input   | TEXT         |            | Sample input    |
| sample_output  | TEXT         |            | Sample output   |
| created_at     | TIMESTAMP    | DEFAULT    | Creation time   |

---

## B.2 Exam & Allocation Tables

### Table: `exam_sessions`
Active or completed exam sessions.

| Column        | Type         | Constraint | Description     |
|:--------------|:-------------|:-----------|:----------------|
| id            | INT          | PK, AI     | Unique ID       |
| session_code  | VARCHAR(50)  | UNIQUE, NOT NULL | Session code |
| subject_id    | INT          | FK→subjects | Subject        |
| staff_id      | INT          | FK→staff    | Conducting staff |
| lab_id        | INT          | FK→labs     | Lab            |
| test_type     | ENUM         | Practice/Weekly/Monthly/Internal | Type |
| duration_minutes | INT       | DEFAULT 0  | Exam duration   |
| started_at    | TIMESTAMP    | DEFAULT    | Start time      |
| ended_at      | TIMESTAMP    | NULL       | End time        |
| status        | ENUM         | Active/Completed/Cancelled | Status |

---

### Table: `student_allocations`
Stores assigned questions per student per session (allocation algorithm output).

| Column         | Type    | Constraint | Description        |
|:---------------|:--------|:-----------|:-------------------|
| id             | INT     | PK, AI     | Unique ID          |
| session_id     | INT     | FK→exam_sessions | Session        |
| student_id     | INT     | FK→students | Student          |
| system_no      | INT     | NOT NULL   | Seat at allocation |
| question_1_id   | INT     | FK→questions | First question   |
| question_2_id   | INT     | FK→questions | Second question  |
| status_q1      | ENUM    | Not Started/In Progress/Submitted | Q1 status |
| status_q2      | ENUM    | Not Started/In Progress/Submitted | Q2 status |
| draft_code_q1  | LONGTEXT | NULL      | Draft code Q1      |
| draft_code_q2  | LONGTEXT | NULL      | Draft code Q2      |
| is_completed   | BOOLEAN | DEFAULT 0  | Exam completed     |
| allocated_at   | TIMESTAMP | DEFAULT  | Allocation time    |

---

### Table: `submissions`
Submitted code and evaluation.

| Column        | Type      | Constraint | Description   |
|:--------------|:----------|:-----------|:--------------|
| id            | INT       | PK, AI     | Unique ID     |
| allocation_id | INT       | FK→student_allocations | Allocation |
| question_id   | INT       | FK→questions | Question   |
| submitted_code| TEXT      | NOT NULL   | Source code    |
| submitted_at  | TIMESTAMP | DEFAULT    | Submit time    |
| marks         | DECIMAL(5,2) | NULL    | Marks awarded  |
| remarks       | TEXT      |            | Evaluator remarks |
| evaluated_by  | INT       |            | Staff ID       |
| evaluated_at  | TIMESTAMP | NULL       | Evaluation time |

---

### Table: `results`
Session-level result per student.

| Column       | Type        | Constraint | Description   |
|:-------------|:------------|:-----------|:--------------|
| id           | INT         | PK, AI     | Unique ID     |
| session_id   | INT         | FK→exam_sessions | Session  |
| student_id   | INT         | FK→students | Student   |
| total_marks  | DECIMAL(5,2)| DEFAULT 0  | Total marks   |
| remarks      | TEXT        |            | General remarks |
| evaluated_by | INT         |            | Staff ID      |
| evaluated_at  | TIMESTAMP   | NULL       | Evaluation time |

---

### Table: `activity_logs`
Audit trail of user actions.

| Column         | Type         | Constraint | Description  |
|:---------------|:-------------|:-----------|:-------------|
| id             | INT          | PK, AI     | Unique ID    |
| user_id        | INT          |            | User ref     |
| user_role      | ENUM         | admin/staff/student | Role  |
| activity_type  | VARCHAR(50)  | NOT NULL   | Action type  |
| description    | TEXT         |            | Details      |
| ip_address     | VARCHAR(45)  |            | Client IP    |
| created_at     | TIMESTAMP    | DEFAULT    | Time         |

---

### Table: `notifications`
In-app notifications.

| Column     | Type         | Constraint | Description |
|:-----------|:-------------|:-----------|:------------|
| id         | INT          | PK, AI     | Unique ID   |
| user_id    | INT          | FK→users   | Recipient   |
| user_role  | ENUM         | admin/staff/student | Role |
| title      | VARCHAR(100) | NOT NULL  | Title       |
| message    | TEXT         | NOT NULL   | Message     |
| link       | VARCHAR(255) |            | Optional URL |
| is_read    | BOOLEAN      | DEFAULT 0  | Read flag   |
| created_at | TIMESTAMP    | DEFAULT    | Time        |

---

*End of Appendix B*
