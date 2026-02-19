# 🎯 QUICK START - Double-Click to Run!

## ⚡ Instant Setup (2 Minutes)

### **Step 1: Install XAMPP** (One-time only)
1. Download XAMPP from: https://www.apachefriends.org/
2. Install to `C:\xampp\`
3. Done!

### **Step 2: Start the System**
1. **Double-click** `START_SYSTEM.bat`
2. Wait 10 seconds
3. Browser opens automatically!

That's it! 🎉

---

## 📝 Default Login Credentials

### Admin Login
```
URL: http://localhost/LAB PROGRAMMING ALLOCATION SYSTEM/
Username: admin
Password: admin123
```

### Staff Login
```
Staff ID: STAFF001
Password: admin123
```

### Student Login
```
Roll No: 2024CS001 (or 2024CS002 through 2024CS070)
Password: admin123
```

---

## 🌐 Network Setup (For Lab Use)

### Option 1: LAN Connection
1. Connect all PCs to same network
2. Run `ipconfig` on admin PC to get IP (e.g., `192.168.1.100`)
3. Students access via: `http://192.168.1.100/LAB PROGRAMMING ALLOCATION SYSTEM/`

### Option 2: WiFi Hotspot
1. Admin PC: Settings → Mobile hotspot → Turn on
2. IP will be `192.168.137.1`
3. Students access via: `http://192.168.137.1/LAB PROGRAMMING ALLOCATION SYSTEM/`

---

## 🛠️ Other BAT Files

| File | Purpose |
|------|---------|
| **START_SYSTEM.bat** | ⭐ Start everything automatically |
| **STOP_SYSTEM.bat** | Stop Apache & MySQL |
| **REINSTALL_DATABASE.bat** | Reset database to fresh state |

---

## 🧪 Quick Test Workflow

### As Staff:
1. Login with **STAFF001** / **admin123**
2. Click "Start New Exam"
3. Select "Data Structures (C++)"
4. Choose "Practice" → START EXAM
5. Watch allocation happen! 🚀

### As Student:
1. Login with **2024CS001** / **admin123**
2. See your 2 unique questions
3. Write code in editors
4. Submit!

---

## ❓ Troubleshooting

### "XAMPP not found"
- Install XAMPP to `C:\xampp\`

### "Database error"
- Run `REINSTALL_DATABASE.bat`

### Students can't connect
- Check firewall settings
- Make sure all PCs on same network

### Browser doesn't open
- Manually go to: `http://localhost/LAB PROGRAMMING ALLOCATION SYSTEM/`

---

## 💡 Tips

- ✅ System is **100% offline** (works without internet)
- ✅ CDN libraries cache automatically on first load
- ✅ All 70 students pre-loaded and ready
- ✅ Keep `START_SYSTEM.bat` window open while using
- ✅ Close window or run `STOP_SYSTEM.bat` to stop

---

## 📚 More Information

See full documentation in `README.md`

---

**Developed by**: Jaiganesh D. (iBOY) | **Organization**: iBOY Innovation HUB | **Version**: 1.0.0
