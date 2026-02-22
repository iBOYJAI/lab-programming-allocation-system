---

**Lab Programming Allocation System**  
*SRINATH | 23AI152 | B.Sc. CS (AI & DS) | Gobi Arts & Science College, Gobichettypalayam*

---

# CHAPTER 2  
# PROJECT DESIGN

## 2.1 Web Design

The design philosophy of the Lab Programming Allocation System (LPAS) deviates from standard "boring" academic portals. It adopts a **"Premium Monochrome"** design language—a minimalist, high-contrast aesthetic that emphasizes clarity, authority, and focus.

### 2.1.1 UI/UX Principles Applied
1.  **Minimalism (Cognitive Load Reduction):** The interface avoids clutter. During an exam, a student should only see the timer, the question, and the code editor. Extraneous navigation elements are removed to prevent distraction.
2.  **Visual Feedback:** The system provides immediate feedback for all actions.
    *   *Success:* Green toast notifications/borders.
    *   *Error:* Red alerts/shake animations.
    *   *Loading:* Skeleton screens and spinners to indicate background processing.
3.  **Consistency:** A strict design system is followed across all modules (Admin, Staff, Student).
    *   **Typography:** 'Plus Jakarta Sans' for UI elements and 'JetBrains Mono' for code snippets.
    *   **Iconography:** Uniform use of Bootstrap Icons (bi-*) and custom SVG assets (Notion-style icons).
4.  **Responsive Layout:** The interface utilizes the Bootstrap 12-column grid system. While primarily designed for desktop monitors in labs, the staff dashboard is fully responsive for use on tablets or mobile devices during invigilation.

### 2.1.2 Module-Wise Flow

#### A. The Admin Module
The central command center.
*   **Dashboard:** Displays high-level cards (Total Labs, Active Staff, Student Count).
*   **Data Management:** Interfaces to Import/Export CSV data for bulk student registration.
*   **Question Bank:** A rich-text interface to add questions, categorized by subject and difficulty.

#### B. The Staff Module
Designed for operational control.
*   **Session Manager:** Staff can "Start" a session for a specific subject. This triggers the **Allocation Component**.
*   **Live Monitor:** A grid view representing the physical lab. As students log in, their specific "card" lights up. When they submit, it turns green. This allows invigilators to spot absentees instantly.

#### C. The Student Module
Designed for security and focus.
*   **Secure Ingress:** Login requires Roll Number + Password.
*   **Exam Interface:** A split-screen layout.
    *   *Left Panel:* Question Title, Description, Sample Input/Output.
    *   *Right Panel:* CodeMirror Editor (Syntax highlighted) with "Copy/Paste Disabled".
*   **Anti-Cheating UI:** The interface disables the right-click context menu and intercepts specific key combinations (Ctrl+C, Ctrl+V) to force students to write code manually.

## 2.2 Database Design

The backbone of LPAS is a highly normalized MySQL database (`lab_allocation_system`). The schema is designed to handle high concurrency, ensuring that 70+ write operations (submissions) occurring simultaneously are handled without data loss.

### 2.2.1 ER Diagram Explanation
The Entity-Relationship model centers around the **User** (Student/Staff) and the **Session** (Exam).
*   **Users** are the central entities. `students` and `staff` tables separate roles but share authentication logic.
*   **Labs** contain **Subjects**, and Subjects contain **Questions**.
*   An **Exam Session** links a Staff member, a Lab, and a Subject.
*   **Student Allocations** is the junction table that links a Student to a Session and assigns specific Questions.
*   **Submissions** logic links back to the Allocation, storing the actual code.

### 2.2.2 Data Dictionary (Table Structure)

The database consists of the following key tables:

#### 1. Table: `users`
Stores login credentials for the super-admin.
| Field | Type | Constraint | Description |
|:---|:---|:---|:---|
| `id` | INT | PK, AI | Unique ID |
| `username` | VARCHAR(50) | UNIQUE | Admin username |
| `password` | VARCHAR(255) | NOT NULL | Bcrypt hashed password |
| `role` | ENUM | 'admin' | Role identifier |

