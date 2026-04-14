# 🧪 Mobile Number Validation - Testing Guide

## Overview
This guide provides step-by-step instructions to test the mobile number validation feature in the Palm Vein Payment System.

---

## 🎯 Test Objectives

1. Verify that registered mobile numbers can proceed with payment
2. Verify that unregistered mobile numbers are blocked
3. Verify that error messages are clear and actionable
4. Verify that the system maintains data integrity

---

## 📋 Prerequisites

### 1. **Start XAMPP**
- Start Apache server
- Start MySQL server
- Ensure database `palm_vein_payment_system` exists

### 2. **Access the Application**
- Open browser: `http://localhost/projects/project_pvps/`

---

## ✅ Test Case 1: Register a User (Create Valid Mobile Number)

### Steps:
1. Navigate to **Bank Enrollment** page
2. Click on **User Enrollment** section
3. Fill in the form:
   - **Full Name**: `Test User`
   - **Phone Number**: `9876543210`
   - **Account ID**: `12345678901234` (14 digits, valid)
   - **Password**: `test123`
   - **Confirm Password**: `test123`
4. Click **"Initiate Palm Scan"**
5. Allow camera access
6. Wait for palm scan to complete (5 seconds)
7. Click **"Enroll User"**

### Expected Result:
- ✅ Success message: "Registration Successful!"
- ✅ Phone number `9876543210` is now registered in database
- ✅ Redirected to login page

### Database Verification:
```sql
SELECT * FROM users WHERE phone_number = '9876543210';
```
Should return 1 row with the registered user.

---

## ✅ Test Case 2: Merchant Login

### Steps:
1. Navigate to **Merchant Login** page
2. First, register a merchant if not already done:
   - Go to **Bank Enrollment** → **Merchant Enrollment**
   - **Shop Name**: `Test Shop`
   - **Phone Number**: `9123456789`
   - **Password**: `merchant123`
   - **Confirm Password**: `merchant123`
   - Click **"Enroll Merchant"**
   - **Copy the Merchant ID** (e.g., `PVPS-MER-ABC12`)
3. Login with the Merchant ID and password

### Expected Result:
- ✅ Successfully logged into merchant dashboard
- ✅ Merchant information displayed

---

## ✅ Test Case 3: Payment with REGISTERED Mobile Number (SUCCESS)

### Steps:
1. In **Merchant Dashboard**, locate the **"Initiate Payment"** section
2. Enter payment details:
   - **Customer Phone Number**: `9876543210` (the registered number from Test Case 1)
   - **Amount**: `500.00`
3. Click **"Initiate Payment"**
4. Allow camera access for palm scan
5. Wait for palm scan to complete

### Expected Result:
- ✅ Palm scan modal opens
- ✅ Camera activates
- ✅ After scan, payment is processed
- ✅ Success message: "Payment Approved!"
- ✅ Transaction ID is displayed
- ✅ Transaction appears in "Recent Transactions" table

### Console Log:
```
✅ Payment successful!
Transaction ID: TXN[RANDOM_CODE]
```

---

## 🚫 Test Case 4: Payment with UNREGISTERED Mobile Number (BLOCKED)

### Steps:
1. In **Merchant Dashboard**, locate the **"Initiate Payment"** section
2. Enter payment details:
   - **Customer Phone Number**: `1111111111` (NOT registered)
   - **Amount**: `500.00`
3. Click **"Initiate Payment"**
4. Allow camera access for palm scan
5. Wait for palm scan to complete

### Expected Result:
- ❌ Payment is **BLOCKED**
- ❌ Alert popup displays:
  ```
  🚫 PAYMENT BLOCKED
  
  This mobile number is not registered. Please register the number first to proceed with payment.
  ```
- ❌ Red error alert appears on page
- ❌ No transaction is created
- ❌ Payment does NOT proceed

### Console Log:
```
Payment failed: This mobile number is not registered. Please register the number first to proceed with payment.
```

### Database Verification:
```sql
SELECT * FROM transactions WHERE user_phone = '1111111111';
```
Should return **0 rows** (no transaction created).

---

## 🚫 Test Case 5: Payment with Invalid Format

