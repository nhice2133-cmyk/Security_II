/**
 * Theme Manager & Toggle Script — SecureAuth / CyberAuth
 * Light Mode is the DEFAULT.
 * Automatically respects and persists user preference in localStorage.
 */

(function () {
  'use strict';

  var THEME_KEY = 'auth_theme';
  var DEFAULT_THEME = 'light'; // Light mode is default as requested

  // Get current active theme
  function getStoredTheme() {
    try {
      var stored = localStorage.getItem(THEME_KEY);
      if (stored === 'dark' || stored === 'light') {
        return stored;
      }
    } catch (e) {
      // localStorage disabled or private browsing
    }
    return DEFAULT_THEME;
  }

  // Apply theme to DOM
  function applyTheme(theme) {
    var validTheme = (theme === 'dark') ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', validTheme);
    if (document.body) {
      document.body.setAttribute('data-theme', validTheme);
    }

    try {
      localStorage.setItem(THEME_KEY, validTheme);
    } catch (e) {}

    // Update all toggle buttons on page
    updateToggleButtons(validTheme);

    // Dispatch custom event for any listeners (e.g., charts, maps)
    window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: validTheme } }));
  }

  // Update button visual states
  function updateToggleButtons(theme) {
    var buttons = document.querySelectorAll('.theme-toggle-btn, #themeToggleBtn');
    buttons.forEach(function (btn) {
      var isDark = (theme === 'dark');
      btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
      btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
      btn.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');

      var textSpan = btn.querySelector('.theme-toggle-text');
      if (textSpan) {
        textSpan.textContent = isDark ? 'Light' : 'Dark';
      }
    });
  }

  // Toggle between dark and light
  function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme') || getStoredTheme();
    var next = (current === 'dark') ? 'light' : 'dark';
    applyTheme(next);
  }

  // Expose global methods
  window.toggleTheme = toggleTheme;
  window.setTheme = applyTheme;
  window.getCurrentTheme = function () {
    return document.documentElement.getAttribute('data-theme') || getStoredTheme();
  };

  // Run initial theme application immediately
  applyTheme(getStoredTheme());

  // Attach event handlers on DOM load
  document.addEventListener('DOMContentLoaded', function () {
    applyTheme(getStoredTheme());

    // Event delegation for theme toggle clicks
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.theme-toggle-btn, #themeToggleBtn');
      if (btn) {
        e.preventDefault();
        toggleTheme();
      }
    });
  });
})();
