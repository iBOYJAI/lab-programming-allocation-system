---

**Lab Programming Allocation System**  
*SRINATH | 23AI152 | B.Sc. CS (AI & DS) | Gobi Arts & Science College, Gobichettypalayam*

---

# CHAPTER 1  
# INTRODUCTION

## 1.1 Introduction to Web Design

Web design is the art and science of planning and creating websites. It encompasses several different aspects, including webpage layout, content production, and graphic design. While the terms "web design" and "web development" are often used interchangeably, web design is technically a subset of the broader category of web development.

The evolution of web design has been rapid. From the text-based pages of the early 1990s to the complex, interactive web applications of today, the field has transformed how we interact with information. Modern web design focuses not just on aesthetics but on user experience (UX), accessibility, and responsiveness across a multitude of devices.

### Key Principles of Modern Web Design:
1.  **Visual Hierarchy:** Using size, color, and texture to guide the user's eye to the most important information.
2.  **Navigation:** Ensuring that users can move around the site with ease and intuition.
3.  **Readability:** Using appropriate typography and spacing to make content easy to digest.
4.  **Responsiveness:** Adapting the layout seamlessly to desktops, tablets, and mobile phones.

In the context of this project, web design plays a crucial role in creating an interface that is distraction-free for students taking exams and information-rich for administrators monitoring them. The "Lab Programming Allocation System" leverages these principles to create a "Premium Monochrome" aesthetic—a high-contrast, professional interface designed for academic seriousness and clarity.

## 1.2 About the Project

The **Lab Programming Allocation System (LPAS)** is a specialized web-based application designed to address a critical gap in the administration of computer programming practical examinations.

### The Core Problem
In a typical computer lab setup, 60 to 70 students sit in close proximity. Administering unique coding problems to each student to preventing copying is a logistical nightmare. Manual distribution of paper chits is inefficient, and standard online exam portals often require active internet connections, which can be unstable in large batches.

### The Solution
LPAS provides a robust, **offline-first intranet solution**. It acts as a local server within the lab.
1.  **Smart Allocation:** When a student logs in, the system checks their specific seat number (system number). It then algorithmically assigns two coding questions from a subject pool. Crucially, it ensures that **no student within a 1-seat radius (left, right, front, back)** receives the same questions.
2.  **Digital Workflow:** Students type their code into an integrated syntax-highlighted editor (CodeMirror) and submit it digitally. There is no need for file storage on local machines or pen drives.
3.  **Centralized Control:** A staff member sits at the server PC and sees a live dashboard. They know exactly who has logged in, who is writing code, and who has submitted.

### Real-World Use Case
This system is specifically tailored for:
*   **University Code Labs:** For internal and practical semester exams.
*   **Technical Interviews:** For conducting walk-in coding tests for recruitment.
*   **Training Centers:** For assessing student progress in programming courses (C, C++, Java, Python, etc.).

## 1.3 Hardware Specification

To deploy the Lab Programming Allocation System effectively, the following hardware infrastructure is required. The system is lightweight, meaning it does not require expensive server-grade hardware for the hosting machine.

### Server (Admin/Staff PC):
*   **Processor:** Intel Core i3 (5th Gen) / AMD Ryzen 3 or higher.
    *   *Justification:* Required to handle concurrent PHP requests from 70+ client machines without latency.
*   **RAM:** 8 GB DDR4 or higher.
    *   *Justification:* To run the Apache Web Server and MySQL Database service smoothly while managing multiple active sessions.
*   **Storage:** 256 GB SSD.
    *   *Justification:* SSD ensures fast database read/write operations, crucial when 70 students save code simultaneously.
*   **Network Interface:** Gigabit Ethernet Port.
    *   *Justification:* To handle high-bandwidth traffic within the LAN.

### Client (Student PCs):
*   **Processor:** Intel Pentium / Core 2 Duo or higher.
    *   *Justification:* The client side is a thin client (web browser only), requiring minimal processing power.
*   **RAM:** 2 GB or higher.
    *   *Justification:* Sufficient to run a modern web browser (Edge/Chrome).
*   **Browser:** Google Chrome, Mozilla Firefox, or Microsoft Edge (Latest Versions).
*   **Display:** 1366x768 resolution minimum.

### Network Infrastructure:
*   **Switch:** 10/100/1000 Mbps Network Switch (24/48 port) connecting all systems.
*   **Cabling:** CAT6 Ethernet cables for stable connectivity.

## 1.4 Software Specification

The robust functionality of LPAS is built upon a stack of open-source, industry-standard software technologies.

### Backend Technology
*   **Language:** **PHP 8.2**
    *   *Reason:* PHP is a server-side scripting language known for its ease of integration with HTML and databases. It powers the core logic, session management, and the allocation algorithm. Version 8.2 offers JIT compilation for improved performance.
*   **Database:** **MySQL 8.0**
    *   *Reason:* A relational database management system (RDBMS) used to store student data, questions, and submission records. It supports ACID properties (via InnoDB engine), ensuring that exam data is never lost, even if a user disconnects.

### Frontend Technology
*   **Markup:** **HTML5**
    *   *Reason:* Provides the semantic structure of the webpages.
*   **Styling:** **CSS3 & Bootstrap 5.3**
    *   *Reason:* CSS3 allows for the custom "Premium Monochrome" design. Bootstrap 5.3 is a responsive framework that ensures the dashboard looks perfect on screens of all sizes.
*   **Interactivity:** **Vanilla JavaScript (ES6+)**
    *   *Reason:* Handles client-side logic like timer countdowns, form validation, and AJAX requests to the server without page reloads.

### Development & Deployment Tools
*   **Server Stack:** **XAMPP** (Cross-Platform, Apache, MySQL, PHP, Perl).
    *   *Reason:* Provides a complete local web server environment necessary to run the application offline. Apache acts as the HTTP server handling requests.
*   **Code Editor:** **Visual Studio Code**.
    *   *Reason:* For writing and debugging the source code, with support for PHP and SQL extensions.
*   **External Libraries:**
    *   **CodeMirror:** An embedded JavaScript code editor utilized to give students a realistic programming environment with line numbering and syntax highlighting.
    *   **Chart.js:** Used in the Admin Dashboard to render visual analytics of pass/fail rates and allocation statuses.
