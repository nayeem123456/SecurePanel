# 🔧 PVPS Error Troubleshooting Guide

## Common Errors During Login and Payment

This guide helps you resolve the "Server error. Check if XAMPP is running" errors that appear during user login and payment operations.

---

## 🔍 Error Identification

### Error 1: User Login Error
**Location:** User Login Page (`user-login.html`)  
**Error Message:** "Error: Server error. Check if XAMPP is running."  
**When:** Occurs when clicking "Login" button after palm scan

### Error 2: Payment Error  
**Location:** Merchant Dashboard (`merchant-dashboard.html`)  
**Error Message:** "Payment error. Server error. Check XAMPP."  
**When:** Occurs when initiating payment after palm scan

---

## ✅ Quick Fix Steps

### Step 1: Verify XAMPP is Running

1. **Open XAMPP Control Panel**
   - Windows: Search for "XAMPP Control Panel" in Start menu
   - Or navigate to: `C:\xampp\xampp-control.exe`

2. **Start Required Services**
   - Click **"Start"** next to **Apache** (port 80, 443)
   - Click **"Start"** next to **MySQL** (port 3306)
   - Both should show green highlighting when running

3. **Check for Port Conflicts**
   - If Apache won't start, another program may be using port 80
   - Common culprits: Skype, IIS, other web servers
   - Solution: Stop conflicting programs or change Apache port

### Step 2: Verify Database Exists

1. **Open phpMyAdmin**
   - Navigate to: http://localhost/phpmyadmin
   - Login with username: `root`, password: *(leave blank)*

2. **Check for `pvps_db` Database**
   - Look in the left sidebar for database named **`pvps_db`**
   - If missing, you need to create it

3. **Create Database (if missing)**
   ```sql
   CREATE DATABASE pvps_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Import Tables**
   - Navigate to `backend/database/` folder in your project
   - Look for `.sql` file (schema file)
   - In phpMyAdmin, select `pvps_db` → Import tab
   - Choose the SQL file and click "Go"

### Step 3: Run System Diagnostics

1. **Open Diagnostics Page**
   - Navigate to: http://localhost/project_pvps/diagnostics.html
   - This will automatically test all APIs

2. **Check Test Results**
   - ✅ Green = Working correctly
   - ⚠️ Orange = Testing in progress
   - ❌ Red = Error detected

3. **Click Individual Test Buttons**
   - Test Database Connection
   - Test User Login API
   - Test Merchant Login API  
   - Test Payment API

4. **Read Error Details**
   - If any test fails, expand error details
   - Follow the specific error message guidance

### Step 4: Verify File Structure

Ensure the following files exist:

```
project_pvps/
├── backend/
│   ├── config/
│   │   ├── db.php ✓
│   │   └── vision_config.php ✓
│   └── api/
│       ├── user_login.php ✓
│       ├── merchant_login.php ✓
│       └── initiate_payment.php ✓
├── js/
│   ├── user-flow.js ✓
│   └── merchant-flow.js ✓
├── user-login.html ✓
└── merchant-dashboard.html ✓
```

---

## 🐛 Specific Error Solutions

### Error: "Database connection failed"

**Cause:** MySQL service not running or credentials incorrect

**Solution:**
1. Start MySQL in XAMPP Control Panel
2. Check `backend/config/db.php` has correct credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Empty for default XAMPP
   define('DB_NAME', 'pvps_db');
   ```

### Error: "Cannot reach backend"

**Cause:** Apache service not running

**Solution:**
1. Start Apache in XAMPP Control Panel
2. Verify Apache is listening on port 80
3. Test by visiting: http://localhost
4. You should see XAMPP welcome page

### Error: "Invalid JSON Response" or "API returned non-JSON data"

**Cause:** PHP syntax error in backend files or missing constants

**Solution:**
1. ✅ **FIXED:** Missing constants have been added to `vision_config.php`
2. Check Apache error logs:
   - Location: `C:\xampp\apache\logs\error.log`
   - Look for PHP Fatal Errors or Parse Errors
3. Verify PHP version:
   - XAMPP should have PHP 7.4+ for this project

### Error: "User not found" or "Invalid credentials"

**Cause:** User/merchant not registered in database

**Solution:**
1. **Register a Test User:**
   - Go to: http://localhost/project_pvps/bank-enrollment.html
   - Fill in details and complete palm scan
   - Complete registration

2. **Register a Test Merchant:**
   - Go to: http://localhost/project_pvps/merchant-login.html
   - Click "Register at Bank" link
   - Complete merchant registration

