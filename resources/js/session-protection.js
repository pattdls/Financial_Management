 // resources/js/session-protection.js
// Create this file ONCE and include it in protected pages

(function() {
    'use strict';
    
    // Initialize session protection when page loads
    function initSessionProtection() {
        // Prevent back button navigation
        if (window.history && window.history.pushState) {
            window.history.pushState(null, null, window.location.href);
            
            window.addEventListener('popstate', function(event) {
                window.history.pushState(null, null, window.location.href);
                checkSessionStatus();
            });
        }
        
        // Check session when page becomes visible again
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                checkSessionStatus();
            }
        });
    }
    
    // Function to check if session is still valid
    function checkSessionStatus() {
        fetch('check_session.php', {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-cache',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!data.authenticated) {
                // Session expired, redirect to login
                window.location.replace('https://rvrsmes-fms.com/login_form.php?session_expired=1');
            }
        })
        .catch(error => {
            console.warn('Session check failed:', error);
            // On error, redirect to login for security
            window.location.replace('https://rvrsmes-fms.com/login_form.php');
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSessionProtection);
    } else {
        initSessionProtection();
    }
    
    // Optional: Check session every 5 minutes
    setInterval(checkSessionStatus, 300000);
    
})();