#### 2. Table: `students`
Stores comprehensive student details.
| Field | Type | Constraint | Description |
|:---|:---|:---|:---|
| `id` | INT | PK, AI | Unique Student ID |
| `roll_number` | VARCHAR(20) | UNIQUE | Academic Roll No (Login ID) |
| `name` | VARCHAR(100) | NOT NULL | Student Name |
| `system_no` | INT | Check (>0) | assigned computer seat number |
| `department` | VARCHAR(100) | | Department Name |

#### 3. Table: `questions`
The repository of all programming problems.
| Field | Type | Constraint | Description |
|:---|:---|:---|:---|
| `id` | INT | PK, AI | Question ID |
| `subject_id` | INT | FK | Link to `subjects` table |
| `question_title` | VARCHAR(200)| NOT NULL | Title of the problem |
| `question_text` | TEXT | NOT NULL | Full problem description |
| `difficulty` | ENUM | 'Easy','Medium','Hard' | Complexity level |

#### 4. Table: `student_allocations` (CRITICAL)
This table stores the result of the allocation algorithm. It ensures persistence; if a student refreshes the browser, they get the exact same questions back.
| Field | Type | Constraint | Description |
|:---|:---|:---|:---|
| `id` | INT | PK, AI | Allocation ID |
| `session_id` | INT | FK | Link to active exam session |
| `student_id` | INT | FK | Link to student |
| `system_no` | INT | NOT NULL | Seat number at time of allocation |
| `question_1_id` | INT | FK | First assigned question |
| `question_2_id` | INT | FK | Second assigned question |
| `status_q1` | ENUM | 'Not Started',... | Progress tracking |

#### 5. Table: `results`
Stores the final evaluation.
| Field | Type | Constraint | Description |
|:---|:---|:---|:---|
| `id` | INT | PK, AI | Result ID |
| `session_id` | INT | FK | Exam Session |
| `student_id` | INT | FK | Student |
| `total_marks` | DECIMAL(5,2)| | Final calculated marks |
| `evaluated_at` | TIMESTAMP | | Time of marking |

### 2.2.3 The "Smart Allocation" Algorithm Logic

The allocated logic resides in `includes/allocation_algorithm.php`. It follows a strict process:

1.  **Fetch & Shuffle:** It fetches all available questions (minimum 15 required) for the selected subject and shuffles them using a seed based on the `system_no` + `session_id`.
2.  **Offset Calculation:** It calculates a starting index offset based on the student's position to vary the starting point of selection.
3.  **Candidate Selection:** It picks two candidate questions.
4.  **Collision Detection (The 'Smart' Part):**
    *   It identifies the "neighboring" system numbers: `Left (n-1)`, `Right (n+1)`, `Front (n-10)`, `Back (n+10)`.
    *   It queries the memory/database to see what questions those neighbors have *already* been assigned.
    *   If a collision (same question) is found, it discards the candidate questions and picks the next pair in the shuffled list.
    *   It retries this up to 50 times to find a unique set.
5.  **Atomic Commit:** Once a valid pair is found, it writes to the database in a single transaction to prevent race conditions.

## 2.4 File Specifications — Table Design

This section provides complete specifications for all database tables in the Lab Programming Allocation System, including all columns, data types, constraints, and relationships.

### 2.4.1 Core Administration Tables

#### Table: `users`
**Purpose:** Stores super-admin login credentials and profile information.

| Column     | Data Type        | Constraints                    | Description                    |
|:-----------|:-----------------|:-------------------------------|:-------------------------------|
| id         | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique admin identifier        |
| username   | VARCHAR(50)      | UNIQUE, NOT NULL               | Admin login username           |
| password   | VARCHAR(255)     | NOT NULL                       | Bcrypt hashed password         |
| role       | ENUM('admin')    | DEFAULT 'admin'                | User role identifier           |
| gender     | ENUM('Male', 'Female') | DEFAULT 'Male'            | Gender                         |
| avatar_id  | INT              | DEFAULT 1                      | Profile avatar identifier      |
| created_at | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Account creation timestamp     |

**Indexes:** PRIMARY KEY (id), UNIQUE (username)

---

#### Table: `departments`
**Purpose:** Master data for academic departments.

