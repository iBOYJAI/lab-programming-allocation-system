---

**Lab Programming Allocation System**  
*SRINATH | 23AI152 | B.Sc. CS (AI & DS) | Gobi Arts & Science College, Gobichettypalayam*

---

# SYNOPSIS

## 1. Problem Statement

In traditional academic laboratory environments, conducting practical programming examinations presents significant logistical and integrity challenges. The primary issues include:

*   **Malpractice and Cheating:** Students seated adjacent to each other often receive identical or similar questions, facilitating unauthorized collaboration and code sharing.
*   **Manual Distributions:** Physically distributing question papers is time-consuming and prone to human error.
*   **Lack of Real-time Monitoring:** Invigilators struggle to monitor the progress of 60-70 students simultaneously in a large computer lab.
*   **Submission Management:** Collecting source code files manually (via pen drives or network shares) is chaotic and risks data loss or file corruption.
*   **Infrastructure Dependency:** Many modern assessment solutions differ from local infrastructure reality, requiring stable high-speed internet which may not always be available or reliable during critical examination hours.

Existing solutions are often cloud-based, expensive, or lack the specific spatial awareness required to prevent "shoulder-surfing" cheating in physical labs. There is a need for a robust, offline-first system that automates the examination process while ensuring academic integrity through intelligent question distribution.

## 2. Objectives

The "Lab Programming Allocation System" (LPAS) aims to revolutionize the management of practical programming exams through the following objectives:

*   **To Automate Question Allocation:** Implement a smart algorithm that assigns unique questions to students based on their physical seating location, ensuring no two adjacent students (horizontal, vertical, or diagonal) receive the same problem set.
*   **To Ensure Academic Integrity:** secure the examination environment by preventing clipboard operations (copy/paste), disabling browser developer tools, and ensuring a locked-down coding interface.
*   **To Enable Real-time Supervision:** Provide faculty with a live dashboard to monitor the status of every student (Not Started, In Progress, Submitted) in real-time.
*   **To Streamline Evaluation:** Centralize code submissions into a database for easy retrieval and evaluation by staff members, eliminating manual file handling.
*   **To Operate Offline:** Design a system that functions 100% on a local area network (LAN) without requiring internet access, making it suitable for any infrastructure.

## 3. Methodology & Architecture

The project adopts a **client-server architecture** designed for local network deployment.

*   **Development Model:** Agile Waterfall model, allowing for iterative refinement of the allocation algorithm and UI/UX based on faculty feedback.
*   **Core Logic:** The system utilizes a **Spatial Collision Detection Algorithm**. It treats the physical lab layout as a matrix and checks the allocation state of neighboring nodes (students) before assigning questions from a randomized pool.
*   **Frontend-Backend Interaction:** A seamless interaction where the frontend (HTML/JS) polls the backend (PHP/MySQL) for real-time state updates without page reloads, using asynchronous requests.

## 4. Scope

This project covers the entire lifecycle of a practical exam:

*   **Pre-Exam:** Admin manages student data, subject repositories, and question banks. Staff configures exam sessions.
*   **During Exam:** Automated login for students, secure question delivery, integrated code editor interaction, and real-time activity logging.
*   **Post-Exam:** Automated collection of scripts, interface for evaluation/marking by staff, and result generation for students.

**Target Audience:**
*   Colleges and Universities with Computer Science departments.
*   Technical training institutes conducting coding assessments.

## 5. Technologies Used

*   **Language:** PHP 8.2 (Backend Logic)
*   **Database:** MySQL 8.0 (Data Persistence)
*   **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
*   **Framework:** Bootstrap 5.3 (Responsive Design)
*   **Tools:** XAMPP Server, Visual Studio Code
*   **Libraries:** CodeMirror (Syntax Highlighting), Chart.js (Analytics)

## 6. Outcomes

The implementation of this system results in:
1.  **Zero-Collision Rate:** Successful elimination of identical question sets for adjacent students.
2.  **90% Reduction in Admin Time:** Automating the setup and collection process saves significant faculty time.
3.  **Enhanced Security:** A controlled digital environment that minimizes malpractice capabilities.
4.  **Reliability:** A fail-safe offline system that performs consistently regardless of internet connectivity status.
