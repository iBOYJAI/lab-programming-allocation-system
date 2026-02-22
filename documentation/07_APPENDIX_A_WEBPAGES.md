---

**Lab Programming Allocation System**  
*SRINATH | 23AI152 | B.Sc. CS (AI & DS) | Gobi Arts & Science College, Gobichettypalayam*

---

# APPENDIX A  
# WEBPAGES (SCREEN FORMATS)

This appendix provides a detailed functional description of the key webpages that constitute the Lab Programming Allocation System.

---

## 📸 SCREENSHOTS REQUIRED FOR APPENDIX A

The following pages require screenshots to be captured and included in this appendix:

### **A.1 Common Pages (2 screenshots)**
1. ✅ **Landing Page** (`index.php`) — Homepage with system overview
2. ✅ **Login Page** (`auth.php`) — Unified authentication form

### **A.2 Admin Module (6 screenshots)**
3. ✅ **Admin Dashboard** (`admin/dashboard.php`) — Statistics cards and charts
4. ✅ **Manage Students** (`admin/manage_students.php`) — Student list with Add/Import buttons
5. ✅ **Manage Labs** (`admin/manage_labs.php`) — Lab configuration form
6. ✅ **Manage Questions** (`admin/manage_questions.php`) — Question bank interface
7. ✅ **Manage Staff** (`admin/manage_staff.php`) — Staff management page
8. ✅ **Manage Subjects** (`admin/manage_subjects.php`) — Subject configuration

### **A.3 Staff Module (5 screenshots)**
9. ✅ **Staff Dashboard** (`staff/dashboard.php`) — Staff home with assigned sessions
10. ✅ **Start Exam** (`staff/start_exam.php`) — Exam session creation form
11. ✅ **Monitor Exam** (`staff/monitor_exam.php`) — **CRITICAL:** Real-time grid view showing student status
12. ✅ **Evaluate Submissions** (`staff/evaluate.php`) — Code evaluation interface with marks input
13. ✅ **View Results** (`staff/view_result.php`) — Results summary page

### **A.4 Student Module (4 screenshots)**
14. ✅ **Student Dashboard** (`student/dashboard.php`) — Waiting room with "Join Exam" button
15. ✅ **Exam Interface** (`student/exam.php`) — **CRITICAL:** Split-screen with questions and code editor
16. ✅ **Exam History** (`student/history.php`) — Past exam results and marks
17. ✅ **Student Profile** (`student/profile.php`) — Student information page

### **A.5 Additional Important Screens (3 screenshots)**
18. ✅ **View Session Details** (`admin/view_session_details.php`) — Session overview with allocations
19. ✅ **View Submission** (`staff/view_submission.php`) — Individual code submission view
20. ✅ **Reports** (`staff/reports.php`) — Analytics and reports dashboard

---

**Total Screenshots Required: 20**

**Priority Screenshots (Must Include):**
- Landing Page (1)
- Login Page (2)
- Admin Dashboard (3)
- Staff Monitor Exam (11) — **Most Important**
- Student Exam Interface (15) — **Most Important**
- Evaluation Console (12)

---

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

---

## 📋 SCREENSHOT CAPTURE GUIDELINES

### **How to Capture Screenshots:**

1. **Browser:** Use Chrome/Edge/Firefox in full-screen mode (F11)
2. **Resolution:** Capture at 1920x1080 or higher if possible
3. **Format:** Save as PNG or JPG
4. **Naming Convention:** 
   - `01_Landing_Page.png`
   - `02_Login_Page.png`
   - `03_Admin_Dashboard.png`
   - `11_Staff_Monitor_Exam.png`
   - `15_Student_Exam_Interface.png`
   - etc.

### **What to Include in Each Screenshot:**

- **Full page view** (scroll if needed, capture multiple images for long pages)
- **Sample data** populated (not empty forms)
- **Active states** (e.g., exam in progress, students logged in)
- **Key UI elements** visible (navigation, buttons, forms)

### **Special Notes:**

- **Monitor Exam (11):** Capture when multiple students are active (showing different status colors)
- **Exam Interface (15):** Capture with timer running and code editor visible
- **Evaluation (12):** Capture with question and student code side-by-side
- **Dashboard pages:** Ensure statistics/charts are visible

### **Screenshot Organization:**

Create a folder structure:
```
documentation/
  └── screenshots/
      ├── 01_Common/
      │   ├── 01_Landing_Page.png
      │   └── 02_Login_Page.png
      ├── 02_Admin/
      │   ├── 03_Admin_Dashboard.png
      │   ├── 04_Manage_Students.png
      │   └── ...
      ├── 03_Staff/
      │   ├── 09_Staff_Dashboard.png
      │   ├── 11_Monitor_Exam.png
      │   └── ...
      └── 04_Student/
          ├── 14_Student_Dashboard.png
          ├── 15_Exam_Interface.png
          └── ...
```

---

**After capturing screenshots, insert them into this document using:**
```markdown
![Figure A.1: Landing Page](screenshots/01_Common/01_Landing_Page.png)
*Figure A.1: Landing Page - System entry point*
```
