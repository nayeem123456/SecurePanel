// ================================================
// Common JavaScript Functions
// Shared utilities for all pages
// ================================================

// ==================== Mobile Menu Toggle ====================
function initMobileMenu() {
  const menuToggle = document.querySelector('.mobile-menu-toggle');
  const nav = document.querySelector('.nav');

  if (menuToggle && nav) {
    menuToggle.addEventListener('click', () => {
      nav.classList.toggle('active');

      // Update icon
      const icon = menuToggle.querySelector('span');
      if (icon) {
        icon.textContent = nav.classList.contains('active') ? '✕' : '☰';
      }
    });

    // Close menu when clicking on a link
    const navLinks = nav.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        nav.classList.remove('active');
        const icon = menuToggle.querySelector('span');
        if (icon) icon.textContent = '☰';
      });
    });
  }
}

// ==================== LocalStorage Helpers ====================

// Save user data
function saveUser(userData) {
  const users = getUsers();
  users.push(userData);
  localStorage.setItem('pvps_users', JSON.stringify(users));
}

// Get all users
function getUsers() {
  const users = localStorage.getItem('pvps_users');
  return users ? JSON.parse(users) : [];
}

// Find user by phone
function findUserByPhone(phone) {
  const users = getUsers();
  return users.find(user => user.phone === phone);
}

// Save merchant data
function saveMerchant(merchantData) {
  const merchants = getMerchants();
  merchants.push(merchantData);
  localStorage.setItem('pvps_merchants', JSON.stringify(merchants));
}

// Get all merchants
function getMerchants() {
  const merchants = localStorage.getItem('pvps_merchants');
  return merchants ? JSON.parse(merchants) : [];
}

// Find merchant by ID
function findMerchantById(merchantId) {
  const merchants = getMerchants();
  return merchants.find(merchant => merchant.merchantId === merchantId);
}

// Save current session
function saveSession(type, data) {
  const session = {
    type: type, // 'user' or 'merchant'
    data: data,
    timestamp: new Date().toISOString()
  };
  localStorage.setItem('pvps_session', JSON.stringify(session));
}

// Get current session
function getSession() {
  const session = localStorage.getItem('pvps_session');
  return session ? JSON.parse(session) : null;
}

// Check if logged in
function isLoggedIn() {
  return getSession() !== null;
}

// Logout
function logout() {
  localStorage.removeItem('pvps_session');
  window.location.href = 'index.html';
}

// ==================== Transaction Management ====================

// Save transaction
function saveTransaction(transaction) {
  const transactions = getTransactions();
  // Only generate ID and timestamp if they don't exist
  if (!transaction.id) {
    transaction.id = 'TXN' + Date.now();
  }
  if (!transaction.timestamp) {
    transaction.timestamp = new Date().toISOString();
  }
  transactions.push(transaction);
  localStorage.setItem('pvps_transactions', JSON.stringify(transactions));
  return transaction;
}

// Get all transactions
function getTransactions() {
  const transactions = localStorage.getItem('pvps_transactions');
  return transactions ? JSON.parse(transactions) : [];
}

// Get user transactions
function getUserTransactions(phone) {
  const transactions = getTransactions();
  return transactions.filter(txn => txn.userPhone === phone);
}

// Get merchant transactions
function getMerchantTransactions(merchantId) {
  const transactions = getTransactions();
  return transactions.filter(txn => txn.merchantId === merchantId);
}

// Update transaction status
function updateTransactionStatus(transactionId, status) {
  const transactions = getTransactions();
  const transaction = transactions.find(txn => txn.id === transactionId);
  if (transaction) {
    transaction.status = status;
    localStorage.setItem('pvps_transactions', JSON.stringify(transactions));
  }
  return transaction;
}

// ==================== UI Helpers ====================

