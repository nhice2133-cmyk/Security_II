/**
 * Disable Right-Click and Developer Tools
 * Prevents users from accessing context menu and developer tools
 */

(function() {
    'use strict';

    // Disable right-click context menu
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });

    // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U, Ctrl+S
    document.addEventListener('keydown', function(e) {
        // F12
        if (e.keyCode === 123) {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+Shift+I (Developer Tools)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+Shift+J (Console)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+Shift+C (Inspect Element)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+U (View Source)
        if (e.ctrlKey && e.keyCode === 85) {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+S (Save Page)
        if (e.ctrlKey && e.keyCode === 83) {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+P (Print - can also be used to view source)
        if (e.ctrlKey && e.keyCode === 80) {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+A (Select All - partially disabled)
        if (e.ctrlKey && e.keyCode === 65) {
            // Allow select all, but show warning
            console.clear();
        }
    });

    // Disable text selection (optional - comment out if you want to allow text selection)
    // document.addEventListener('selectstart', function(e) {
    //     e.preventDefault();
    //     return false;
    // });

    // Disable drag and drop
    document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
    });

    // Disable copy (Ctrl+C) - optional, comment out if you want to allow copying
    document.addEventListener('copy', function(e) {
        // Uncomment to completely disable copy
        // e.clipboardData.setData('text/plain', '');
        // e.preventDefault();
        // return false;
    });

    // Clear console periodically (makes it harder to use developer tools)
    setInterval(function() {
        console.clear();
    }, 1000);

    // Detect and disable developer tools
    let devtools = {open: false, orientation: null};
    const threshold = 160;
    
    setInterval(function() {
        if (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold) {
            if (!devtools.open) {
                devtools.open = true;
                // Optionally redirect or show warning
                // window.location.href = 'about:blank';
                // alert('Developer tools detected!');
            }
        } else {
            if (devtools.open) {
                devtools.open = false;
            }
        }
    }, 500);

    // Disable image right-click (specific to images)
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img');
        images.forEach(function(img) {
            img.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
            // Prevent dragging images
            img.setAttribute('draggable', 'false');
        });
    });

    // Disable common keyboard shortcuts for developer tools
    document.addEventListener('keydown', function(e) {
        // Ctrl+Shift+K (Firefox Console)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 75) {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+Shift+E (Firefox Network Monitor)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 69) {
            e.preventDefault();
            return false;
        }
    });

    // Warn users about trying to access developer tools
    console.log('%cStop!', 'color: red; font-size: 50px; font-weight: bold;');
    console.log('%cThis is a browser feature intended for developers. If someone told you to copy-paste something here, it is a scam and will give them access to your account.', 'color: red; font-size: 20px;');

})();

