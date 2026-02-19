# CHAPTER 3
# WEB HOSTING PROCEDURE

Unlike traditional public websites hosted on cloud providers (like AWS, GoDaddy, or Heroku), the Lab Programming Allocation System (LPAS) is designed to be hosted **locally** on an intranet. This ensures data privacy, zero latency, and independence from external internet connectivity.

The hosting procedure involves transforming a standard high-performance PC into a **Web Server** using the XAMPP stack.

## 3.1 Domain and Hosting Environment

### 3.1.1 The Environment: XAMPP
We utilize **XAMPP** (Apache, MySQL, PHP, Perl), a free and open-source cross-platform web server solution stack package.
*   **Apache:** Acts as the web server application, processing HTTP requests from student computers and serving the HTML/PHP files.
*   **MySQL (MariaDB):** Acts as the database server, storing all the relational data.
*   **PHP:** The processing engine that executes the backend code.

### 3.1.2 Network Configuration (The "Domain")
Since we are on a Local Area Network (LAN), we do not use a "www.example.com" domain. Instead, the server is accessed via its **IP Address**.
*   **Static IP Assignment:** The Admin PC (Server) is assigned a static IP address (e.g., `192.168.1.100`) to ensure the address doesn't change if the router restarts.
*   **Access URL:** Student computers access the portal via: `http://192.168.1.100/LAB PROGRAMMING ALLOCATION SYSTEM/`

## 3.2 Deployment Steps

The deployment process is automated using Batch scripts (`.bat`) included in the project root, simplifying the complex server startup for non-technical staff.

### Step 1: Server Preparation
1.  Install XAMPP on the designated Admin PC (Install path: `C:\xampp\`).
2.  Copy the project folder `LAB PROGRAMMING ALLOCATION SYSTEM` into the web root directory: `C:\xampp\htdocs\`.
    *   *Resulting Path:* `C:\xampp\htdocs\LAB PROGRAMMING ALLOCATION SYSTEM\`

### Step 2: Database Initialization
1.  Start the **Apache** and **MySQL** modules from the XAMPP Control Panel.
2.  Open a web browser on the server and navigate to `http://localhost/phpmyadmin`.
3.  Create a new database named `lab_allocation_system`.
4.  Import the SQL dump file located at `database/lab_allocation_system.sql`.
    *   *Note:* This file contains the schema structure and the seed data (initial admin account).

### Step 3: Network Exposure
1.  **Firewall Options:** Open "Windows Defender Firewall" settings.
2.  **Inbound Rules:** detailed a new "Inbound Rule" for Port **80** (HTTP) and Port **3306** (MySQL).
3.  **Allow Connection:** Ensure "Allow the connection" is selected for Private Networks. This allows other computers on the LAN to "see" the web server.

### Step 4: Client Connection
1.  Connect student computers to the same Switch/WiFi network as the Admin PC.
2.  Open the web browser on a student PC.
3.  Type the Admin PC's IP address followed by the project folder name.
    *   Example: `http://192.168.1.50/LAB PROGRAMMING ALLOCATION SYSTEM/`

## 3.3 Security, Scalability & Maintenance

### 3.3.1 Security Measures
Hosting internally already provides a layer of physical security.
*   **Session Hijacking Protection:** The `includes/security.php` script enforces strict session regeneration checks (`session_regenerate_id`).
*   **CSRF Tokens:** All forms (Login, Submission, Settings) are protected by Cross-Site Request Forgery tokens to prevent malicious requests.
*   **Input Sanitization:** All data entering the system is stripped of HTML tags (`htmlspecialchars`) and SQL injection vectors (`PDO Prepared Statements`).

### 3.3.2 Scalability
The system is built to scale vertically.
*   **Current Capacity:** Tested for up to 100 concurrent connections (Typical Lab Size: 60-70).
*   **Expansion:** To handle multiple labs (e.g., Lab A, Lab B, Lab C) simultaneously, the Apache `httpd.conf` can be tuned to increase `MaxKeepAliveRequests` and `ThreadsPerChild`.

### 3.3.3 Maintenance
*   **Log Monitoring:** The `activity_logs` table records every failed login attempt and system error. Administrators can review this via the Dashboard to identify issues.
*   **Data Backup:** A `REINSTALL_DATABASE.bat` script is provided to reset the system for a new semester. Administrators can also export the `results` table to Excel for archival purposes before resetting.
