// ================================================
// Theme Management System
// Dark/Light Mode Toggle with localStorage Persistence
// ================================================

(function () {
    'use strict';

    // Get saved theme or default to light
    const getTheme = () => localStorage.getItem('pvps-theme') || 'light';

    // Apply theme to document
    const applyTheme = (theme) => {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('pvps-theme', theme);
        updateToggleIcon(theme);
    };

    // Update the toggle button icon
    const updateToggleIcon = (theme) => {
        const toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) {
            toggleBtn.textContent = theme === 'dark' ? '☀️' : '🌙';
            toggleBtn.setAttribute('aria-label',
                theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'
            );
        }
    };

    // Toggle between themes
    const toggleTheme = () => {
        const currentTheme = getTheme();
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        applyTheme(newTheme);
    };

    // Apply theme immediately on page load (prevent flash)
    applyTheme(getTheme());

    // Initialize theme toggle button when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('themeToggle');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleTheme);
            updateToggleIcon(getTheme());
        }
    });

    // Make toggle function globally available
    window.toggleTheme = toggleTheme;
})();
