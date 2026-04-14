# PVPS Backend - Setup & API Documentation

## 📋 Setup Instructions

### 1. Database Setup
1. Open **phpMyAdmin** (http://localhost/phpmyadmin)
2. Click **Import** tab
3. Choose file: `backend/database/schema.sql`
4. Click **Go** to execute

This will:
- Create database `pvps_db`
- Create tables: `users`, `merchants`, `transactions`
- Insert demo data for testing

### 2. Verify Database Connection
- Ensure XAMPP Apache and MySQL are running
- Database credentials in `backend/config/db.php`:
  - Host: `localhost`
  - User: `root`
  - Password: `` (empty)
  - Database: `pvps_db`

---

## 🔌 API Endpoints

### Base URL
```
http://localhost/project_pvps/backend/api/
```

---

### 1. User Registration
**Endpoint:** `POST /user_register.php`

**Request Body:**
```json
{
  "full_name": "John Doe",
  "phone_number": "9876543210",
  "account_id": "ACC123456789",
  "password": "user123",
  "biometric_template": "SIMULATED_TEMPLATE_DATA"
}
```

**Response (Success):**
```json
{
  "success": true,
  "user_id": 1,
  "phone_number": "9876543210",
  "account_id": "ACC123456789",
  "message": "User registered successfully"
}
```

---

### 2. User Login
**Endpoint:** `POST /user_login.php`

**Request Body:**
```json
{
  "phone_number": "9876543210",
  "password": "user123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "user": {
    "user_id": 1,
    "full_name": "John Doe",
    "phone_number": "9876543210",
    "account_id": "ACC123456789"
  },
  "biometric_template": "SIMULATED_TEMPLATE_DATA",
  "message": "Login successful"
}
```

---

### 3. Merchant Registration
**Endpoint:** `POST /merchant_register.php`

**Request Body:**
```json
{
  "shop_name": "SuperMart",
  "phone_number": "9123456789",
  "password": "merchant123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "merchant_id": "PVPS-MER-A1B2C",
  "shop_name": "SuperMart",
  "phone_number": "9123456789",
  "message": "Merchant registered successfully"
}
```

---

### 4. Merchant Login
**Endpoint:** `POST /merchant_login.php`

**Request Body:**
```json
{
  "merchant_id": "MER987654321",
  "password": "merchant123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "merchant": {
    "merchant_id": "MER987654321",
    "shop_name": "SuperMart",
    "phone_number": "9123456789"
  },
  "message": "Login successful"
}
```

---

### 5. Initiate Payment
**Endpoint:** `POST /initiate_payment.php`

**Request Body:**
```json
{
  "merchant_id": "MER987654321",
  "user_phone": "9876543210",
  "amount": 500.00
}
```

**Response (Success):**
```json
{
  "success": true,
  "transaction_id": "TXNA1B2C3D4E",
  "amount": "500.00",
  "status": "APPROVED",
  "timestamp": "2025-12-19 20:30:45",
  "message": "Payment initiated successfully"
}
```

---

### 6. Get Transactions
**Endpoint:** `GET /get_transactions.php?merchant_id=MER987654321`  
or `POST /get_transactions.php`

**Request Body (if POST):**
```json
{
  "merchant_id": "MER987654321"
}
```

**Response (Success):**
```json
{
  "success": true,
  "transactions": [
    {
      "id": "TXNA1B2C3D4E",
      "merchantId": "MER987654321",
      "userPhone": "9876543210",
      "amount": 500.00,
      "status": "APPROVED",
      "timestamp": "2025-12-19 20:30:45"
    }
  ],
  "count": 1,
  "message": "Transactions retrieved successfully"
}
```

---

## 🧪 Testing with Postman

### Test User Login
1. Method: **POST**
2. URL: `http://localhost/project_pvps/backend/api/user_login.php`
3. Headers: `Content-Type: application/json`
4. Body (raw JSON):
```json
{
  "phone_number": "9876543210",
  "password": "user123"
}
```

### Test Merchant Login
1. Method: **POST**
2. URL: `http://localhost/project_pvps/backend/api/merchant_login.php`
3. Body:
```json
{
  "merchant_id": "MER987654321",
  "password": "merchant123"
}
```

---

## 📁 File Structure
```
backend/
├── config/
│   └── db.php              # Database connection
├── api/
│   ├── user_register.php   # User registration
│   ├── user_login.php      # User login
│   ├── merchant_register.php # Merchant registration
│   ├── merchant_login.php  # Merchant login
│   ├── initiate_payment.php # Payment processing
│   └── get_transactions.php # Transaction retrieval
├── database/
│   └── schema.sql          # Database schema & demo data
└── README.md               # This file
```

---

## 🔐 Demo Credentials

### Demo User
- Phone: `9876543210`
- Password: `user123`

### Demo Merchant
- Merchant ID: `MER987654321`
- Password: `merchant123`

---

## ⚠️ Important Notes

1. **CORS Headers:** All API files include CORS headers for frontend compatibility
2. **Password Hashing:** Uses PHP `password_hash()` and `password_verify()`
3. **Simulated Biometrics:** Biometric template is stored as text (not actual image/scan)
4. **Demo Data:** Schema includes demo users, merchants, and transactions
5. **Academic Project:** Simplified for demonstration purposes

---

## 🐛 Troubleshooting

### Database Connection Error
- Verify XAMPP MySQL is running
- Check credentials in `backend/config/db.php`
- Ensure database `pvps_db` exists

### API Returns 500 Error
- Check PHP error log in XAMPP
- Verify all required fields are sent
- Check database table structure

### CORS Issues
- All APIs already include CORS headers
- If still blocked, check browser console

---

## 📞 Support
For viva preparation or debugging, refer to the inline comments in each API file.