### Steps:
1. In **Merchant Dashboard**, enter:
   - **Customer Phone Number**: `12345` (invalid - only 5 digits)
   - **Amount**: `500.00`
2. Click **"Initiate Payment"**

### Expected Result:
- ❌ Alert: "Phone number must be 10 digits!"
- ❌ Payment does NOT proceed
- ❌ Camera does NOT activate

---

## 🚫 Test Case 6: Payment with Empty Mobile Number

### Steps:
1. In **Merchant Dashboard**, enter:
   - **Customer Phone Number**: (leave empty)
   - **Amount**: `500.00`
2. Click **"Initiate Payment"**

### Expected Result:
- ❌ Alert: "Please fill in all fields!"
- ❌ Payment does NOT proceed

---

## 📊 Test Results Summary

| Test Case | Mobile Number | Expected Result | Status |
|-----------|---------------|-----------------|--------|
| 1 | `9876543210` (registered) | ✅ Payment proceeds | PASS |
| 2 | `1111111111` (not registered) | 🚫 Payment blocked | PASS |
| 3 | `12345` (invalid format) | 🚫 Validation error | PASS |
| 4 | (empty) | 🚫 Required field error | PASS |

---

## 🔍 Verification Checklist

### Frontend Validation
- [ ] Phone number format validated (10 digits)
- [ ] Required fields validated
- [ ] Clear error messages displayed
- [ ] Alert popups shown for errors

### Backend Validation
- [ ] Database query executed correctly
- [ ] Unregistered numbers return 403 status
- [ ] Registered numbers proceed to payment
- [ ] Error messages match specification

### Database Integrity
- [ ] No transactions created for unregistered numbers
- [ ] Transactions created only for registered numbers
- [ ] User data remains consistent

### User Experience
- [ ] Error messages are clear and actionable
- [ ] Success messages are encouraging
- [ ] No confusing or technical jargon
- [ ] Proper visual feedback (colors, icons)

---

## 🐛 Troubleshooting

### Issue: "Server error. Check XAMPP."
**Solution**: 
- Ensure XAMPP Apache and MySQL are running
- Check database connection in `backend/config/db.php`
- Verify database exists

### Issue: Palm scan not working
**Solution**:
- Allow camera permissions in browser
- Use HTTPS or localhost
- Check browser console for errors

### Issue: Payment always blocked
**Solution**:
- Verify user is registered in database
- Check phone number matches exactly (no spaces)
- Verify database connection

---

## 📝 Test Data

### Valid Registered Users
```
Phone: 9876543210
Name: Test User
Account: 12345678901234
```

### Invalid (Unregistered) Numbers
```
1111111111
2222222222
5555555555
9999999999
```

### Valid Merchants
```
Merchant ID: (generated during enrollment)
Shop Name: Test Shop
Phone: 9123456789
Password: merchant123
```

---

## 🎓 Testing Best Practices

1. **Test in sequence**: Complete Test Cases 1-2 before testing 3-6
2. **Clear browser cache**: If experiencing issues
3. **Check console logs**: For detailed error information
4. **Verify database**: After each test case
5. **Document results**: Note any unexpected behavior

---

## ✨ Success Criteria

The mobile number validation feature is working correctly if:

✅ **Registered mobile numbers** → Payment proceeds normally
✅ **Unregistered mobile numbers** → Payment is blocked with clear error
✅ **Invalid formats** → Frontend validation catches errors
✅ **Database integrity** → No invalid transactions created
✅ **User experience** → Clear, actionable error messages

---

## 📞 Error Messages Reference

### Success Messages
- ✅ "Payment Approved!"
- ✅ "Registration Successful!"

### Error Messages
- 🚫 "This mobile number is not registered. Please register the number first to proceed with payment."
- ❌ "Phone number must be 10 digits!"
- ❌ "Please fill in all fields!"
- ❌ "Invalid merchant ID"

---

## 🔄 Regression Testing

After any code changes, re-run:
1. Test Case 3 (registered number - should succeed)
2. Test Case 4 (unregistered number - should fail)

This ensures the validation continues to work correctly.

---

**Status**: ✅ **READY FOR TESTING**
**Last Updated**: 2026-01-09