| Column           | Data Type        | Constraints                    | Description                    |
|:-----------------|:-----------------|:-------------------------------|:-------------------------------|
| id               | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique department identifier   |
| department_name  | VARCHAR(100)     | UNIQUE, NOT NULL               | Full department name           |
| department_code  | VARCHAR(20)      | UNIQUE, NOT NULL               | Department code (e.g., 'CS', 'AIDS') |
| description      | TEXT             |                                | Optional description           |
| created_at       | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Record creation timestamp      |

**Indexes:** PRIMARY KEY (id), UNIQUE (department_name), UNIQUE (department_code)

---

#### Table: `staff`
**Purpose:** Faculty/staff member accounts for conducting exams.

| Column        | Data Type        | Constraints                    | Description                    |
|:--------------|:-----------------|:-------------------------------|:-------------------------------|
| id            | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique staff identifier        |
| staff_id      | VARCHAR(20)      | UNIQUE, NOT NULL               | Staff ID (login credential)   |
| name          | VARCHAR(100)     | NOT NULL                       | Full name                      |
| email         | VARCHAR(100)     | UNIQUE                         | Email address                  |
| phone         | VARCHAR(20)      |                                | Phone number                   |
| gender        | ENUM('Male', 'Female', 'Other') | DEFAULT 'Male'        | Gender                         |
| avatar_id     | INT              | DEFAULT 1                      | Profile avatar identifier      |
| status        | ENUM('Active', 'Inactive') | DEFAULT 'Active'        | Account status                 |
| password      | VARCHAR(255)     | NOT NULL                       | Bcrypt hashed password         |
| department    | VARCHAR(100)     |                                | Department name (legacy)       |
| department_id | INT              | FOREIGN KEY → departments(id) ON DELETE SET NULL | Department reference |
| created_by    | INT              | FOREIGN KEY → users(id) ON DELETE SET NULL | Creator admin ID |
| created_at    | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Account creation timestamp     |

**Indexes:** PRIMARY KEY (id), UNIQUE (staff_id), UNIQUE (email), FOREIGN KEY (department_id), FOREIGN KEY (created_by)

---

#### Table: `students`
**Purpose:** Student records with seat mapping for allocation algorithm.

| Column        | Data Type        | Constraints                    | Description                    |
|:--------------|:-----------------|:-------------------------------|:-------------------------------|
| id            | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique student identifier      |
| roll_number   | VARCHAR(20)      | UNIQUE, NOT NULL               | Academic roll number (login)   |
| name          | VARCHAR(100)     | NOT NULL                       | Full name                      |
| email         | VARCHAR(100)     |                                | Email address                  |
| phone         | VARCHAR(20)      |                                | Phone number                   |
| gender        | ENUM('Male', 'Female', 'Other') | DEFAULT 'Male'        | Gender                         |
| avatar_id     | INT              | DEFAULT 1                      | Profile avatar identifier      |
| department    | VARCHAR(100)     |                                | Department name                |
| department_id | INT              | FOREIGN KEY → departments(id) ON DELETE SET NULL | Department reference |
| semester      | INT              |                                | Current semester               |
| system_no     | INT              |                                | Lab seat/system number (critical for allocation) |
| password      | VARCHAR(255)     | NOT NULL                       | Bcrypt hashed password         |
| created_at    | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Account creation timestamp     |

**Indexes:** PRIMARY KEY (id), UNIQUE (roll_number), FOREIGN KEY (department_id)

---

### 2.4.2 Lab & Subject Management Tables

#### Table: `labs`
**Purpose:** Physical lab infrastructure configuration.

| Column         | Data Type        | Constraints                    | Description                    |
|:---------------|:-----------------|:-------------------------------|:-------------------------------|
| id             | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique lab identifier          |
| lab_name       | VARCHAR(100)     | NOT NULL                       | Lab name                       |
| total_systems  | INT              | DEFAULT 70                     | Total number of computer systems |
| location       | VARCHAR(200)     |                                | Physical location              |
| seating_layout | TEXT             |                                | Layout description (e.g., '10x10 Grid') |
| created_at     | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Record creation timestamp      |

