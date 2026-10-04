// Reset Password JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetPasswordForm');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const resetButton = document.getElementById('resetBtn');
    
    // Check if we have the required data from previous steps
    const idNumber = sessionStorage.getItem('fp_idNumber');
    const answers = sessionStorage.getItem('fp_answers');
    
    if (!idNumber || !answers) {
        alert('Session expired. Please start the forgot password process again.');
        window.location.href = 'forgot-password.php';
        return;
    }

    // Real-time password validation
    newPasswordInput.addEventListener('input', function() {
        validatePassword();
        updatePasswordRequirements();
    });

    confirmPasswordInput.addEventListener('input', function() {
        validateConfirmPassword();
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitPasswordReset();
        }
    });

    function validateForm() {
        let isValid = true;
        
        if (!validatePassword()) {
            isValid = false;
        }
        
        if (!validateConfirmPassword()) {
            isValid = false;
        }
        
        return isValid;
    }

    function validatePassword() {
        const password = newPasswordInput.value;
        const errorElement = document.getElementById('newPasswordError');
        
        if (password.length < 8) {
            showError('newPassword', 'Password must be at least 8 characters long');
            return false;
        }
        
        if (!/(?=.*[a-z])/.test(password)) {
            showError('newPassword', 'Password must contain at least one lowercase letter');
            return false;
        }
        
        if (!/(?=.*[A-Z])/.test(password)) {
            showError('newPassword', 'Password must contain at least one uppercase letter');
            return false;
        }
        
        if (!/(?=.*\d)/.test(password)) {
            showError('newPassword', 'Password must contain at least one number');
            return false;
        }
        
        if (!/(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?])/.test(password)) {
            showError('newPassword', 'Password must contain at least one special character');
            return false;
        }
        
        clearError('newPassword');
        return true;
    }

    function validateConfirmPassword() {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword !== password) {
            showError('confirmPassword', 'Passwords do not match');
            return false;
        }
        
        clearError('confirmPassword');
        return true;
    }

    function updatePasswordRequirements() {
        const password = newPasswordInput.value;
        
        // Update requirement indicators
        updateRequirement('req-length', password.length >= 8);
        updateRequirement('req-uppercase', /(?=.*[A-Z])/.test(password));
        updateRequirement('req-lowercase', /(?=.*[a-z])/.test(password));
        updateRequirement('req-number', /(?=.*\d)/.test(password));
        updateRequirement('req-special', /(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?])/.test(password));
    }

    function updateRequirement(elementId, isValid) {
        const element = document.getElementById(elementId);
        const icon = element.querySelector('.req-icon');
        
        if (isValid) {
            icon.textContent = '✅';
            element.style.color = '#23d18b';
        } else {
            icon.textContent = '❌';
            element.style.color = '#ff0040';
        }
    }

    function submitPasswordReset() {
        const originalText = resetButton.querySelector('.btn-text').textContent;
        
        // Show loading state
        resetButton.disabled = true;
        resetButton.querySelector('.btn-text').textContent = 'RESETTING...';
        
        const answers = JSON.parse(sessionStorage.getItem('fp_answers'));
        
        const payload = {
            idNumber: idNumber,
            answer1: answers.answer1,
            answer2: answers.answer2,
            answer3: answers.answer3,
            newPassword: newPasswordInput.value
        };
        
        fetch('../php/verify_security_answers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password reset successful! You can now login with your new password.');
                // Clear session data
                sessionStorage.removeItem('fp_idNumber');
                sessionStorage.removeItem('fp_answers');
                // Reset login attempts since password was successfully reset
                localStorage.removeItem('loginAttempts');
                localStorage.removeItem('lockoutUntil');
                localStorage.removeItem('lockoutLevel');
                window.location.href = 'login.php';
            } else {
                alert(data.message || 'Password reset failed. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error. Please try again.');
        })
        .finally(() => {
            // Reset button state
            resetButton.disabled = false;
            resetButton.querySelector('.btn-text').textContent = originalText;
        });
    }

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + 'Error');
        
        field.classList.add('error');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    function clearError(fieldId) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + 'Error');
        
        field.classList.remove('error');
        errorElement.style.display = 'none';
    }

    // Cyber eye SVG icon
    function getEyeSVG(isVisible) {
        const stroke = isVisible ? '#ff00ff' : '#00eaff';
        const glow = isVisible ? 'rgba(255,0,255,0.6)' : 'rgba(0,234,255,0.6)';
        const slash = isVisible ? '<path d="M3 3L21 21"></path>' : '';
        return `
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="${stroke}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0 0 6px ${glow});">
            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
            <circle cx="12" cy="12" r="3"></circle>
            ${slash}
          </svg>
        `;
    }

    // Password visibility toggle
    window.togglePassword = function(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.parentNode.querySelector('.show-password');
        
        if (field.type === 'password') {
            field.type = 'text';
            button.innerHTML = getEyeSVG(true);
            button.setAttribute('aria-label', 'Hide password');
            button.setAttribute('aria-pressed', 'true');
        } else {
            field.type = 'password';
            button.innerHTML = getEyeSVG(false);
            button.setAttribute('aria-label', 'Show password');
            button.setAttribute('aria-pressed', 'false');
        }
    };

    // Initialize eye icons on page load
    const newPasswordButton = newPasswordInput.parentNode.querySelector('.show-password');
    const confirmPasswordButton = confirmPasswordInput.parentNode.querySelector('.show-password');
    if (newPasswordButton) newPasswordButton.innerHTML = getEyeSVG(false);
    if (confirmPasswordButton) confirmPasswordButton.innerHTML = getEyeSVG(false);

    // Auto-focus on password field
    newPasswordInput.focus();
});

