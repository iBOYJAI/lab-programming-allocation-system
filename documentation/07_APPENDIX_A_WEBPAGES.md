# APPENDIX A
# WEBPAGES

This appendix provides a detailed functional description of the key webpages that constitute the Lab Programming Allocation System.

## A.1 Common Pages

### 1. Landing Page (`index.php`)
The entry point of the system.
*   **Purpose:** Welcomes users and serves as a navigation hub.
*   **Features:** Displays the system architecture, features, and "Login" button. Use "Notion-style" aesthetics with clean typography.
*   **Logic:** Checks if a session already exists; if so, redirects to the respective dashboard.

### 2. Authentication Gateway (`auth.php`)
A unified login interface for all user roles.
*   **Purpose:** Verifies identity using credential/password pairs.
*   **Features:**
    *   Single form for Admin, Staff, and Student.
    *   "Secure Ingress Mode" design.
    *   CSRF Token validation.
*   **Logic:** Queries `users`, `staff`, and `students` tables sequentially to identify the user type and redirects accordingly.

## A.2 Admin Module Pages (`admin/`)

### 3. Admin Dashboard (`admin/dashboard.php`)
*   **Purpose:** High-level overview of system health.
*   **Features:** Displays cards for Total Labs, Active Staff, Student Count. Includes a "Quick Actions" sidebar.

### 4. Manage Students (`admin/manage_students.php`)
*   **Purpose:** database Interface (CRUD) for student records.
*   **Features:**
    *   **Add Student:** Searchable form to add single students.
    *   **Import CSV:** Bulk upload feature for class lists.
    *   **List View:** Data table with Edit/Delete actions.

### 5. Manage Labs (`admin/manage_labs.php`)
*   **Purpose:** Configure physical lab infrastructure.
*   **Features:** Define Lab Name, Total Systems, and Layout configuration (Rows/Cols) used by the algorithm.

### 6. Question Bank (`admin/manage_questions.php`)
*   **Purpose:** Repository for exam content.
*   **Features:** Rich text inputs for Question Title, Description, Sample Input/Output, and Difficulty Level.

## A.3 Staff Module Pages (`staff/`)

### 7. Staff Dashboard (`staff/dashboard.php`)
*   **Purpose:** Personal workspace for faculty.
*   **Features:** Shows upcoming assigned sessions and active exams.

### 8. Exam Monitor (`staff/monitor_exam.php`)
*   **Purpose:** Real-time invigilation tool.
*   **Features:**
    *   **Grid View:** Represents student seats.
    *   **Status Colors:** Grey (Offline), Blue (Active), Green (Submitted).
    *   **Auto-Refresh:** Uses AJAX to poll status every 5 seconds.

### 9. Evaluation Console (`staff/evaluate.php`)
*   **Purpose:** Interface to mark student submissions.
*   **Features:** Side-by-side view of the Question and the Student's Code. Input field for "Marks" and "Remarks".

## A.4 Student Module Pages (`student/`)

### 10. Start Exam / Waiting Room (`student/dashboard.php`)
*   **Purpose:** Pre-exam holding area.
*   **Features:** Shows student profile and "Join Exam" button. The button is only valid if a staff member has activated a session.

### 11. Exam Interface (`student/exam.php`)
*   **Purpose:** The main testing environment.
*   **Features:**
    *   **Timer:** Countdown based on session duration.
    *   **Question Panel:** Displays the two unique allocated questions.
    *   **Code Editor:** Integrated CodeMirror instance for typing code.
    *   **Submit Button:** Commits code to the database (irreversible).

### 12. Results View (`student/history.php`)
*   **Purpose:** Feedback mechanism.
*   **Features:** Shows past exams, questions attempted, allocated marks, and staff remarks.
