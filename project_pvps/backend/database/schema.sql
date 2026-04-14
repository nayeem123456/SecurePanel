-- ================================================
-- PVPS Database Schema
-- Palm Vein Payment System - Academic Project
-- ================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS pvps_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE pvps_db;

-- ================================================
-- Table: users
-- Purpose: Store user registration and biometric data
-- ================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15) NOT NULL UNIQUE,
    account_id VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    biometric_template TEXT,
    palm_image_path VARCHAR(255),
    palm_registered BOOLEAN DEFAULT FALSE,
    last_palm_scan TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone_number),
    INDEX idx_account (account_id),
    INDEX idx_palm_registered (palm_registered)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Table: palm_scans
-- Purpose: Store palm scan images and biometric data
-- ================================================
CREATE TABLE IF NOT EXISTS palm_scans (
    scan_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    scan_type ENUM('registration', 'authentication', 'transaction') NOT NULL DEFAULT 'registration',
    image_path VARCHAR(255) NOT NULL,
    image_hash VARCHAR(64) NOT NULL,
    biometric_signature TEXT NOT NULL,
    confidence_score DECIMAL(5, 4) NOT NULL,
    is_valid BOOLEAN DEFAULT TRUE,
    scan_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_scan_type (scan_type),
    INDEX idx_timestamp (scan_timestamp),
    INDEX idx_image_hash (image_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Table: palm_analytics
-- Purpose: Store deep learning analysis results
-- ================================================
CREATE TABLE IF NOT EXISTS palm_analytics (
    analytics_id INT AUTO_INCREMENT PRIMARY KEY,
    scan_id INT NOT NULL,
    vein_pattern_score DECIMAL(5, 4),
    palm_lines_score DECIMAL(5, 4),
    skin_texture_score DECIMAL(5, 4),
    finger_geometry_score DECIMAL(5, 4),
    palm_shape_score DECIMAL(5, 4),
    image_quality_score DECIMAL(5, 4),
    overall_confidence DECIMAL(5, 4) NOT NULL,
    analysis_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scan_id) REFERENCES palm_scans(scan_id) ON DELETE CASCADE,
    INDEX idx_scan (scan_id),
    INDEX idx_confidence (overall_confidence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Table: palm_matches
-- Purpose: Track palm matching attempts for authentication
-- ================================================
CREATE TABLE IF NOT EXISTS palm_matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    scan_id INT NOT NULL,
    reference_scan_id INT NOT NULL,
    match_score DECIMAL(5, 4) NOT NULL,
    is_match BOOLEAN NOT NULL,
    match_type ENUM('login', 'transaction', 'verification') NOT NULL,
    match_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (scan_id) REFERENCES palm_scans(scan_id) ON DELETE CASCADE,
    FOREIGN KEY (reference_scan_id) REFERENCES palm_scans(scan_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_match_type (match_type),
    INDEX idx_timestamp (match_timestamp),
    INDEX idx_is_match (is_match)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Table: merchants
-- Purpose: Store merchant credentials and profile
-- ================================================
CREATE TABLE IF NOT EXISTS merchants (
    merchant_id VARCHAR(50) PRIMARY KEY,
    shop_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Table: transactions
-- Purpose: Store payment transaction records
-- ================================================
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id VARCHAR(50) PRIMARY KEY,
    merchant_id VARCHAR(50) NOT NULL,
    user_phone VARCHAR(15) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants(merchant_id) ON DELETE CASCADE,
    INDEX idx_merchant (merchant_id),
    INDEX idx_user_phone (user_phone),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Insert Demo Data (for testing)
-- ================================================

-- Demo User (password: user123)
INSERT INTO users (full_name, phone_number, account_id, password_hash, biometric_template) 
VALUES (
    'John Doe',
    '9876543210',
    'ACC123456789',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'SIMULATED_BIOMETRIC_TEMPLATE_12345'
) ON DUPLICATE KEY UPDATE full_name = full_name;

-- Demo Merchant (password: merchant123)
INSERT INTO merchants (merchant_id, shop_name, phone_number, password_hash) 
VALUES (
    'MER987654321',
    'SuperMart',
    '9123456789',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
) ON DUPLICATE KEY UPDATE shop_name = shop_name;

-- Demo Transactions
INSERT INTO transactions (transaction_id, merchant_id, user_phone, amount, status, created_at) 
VALUES 
    ('TXN1001', 'MER987654321', '9876543210', 1250.00, 'APPROVED', DATE_SUB(NOW(), INTERVAL 1 DAY)),
    ('TXN1002', 'MER987654321', '9876543210', 3500.50, 'APPROVED', DATE_SUB(NOW(), INTERVAL 2 DAY))
ON DUPLICATE KEY UPDATE status = status;

-- ================================================
-- Verification Queries
-- ================================================

-- Count records
SELECT 'Users' as Table_Name, COUNT(*) as Record_Count FROM users
UNION ALL
SELECT 'Merchants', COUNT(*) FROM merchants
UNION ALL
SELECT 'Transactions', COUNT(*) FROM transactions;