**Indexes:** PRIMARY KEY (id)

---

#### Table: `subjects`
**Purpose:** Subjects linked to labs and departments.

| Column        | Data Type        | Constraints                    | Description                    |
|:--------------|:-----------------|:-------------------------------|:-------------------------------|
| id            | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique subject identifier      |
| subject_code  | VARCHAR(20)      | UNIQUE, NOT NULL               | Subject code (e.g., 'CS101')  |
| subject_name  | VARCHAR(100)     | NOT NULL                       | Full subject name              |
| language      | VARCHAR(50)      | NOT NULL                       | Programming language (C, Python, Java, etc.) |
| lab_id        | INT              | FOREIGN KEY → labs(id) ON DELETE CASCADE | Lab reference |
| department_id | INT              | FOREIGN KEY → departments(id) ON DELETE SET NULL | Department reference |
| created_at    | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Record creation timestamp      |

**Indexes:** PRIMARY KEY (id), UNIQUE (subject_code), FOREIGN KEY (lab_id), FOREIGN KEY (department_id)

---

#### Table: `student_subjects`
**Purpose:** Many-to-many relationship between students and subjects (enrollment).

| Column      | Data Type        | Constraints                    | Description                    |
|:------------|:-----------------|:-------------------------------|:-------------------------------|
| id          | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique enrollment identifier   |
| student_id  | INT              | FOREIGN KEY → students(id) ON DELETE CASCADE, NOT NULL | Student reference |
| subject_id  | INT              | FOREIGN KEY → subjects(id) ON DELETE CASCADE, NOT NULL | Subject reference |
| enrolled_at | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Enrollment timestamp           |

**Indexes:** PRIMARY KEY (id), UNIQUE KEY unique_enrollment (student_id, subject_id), FOREIGN KEY (student_id), FOREIGN KEY (subject_id)

---

#### Table: `questions`
**Purpose:** Question bank repository for all programming problems.

| Column          | Data Type        | Constraints                    | Description                    |
|:----------------|:-----------------|:-------------------------------|:-------------------------------|
| id              | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique question identifier     |
| subject_id      | INT              | FOREIGN KEY → subjects(id) ON DELETE CASCADE, NOT NULL | Subject reference |
| question_title  | VARCHAR(200)     | NOT NULL                       | Question title/heading          |
| question_text   | TEXT             | NOT NULL                       | Full problem description        |
| difficulty      | ENUM('Easy', 'Medium', 'Hard') | DEFAULT 'Medium'      | Difficulty level               |
| pattern_category| VARCHAR(50)      |                                | Question category/pattern       |
| sample_input    | TEXT             |                                | Sample input examples           |
| sample_output   | TEXT             |                                | Expected output examples        |
| expected_approach | TEXT          |                                | Suggested solution approach     |
| created_by      | INT              |                                | Creator staff/admin ID         |
| created_at      | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Record creation timestamp       |

**Indexes:** PRIMARY KEY (id), FOREIGN KEY (subject_id)

---

#### Table: `staff_assignments`
**Purpose:** Many-to-many relationship assigning staff to labs and subjects.

| Column      | Data Type        | Constraints                    | Description                    |
|:------------|:-----------------|:-------------------------------|:-------------------------------|
| id          | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique assignment identifier   |
| staff_id    | INT              | FOREIGN KEY → staff(id) ON DELETE CASCADE, NOT NULL | Staff reference |
| lab_id      | INT              | FOREIGN KEY → labs(id) ON DELETE CASCADE, NOT NULL | Lab reference |
| subject_id  | INT              | FOREIGN KEY → subjects(id) ON DELETE CASCADE, NOT NULL | Subject reference |
| assigned_at | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Assignment timestamp           |

**Indexes:** PRIMARY KEY (id), UNIQUE KEY unique_assignment (staff_id, lab_id, subject_id), FOREIGN KEY (staff_id), FOREIGN KEY (lab_id), FOREIGN KEY (subject_id)

---

### 2.4.3 Exam & Allocation Tables

#### Table: `exam_sessions`
**Purpose:** Active or completed exam sessions.

