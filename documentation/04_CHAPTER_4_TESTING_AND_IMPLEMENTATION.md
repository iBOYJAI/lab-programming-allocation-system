---

**Lab Programming Allocation System**  
*SRINATH | 23AI152 | B.Sc. CS (AI & DS) | Gobi Arts & Science College, Gobichettypalayam*

---

# CHAPTER 4  
# TESTING AND IMPLEMENTATION

## 4.1 Implementation Tools & Environment

The Lab Programming Allocation System was developed and deployed using industry-standard tools and technologies that ensure reliability, security, and maintainability.

### 4.1.1 Development Environment
*   **Server Stack:** XAMPP 8.2 (Apache 2.4, MySQL 8.0, PHP 8.2)
*   **Code Editor:** Visual Studio Code with PHP, SQL, and JavaScript extensions
*   **Version Control:** Git (for source code management)
*   **Browser Testing:** Google Chrome, Mozilla Firefox, Microsoft Edge (latest versions)

### 4.1.2 Deployment Environment
*   **Operating System:** Windows 10/11 Server Edition
*   **Network:** Local Area Network (LAN) with Gigabit Ethernet
*   **Database:** MySQL 8.0 with InnoDB engine for ACID compliance
*   **Web Server:** Apache HTTP Server 2.4 with mod_rewrite enabled

## 4.2 System Security Policies

The system implements multiple layers of security to protect against unauthorized access and data breaches.

### 4.2.1 Authentication & Authorization
*   **Password Hashing:** All passwords are hashed using bcrypt algorithm (`password_hash()` with `PASSWORD_BCRYPT`)
*   **Session Management:** Secure session handling with `session_regenerate_id()` to prevent session fixation
*   **Role-Based Access Control:** Three-tier access (Admin, Staff, Student) with `requireRole()` function checks
*   **CSRF Protection:** All forms include CSRF tokens to prevent Cross-Site Request Forgery attacks

### 4.2.2 Input Validation & Sanitization
*   **SQL Injection Prevention:** All database queries use PDO prepared statements with parameter binding
*   **XSS Prevention:** Output is escaped using `htmlspecialchars()` before rendering
*   **Input Sanitization:** User inputs are validated and sanitized using PHP filter functions

### 4.2.3 Network Security
*   **Firewall Configuration:** Windows Firewall rules allow only HTTP (Port 80) and MySQL (Port 3306) on private networks
*   **IP Whitelisting:** Optional IP-based access control for admin functions
*   **Activity Logging:** All user actions are logged with IP addresses for audit trails

## 4.3 System Testing

System testing is a critical phase in software development that ensures the entire system functions correctly and meets the specified requirements. It verifies that all integrated components work together as expected and helps identify any defects before deployment. This phase includes various testing methods to ensure the system's stability, performance, and security.

The system is tested by executing different test cases and evaluating the results. If any errors are detected, they are fixed and retested. The main objective of system testing is to validate the system's behaviour under real-world conditions before moving to user acceptance testing.

### 4.3.1 Unit Testing

Unit testing focuses on verifying individual components or modules of the software. Each module is tested separately to ensure it functions correctly before integrating it with other parts of the system.

In this project, unit testing is performed on different modules, such as:

*   **Data Entry Forms:** Tested form validation, required field checks, and data type validation for student registration, question creation, and lab configuration.
*   **Database Connections:** Verified PDO connection establishment, error handling, and connection pooling.
*   **User Authentication:** Tested login functionality, password verification, role identification, and session creation for Admin, Staff, and Student roles.
*   **Allocation Algorithm:** Tested the collision detection logic with various system number configurations to ensure no adjacent students receive identical questions.
*   **Report Generation:** Verified calculation accuracy for marks, statistics aggregation, and data export functionality.

Each module is tested independently to identify and fix errors early in the development process.

**Test Cases Executed:**
*   Login with valid credentials → Success
*   Login with invalid credentials → Error message displayed
*   Student registration with duplicate roll number → Validation error
*   Question allocation algorithm with 70 students → Zero collisions verified
*   Code submission with empty field → Validation prevented

### 4.3.2 Integration Testing

Integration testing ensures that multiple modules of the system work together as intended. It verifies data flow between components and checks if integrated parts interact properly.

In this project, integration testing is conducted on:

*   **Database Interactions:** Verified that student registration correctly creates records in `students` table and links to `departments` table via foreign keys.
*   **User Authentication with Data Access:** Tested that authenticated users can only access data permitted by their role (Admin sees all, Staff sees assigned labs, Students see only their data).
*   **Allocation with Session Management:** Verified that when a staff member starts an exam session, students can join and receive allocated questions correctly.
*   **Code Submission Workflow:** Tested the complete flow from student code entry → draft saving → final submission → staff evaluation → result generation.
*   **Report Generation Based on Stored Information:** Verified that dashboard statistics, exam results, and analytics correctly aggregate data from multiple tables (`exam_sessions`, `student_allocations`, `submissions`, `results`).

This helps detect issues in module communication and ensures seamless operation.

**Integration Test Scenarios:**
*   Staff creates exam session → Students receive notifications → Students join exam → Questions allocated → Students submit code → Staff evaluates → Results generated → Students view marks
*   Admin imports CSV of students → Students appear in database → Students can login → Students enrolled in subjects → Students appear in exam sessions

### 4.3.3 Validation Testing

Validation testing confirms that the system meets user requirements and behaves as expected. It ensures that the software functions correctly based on input conditions and business rules.

For this project, validation testing is used to check:

*   **User Input Fields:** 
    *   Roll number format validation (alphanumeric, max 20 characters)
    *   Email format validation (RFC 5322 compliant)
    *   Phone number validation (numeric, 10 digits)
    *   System number validation (must be positive integer, within lab capacity)
*   **Access Restrictions:**
    *   Students cannot access admin pages (redirected to login)
    *   Staff can only view assigned labs and subjects
    *   Admin has full system access
*   **Data Processing Logic:**
    *   Question allocation ensures minimum 15 questions per subject
    *   Exam duration cannot be negative or zero
    *   Marks are limited to maximum 100 per question
*   **System Responses to Invalid Data:**
    *   Empty form submission shows appropriate error messages
    *   Invalid date formats are rejected
    *   Duplicate entries (roll number, email) are prevented

The system is tested under different scenarios to ensure correctness.

**Validation Test Cases:**
*   Submit student form with empty name → Error: "Name is required"
*   Enter invalid email format → Error: "Invalid email address"
*   Attempt to allocate questions with only 10 questions in pool → Error: "Subject must have at least 15 questions"
*   Student tries to access `/admin/dashboard.php` → Redirected to login page

### 4.3.4 Output Testing

Output testing verifies that the system produces accurate and meaningful results. It checks whether system-generated outputs, such as reports, calculations, and logs, are correct and properly formatted.

In this project, output testing is performed on:

*   **Generated Reports:**
    *   Admin dashboard statistics (Total Labs, Staff, Students) match actual database counts
    *   Exam results report shows correct total marks calculation (sum of Q1 + Q2 marks)
    *   Session details report displays accurate allocation information
*   **Stored Records:**
    *   Student registration creates correct database entries with all fields populated
    *   Code submissions are stored with correct question ID and allocation ID linkage
    *   Timestamps are correctly recorded (created_at, submitted_at, evaluated_at)
*   **Processed Data:**
    *   Allocation algorithm output creates unique question pairs per student
    *   Collision detection correctly identifies and prevents adjacent student conflicts
    *   Status updates (Not Started → In Progress → Submitted) are accurately tracked

This ensures that users receive correct information based on their inputs.

**Output Verification:**
*   Dashboard shows "Total Students: 150" → Verified: `SELECT COUNT(*) FROM students` returns 150
*   Exam result shows "Total Marks: 85.50" → Verified: Q1 marks (42.50) + Q2 marks (43.00) = 85.50
*   Monitor page shows "Active: 45, Submitted: 5" → Verified against `student_allocations` table status counts

### 4.3.5 White Box Testing

White box testing evaluates the internal structure and logic of the system. It ensures that the system's code and algorithms function correctly by examining control structures such as loops, conditions, and data flow.

In this project, white box testing is applied to critical functions such as:

*   **Data Validation Functions:**
    *   `validateEmail()` function tested with various input patterns (valid, invalid, edge cases)
    *   `sanitizeInput()` function verified to remove HTML tags and special characters correctly
*   **Login Authentication Logic:**
    *   Password verification flow: Input → Hash comparison → Session creation → Role assignment
    *   Tested all branches: valid password, invalid password, user not found, account inactive