3. **Verify in Database:**
   - phpMyAdmin → pvps_db → users table
   - Check if your user exists
   - phpMyAdmin → pvps_db → merchants table
   - Check if your merchant exists

---

## 🔧 Advanced Troubleshooting

### Check Browser Console

1. **Open Developer Tools**
   - Chrome/Edge: Press `F12` or `Ctrl+Shift+I`
   - Firefox: Press `F12` or `Ctrl+Shift+K`

2. **Go to "Console" Tab**
   - Look for red error messages
   - Common errors:
     - `Failed to fetch` = Backend not accessible
     - `Unexpected token < in JSON` = PHP error returned HTML instead of JSON
     - `NetworkError` = XAMPP not running

3. **Go to "Network" Tab**
   - Reload the page and perform the action
   - Click on the failed request
   - Check "Response" tab to see exact error

### Check PHP Error Logs

1. **Apache Error Log**
   - Location: `C:\xampp\apache\logs\error.log`
   - Open in text editor
   - Look for recent PHP errors with timestamps

2. **Enable PHP Error Display** (for debugging only)
   - Edit: `C:\xampp\php\php.ini`
   - Find: `display_errors = Off`
   - Change to: `display_errors = On`
   - Restart Apache in XAMPP

### Test Backend Files Directly

1. **Test Database Connection**
   ```
   http://localhost/project_pvps/backend/config/db.php
   ```
   - Should show blank page or JSON error
   - If you see PHP code, Apache isn't processing PHP

2. **Test User Login API**
   - Use Postman or curl:
   ```bash
   curl -X POST http://localhost/project_pvps/backend/api/user_login.php \
        -H "Content-Type: application/json" \
        -d '{"phone_number":"1234567890","password":"test"}'
   ```
   - Should return JSON response

---

## 📋 Checklist Before Using System

- [ ] XAMPP Apache service is **RUNNING** (green in Control Panel)
- [ ] XAMPP MySQL service is **RUNNING** (green in Control Panel)
- [ ] Database `pvps_db` exists in phpMyAdmin
- [ ] Database tables are imported (users, merchants, transactions)
- [ ] http://localhost works (shows XAMPP dashboard)
- [ ] http://localhost/project_pvps shows your project
- [ ] Diagnostics page shows all tests passing
- [ ] At least one test user is registered
- [ ] At least one test merchant is registered

---

## ✨ What Was Fixed

### Issue: Missing PHP Constants

**Problem:**  
The `vision_api.php` file was trying to use constants that didn't exist in `vision_config.php`:
- `ENABLE_VISION_VALIDATION`
- `VISION_API_KEY`
- `HAND_DETECTION_THRESHOLD`
- `HAND_LABELS`

This caused PHP fatal errors, preventing proper JSON responses.

**Solution:**  
Added all missing constants to `backend/config/vision_config.php`:

```php
// Vision API Key (placeholder)
define('VISION_API_KEY', 'YOUR_GOOGLE_VISION_API_KEY_HERE');

// Enable/disable legacy Vision API validation (DISABLED - using OpenRouter instead)
define('ENABLE_VISION_VALIDATION', false);

// Hand detection threshold for legacy Vision API (0.0 to 1.0)
define('HAND_DETECTION_THRESHOLD', 0.70);

// Hand-related labels for legacy Vision API detection
define('HAND_LABELS', [
    'hand', 'palm', 'finger', 'thumb', 'wrist', 'fist', 'gesture'
]);
```

**Result:**  
- APIs now return proper JSON responses
- Error messages are clearer and more helpful
- System validates palm images correctly (when enabled)

---

## 📞 Still Having Issues?

If you've followed all steps and still experiencing errors:

1. **Run the diagnostics page** and take a screenshot of all test results
2. **Check browser console** for JavaScript errors
3. **Check Apache error log** for PHP errors  
4. **Verify XAMPP versions:**
   - Apache: 2.4+
   - MySQL: 5.7+ or MariaDB 10.4+
   - PHP: 7.4+ or 8.0+

5. **Try restarting XAMPP completely:**
   - Stop all services
   - Close XAMPP Control Panel
   - Re-open and restart services

---

## 🎯 Quick Test After Fixes

1. ✅ Visit: http://localhost/project_pvps/diagnostics.html
2. ✅ All 4 tests should show GREEN (success)
3. ✅ Register a test user at bank-enrollment.html
4. ✅ Try logging in at user-login.html
5. ✅ Register a test merchant
6. ✅ Try merchant login and payment

If all steps pass, your system is now working correctly! 🎉

---

**Last Updated:** 2026-01-01  
**Version:** 1.0
