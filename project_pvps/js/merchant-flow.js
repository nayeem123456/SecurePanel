// ================================================
// Merchant Flow JavaScript - FIXED VERSION
// Login, Dashboard, and Payment with Full Error Handling
// ================================================

console.log('✅ Merchant flow script loaded');

document.addEventListener('DOMContentLoaded', function () {
    console.log('✅ Merchant flow DOM loaded');

    // Check which page
    if (document.getElementById('merchantLoginForm')) {
        initMerchantLogin();
    } else if (document.getElementById('merchantDashboard')) {
        initMerchantDashboard();
    }
});

function initMerchantLogin() {
    console.log('✅ Initializing merchant login');

    const loginForm = document.getElementById('merchantLoginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            console.log('📝 Merchant login submitted');

            const merchantId = document.getElementById('loginMerchantId').value.trim();
            const password = document.getElementById('loginPassword').value;
            const submitBtn = document.getElementById('merchantLoginBtn');

            if (!merchantId || !password) {
                alert('❌ Please fill in all fields!');
                return;
            }

            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Authenticating...';

            try {
                console.log('📤 Sending merchant login...');

                const response = await fetch('backend/api/merchant_login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        merchant_id: merchantId,
                        password: password
                    })
                });

                const text = await response.text();
                console.log('📥 Response:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    throw new Error('Server error. Check XAMPP.');
                }

                if (result.success && result.merchant) {
                    alert('✅ Login successful! Redirecting...');
                    showAlert('✅ Login successful!', 'success');

                    const merchantData = {
                        merchantId: result.merchant.merchant_id,
                        shopName: result.merchant.shop_name,
                        phone: result.merchant.phone_number,
                        createdAt: result.merchant.created_at
                    };

                    if (typeof saveSession === 'function') {
                        saveSession('merchant', merchantData);
                    } else {
                        localStorage.setItem('pvps_session', JSON.stringify({
                            type: 'merchant',
                            data: merchantData,
                            timestamp: new Date().toISOString()
                        }));
                    }

                    setTimeout(function () {
                        window.location.href = 'merchant-dashboard.html';
                    }, 1500);
                } else {
                    const errorMsg = result.message || 'Invalid credentials';
                    alert('❌ Login failed: ' + errorMsg);
                    showAlert('❌ ' + errorMsg, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }

            } catch (error) {
                console.error('❌ Error:', error);
                alert('❌ Error: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
}

function initMerchantDashboard() {
    console.log('✅ Initializing merchant dashboard');

    // Check session
    let session;
    try {
        const sessionData = localStorage.getItem('pvps_session');
        session = sessionData ? JSON.parse(sessionData) : null;
    } catch (e) {
        console.error('Session error:', e);
    }

    if (!session || session.type !== 'merchant') {
        alert('❌ Please login first');
        window.location.href = 'merchant-login.html';
        return;
    }

    const merchant = session.data;

    // Display merchant info
    const shopNameEl = document.getElementById('merchantShopName');
    const merchantIdEl = document.getElementById('merchantIdDisplay');
    const merchantPhoneEl = document.getElementById('merchantPhone');
    const enrollmentDateEl = document.getElementById('merchantEnrollmentDate');

    if (shopNameEl) shopNameEl.textContent = merchant.shopName || 'Merchant';
    if (merchantIdEl) merchantIdEl.textContent = merchant.merchantId || 'N/A';
    if (merchantPhoneEl) merchantPhoneEl.textContent = merchant.phone || 'N/A';
    if (enrollmentDateEl) {
        enrollmentDateEl.textContent = merchant.createdAt ? formatDate(merchant.createdAt) : 'N/A';
    }

    // Load transactions
    if (typeof getMerchantTransactions === 'function') {
        loadMerchantTransactions(merchant.merchantId);
    }

    // Logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            localStorage.removeItem('pvps_session');
            alert('✅ Logged out successfully');
            window.location.href = 'merchant-login.html';
        });
    }

    // Payment initiation
    const initPaymentBtn = document.getElementById('initPaymentBtn');
    if (initPaymentBtn) {
        initPaymentBtn.addEventListener('click', async function (e) {
            e.preventDefault();
            console.log('💳 Payment initiated');

            const userPhone = document.getElementById('customerPhone').value.trim();
            const amount = document.getElementById('paymentAmount').value.trim();

            if (!userPhone || !amount) {
                alert('❌ Please fill in all fields!');
                return;
            }

            if (!/^[0-9]{10}$/.test(userPhone)) {
                alert('❌ Phone number must be 10 digits!');
                return;
            }

            if (parseFloat(amount) <= 0) {
                alert('❌ Amount must be greater than 0!');
                return;
            }

            try {
                if (typeof initPalmScanModal !== 'function') {
                    throw new Error('Camera module not loaded');
                }

                await initPalmScanModal({
                    title: 'Payment Authorization',
                    subtitle: `Amount: ₹${amount}`,
                    statusMessage: 'Scanning customer palm...'
                }, async function (success, imageData) {
                    if (success) {
                        // Send payment request to backend with hand detection
                        try {
                            const response = await fetch('backend/api/initiate_payment.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    merchant_id: merchant.merchantId,
                                    user_phone: userPhone,
                                    amount: parseFloat(amount),
                                    palm_image_data: imageData // Send captured image for hand detection
                                })
                            });

                            const text = await response.text();
                            console.log('📥 Payment response:', text);

                            let result;
                            try {
                                result = JSON.parse(text);
                            } catch (e) {
                                throw new Error('Server error. Check XAMPP.');
                            }

                            if (result.success) {
                                // Payment approved!
                                alert(`✅ Payment Approved!\n\nAmount: ₹${amount}\nTransaction ID: ${result.transaction_id}`);
                                showAlert('✅ Payment successful!', 'success');

                                // Save transaction
                                if (typeof saveTransaction === 'function') {
                                    saveTransaction({
                                        id: result.transaction_id,
                                        amount: parseFloat(amount),
                                        merchantId: merchant.merchantId,
                                        merchantName: merchant.shopName,
                                        userPhone: userPhone,
                                        status: 'Approved',
                                        timestamp: new Date().toISOString()
                                    });
                                }

                                // Clear form
                                document.getElementById('customerPhone').value = '';
                                document.getElementById('paymentAmount').value = '';

                                // Reload transactions
                                if (typeof loadMerchantTransactions === 'function') {
                                    loadMerchantTransactions(merchant.merchantId);
                                }
                            } else {
                                // Payment failed - Display prominent error message
                                const errorMessage = result.message || 'Payment processing failed';

                                // Show prominent alert for unregistered mobile numbers
                                alert('🚫 PAYMENT BLOCKED\n\n' + errorMessage);
                                showAlert('🚫 ' + errorMessage, 'error');

                                console.error('Payment failed:', errorMessage);
                            }

                        } catch (error) {
                            console.error('❌ Payment API error:', error);
                            alert('❌ Payment error: ' + error.message);
                            showAlert('❌ Payment failed', 'error');
                        }
                    } else {
                        alert('❌ Palm scan failed or cancelled');
                    }
                });

            } catch (error) {
                console.error('❌ Payment error:', error);
                alert('❌ Error: ' + error.message);
            }
        });
    }
}

function loadMerchantTransactions(merchantId) {
    const transactions = getMerchantTransactions(merchantId);
    const tbody = document.getElementById('merchantTransactionBody');
    if (!tbody) return;

    if (transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:2rem;">No transactions found</td></tr>';
        return;
    }

    transactions.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

    tbody.innerHTML = transactions.slice(0, 10).map(txn => `
        <tr>
            <td>${txn.id}</td>
            <td>${txn.userPhone || 'N/A'}</td>
            <td>${formatCurrency(txn.amount)}</td>
            <td><span class="badge ${txn.status === 'Approved' ? 'badge-success' : 'badge-error'}">${txn.status}</span></td>
            <td>${formatDate(txn.timestamp)}</td>
        </tr>
    `).join('');
}

console.log('✅ Merchant flow script initialized');