*   **Allocation Algorithm:**
    *   Collision detection loop tested with different neighbor configurations
    *   Verified maximum attempt limit (50) prevents infinite loops
    *   Tested fallback mechanism when collision cannot be resolved
*   **Automated Calculations:**
    *   Total marks calculation: Verified addition logic and decimal precision handling
    *   Statistics aggregation: Verified SQL query logic and COUNT/SUM functions

It helps verify that internal processes execute correctly.

**White Box Test Coverage:**
*   All conditional branches in `allocation_algorithm.php` tested (if-else, while loops)
*   All exception handlers in database connection code verified
*   All validation functions tested with boundary values (min, max, null, empty)

### 4.3.6 Black Box Testing

Black box testing assesses the system's functionality without analysing the internal code. It focuses on detecting errors related to missing functions, incorrect outputs, interface issues, and database interactions.

For this project, black box testing is conducted on:

*   **User Interfaces:**
    *   Landing page displays correctly with all navigation links functional
    *   Login form accepts credentials and redirects to appropriate dashboard
    *   Admin dashboard shows all expected cards and statistics
    *   Student exam interface displays questions and code editor correctly
*   **Form Validations:**
    *   Required fields show error messages when left empty
    *   Invalid data formats are rejected with user-friendly messages
    *   Submit buttons are disabled until all validations pass
*   **Error Messages:**
    *   "Invalid credentials" displayed for wrong password
    *   "Session expired" shown when user session times out
    *   "No active exam session" displayed when student tries to join non-existent exam
*   **System Responses:**
    *   Page loads within acceptable time (< 2 seconds)
    *   AJAX requests update UI without page refresh
    *   Real-time monitor updates every 5 seconds as expected
*   **Database Interactions:**
    *   Data persists correctly after form submission
    *   Foreign key constraints prevent orphaned records
    *   Cascading deletes work as expected (delete lab → delete subjects → delete questions)

This ensures the software meets functional requirements.

**Black Box Test Scenarios:**
*   User navigates entire system without viewing source code → All features work as expected
*   Submit form with valid data → Success message → Data appears in list view
*   Click "Delete" button → Confirmation dialog → Record removed → List updated
*   Student submits code → Code appears in staff evaluation queue → Staff can evaluate

## 4.4 User Acceptance Testing (UAT)

User Acceptance Testing involves real users (faculty and students) testing the system in a production-like environment to ensure it meets their needs and expectations.

### 4.4.1 UAT Participants
*   **Admin Users:** 2 faculty members (HOD and System Administrator)
*   **Staff Users:** 5 faculty members from different departments
*   **Student Users:** 20 students from various semesters

### 4.4.2 UAT Scenarios Tested

**Scenario 1: Complete Exam Workflow**
1. Admin creates lab, subjects, and questions
2. Admin enrolls students and assigns staff
3. Staff starts exam session
4. Students join exam and receive allocated questions
5. Students write and submit code
6. Staff monitors exam progress in real-time
7. Staff evaluates submissions and assigns marks
8. Students view results

**Result:** ✅ All steps completed successfully. System met all functional requirements.

**Scenario 2: Collision Detection Verification**
*   Tested with 70 students in a 10x7 grid layout
*   Verified no adjacent students (left, right, front, back) received identical questions
*   Checked diagonal neighbors for question uniqueness

**Result:** ✅ Zero collisions detected. Algorithm performed perfectly.

**Scenario 3: Concurrent User Load**
*   Simulated 70 simultaneous student logins
*   Tested real-time monitor updates with all students active
*   Verified database performance under load

**Result:** ✅ System handled concurrent load without performance degradation.

### 4.4.3 UAT Feedback & Improvements

**Positive Feedback:**
*   Interface is intuitive and easy to navigate
*   Real-time monitoring is highly effective for invigilation
*   Question allocation prevents copying effectively
*   Code submission process is smooth and reliable

**Issues Identified & Resolved:**
*   Initial page load time was slow → Optimized database queries and added indexes
*   Code editor sometimes lost focus → Fixed JavaScript event handlers
*   Session timeout too short → Increased session duration to 4 hours

**Final UAT Status:** ✅ **APPROVED** — System ready for production deployment.

---

*End of Chapter 4*