| Column              | Data Type        | Constraints                    | Description                    |
|:--------------------|:-----------------|:-------------------------------|:-------------------------------|
| id                  | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique session identifier      |
| session_code        | VARCHAR(50)      | UNIQUE, NOT NULL               | Human-readable session code   |
| subject_id          | INT              | FOREIGN KEY → subjects(id) ON DELETE CASCADE, NOT NULL | Subject reference |
| staff_id            | INT              | FOREIGN KEY → staff(id) ON DELETE CASCADE, NOT NULL | Conducting staff |
| lab_id              | INT              | FOREIGN KEY → labs(id) ON DELETE CASCADE, NOT NULL | Lab reference |
| test_type           | ENUM('Practice', 'Weekly Test', 'Monthly Test', 'Internal Exam') | NOT NULL | Exam type |
| duration_minutes    | INT              | DEFAULT 0                      | Exam duration in minutes       |
| current_submission_key | VARCHAR(6)    | DEFAULT '000000'               | Current submission key for security |
| key_updated_at      | TIMESTAMP        | NULL                           | Last key update timestamp      |
| started_at          | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Session start time             |
| ended_at            | TIMESTAMP        | NULL                           | Session end time               |
| status              | ENUM('Active', 'Completed', 'Cancelled') | DEFAULT 'Active' | Session status |

**Indexes:** PRIMARY KEY (id), UNIQUE (session_code), FOREIGN KEY (subject_id), FOREIGN KEY (staff_id), FOREIGN KEY (lab_id)

---

#### Table: `student_allocations`
**Purpose:** Stores allocated questions per student per session (output of allocation algorithm).

| Column         | Data Type        | Constraints                    | Description                    |
|:---------------|:-----------------|:-------------------------------|:-------------------------------|
| id             | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique allocation identifier  |
| session_id     | INT              | FOREIGN KEY → exam_sessions(id) ON DELETE CASCADE, NOT NULL | Session reference |
| student_id     | INT              | FOREIGN KEY → students(id) ON DELETE CASCADE, NOT NULL | Student reference |
| system_no      | INT              | NOT NULL                       | Seat number at allocation time |
| question_1_id  | INT              | FOREIGN KEY → questions(id) ON DELETE CASCADE, NOT NULL | First allocated question |
| question_2_id  | INT              | FOREIGN KEY → questions(id) ON DELETE CASCADE, NOT NULL | Second allocated question |
| allocated_at   | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Allocation timestamp          |
| status_q1      | ENUM('Not Started', 'In Progress', 'Submitted') | DEFAULT 'Not Started' | Q1 progress status |
| status_q2      | ENUM('Not Started', 'In Progress', 'Submitted') | DEFAULT 'Not Started' | Q2 progress status |
| can_resubmit_q1 | BOOLEAN          | DEFAULT 0                      | Allow resubmission for Q1      |
| can_resubmit_q2 | BOOLEAN          | DEFAULT 0                      | Allow resubmission for Q2      |
| is_completed   | BOOLEAN          | DEFAULT 0                      | Exam completion flag           |
| completed_at   | TIMESTAMP        | NULL                           | Completion timestamp           |
| draft_code_q1  | LONGTEXT         | NULL                           | Auto-saved draft code for Q1    |
| draft_code_q2  | LONGTEXT         | NULL                           | Auto-saved draft code for Q2   |

**Indexes:** PRIMARY KEY (id), UNIQUE KEY unique_allocation (session_id, student_id), FOREIGN KEY (session_id), FOREIGN KEY (student_id), FOREIGN KEY (question_1_id), FOREIGN KEY (question_2_id)

---

#### Table: `submissions`
**Purpose:** Submitted code and evaluation results.

| Column        | Data Type        | Constraints                    | Description                    |
|:--------------|:-----------------|:-------------------------------|:-------------------------------|
| id            | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique submission identifier   |
| allocation_id | INT              | FOREIGN KEY → student_allocations(id) ON DELETE CASCADE, NOT NULL | Allocation reference |
| question_id   | INT              | FOREIGN KEY → questions(id) ON DELETE CASCADE, NOT NULL | Question reference |
| submitted_code | TEXT             | NOT NULL                       | Source code submitted          |
| submitted_at  | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Submission timestamp           |
| marks         | DECIMAL(5,2)     | NULL                           | Marks awarded (0.00 to 100.00) |
| remarks       | TEXT             |                                | Evaluator comments/remarks     |
| evaluated_by  | INT              |                                | Staff member who evaluated     |
| evaluated_at  | TIMESTAMP        | NULL                           | Evaluation timestamp           |

