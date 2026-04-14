// ================================================
// Bank Enrollment Page JavaScript - FIXED VERSION
// User and Merchant Registration with ALL Validations
// ================================================

console.log('✅ Enrollment script loaded successfully');

let palmScanned = false;
let capturedPalmImage = null;

document.addEventListener('DOMContentLoaded', function () {
    console.log('✅ DOM Content Loaded - Initializing enrollment page');

    // ==================== PALM SCAN BUTTON ====================
    const palmScanBtn = document.getElementById('palmScanBtn');

    if (palmScanBtn) {
        console.log('✅ Palm scan button found');

        palmScanBtn.addEventListener('click', async function () {
            console.log('🖐️ Palm scan button clicked!');

            // Check if camera API is available
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('❌ Camera not supported in this browser. Please use Chrome, Firefox, or Edge.');
                console.error('Camera API not available');
                return;
            }

            console.log('✅ Camera API available');

            // Disable button
            palmScanBtn.disabled = true;
            palmScanBtn.textContent = '⏳ Opening camera...';

            try {
                // Check if initPalmScanModal exists
                if (typeof initPalmScanModal !== 'function') {
                    throw new Error('Camera module not loaded. Please refresh the page.');
                }

                console.log('📸 Calling initPalmScanModal...');

                await initPalmScanModal({
                    title: 'Biometric Enrollment',
                    subtitle: 'Please place your palm in front of the camera',
                    statusMessage: 'Capturing biometric template...'
                }, function (success, imageData) {
                    console.log('Palm scan result:', success);

                    if (success) {
                        palmScanned = true;
                        capturedPalmImage = imageData; // Store captured image
                        palmScanBtn.textContent = '✅ Palm Scan Complete';
                        palmScanBtn.style.backgroundColor = '#4CAF50';
                        palmScanBtn.style.color = 'white';
                        showAlert('✅ Palm vein pattern captured successfully!', 'success');
                    } else {
                        palmScanBtn.disabled = false;
                        palmScanBtn.textContent = '🔄 Retry Palm Scan';
                        palmScanBtn.style.backgroundColor = '';
                        showAlert('❌ Palm scan failed. Please try again.', 'error');
                    }
                });

            } catch (error) {
                console.error('❌ Palm scan error:', error);
                palmScanBtn.disabled = false;
                palmScanBtn.textContent = '🔄 Retry Palm Scan';
                palmScanBtn.style.backgroundColor = '';
                alert('❌ Error: ' + error.message);
            }
        });
    } else {
        console.warn('⚠️ Palm scan button not found on this page');
    }

    // ==================== USER ENROLLMENT FORM ====================
    const userForm = document.getElementById('userEnrollmentForm');

    if (userForm) {
        console.log('✅ User enrollment form found');

        const userPassword = document.getElementById('userPassword');
        const userConfirmPassword = document.getElementById('userConfirmPassword');
        const userPhone = document.getElementById('userPhone');

        // Real-time password match validation
        if (userConfirmPassword && userPassword) {
            userConfirmPassword.addEventListener('input', function () {
                if (userConfirmPassword.value && userPassword.value !== userConfirmPassword.value) {
                    userConfirmPassword.style.borderColor = '#f44336';
                    showFieldError(userConfirmPassword, '❌ Passwords do not match');
                } else {
                    userConfirmPassword.style.borderColor = '#4CAF50';
                    hideFieldError(userConfirmPassword);
                }
            });

            userPassword.addEventListener('input', function () {
                if (userConfirmPassword.value) {
                    if (userPassword.value !== userConfirmPassword.value) {
                        userConfirmPassword.style.borderColor = '#f44336';
                        showFieldError(userConfirmPassword, '❌ Passwords do not match');
                    } else {
                        userConfirmPassword.style.borderColor = '#4CAF50';
                        hideFieldError(userConfirmPassword);
                    }
                }
            });
        }

        // Real-time phone validation
        if (userPhone) {
            userPhone.addEventListener('input', function () {
                const phone = userPhone.value.trim();
                if (phone.length > 0 && phone.length < 10) {
                    userPhone.style.borderColor = '#f44336';
                    showFieldError(userPhone, '❌ Phone number must be exactly 10 digits');
                } else if (phone.length === 10 && !/^[0-9]{10}$/.test(phone)) {
                    userPhone.style.borderColor = '#f44336';
                    showFieldError(userPhone, '❌ Phone number must contain only digits');
                } else if (phone.length === 10) {
                    userPhone.style.borderColor = '#4CAF50';
                    hideFieldError(userPhone);
                } else {
                    userPhone.style.borderColor = '';
                    hideFieldError(userPhone);
                }
            });
        }

        // Real-time Account ID validation with ADVANCED BANKING RULES
        const userAccount = document.getElementById('userAccount');
        if (userAccount) {
            userAccount.addEventListener('input', function () {
                const accountId = userAccount.value.trim();

                // Remove any non-numeric characters automatically
                if (/[^0-9]/.test(accountId)) {
                    userAccount.value = accountId.replace(/[^0-9]/g, '');
                    return;
                }

                // Validate length first
                if (accountId.length > 18) {
                    userAccount.value = accountId.substring(0, 18); // Trim to max length
                    return;
                }

                // Basic length validation
                if (accountId.length > 0 && accountId.length < 11) {
                    userAccount.style.borderColor = '#f44336';
                    showFieldError(userAccount, '❌ Account number must be at least 11 digits');
                    return;
                }

                // Advanced validation for complete account numbers (11+ digits)
                if (accountId.length >= 11) {
                    const validationResult = validateBankAccountNumber(accountId);

                    if (!validationResult.valid) {
                        userAccount.style.borderColor = '#f44336';
                        showFieldError(userAccount, '❌ ' + validationResult.message);
                    } else {
                        userAccount.style.borderColor = '#4CAF50';
                        hideFieldError(userAccount);
                    }
                } else {
                    userAccount.style.borderColor = '';
                    hideFieldError(userAccount);
                }
            });
        }



        // Form submission
        userForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            console.log('📝 User form submitted');

            // Validate palm scan
            if (!palmScanned) {
                showAlert('❌ Please complete palm vein scan before enrollment.', 'error');
                alert('❌ Please complete palm vein scan first!');
                return;
            }

            // Get values
            const fullName = document.getElementById('userName').value.trim();
            const phone = userPhone.value.trim();
            const accountId = document.getElementById('userAccount').value.trim();
            const password = userPassword.value;
            const confirmPassword = userConfirmPassword.value;

            // Validate phone
            if (!/^[0-9]{10}$/.test(phone)) {
                showAlert('❌ Phone number must be exactly 10 digits', 'error');
                alert('❌ Phone number must be exactly 10 digits!');
                userPhone.focus();
                return;
            }

            // Validate Account ID with ADVANCED BANKING RULES
            if (!accountId || accountId.length < 11 || accountId.length > 18) {
                showAlert('❌ Account number must be between 11 and 18 digits', 'error');
                alert('❌ Account number must be between 11 and 18 digits!');
                document.getElementById('userAccount').focus();
                return;
            }

            if (!/^[0-9]+$/.test(accountId)) {
                showAlert('❌ Account number must contain only numeric digits', 'error');
                alert('❌ Account number must contain only numeric digits!');
                document.getElementById('userAccount').focus();
                return;
            }

            // Advanced pattern validation
            const accountValidation = validateBankAccountNumber(accountId);
            if (!accountValidation.valid) {
                showAlert('❌ ' + accountValidation.message, 'error');
                alert('❌ ' + accountValidation.message);
                document.getElementById('userAccount').focus();
                return;
            }



            // Validate password match
            if (password !== confirmPassword) {
                showAlert('❌ Passwords do not match!', 'error');
                alert('❌ Passwords do not match!');
                userConfirmPassword.focus();
                return;
            }

            if (password.length < 6) {
                showAlert('❌ Password must be at least 6 characters', 'error');
                alert('❌ Password must be at least 6 characters!');
                userPassword.focus();
                return;
            }

            // Submit button
            const submitBtn = userForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Registering...';

            try {
                console.log('📤 Sending registration to backend...');

                const response = await fetch('backend/api/user_register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        full_name: fullName,
                        phone_number: phone,
                        account_id: accountId,
                        password: password,
                        biometric_template: 'BIOMETRIC_' + Date.now(),
                        palm_image_data: capturedPalmImage // Send captured image for hand detection
                    })
                });

                const text = await response.text();
                console.log('📥 Server response:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('❌ Invalid JSON response:', text);
                    throw new Error('Server error. Please check if XAMPP is running.');
                }

                if (result.success) {
                    // SUCCESS!
                    const successMsg = `
                        ✅ Registration Successful!
                        
                        📱 Phone: ${phone}
                        🏦 Account ID: ${accountId}
                        👤 Name: ${fullName}
                        
                        Your account has been saved to the database!
                        Redirecting to login page...
                    `;

                    alert(successMsg);
                    showAlert('✅ User registered successfully!', 'success');

                    userForm.reset();
                    palmScanned = false;
                    capturedPalmImage = null; // Clear captured image
                    palmScanBtn.disabled = false;
                    palmScanBtn.textContent = 'Initiate Palm Scan';
                    palmScanBtn.style.backgroundColor = '';

                    setTimeout(() => {
                        window.location.href = 'user-login.html';
                    }, 2000);
                } else {
                    throw new Error(result.message || 'Registration failed');
                }

            } catch (error) {
                console.error('❌ Registration error:', error);
                alert('❌ Error: ' + error.message);
                showAlert('❌ ' + error.message, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }

    // ==================== MERCHANT ENROLLMENT FORM ====================
    const merchantForm = document.getElementById('merchantEnrollmentForm');

    if (merchantForm) {
        console.log('✅ Merchant enrollment form found');

        const merchantPassword = document.getElementById('merchantPassword');
        const merchantConfirmPassword = document.getElementById('merchantConfirmPassword');
        const merchantPhone = document.getElementById('merchantPhone');

        // Real-time password validation
        if (merchantConfirmPassword && merchantPassword) {
            merchantConfirmPassword.addEventListener('input', function () {
                if (merchantConfirmPassword.value && merchantPassword.value !== merchantConfirmPassword.value) {
                    merchantConfirmPassword.style.borderColor = '#f44336';
                    showFieldError(merchantConfirmPassword, '❌ Passwords do not match');
                } else {
                    merchantConfirmPassword.style.borderColor = '#4CAF50';
                    hideFieldError(merchantConfirmPassword);
                }
            });
        }

        // Real-time phone validation
        if (merchantPhone) {
            merchantPhone.addEventListener('input', function () {
                const phone = merchantPhone.value.trim();
                if (phone.length > 0 && phone.length < 10) {
                    merchantPhone.style.borderColor = '#f44336';
                    showFieldError(merchantPhone, '❌ Phone number must be exactly 10 digits');
                } else if (phone.length === 10) {
                    merchantPhone.style.borderColor = '#4CAF50';
                    hideFieldError(merchantPhone);
                }
            });
        }

        // Form submission
        merchantForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            console.log('📝 Merchant form submitted');

            const shopName = document.getElementById('shopName').value.trim();
            const phone = merchantPhone.value.trim();
            const password = merchantPassword.value;
            const confirmPassword = merchantConfirmPassword.value;

            // Validate
            if (!/^[0-9]{10}$/.test(phone)) {
                alert('❌ Phone number must be exactly 10 digits!');
                return;
            }

            if (password !== confirmPassword) {
                alert('❌ Passwords do not match!');
                return;
            }

            if (password.length < 6) {
                alert('❌ Password must be at least 6 characters!');
                return;
            }

            const submitBtn = merchantForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Registering...';

            try {
                const response = await fetch('backend/api/merchant_register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        shop_name: shopName,
                        phone_number: phone,
                        password: password
                    })
                });

                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    throw new Error('Server error. Check XAMPP.');
                }

                if (result.success) {
                    const merchantId = result.merchant_id;

                    // Show success inline display
                    const merchantIdDisplayDiv = document.getElementById('merchantIdDisplay');
                    const generatedMerchantIdEl = document.getElementById('generatedMerchantId');

                    if (generatedMerchantIdEl) {
                        generatedMerchantIdEl.textContent = merchantId;
                    }

                    if (merchantIdDisplayDiv) {
                        merchantIdDisplayDiv.style.display = 'block';
                    }

                    // Setup copy button
                    const copyBtn = document.getElementById('copyMerchantIdBtn');
                    if (copyBtn) {
                        copyBtn.onclick = function () {
                            navigator.clipboard.writeText(merchantId).then(function () {
                                copyBtn.textContent = '✅ Copied!';
                                copyBtn.style.backgroundColor = '#4CAF50';
                                setTimeout(function () {
                                    copyBtn.textContent = '📋 Copy Merchant ID';
                                    copyBtn.style.backgroundColor = '';
                                }, 2000);
                            }).catch(function (err) {
                                alert('Failed to copy: ' + err);
                            });
                        };
                    }

                    showAlert('✅ Merchant registered successfully!', 'success');

                    // Disable form and scroll to success message
                    submitBtn.style.display = 'none';
                    merchantForm.querySelectorAll('input').forEach(function (input) {
                        input.disabled = true;
                    });

                    merchantIdDisplayDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // User must manually navigate to login (no auto-redirect)
                } else {
                    throw new Error(result.message || 'Registration failed');
                }

            } catch (error) {
                console.error('❌ Error:', error);
                alert('❌ Error: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});

// ==================== HELPER FUNCTIONS ====================
function showFieldError(input, message) {
    let errorEl = input.parentElement.querySelector('.field-error');
    if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.className = 'field-error';
        errorEl.style.color = '#f44336';
        errorEl.style.fontSize = '12px';
        errorEl.style.marginTop = '5px';
        input.parentElement.appendChild(errorEl);
    }
    errorEl.textContent = message;
}

function hideFieldError(input) {
    const errorEl = input.parentElement.querySelector('.field-error');
    if (errorEl) {
        errorEl.remove();
    }
}

// ==================== SIMPLIFIED BANK ACCOUNT VALIDATION ====================
/**
 * Validates bank account number with basic rules
 * - Length: 11-18 digits
 * - Only numeric characters
 * - No all-same repetitive numbers (e.g., 111111111111)
 * @param {string} accountNumber - The account number to validate
 * @returns {object} - {valid: boolean, message: string}
 */
function validateBankAccountNumber(accountNumber) {
    // Rule 1: Check for all same digits (e.g., 11111111111, 22222222222)
    if (/^(\d)\1+$/.test(accountNumber)) {
        return {
            valid: false,
            message: 'Invalid account number: Cannot contain all identical digits (e.g., 111111111111)'
        };
    }

    // All validations passed
    return {
        valid: true,
        message: 'Valid account number'
    };
}


console.log('✅ Enrollment script fully initialized');


