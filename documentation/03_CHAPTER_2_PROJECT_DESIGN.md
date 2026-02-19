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
