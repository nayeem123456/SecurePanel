// ================================================
// User Flow JavaScript - FIXED VERSION
// Login and Dashboard with Full Error Handling
// ================================================

console.log('✅ User flow script loaded');

let palmScanned = false;
let capturedPalmImage = null;

document.addEventListener('DOMContentLoaded', function () {
    console.log('✅ User flow DOM loaded');

    // ==================== USER LOGIN PAGE ====================
    const loginForm = document.getElementById('userLoginForm');
    const palmScanBtn = document.getElementById('palmScanLoginBtn');

    if (palmScanBtn) {
        console.log('✅ Palm scan login button found');

        palmScanBtn.addEventListener('click', async function () {
            console.log('🖐️ Login palm scan clicked');

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('❌ Camera not supported in this browser');
                return;
            }

            palmScanBtn.disabled = true;
            palmScanBtn.textContent = '⏳ Opening camera...';

            try {
                if (typeof initPalmScanModal !== 'function') {
                    throw new Error('Camera module not loaded');
                }

                await initPalmScanModal({
                    title: 'User Authentication',
                    subtitle: 'Scan your palm to login',
                    statusMessage: 'Verifying biometric identity...'
                }, function (success, imageData) {
                    if (success) {
                        palmScanned = true;
                        capturedPalmImage = imageData; // Store captured image
                        palmScanBtn.textContent = '✅ Palm Scanned';
                        palmScanBtn.style.backgroundColor = '#4CAF50';
                        palmScanBtn.style.color = 'white';
                        showAlert('✅ Biometric verification successful!', 'success');
                    } else {
                        palmScanBtn.disabled = false;
                        palmScanBtn.textContent = '🔄 Retry Palm Scan';
                        palmScanBtn.style.backgroundColor = '';
                        showAlert('❌ Palm scan failed', 'error');
                    }
                });
            } catch (error) {
                console.error('❌ Palm scan error:', error);
                palmScanBtn.disabled = false;
                palmScanBtn.textContent = '🔄 Retry Palm Scan';
                alert('❌ Error: ' + error.message);
            }
        });
    }

    if (loginForm) {
        console.log('✅ User login form found');

        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            console.log('📝 User login form submitted');

            if (!palmScanned) {
                alert('❌ Please complete palm scan first!');
                showAlert('❌ Palm scan required', 'error');
                return;
            }

            const phone = document.getElementById('loginPhone').value.trim();
            const password = document.getElementById('loginPassword').value;
            const submitBtn = document.getElementById('loginSubmitBtn');

            // Validate
            if (!/^[0-9]{10}$/.test(phone)) {
                alert('❌ Phone number must be 10 digits!');
                return;
            }

            if (!password) {
                alert('❌ Please enter password!');
                return;
            }

            // Show loading
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Logging in...';

            try {
                console.log('📤 Sending login request...');

                const response = await fetch('backend/api/user_login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        phone_number: phone,
                        password: password,
                        palm_image_data: capturedPalmImage // Send captured image for hand detection
                    })
                });

                const text = await response.text();
                console.log('📥 Server response:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    throw new Error('Server error. Check if XAMPP is running.');
                }

                if (result.success && result.user) {
                    // SUCCESS!
                    alert('✅ Login successful! Redirecting...');
                    showAlert('✅ Login successful!', 'success');

                    const userData = {
                        fullName: result.user.full_name,
                        phone: result.user.phone_number,
                        accountId: result.user.account_id,
                        userId: result.user.user_id,
                        createdAt: result.user.created_at
                    };

                    if (typeof saveSession === 'function') {
                        saveSession('user', userData);
                    } else {
                        localStorage.setItem('pvps_session', JSON.stringify({
                            type: 'user',
                            data: userData,
                            timestamp: new Date().toISOString()
                        }));
                    }

                    setTimeout(function () {
                        window.location.href = 'user-dashboard.html';
                    }, 1500);
                } else {
                    // FAILURE
                    const errorMsg = result.message || 'Invalid credentials';
                    alert('❌ Login failed: ' + errorMsg);
                    showAlert('❌ ' + errorMsg, 'error');

                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;

                    // Reset palm scan
                    palmScanned = false;
                    capturedPalmImage = null; // Clear captured image
                    if (palmScanBtn) {
                        palmScanBtn.textContent = '🖐️ Scan Palm to Authenticate';
                        palmScanBtn.style.backgroundColor = '';
                        palmScanBtn.disabled = false;
                    }
                }

            } catch (error) {
                console.error('❌ Login error:', error);
                alert('❌ Error: ' + error.message);
                showAlert('❌ Network error', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }

    // ==================== USER DASHBOARD ====================
    const dashboardEl = document.getElementById('userDashboard');

    if (dashboardEl) {
        console.log('✅ User dashboard found');

        // Check login
        let session;
        try {
            const sessionData = localStorage.getItem('pvps_session');
            session = sessionData ? JSON.parse(sessionData) : null;
        } catch (e) {
            console.error('Session parse error:', e);
        }

        if (!session || session.type !== 'user') {
            alert('❌ Please login first');
            window.location.href = 'user-login.html';
            return;
        }

        const user = session.data;

        // Display user info
        const elements = {
            welcomeName: document.getElementById('welcomeName'),
            userFullName: document.getElementById('userFullName'),
            userPhone: document.getElementById('userPhone'),
            userAccount: document.getElementById('userAccount'),
            enrollmentDate: document.getElementById('enrollmentDate')
        };

        if (elements.welcomeName) elements.welcomeName.textContent = user.fullName || 'User';
        if (elements.userFullName) elements.userFullName.textContent = user.fullName || 'N/A';
        if (elements.userPhone) elements.userPhone.textContent = user.phone || 'N/A';
        if (elements.userAccount) elements.userAccount.textContent = user.accountId || 'N/A';
        if (elements.enrollmentDate) {
            elements.enrollmentDate.textContent = user.createdAt ? formatDate(user.createdAt) : 'N/A';
        }

        // Load transactions
        if (typeof getUserTransactions === 'function') {
            loadUserTransactions(user.phone);
        }

        // Logout
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function () {
                localStorage.removeItem('pvps_session');
                alert('✅ Logged out successfully');
                window.location.href = 'index.html';
            });
        }
    }
});

function loadUserTransactions(phone) {
    const transactions = getUserTransactions(phone);
    const tbody = document.getElementById('transactionTableBody');
    if (!tbody) return;

    if (transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:2rem;">No transactions yet</td></tr>';
        return;
    }

    transactions.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

    tbody.innerHTML = transactions.map(txn => `
        <tr>
            <td>${txn.id}</td>
            <td>${txn.merchantName || 'Merchant'}</td>
            <td>${formatCurrency(txn.amount)}</td>
            <td><span class="badge ${txn.status === 'Approved' ? 'badge-success' : 'badge-error'}">${txn.status}</span></td>
            <td>${formatDate(txn.timestamp)}</td>
        </tr>
    `).join('');
}

console.log('✅ User flow script initialized');
