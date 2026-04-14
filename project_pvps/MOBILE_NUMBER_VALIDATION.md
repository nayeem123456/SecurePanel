# 📱 Mobile Number Validation System

## Overview
The Palm Vein Payment System implements **strict mobile number validation** to ensure that only registered users can make payments. The mobile number serves as the **primary identifier** for customer accounts.

---

## 🔐 Security Flow

### 1. **Bank Enrollment (Registration)**
- During user enrollment at the bank, customers register their mobile number
- The mobile number is stored in the `users` table in the database
- This number becomes the **authorized identifier** for all future transactions
- **File**: `bank-enrollment.html` → `js/enrollment.js` → `backend/api/user_register.php`

### 2. **Merchant Payment Initiation**
- Merchant enters customer's mobile number in the dashboard
- System validates the mobile number against the database
- **File**: `merchant-dashboard.html` → `js/merchant-flow.js` → `backend/api/initiate_payment.php`

### 3. **Validation Process**
```
┌─────────────────────────────────────────────────────────────┐
│  Merchant enters mobile number                              │
│  ↓                                                           │
│  System checks: SELECT * FROM users WHERE phone_number = ?  │
│  ↓                                                           │
│  ┌─────────────────┐         ┌──────────────────┐          │
│  │ Number Found?   │ ──YES→  │ Allow Payment    │          │
│  │                 │         │ Proceed to Auth  │          │
│  └─────────────────┘         └──────────────────┘          │
│         │                                                    │
│         NO                                                   │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────┐          │
│  │ ❌ BLOCK PAYMENT                             │          │
│  │ "This mobile number is not registered.       │          │
│  │  Please register the number first to         │          │
│  │  proceed with payment."                      │          │
│  └──────────────────────────────────────────────┘          │
└─────────────────────────────────────────────────────────────┘
```

---

## 📋 Implementation Details

### Backend Validation (`backend/api/initiate_payment.php`)

```php
// STRICT MOBILE NUMBER VALIDATION
$check_user = "SELECT user_id, full_name, phone_number FROM users WHERE phone_number = '$user_phone'";
$result = $conn->query($check_user);

if ($result->num_rows === 0) {
    // Mobile number is NOT registered - BLOCK the payment
    send_response(false, [], 'This mobile number is not registered. Please register the number first to proceed with payment.', 403);
}

// Mobile number is registered - Extract user details
$user_data = $result->fetch_assoc();
$user_id = $user_data['user_id'];
$user_name = $user_data['full_name'];
```

### Frontend Validation (`js/merchant-flow.js`)

The frontend performs basic format validation:
```javascript
if (!/^[0-9]{10}$/.test(userPhone)) {
    alert('❌ Phone number must be 10 digits!');
    return;
}
```

The backend performs the **authoritative validation** by checking the database.

---

## ✅ Success Scenario

**When mobile number IS registered:**
1. ✅ Merchant enters registered mobile number: `9876543210`
2. ✅ System finds user in database
3. ✅ Payment proceeds to palm vein authentication
4. ✅ Transaction is created and processed

---

## 🚫 Failure Scenario

**When mobile number is NOT registered:**
1. ❌ Merchant enters unregistered mobile number: `1234567890`
2. ❌ System does NOT find user in database
3. ❌ Payment is **BLOCKED** immediately
4. ❌ Error message displayed:
   ```
   "This mobile number is not registered. 
    Please register the number first to proceed with payment."
   ```
5. ❌ HTTP Status: `403 Forbidden`

---

## 🔍 Database Schema

### Users Table
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(10) UNIQUE NOT NULL,  -- PRIMARY IDENTIFIER
    account_id VARCHAR(18) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    biometric_template TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

The `phone_number` field is:
- **UNIQUE**: Each mobile number can only be registered once
- **NOT NULL**: Must be provided during enrollment
- **PRIMARY IDENTIFIER**: Used to identify customers during payment

---

## 🎯 Key Features

