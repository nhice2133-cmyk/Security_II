// Cyberpunk Change Password Form Validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('changePasswordForm');
    const currentPasswordInput = document.getElementById('currentPassword');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const updateBtn = document.getElementById('updateBtn');
    const passwordStrength = document.getElementById('passwordStrength');
    const toggleButtons = document.querySelectorAll('.show-password');

    // Initialize cyberpunk effects
    initializeCyberpunkEffects();

    // Initialize show password icons with cyber SVGs
    toggleButtons.forEach(btn => {
        btn.setAttribute('aria-label', 'Show password');
        btn.setAttribute('aria-pressed', 'false');
        btn.innerHTML = getEyeSVG(false);
    });

    // Real-time validation
    currentPasswordInput.addEventListener('input', function() {
        clearError(this);
        addTypingEffect(this);
    });

    newPasswordInput.addEventListener('input', function() {
        checkPasswordStrength();
        validatePasswordRequirements();
        clearError(this);
        addTypingEffect(this);
    });

    confirmPasswordInput.addEventListener('input', function() {
        validatePasswordMatch();
        clearError(this);
        addTypingEffect(this);
    });

    // If in forgot mode, hide current password and require ID number from session
    const params = new URLSearchParams(window.location.search);
    const isForgot = params.get('mode') === 'forgot';
    if (isForgot) {
        const idNumber = sessionStorage.getItem('cp_idNumber');
        if (!idNumber) {
            window.location.href = 'forgot-password.php';
            return;
        }
        // hide current password field in UI
        const currentGroup = currentPasswordInput.closest('.input-group');
        if (currentGroup) currentGroup.style.display = 'none';
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm();
    });

    // Cyberpunk effects initialization
    function initializeCyberpunkEffects() {
        // Add glitch effect to terminal
        addTerminalEffect();
        
        // Add typing animation to inputs
        const inputs = [currentPasswordInput, newPasswordInput, confirmPasswordInput];
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                addTypingEffect(this);
            });
        });
    }

    // Terminal effect
    function addTerminalEffect() {
        const terminal = document.querySelector('.terminal-screen');
        terminal.style.animation = 'terminal-glow 2s ease-in-out infinite';
    }

    // Typing effect
    function addTypingEffect(input) {
        input.style.borderColor = '#00ffff';
        input.style.boxShadow = '0 0 10px rgba(0, 255, 255, 0.5)';
        
        setTimeout(() => {
            input.style.borderColor = '';
            input.style.boxShadow = '';
        }, 1000);
    }

    // Password strength check
    function checkPasswordStrength() {
        const password = newPasswordInput.value;
        let strength = 0;
        let strengthText = '';
        let strengthClass = '';

        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        switch (strength) {
            case 0:
            case 1:
                strengthText = 'WEAK ENCRYPTION';
                strengthClass = 'strength-weak';
                break;
            case 2:
            case 3:
                strengthText = 'MEDIUM ENCRYPTION';
                strengthClass = 'strength-medium';
                break;
            case 4:
            case 5:
                strengthText = 'STRONG ENCRYPTION';
                strengthClass = 'strength-strong';
                break;
        }

        passwordStrength.textContent = strengthText;
        passwordStrength.className = `password-strength ${strengthClass}`;
    }

    // Validate password requirements
    function validatePasswordRequirements() {
        const password = newPasswordInput.value;
        
        // Length requirement
        const lengthReq = document.getElementById('req-length');
        if (password.length >= 8) {
            lengthReq.classList.add('valid');
            lengthReq.querySelector('.req-icon').textContent = '✅';
        } else {
            lengthReq.classList.remove('valid');
            lengthReq.querySelector('.req-icon').textContent = '❌';
        }

        // Uppercase requirement
        const uppercaseReq = document.getElementById('req-uppercase');
        if (/[A-Z]/.test(password)) {
            uppercaseReq.classList.add('valid');
            uppercaseReq.querySelector('.req-icon').textContent = '✅';
        } else {
            uppercaseReq.classList.remove('valid');
            uppercaseReq.querySelector('.req-icon').textContent = '❌';
        }

        // Lowercase requirement
        const lowercaseReq = document.getElementById('req-lowercase');
        if (/[a-z]/.test(password)) {
            lowercaseReq.classList.add('valid');
            lowercaseReq.querySelector('.req-icon').textContent = '✅';
        } else {
            lowercaseReq.classList.remove('valid');
            lowercaseReq.querySelector('.req-icon').textContent = '❌';
        }

        // Number requirement
        const numberReq = document.getElementById('req-number');
        if (/[0-9]/.test(password)) {
            numberReq.classList.add('valid');
            numberReq.querySelector('.req-icon').textContent = '✅';
        } else {
            numberReq.classList.remove('valid');
            numberReq.querySelector('.req-icon').textContent = '❌';
        }

        // Special character requirement
        const specialReq = document.getElementById('req-special');
        if (/[^A-Za-z0-9]/.test(password)) {
            specialReq.classList.add('valid');
            specialReq.querySelector('.req-icon').textContent = '✅';
        } else {
            specialReq.classList.remove('valid');
            specialReq.querySelector('.req-icon').textContent = '❌';
        }
    }

    // Validate password match
    function validatePasswordMatch() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword && newPassword !== confirmPassword) {
            showError(confirmPasswordInput, 'ENCRYPTION KEYS DO NOT MATCH');
        } else {
            clearError(confirmPasswordInput);
        }
    }

    // Show error
    function showError(field, message) {
        const fieldName = field.id;
        const errorElement = document.getElementById(`${fieldName}Error`);
        errorElement.textContent = message;
        errorElement.classList.add('show');
        
        field.style.borderColor = '#ff0040';
        field.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.5)';
        
        // Add glitch effect
        field.style.animation = 'glitch 0.3s ease-in-out';
        setTimeout(() => {
            field.style.animation = '';
        }, 300);
    }

    // Clear error
    function clearError(field) {
        const fieldName = field.id;
        const errorElement = document.getElementById(`${fieldName}Error`);
        errorElement.textContent = '';
        errorElement.classList.remove('show');
        
        field.style.borderColor = '';
        field.style.boxShadow = '';
    }

    // Form validation
    function validateForm() {
        let isValid = true;
        
        // Current password validation (skip in forgot mode)
        if (!isForgot) {
            if (!currentPasswordInput.value.trim()) {
                showError(currentPasswordInput, 'CURRENT ENCRYPTION KEY REQUIRED');
                isValid = false;
            }
        }

        // New password validation
        if (!newPasswordInput.value.trim()) {
            showError(newPasswordInput, 'NEW ENCRYPTION KEY REQUIRED');
            isValid = false;
        } else if (newPasswordInput.value.length < 8) {
            showError(newPasswordInput, 'ENCRYPTION KEY TOO WEAK (MIN 8 CHARS)');
            isValid = false;
        } else if (!/[A-Z]/.test(newPasswordInput.value)) {
            showError(newPasswordInput, 'ENCRYPTION KEY MUST CONTAIN UPPERCASE');
            isValid = false;
        } else if (!/[a-z]/.test(newPasswordInput.value)) {
            showError(newPasswordInput, 'ENCRYPTION KEY MUST CONTAIN LOWERCASE');
            isValid = false;
        } else if (!/[0-9]/.test(newPasswordInput.value)) {
            showError(newPasswordInput, 'ENCRYPTION KEY MUST CONTAIN NUMBER');
            isValid = false;
        } else if (!/[^A-Za-z0-9]/.test(newPasswordInput.value)) {
            showError(newPasswordInput, 'ENCRYPTION KEY MUST CONTAIN SPECIAL CHAR');
            isValid = false;
        }

        // Confirm password validation
        if (!confirmPasswordInput.value.trim()) {
            showError(confirmPasswordInput, 'CONFIRM ENCRYPTION KEY REQUIRED');
            isValid = false;
        } else if (newPasswordInput.value !== confirmPasswordInput.value) {
            showError(confirmPasswordInput, 'ENCRYPTION KEYS DO NOT MATCH');
            isValid = false;
        }

        // Check if current and new passwords are the same (skip in forgot mode)
        if (!isForgot) {
            if (currentPasswordInput.value === newPasswordInput.value) {
                showError(newPasswordInput, 'NEW KEY MUST BE DIFFERENT FROM CURRENT');
                isValid = false;
            }
        }

        return isValid;
    }

    // Form submission
    function submitForm() {
        if (validateForm()) {
            // Show loading state
            updateBtn.innerHTML = '<span class="btn-text">UPDATING ENCRYPTION...</span><div class="btn-glow"></div>';
            updateBtn.disabled = true;
            
            // Add terminal effect
            addTerminalEffect();
            
            if (isForgot) {
                const idNumber = sessionStorage.getItem('cp_idNumber');
                const answers = JSON.parse(sessionStorage.getItem('fp_answers') || '{}');
                fetch('../php/verify_security_answers.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        idNumber,
                        answer1: answers.answer1,
                        answer2: answers.answer2,
                        answer3: answers.answer3,
                        newPassword: newPasswordInput.value
                    }),
                    credentials: 'same-origin'
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        sessionStorage.removeItem('cp_idNumber');
                        sessionStorage.removeItem('fp_answers');
                        showSuccessMessage();
                    } else {
                        showErrorMessage(res.message || 'Update failed');
                    }
                })
                .catch(() => showErrorMessage('Network error'))
                .finally(() => {
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = '<div class="btn-glow"></div><span class="btn-text">UPDATE ENCRYPTION KEY</span>';
                });
            } else {
                // Submit to backend for authenticated password change
                const formData = new URLSearchParams();
                formData.append('current_password', currentPasswordInput.value);
                formData.append('new_password', newPasswordInput.value);
                formData.append('confirm_new_password', confirmPasswordInput.value);
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) {
                    formData.append('csrf_token', csrfMeta.getAttribute('content'));
                }

                fetch('../php/change-password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString(),
                    credentials: 'same-origin'
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showSuccessMessage();
                    } else {
                        showErrorMessage(res.message || 'Update failed');
                    }
                })
                .catch(() => showErrorMessage('Network error'))
                .finally(() => {
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = '<div class="btn-glow"></div><span class="btn-text">UPDATE ENCRYPTION KEY</span>';
                });
            }
        } else {
            showErrorMessage('ENCRYPTION UPDATE FAILED - CHECK NEURAL DATA');
        }
    }

    // Success message
    function showSuccessMessage() {
        const terminal = document.querySelector('.terminal-screen');
        terminal.innerHTML = `
            <div class="success-message">
                <div class="terminal-prompt">
                    <span class="prompt-text">admin@neuralnet:~$</span>
                    <span class="cursor-blink">_</span>
                </div>
                <div class="success-content">
                    <h3 style="color: #00ff00; text-align: center; margin: 1rem 0;">✓ ENCRYPTION KEY UPDATED</h3>
                    <p style="color: #00ffff; text-align: center; margin: 1rem 0;">Your neural network access credentials have been successfully updated.</p>
                    <div style="text-align: center; margin: 2rem 0;">
                        <a href="dashboard.php" class="cyber-btn" style="display: inline-block;">
                            <span class="btn-text">RETURN TO DASHBOARD</span>
                            <div class="btn-glow"></div>
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    // Error message
    function showErrorMessage(message) {
        const terminal = document.querySelector('.terminal-screen');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message-global';
        errorDiv.style.cssText = `
            color: #ff0040;
            text-align: center;
            padding: 1rem;
            border: 1px solid #ff0040;
            border-radius: 4px;
            margin: 1rem 0;
            background: rgba(255, 0, 64, 0.1);
            animation: glitch 0.5s ease-in-out;
        `;
        errorDiv.textContent = message;
        
        terminal.appendChild(errorDiv);
        
        setTimeout(() => {
            errorDiv.remove();
        }, 3000);
    }
});

// SVG icon generator for cyber eye
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

// Password visibility toggle function
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    
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
    
    // Add typing effect
    field.style.borderColor = '#00ffff';
    field.style.boxShadow = '0 0 10px rgba(0, 255, 255, 0.5)';
    
    setTimeout(() => {
        field.style.borderColor = '';
        field.style.boxShadow = '';
    }, 1000);
}

// CSS Animations
const style = document.createElement('style');
style.textContent = `
    @keyframes terminal-glow {
        0%, 100% { box-shadow: 0 0 30px rgba(0, 255, 255, 0.3); }
        50% { box-shadow: 0 0 50px rgba(0, 255, 255, 0.6); }
    }
    
    @keyframes glitch {
        0% { transform: translate(0); }
        20% { transform: translate(-2px, 2px); }
        40% { transform: translate(-2px, -2px); }
        60% { transform: translate(2px, 2px); }
        80% { transform: translate(2px, -2px); }
        100% { transform: translate(0); }
    }
    
    .success-message {
        padding: 1rem;
        text-align: center;
    }
    
    .success-content {
        margin-top: 1rem;
    }
    
    .requirement-item.valid {
        color: #00ff00;
    }
    
    .requirement-item.valid .req-icon {
        color: #00ff00;
        text-shadow: 0 0 5px #00ff00;
    }
`;
document.head.appendChild(style);
