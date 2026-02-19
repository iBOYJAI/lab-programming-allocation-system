# Lab Programming Allocation System

A complete **offline** web-based programming lab exam management system with smart question allocation algorithm that prevents cheating by ensuring adjacent students receive different questions.

## Features

### Core Functionality
- ✅ **Smart Question Allocation**: Each student gets 2 unique questions from a pool of at least 15 questions.
- ✅ **Collision Detection**: No adjacent students (±1 seat, ±1 row) receive the same questions
- ✅ **Real-time Monitoring**: Staff can monitor all students' progress in real-time
- ✅ **Code Editor**: Integrated CodeMirror editor with syntax highlighting
- ✅ **Anti-Cheating**: Disabled copy/paste, F12, right-click during exams
- ✅ **100% Offline**: Works entirely on LAN/WiFi hotspot

### User Roles
1. **Admin**: Complete system management (labs, staff, students, subjects, questions)
2. **Staff**: Start exams, monitor students, evaluate submissions
3. **Student**: Take exams, submit code, view results

### Technology Stack
- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, Bootstrap 5.3, Vanilla JavaScript
- **Libraries**: CodeMirror (code editor), Chart.js (analytics)
- **Server**: Apache (XAMPP/WAMP)

## Installation

### Prerequisites
- XAMPP installed on admin PC (download from https://www.apachefriends.org/)
- PHP 7.4 or higher
- MySQL 8.0 or higher
- At least 2GB RAM
- Web browser (Chrome/Firefox recommended)

### ⭐ Easiest Method: Auto-Installer (Recommended)

1. **Install XAMPP** (if not already installed)
   - Download from https://www.apachefriends.org/
   - Install to `C:\xampp\`

2. **Double-click `START_SYSTEM.bat`**
   - It will automatically:
     - Start Apache and MySQL
     - Create the database
     - Import all sample data
     - Open your browser
   - That's it! 🎉

### Alternative: Manual Installation

If the BAT file doesn't work, follow these steps:

1. **Copy System Files**
   ```
   Copy the entire "LAB PROGRAMMING ALLOCATION SYSTEM" folder to:
   C:\xampp\htdocs\
   ```

2. **Start Apache & MySQL**
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL

3. **Import Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Click "New" to create a database
   - Name it: `lab_allocation_system`
   - Click "Import" tab
   - Choose file: `database/lab_allocation_system.sql`
   - Click "Go"

4. **Access the System**
   - Open browser and go to: `http://localhost/LAB PROGRAMMING ALLOCATION SYSTEM/`
   - Or use your PC's IP address from student computers

### Default Login Credentials

**Admin:**
- Username: `admin`
- Password: `admin123`

**Staff (Test Account):**
- Staff ID: `STAFF001`
- Password: `admin123`

**Student (Test Account):**
- Roll No: `2024CS001`
- Password: `admin123`

## Network Setup for Students

### Option 1: LAN Connection
1. Connect all student PCs to the same network switch
2. Note admin PC's IP address (run `ipconfig` in CMD)
3. Students access via: `http://[ADMIN_PC_IP]/LAB PROGRAMMING ALLOCATION SYSTEM/`

### Option 2: WiFi Hotspot
1. On admin PC, create a mobile hotspot
2. Students connect to the hotspot
3. Note admin PC's IP (usually `192.168.137.1`)
4. Students access via: `http://192.168.137.1/LAB PROGRAMMING ALLOCATION SYSTEM/`

## Usage Guide

### For Admin

1. **Login** to admin panel
2. **Add Labs**: Create computer labs with total systems
3. **Add Staff**: Create staff accounts
4. **Add Students**: Import students via CSV or add manually
5. **Add Subjects**: Create programming subjects (C, C++, Java, Python)
6. **Add Questions**: Add at least 15 questions per subject.
7. **Assign Staff**: Assign staff to labs and subjects

### For Staff

1. **Login** to staff panel
2. **Start Exam**:
   - Select subject (must have at least 15 questions)
   - Choose test type (Practice, Weekly, Monthly, Internal)
   - Set duration (optional)
   - Click "START EXAM"
3. **Monitor**: Real-time view of all students' progress
4. **Stop Exam**: End the session when time's up

### For Students

1. **Login** with roll number and password
2. **View Questions**: See your 2 allocated questions
3. **Write Code**: Use the code editor (syntax highlighting enabled)
4. **Submit**: Submit each question separately
5. **Note**: Copy/paste is disabled for security

## System Architecture

### Database Tables (12)
- `users` - System users (admin)
- `staff` - Staff members
- `students` - Student records
- `labs` - Computer labs
- `subjects` - Programming subjects
- `questions` - Question bank
- `staff_assignments` - Staff-lab-subject mapping
- `exam_sessions` - Active/completed exams
- `student_allocations` - Question allocations (THE CORE!)
- `submissions` - Student code submissions
- `results` - Evaluation results
- `activity_logs` - System activity tracking

### Smart Allocation Algorithm
Located in: `includes/allocation_algorithm.php`

**How it works:**
1. Fetches all students ordered by system number
2. Randomly selects 2 questions from the subject pool
3. Checks for collisions with adjacent students:
   - Previous/next student (±1)
   - Student 10 positions away (±10, for rows)
4. Re-allocates if collision detected (max 10 attempts)
5. Falls back to random if no collision-free pair found
6. Verifies entire allocation for quality

## Security Features

- ✅ **Password Hashing**: Bcrypt with salt
- ✅ **SQL Injection Prevention**: Prepared statements
- ✅ **XSS Protection**: Input sanitization
- ✅ **CSRF Protection**: Tokens on all forms
- ✅ **Session Management**: Secure session handling
- ✅ **Activity Logging**: All actions logged
- ✅ **Role-Based Access Control**: Strict permissions

## Troubleshooting

### Students can't access the system
- Verify all PCs are on the same network
- Check Windows Firewall (allow Apache)
- Ensure Apache is running on admin PC

### Database connection error
- Verify MySQL is running
- Check credentials in `config/database.php`
- Ensure database was imported successfully

### Questions not allocated
- Ensure subject has at least 15 questions accumulated.
- Check `activity_logs` table for errors
- Verify students exist in the system

### Code editor not working
- Check internet connection (for CDN libraries)
- Use local copies if fully offline needed

## File Structure

```
LAB PROGRAMMING ALLOCATION SYSTEM/
├── admin/              # Admin module
├── staff/              # Staff module  
├── student/            # Student module
├── config/             # Configuration files
├── includes/           # Core functions & security
│   ├── allocation_algorithm.php ⭐
│   ├── security.php
│   └── functions.php
├── database/           # SQL files
├── assets/             # CSS, JS, images
├── index.php           # Landing page
└── README.md           # This file
```

## Credits

**Developer**: Jaiganesh D. (iBOY)  
**Organization**: iBOY Innovation HUB  
**Version**: 1.0.0  
**Year**: 2026

## License

This system is developed for educational purposes. Feel free to modify and enhance as needed for your institution.

## Support

For issues or questions:
1. Check this README first
2. Review code comments in critical files
3. Check `activity_logs` table for errors
4. Verify network connectivity

---

**Note**: This system is designed to work 100% offline. The admin PC acts as the server, and all student PCs are clients accessing via LAN/WiFi.
