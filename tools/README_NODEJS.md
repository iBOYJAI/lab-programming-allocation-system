# Node.js Screenshot Capture Tool (Puppeteer)

This is the **recommended** method for capturing screenshots. It uses Puppeteer (headless Chrome) and works cross-platform.

## 🚀 Quick Start

### Prerequisites
- **Node.js** (v14 or higher) - Download from [nodejs.org](https://nodejs.org/)
- **XAMPP** running (Apache + MySQL)
- Internet connection (for first-time npm install)

### Installation (One-time)

1. **Install Node.js** (if not already installed)
   - Download from: https://nodejs.org/
   - Install with default settings

2. **Install dependencies**
   ```bash
   cd tools
   npm install
   ```
   
   Or just double-click: `tools\capture-screenshots.bat` (it will auto-install)

### Usage

**Option 1: Double-click (Easiest)**
```
tools\capture-screenshots.bat
```

**Option 2: Command Line**
```bash
cd tools
node capture-screenshots.js
```

**Option 3: NPM Script**
```bash
cd tools
npm run capture
```

## ✨ Features

- ✅ **Automatic Login** - Logs in as Admin/Staff/Student as needed
- ✅ **Full Page Screenshots** - Captures entire page (scrolls automatically)
- ✅ **Smart Role Switching** - Switches between roles automatically
- ✅ **Proper Naming** - Saves as `01_Landing_Page.png`, `02_Login_Page.png`, etc.
- ✅ **Organized Folders** - Saves in `documentation/screenshots/` with subfolders
- ✅ **Error Handling** - Tries multiple usernames if one fails
- ✅ **Cross-Platform** - Works on Windows, Mac, Linux

## 📋 What It Does

1. Creates folder structure automatically
2. Launches Chrome browser (headless mode available)
3. For each of 20 pages:
   - Logs in automatically if needed (Admin/Staff/Student)
   - Navigates to the page
   - Waits for content to load
   - Captures full-page screenshot
   - Saves with proper filename
4. Closes browser when done

## 🔧 Configuration

Edit `capture-screenshots.js` to customize:

### Change Base URL
```javascript
const BASE_URL = 'http://localhost/LAB PROGRAMMING ALLOCATION SYSTEM';
```

### Change Credentials
Match the "access keys" on your auth page. Default in this project:
```javascript
// From auth.php: Admin@123 (admin/staff), Password@123 (student)
const credentials = {
    admin: { usernames: ['srinath', 'admin'], password: 'Admin@123' },
    staff: { usernames: ['STF100', 'STAFF001'], password: 'Admin@123' },
    student: { usernames: ['23-AI-001', '23-CS-001'], passwords: ['Password@123', 'admin123'] }
};
```

### Change Delay
```javascript
const DELAY_SECONDS = 3; // Wait time between captures
```

### Headless Mode
```javascript
const browser = await puppeteer.launch({
    headless: true, // Set to true for background capture
    // ...
});
```

## 📁 Output Structure (two parent folders)

Screenshots are saved in two parent folders: **viewport** and **fullheight**.

- **viewport** — visible area only (1920×1080), one screen per page  
- **fullheight** — full page (scrolls and stitches into one tall image)

```
documentation/screenshots/
  ├── viewport/
  │   ├── 01_Common/
  │   ├── 02_Admin/
  │   ├── 03_Staff/
  │   ├── 04_Student/
  │   └── 05_Additional/
  └── fullheight/
      ├── 01_Common/
      ├── 02_Admin/
      ├── 03_Staff/
      ├── 04_Student/
      └── 05_Additional/
```

## 🐛 Troubleshooting

### "Node.js is not installed"
- Install Node.js from https://nodejs.org/
- Restart terminal/command prompt after installation

### "npm install fails"
- Check internet connection
- Try: `npm install puppeteer --verbose`
- If behind proxy: `npm config set proxy http://proxy:port`

### "Cannot find module 'puppeteer'"
- Run: `cd tools && npm install`
- Make sure you're in the `tools` folder

### "Login fails"
- Check database credentials match script defaults
- Edit `capture-screenshots.js` credentials section
- Verify users exist in database

### "Page timeout"
- Increase timeout in script: `timeout: 60000` (60 seconds)
- Check if XAMPP is running
- Verify URL is correct

### "Screenshots are blank"
- Increase `DELAY_SECONDS` value
- Check if page requires JavaScript to load
- Try setting `headless: false` to see what's happening

## 📊 Performance

- **Total Time:** ~2-3 minutes for all 20 screenshots
- **Per Screenshot:** ~6-10 seconds (includes login + capture)
- **File Size:** ~200-500 KB per screenshot (PNG format)

## 🔄 Comparison: Puppeteer vs PowerShell/Selenium

| Feature | Puppeteer (Node.js) | PowerShell/Selenium |
|---------|-------------------|---------------------|
| Cross-platform | ✅ Yes | ⚠️ Windows only |
| Setup | ✅ Simple (npm install) | ⚠️ Complex (module install) |
| Speed | ✅ Fast | ⚠️ Slower |
| Reliability | ✅ High | ⚠️ Medium |
| **Recommended** | ✅ **YES** | ⚠️ Alternative |

## 📝 Notes

- First run downloads Chromium (~170 MB) - this is normal
- Browser opens visibly by default (set `headless: true` for background)
- Screenshots are full-page (automatically scrolls and stitches)
- All 20 screenshots captured automatically in one run

## 🆘 Support

If you encounter issues:
1. Check Node.js version: `node --version` (should be v14+)
2. Check npm version: `npm --version`
3. Verify XAMPP is running
4. Check browser console for errors (if headless: false)
5. Review error messages in terminal

---

**Last Updated:** 2026-02-23  
**Version:** 1.0.0  
**Author:** SRINATH (23AI152)