**Indexes:** PRIMARY KEY (id), FOREIGN KEY (allocation_id), FOREIGN KEY (question_id)

---

#### Table: `results`
**Purpose:** Session-level aggregated results per student.

| Column       | Data Type        | Constraints                    | Description                    |
|:-------------|:-----------------|:-------------------------------|:-------------------------------|
| id           | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique result identifier       |
| session_id   | INT              | FOREIGN KEY → exam_sessions(id) ON DELETE CASCADE, NOT NULL | Session reference |
| student_id   | INT              | FOREIGN KEY → students(id) ON DELETE CASCADE, NOT NULL | Student reference |
| total_marks  | DECIMAL(5,2)     | DEFAULT 0                      | Total marks (sum of Q1 + Q2)   |
| remarks      | TEXT             |                                | General remarks/feedback       |
| evaluated_by | INT              |                                | Staff member who finalized result |
| evaluated_at | TIMESTAMP        | NULL                           | Result finalization timestamp  |

**Indexes:** PRIMARY KEY (id), FOREIGN KEY (session_id), FOREIGN KEY (student_id)

---

### 2.4.4 System & Audit Tables

#### Table: `activity_logs`
**Purpose:** Audit trail of all user actions and system events.

| Column         | Data Type        | Constraints                    | Description                    |
|:---------------|:-----------------|:-------------------------------|:-------------------------------|
| id             | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique log identifier          |
| user_id        | INT              |                                | User identifier (if applicable) |
| user_role      | ENUM('admin', 'staff', 'student') | NOT NULL          | User role                      |
| activity_type  | VARCHAR(50)      | NOT NULL                       | Type of activity (login, logout, submit, etc.) |
| description    | TEXT             |                                | Detailed description           |
| ip_address     | VARCHAR(45)      |                                | Client IP address              |
| created_at     | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Log timestamp                  |

**Indexes:** PRIMARY KEY (id)

---

#### Table: `notifications`
**Purpose:** In-app notification system for users.

| Column     | Data Type        | Constraints                    | Description                    |
|:-----------|:-----------------|:-------------------------------|:-------------------------------|
| id         | INT              | PRIMARY KEY, AUTO_INCREMENT    | Unique notification identifier |
| user_id    | INT              | FOREIGN KEY → users(id) ON DELETE CASCADE | Recipient user ID |
| user_role  | ENUM('admin', 'staff', 'student') |                        | Recipient role                 |
| title      | VARCHAR(100)     | NOT NULL                       | Notification title             |
| message    | TEXT             | NOT NULL                       | Notification message           |
| link       | VARCHAR(255)     |                                | Optional URL link              |
| is_read    | BOOLEAN          | DEFAULT 0                      | Read status flag               |
| icon       | VARCHAR(50)      | DEFAULT 'bi-bell'              | Icon identifier                |
| created_at | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP      | Notification timestamp         |

**Indexes:** PRIMARY KEY (id), FOREIGN KEY (user_id)

---

### 2.4.5 Database Relationships Summary

**Primary Relationships:**
- `departments` → `staff` (one-to-many)
- `departments` → `students` (one-to-many)
- `labs` → `subjects` (one-to-many)
- `subjects` → `questions` (one-to-many)
- `subjects` ↔ `students` (many-to-many via `student_subjects`)
- `staff` ↔ `labs` ↔ `subjects` (many-to-many via `staff_assignments`)
- `exam_sessions` → `student_allocations` (one-to-many)
- `student_allocations` → `submissions` (one-to-many)
- `exam_sessions` → `results` (one-to-many)

**Total Tables:** 14  
**Total Foreign Key Relationships:** 18  
**Database Engine:** InnoDB (for ACID compliance and foreign key support)