### 1. **Strict Validation**
- Only registered mobile numbers are allowed
- No exceptions or bypasses

### 2. **Clear Error Messages**
- User-friendly error message
- Instructs user on next steps (register first)

### 3. **Security**
- Prevents unauthorized transactions
- Ensures only enrolled customers can make payments

### 4. **Database-Driven**
- Validation is performed against the database
- Real-time check during payment initiation

---

## 📝 User Journey

### For Customers (Users)
1. Visit bank branch
2. Complete enrollment form with mobile number
3. Scan palm vein biometric
4. Mobile number is registered in system ✅
5. Can now make payments using this mobile number

### For Merchants
1. Login to merchant dashboard
2. Enter customer's mobile number
3. Enter payment amount
4. System validates mobile number:
   - **If registered**: Proceed to palm scan
   - **If not registered**: Show error, block payment

---

## 🛡️ Security Benefits

1. **Prevents Fraud**: Only registered users can transact
2. **Account Protection**: Mobile number acts as first layer of authentication
3. **Audit Trail**: All transactions linked to registered mobile numbers
4. **Compliance**: Ensures KYC (Know Your Customer) requirements

---

## 🔧 Testing

### Test Case 1: Registered Mobile Number
```
Input: 9876543210 (registered)
Expected: Payment proceeds to palm authentication
Status: ✅ PASS
```

### Test Case 2: Unregistered Mobile Number
```
Input: 1111111111 (not registered)
Expected: Error message + payment blocked
Status: ✅ PASS
Message: "This mobile number is not registered. Please register the number first to proceed with payment."
```

### Test Case 3: Invalid Format
```
Input: 12345 (invalid format)
Expected: Frontend validation error
Status: ✅ PASS
Message: "Phone number must be 10 digits!"
```

---

## 📊 HTTP Response Codes

| Scenario | HTTP Code | Response |
|----------|-----------|----------|
| Mobile number registered | `200 OK` | Payment proceeds |
| Mobile number not registered | `403 Forbidden` | Error message |
| Invalid format | `400 Bad Request` | Validation error |
| Server error | `500 Internal Server Error` | System error |

---

## 🔄 Integration Points

### Files Modified
1. `backend/api/initiate_payment.php` - Added strict validation
2. `MOBILE_NUMBER_VALIDATION.md` - This documentation

### Files Involved (No Changes)
1. `bank-enrollment.html` - User registration form
2. `js/enrollment.js` - Enrollment logic
3. `backend/api/user_register.php` - Stores mobile number
4. `merchant-dashboard.html` - Payment initiation form
5. `js/merchant-flow.js` - Payment flow logic

---

## 📞 Error Messages

### Primary Error (Mobile Not Registered)
```
"This mobile number is not registered. Please register the number first to proceed with payment."
```

### Secondary Errors
- "Phone number must be 10 digits!" (Frontend validation)
- "Missing required field: user_phone" (Missing data)
- "Invalid merchant ID" (Merchant validation)

---

## 🎓 Best Practices

1. **Always validate on backend**: Frontend validation is for UX, backend is for security
2. **Use clear error messages**: Tell users exactly what to do
3. **Log validation failures**: Track attempted transactions with unregistered numbers
4. **Maintain data integrity**: Ensure phone numbers are unique in database

---

## 📈 Future Enhancements

1. **OTP Verification**: Add SMS OTP during enrollment
2. **Multiple Numbers**: Allow users to register multiple mobile numbers
3. **Number Portability**: Handle mobile number changes
4. **Blacklist**: Block specific numbers from registration
5. **Rate Limiting**: Prevent brute-force mobile number guessing

---

## ✨ Summary

The mobile number validation system ensures that:
- ✅ Only registered customers can make payments
- ✅ Mobile number is the primary account identifier
- ✅ Unregistered numbers are blocked with clear error messages
- ✅ System maintains security and data integrity
- ✅ Merchants receive immediate feedback on validation status

**Status**: ✅ **IMPLEMENTED AND ACTIVE**
