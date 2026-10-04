/**
 * Back Button Protection Script
 * Destroys session when user navigates back from protected pages
 */

(function() {
    'use strict';

    // Only destroy session on an actual back navigation, not on normal navigation,
    // tab switches, reloads, or page unloads.

    let backHandled = false;

    function destroySessionAndRedirect() {
        if (backHandled) return;
        backHandled = true;
        fetch('../php/destroy_session.php', { method: 'POST' })
            .finally(() => { window.location.href = '../pages/login.php'; });
    }

    // Push a marker state so that a subsequent popstate indicates a back action
    try {
        history.replaceState({ antiBackGuard: true }, document.title, location.href);
        history.pushState({ antiBackGuard: true }, document.title, location.href);
    } catch (e) { /* ignore */ }

    window.addEventListener('popstate', function(event) {
        const state = event.state || {};
        if (state && state.antiBackGuard) {
            destroySessionAndRedirect();
        }
    });
})();
