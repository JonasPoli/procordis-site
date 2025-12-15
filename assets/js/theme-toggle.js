// Theme Toggle Functionality
(function () {
    'use strict';

    // Get theme from localStorage or default to 'light'
    const getTheme = () => {
        return localStorage.getItem('theme') || 'light';
    };

    // Set theme
    const setTheme = (theme) => {
        localStorage.setItem('theme', theme);
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    };

    // Initialize theme on page load
    const initTheme = () => {
        const theme = getTheme();
        setTheme(theme);
        updateToggleIcons(theme);
    };

    // Update toggle button icons
    const updateToggleIcons = (theme) => {
        const sunIcons = document.querySelectorAll('.theme-toggle-sun');
        const moonIcons = document.querySelectorAll('.theme-toggle-moon');

        if (theme === 'dark') {
            sunIcons.forEach(icon => icon.classList.remove('hidden'));
            moonIcons.forEach(icon => icon.classList.add('hidden'));
        } else {
            sunIcons.forEach(icon => icon.classList.add('hidden'));
            moonIcons.forEach(icon => icon.classList.remove('hidden'));
        }
    };

    // Toggle theme
    const toggleTheme = () => {
        const currentTheme = getTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
        updateToggleIcons(newTheme);
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }

    // Attach toggle functionality to all theme toggle buttons
    document.addEventListener('DOMContentLoaded', () => {
        const toggleButtons = document.querySelectorAll('.theme-toggle-btn');
        toggleButtons.forEach(btn => {
            btn.addEventListener('click', toggleTheme);
        });
    });

    // Export for global access if needed
    window.themeToggle = {
        getTheme,
        setTheme,
        toggleTheme
    };
})();
