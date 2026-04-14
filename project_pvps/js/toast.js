// ================================================
// Custom Toast Notification System
// Beautiful in-page notifications
// ================================================

(function () {
    'use strict';

    // Create toast container on page load
    let toastContainer = null;

    function initToastContainer() {
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }
        return toastContainer;
    }

    // Show toast notification
    function showToast(message, type = 'info', duration = 5000) {
        const container = initToastContainer();

        // Icon mapping
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        // Title mapping
        const titles = {
            success: 'Success',
            error: 'Error',
            warning: 'Warning',
            info: 'Information'
        };

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                <div class="toast-title">${titles[type] || titles.info}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" aria-label="Close">×</button>
            <div class="toast-progress"></div>
        `;

        // Add to container
        container.appendChild(toast);

        // Close button handler
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => {
            removeToast(toast);
        });

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                removeToast(toast);
            }, duration);
        }

        return toast;
    }

    // Remove toast with animation
    function removeToast(toast) {
        toast.classList.add('removing');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        }, 300);
    }

    // Override native alert() function
    window.alert = function (message) {
        // Determine type based on message content
        let type = 'info';
        if (message.includes('✅') || message.toLowerCase().includes('success')) {
            type = 'success';
        } else if (message.includes('❌') || message.toLowerCase().includes('error') || message.toLowerCase().includes('failed')) {
            type = 'error';
        } else if (message.includes('⚠️') || message.toLowerCase().includes('warning')) {
            type = 'warning';
        }

        // Clean up emoji from message if present
        message = message.replace(/^[✅❌⚠️ℹ️]\s*/, '');

        showToast(message, type);
    };

    // Custom showAlert function (better than alert)
    window.showAlert = function (message, type = 'info', duration = 5000) {
        // Clean up emoji from message if present
        message = message.replace(/^[✅❌⚠️ℹ️]\s*/, '');

        showToast(message, type, duration);
    };

    // Success notification
    window.showSuccess = function (message, duration = 5000) {
        message = message.replace(/^[✅❌⚠️ℹ️]\s*/, '');
        showToast(message, 'success', duration);
    };

    // Error notification
    window.showError = function (message, duration = 7000) {
        message = message.replace(/^[✅❌⚠️ℹ️]\s*/, '');
        showToast(message, 'error', duration);
    };

    // Warning notification
    window.showWarning = function (message, duration = 6000) {
        message = message.replace(/^[✅❌⚠️ℹ️]\s*/, '');
        showToast(message, 'warning', duration);
    };

    // Info notification
    window.showInfo = function (message, duration = 5000) {
        message = message.replace(/^[✅❌⚠️ℹ️]\s*/, '');
        showToast(message, 'info', duration);
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToastContainer);
    } else {
        initToastContainer();
    }

    console.log('✅ Toast notification system loaded');
})();
