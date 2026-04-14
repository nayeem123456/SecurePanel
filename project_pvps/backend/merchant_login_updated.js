// ================================================
// Merchant Login Updated - Backend Integration
// This file updates ONLY the login function
// ================================================

// ==================== Merchant Login (No Biometrics) - BACKEND VERSION ====================
function initMerchantLogin() {
    const loginForm = document.getElementById('merchantLoginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', async(e) =\u003e {
            e.preventDefault();

            const merchantId = document.getElementById('loginMerchantId').value.trim();
            const password = document.getElementById('loginPassword').value;
            const submitBtn = document.getElementById('merchantLoginBtn');

            // Disable button and show loading state
            submitBtn.disabled = true;
            showButtonLoading(submitBtn, 'Authenticating...');

            try {
                // Call backend login API
                const response = await fetch('backend/api/merchant_login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        merchant_id: merchantId,
                        password: password
                    })
                });

                const result = await response.json();

                if(result.success && result.merchant) {
            // Success - keep button disabled during redirect
            showAlert('Login successful! Redirecting...', 'success');

            // Save session with merchant data
            const merchantData = {
                merchantId: result.merchant.merchant_id,
                shopName: result.merchant.shop_name,
                phone: result.merchant.phone_number
            };
            saveSession('merchant', merchantData);

            setTimeout(() =\u003e {
                window.location.href = 'merchant-dashboard.html';
            }, 1500);
        } else {
            // Failure
            submitBtn.disabled = false;
            hideButtonLoading(submitBtn);
            showAlert(result.message || 'Login failed. Please check your credentials.', 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        submitBtn.disabled = false;
        hideButtonLoading(submitBtn);
        showAlert('❌ Network error. Please ensure XAMPP is running and try again.', 'error');
    }
});
    }
}

// ==================== INSTRUCTIONS ====================
// Replace the initMerchantLogin function in merchant-flow.js with this version
// This integrates with the backend MySQL database via merchant_login.php
