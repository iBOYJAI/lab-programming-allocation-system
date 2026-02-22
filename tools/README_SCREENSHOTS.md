# Screenshot Capture Guide

This guide helps you automatically or manually capture screenshots for Appendix A documentation.

## 🚀 Quick Start

### Option 1: Node.js + Puppeteer (⭐⭐ MOST RECOMMENDED - Cross-Platform)

**Best for:** Windows, Mac, Linux users

**Requirements:**
- Node.js (v14+) - Download from [nodejs.org](https://nodejs.org/)
- XAMPP running (Apache + MySQL)
- Internet connection (for first-time npm install)

**Steps:**
1. **Double-click:** `tools\capture-screenshots.bat`
   OR
2. Open terminal and run:
   ```bash
   cd tools
   npm install
   node capture-screenshots.js
   ```
   (Run `npm install` once to install Puppeteer.)

**Advantages:**
- ✅ Works on Windows, Mac, Linux
- ✅ Faster and more reliable
- ✅ Better error handling
- ✅ Full-page screenshots automatically
- ✅ See `README_NODEJS.md` for details

---

### Option 2: PowerShell + Selenium (Windows Only)

**Requirements:**
- PowerShell 5.1 or higher
- Google Chrome or Microsoft Edge installed
- XAMPP running (Apache + MySQL)
- Internet connection (for Selenium module installation - one time only)

**Steps:**
1. **Double-click:** `tools\capture_with_login.bat`
   OR
2. Open PowerShell and run:
   ```powershell
   cd "C:\xampp\htdocs\LAB PROGRAMMING ALLOCATION SYSTEM"
   .\tools\capture_screenshots.ps1
   ```

**The script will automatically:**
- ✅ Install Selenium module (if needed, one-time only)
- ✅ Create folder structure automatically
- ✅ **Login as Admin/Staff/Student as needed** (tries multiple usernames)
- ✅ Switch between roles automatically for different pages
- ✅ Capture full-page screenshots
- ✅ Save with proper naming convention (`01_Landing_Page.png`, etc.)
- ✅ Generate a CSV file with all URLs if automation fails

**Default Credentials (script tries these automatically):**
- **Admin:** `admin` or `srinath` / `admin123`
- **Staff:** `STAFF001` or `STF100` / `admin123`
- **Student:** `2024CS001` or `23-CS-001` / `admin123`

**Note:** If your database has different usernames, edit `capture_screenshots.ps1` and update the `$credentials` section.

---

### Option 3: Simple Batch Script (Opens Pages - Manual Login)

**Steps:**
1. Double-click: `tools\capture_screenshots_simple.bat`
2. All pages will open in your browser
3. Manually capture each page using Windows Snipping Tool (Win + Shift + S)
4. Save with the filename shown in the console

---

### Option 3: Browser Extension (Easiest Manual Method)

**Recommended Extension:** "Full Page Screen Capture" for Chrome/Edge

**Steps:**
1. Install extension from Chrome Web Store
2. Open each page from the URL list below
3. Click extension icon → Capture full page
4. Save with naming convention: `##_PageName.png`

---

## 📁 Folder Structure

Screenshots are saved in **two parent folders**: **viewport** and **fullheight**.
```
documentation/screenshots/
  ├── viewport/          ← visible area only (1920×1080)
  │   ├── 01_Common/
  │   ├── 02_Admin/
  │   ├── 03_Staff/
  │   ├── 04_Student/
  │   └── 05_Additional/
  └── fullheight/        ← full page height
      ├── 01_Common/
      ├── 02_Admin/
      ├── 03_Staff/
      ├── 04_Student/
      └── 05_Additional/
```

---

## 📋 Complete Page List (20 Screenshots)

### Common Pages (2)
1. `01_Landing_Page.png` - `index.php`
2. `02_Login_Page.png` - `auth.php`

### Admin Module (6)
3. `03_Admin_Dashboard.png` - `admin/dashboard.php`
4. `04_Manage_Students.png` - `admin/manage_students.php`
5. `05_Manage_Labs.png` - `admin/manage_labs.php`
6. `06_Manage_Questions.png` - `admin/manage_questions.php`
7. `07_Manage_Staff.png` - `admin/manage_staff.php`
8. `08_Manage_Subjects.png` - `admin/manage_subjects.php`

### Staff Module (5)
9. `09_Staff_Dashboard.png` - `staff/dashboard.php`
10. `10_Start_Exam.png` - `staff/start_exam.php`
11. `11_Monitor_Exam.png` - `staff/monitor_exam.php` ⭐ **CRITICAL**
12. `12_Evaluate_Submissions.png` - `staff/evaluate.php`
13. `13_View_Results.png` - `staff/view_result.php`

### Student Module (4)
14. `14_Student_Dashboard.png` - `student/dashboard.php`
15. `15_Exam_Interface.png` - `student/exam.php` ⭐ **CRITICAL**
16. `16_Exam_History.png` - `student/history.php`
17. `17_Student_Profile.png` - `student/profile.php`

### Additional Pages (3)
18. `18_View_Session_Details.png` - `admin/view_session_details.php`
19. `19_View_Submission.png` - `staff/view_submission.php`
20. `20_Reports.png` - `staff/reports.php`

---

## 🎯 Capture Tips

### Before Capturing:
1. **Start XAMPP** - Ensure Apache and MySQL are running ✅
2. **Login Required** - ✅ **AUTOMATIC** with Option 1 (script handles login)
   - For Option 2/3: Login manually as Admin/Staff/Student for protected pages
3. **Sample Data** - Populate database with sample data for better screenshots
4. **Full Screen** - ✅ **AUTOMATIC** with Option 1 (browser maximizes automatically)
   - For Option 2/3: Press F11 for fullscreen mode before capturing

### For Critical Pages:
- **Monitor Exam (11):** Capture when multiple students are active (showing different status colors)
- **Exam Interface (15):** Capture with timer running and code editor visible
- **Evaluation (12):** Capture with question and student code side-by-side

### Image Quality:
- **Resolution:** 1920x1080 or higher
- **Format:** PNG (recommended) or JPG
- **Full Page:** Capture entire page, scroll if needed (capture multiple images for long pages)

---

## 🔧 Troubleshooting

### PowerShell Script Fails:
- Check if Chrome/Edge is installed
- Run PowerShell as Administrator (or use the .bat file)
- Install Selenium module manually: `Install-Module -Name Selenium -Scope CurrentUser`
- Check if XAMPP is running (Apache + MySQL)

### Login Fails:
- Check database credentials match the script defaults
- Edit `capture_screenshots.ps1` and update `$credentials` section with your actual usernames
- Verify users exist in database: `users`, `staff`, `students` tables
- Default password is `admin123` - change if different

### Pages Not Loading:
- Verify XAMPP is running (Apache + MySQL)
- Check URL: `http://localhost/LAB PROGRAMMING ALLOCATION SYSTEM`
- Ensure database is imported

### Login Required:
- Use admin credentials: `srinath` / `password` (default)
- Or create test accounts for Staff/Student roles

---

## 📝 After Capturing

1. **Verify All Screenshots:** Check that all 20 files exist
2. **Review Quality:** Ensure screenshots are clear and readable
3. **Update Appendix A:** Add screenshot references to `documentation/07_APPENDIX_A_WEBPAGES.md`

Example markdown:
```markdown
![Figure A.1: Landing Page](screenshots/01_Common/01_Landing_Page.png)
*Figure A.1: Landing Page - System entry point*
```

---

## 📞 Support

If you encounter issues:
1. Check `tools\capture_screenshots.ps1` for error messages
2. Review the CSV file generated: `documentation\screenshots\screenshot_urls.csv`
3. Use manual capture method (Option 2 or 3)

---

**Last Updated:** 2026-02-23  
**Total Screenshots Required:** 20