// Show alert message
function showAlert(message, type = 'info') {
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type}`;
  alertDiv.textContent = message;

  const container = document.querySelector('.container') || document.body;
  container.insertBefore(alertDiv, container.firstChild);

  // Auto remove after 5 seconds
  setTimeout(() => {
    alertDiv.remove();
  }, 5000);
}

// Format currency
function formatCurrency(amount) {
  return '₹' + parseFloat(amount).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

// Format date
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-IN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Generate random account ID
function generateAccountId() {
  return 'ACC' + Math.random().toString(36).substr(2, 9).toUpperCase();
}

// Generate random merchant ID
function generateMerchantId() {
  const randomCode = Math.random().toString(36).substr(2, 5).toUpperCase();
  return 'PVPS-MER-' + randomCode;
}

// ==================== Form Validation ====================

// Validate phone number (10 digits)
function validatePhone(phone) {
  const phoneRegex = /^[0-9]{10}$/;
  return phoneRegex.test(phone);
}

// Validate required fields
function validateRequired(value) {
  return value && value.trim() !== '';
}

// Validate password match
function validatePasswordMatch(password, confirmPassword) {
  return password === confirmPassword && password.length >= 6;
}

// ==================== Password Toggle Functionality ====================

// Initialize password toggles
function initPasswordToggles() {
  const passwordWrappers = document.querySelectorAll('.password-wrapper');

  passwordWrappers.forEach(wrapper => {
    const input = wrapper.querySelector('.form-input');
    const toggle = wrapper.querySelector('.password-toggle');

    if (input && toggle) {
      toggle.addEventListener('click', () => {
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        toggle.textContent = type === 'password' ? '👁️' : '🙈';
      });
    }
  });
}

// ==================== Loading State Management ====================

// Show loading state on button
function showButtonLoading(button, loadingText = 'Processing...') {
  if (!button) return;

  button.setAttribute('data-original-text', button.textContent);
  button.classList.add('btn-loading');
  button.disabled = true;

  const spinner = document.createElement('span');
  spinner.className = 'spinner';
  button.textContent = loadingText;
  button.prepend(spinner);
}

// Hide loading state on button
function hideButtonLoading(button) {
  if (!button) return;

  button.classList.remove('btn-loading');
  button.disabled = false;

  const originalText = button.getAttribute('data-original-text');
  if (originalText) {
    const spinner = button.querySelector('.spinner');
    if (spinner) spinner.remove();
    button.textContent = originalText;
  }
}

// ==================== Copy to Clipboard ====================

// Copy text to clipboard
function copyToClipboard(text, successCallback) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(() => {
      if (successCallback) successCallback();
    }).catch(err => {
      console.error('Failed to copy:', err);
    });
  } else {
    // Fallback for older browsers
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    try {
      document.execCommand('copy');
      if (successCallback) successCallback();
    } catch (err) {
      console.error('Failed to copy:', err);
    }
    document.body.removeChild(textArea);
  }
}


// ==================== Page Guards ====================

// Require user login
function requireUserLogin() {
  const session = getSession();
  if (!session || session.type !== 'user') {
    window.location.href = 'user-login.html';
    return false;
  }
  return true;
}

// Require merchant login
function requireMerchantLogin() {
  const session = getSession();
  if (!session || session.type !== 'merchant') {
    window.location.href = 'merchant-login.html';
    return false;
  }
  return true;
}

// ==================== Initialize Demo Data ====================
function initDemoData() {
  // Check if demo data already exists
  if (localStorage.getItem('pvps_demo_initialized')) {
    return;
  }

  // Create demo user
  const demoUser = {
    fullName: 'John Doe',
    phone: '9876543210',
    accountId: 'ACC123456789',
    palmScanned: true,
    enrollmentDate: new Date().toISOString()
  };
  saveUser(demoUser);

  // Create demo merchant
  const demoMerchant = {
    shopName: 'SuperMart',
    merchantId: 'MER987654321',
    phone: '9123456789',
    password: 'merchant123'
  };
  saveMerchant(demoMerchant);

  // Create demo transactions
  const demoTransactions = [
    {
      id: 'TXN1001',
      userPhone: '9876543210',
      merchantId: 'MER987654321',
      merchantName: 'SuperMart',
      amount: 1250.00,
      status: 'Approved',
      timestamp: new Date(Date.now() - 86400000).toISOString()
    },
    {
      id: 'TXN1002',
      userPhone: '9876543210',
      merchantId: 'MER987654321',
      merchantName: 'Fashion Store',
      amount: 3500.50,
      status: 'Approved',
      timestamp: new Date(Date.now() - 172800000).toISOString()
    }
  ];

  demoTransactions.forEach(txn => {
    const transactions = getTransactions();
    transactions.push(txn);
    localStorage.setItem('pvps_transactions', JSON.stringify(transactions));
  });

  // Mark demo as initialized
  localStorage.setItem('pvps_demo_initialized', 'true');
}

// ==================== Initialize on Page Load ====================
document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initDemoData();
  initPasswordToggles();
});
