// Cyberpunk Login Validation JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const submitButton = document.getElementById('loginBtn');
    const showPasswordBtn = document.getElementById('showPassword');
    const loginAttemptsDiv = document.getElementById('loginAttempts');
    const attemptCountSpan = document.getElementById('attemptCount');
    const timerTextSpan = document.getElementById('timerText');
    const forgotPrompt = document.getElementById('forgotPrompt');

    // Initialize cyberpunk effects
    initializeCyberpunkEffects();

    // Initialize cyber-themed eye icon for show/hide password
    if (showPasswordBtn) {
        showPasswordBtn.setAttribute('aria-label', 'Show password');
        showPasswordBtn.setAttribute('aria-pressed', 'false');
        showPasswordBtn.innerHTML = getEyeSVG(false);
    }
    updateForgotPromptDisplay(parseInt(localStorage.getItem('loginAttempts') || '0'));

    // Password visibility toggle with cyber SVG icon
    if (showPasswordBtn) {
        showPasswordBtn.addEventListener('click', function() {
            const isHidden = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isHidden ? 'text' : 'password');
            showPasswordBtn.innerHTML = getEyeSVG(isHidden);
            showPasswordBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            showPasswordBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        });
    }

    // Track if fields have been typed in
    let usernameTyped = false;
    let passwordTyped = false;

    // Real-time validation with cyberpunk effects
    usernameInput.addEventListener('blur', function() {
        // Only validate if user has typed something or field has a value
        if (usernameTyped || usernameInput.value.trim() !== '') {
            validateUsername();
        }
    });
    usernameInput.addEventListener('input', function() {
        usernameTyped = true;
        addTypingEffect(usernameInput);
        const errorElement = getOrCreateErrorElement(usernameInput);
        if (usernameInput.value.trim().length >= 3) {
            clearError(usernameInput, errorElement);
        } else if (usernameInput.classList.contains('error')) {
            validateUsername();
        }
    });
    
    passwordInput.addEventListener('blur', function() {
        // Only validate if user has typed something or field has a value
        if (passwordTyped || passwordInput.value !== '') {
            validatePassword();
        }
    });
    passwordInput.addEventListener('input', function() {
        passwordTyped = true;
        addTypingEffect(passwordInput);
        const errorElement = getOrCreateErrorElement(passwordInput);
        if (passwordInput.value.trim().length >= 6) {
            clearError(passwordInput, errorElement);
        } else if (passwordInput.classList.contains('error')) {
            validatePassword();
        }
    });

    // Allow URL query parameter (?reset=1 or ?unlock=1) to clear client-side lockout
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('reset') || urlParams.has('unlock') || urlParams.has('reset_timer')) {
        localStorage.removeItem('lockoutUntil');
        localStorage.removeItem('loginAttempts');
        localStorage.removeItem('lockoutLevel');
        clearLockoutState();
        if (loginAttemptsDiv) loginAttemptsDiv.style.display = 'none';
    }

    // Restore lockout state on load
    restoreLockoutState();

    // Form submission
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        // Normalize username/email to lowercase before submit
        if (usernameInput && usernameInput.value) {
            usernameInput.value = usernameInput.value.trim().toLowerCase();
        }
        
        if (isLockedOut()) {
            // ignore submits during lockout
            return;
        }

        if (validateForm()) {
            submitForm();
        }
    });

    function initializeCyberpunkEffects() {
        // Add glitch effect to terminal title
        const terminalTitle = document.querySelector('.terminal-title');
        if (terminalTitle) {
            setInterval(() => {
                if (Math.random() < 0.1) {
                    terminalTitle.style.textShadow = '2px 0 #ff0040, -2px 0 #00ffff';
                    setTimeout(() => {
                        terminalTitle.style.textShadow = '0 0 20px var(--cyber-primary)';
                    }, 100);
                }
            }, 2000);
        }

        // Add random data stream effects
        createDataStreams();
        
        // Add terminal typing effect
        simulateTerminalTyping();
    }

    function addTypingEffect(input) {
        input.style.boxShadow = '0 0 20px rgba(0, 255, 255, 0.5)';
        setTimeout(() => {
            input.style.boxShadow = '';
        }, 200);
    }

    function createDataStreams() {
        const terminalScreen = document.querySelector('.terminal-screen');
        if (!terminalScreen) return;

        setInterval(() => {
            if (Math.random() < 0.3) {
                const dataStream = document.createElement('div');
                dataStream.className = 'data-stream-effect';
                dataStream.style.cssText = `
                    position: absolute;
                    top: ${Math.random() * 100}%;
                    left: 0;
                    width: 100%;
                    height: 1px;
                    background: linear-gradient(90deg, transparent, #00ffff, transparent);
                    animation: data-stream-flow 2s linear forwards;
                    pointer-events: none;
                `;
                
                terminalScreen.appendChild(dataStream);
                
                setTimeout(() => {
                    dataStream.remove();
                }, 2000);
            }
        }, 3000);
    }

    function simulateTerminalTyping() {
        const promptText = document.querySelector('.prompt-text');
        if (!promptText) return;

        const originalText = promptText.textContent;
        let isTyping = false;

        setInterval(() => {
            if (!isTyping && Math.random() < 0.2) {
                isTyping = true;
                const commands = [
                    'scanning_neural_networks...',
                    'encrypting_data_stream...',
                    'authenticating_user...',
                    'checking_security_protocols...',
                    'initializing_quantum_encryption...'
                ];
                
                const randomCommand = commands[Math.floor(Math.random() * commands.length)];
                typeText(promptText, originalText + ' ' + randomCommand, () => {
                    setTimeout(() => {
                        typeText(promptText, originalText, () => {
                            isTyping = false;
                        });
                    }, 2000);
                });
            }
        }, 5000);
    }

    function typeText(element, text, callback) {
        element.textContent = '';
        let i = 0;
        const typeInterval = setInterval(() => {
            element.textContent += text[i];
            i++;
            if (i >= text.length) {
                clearInterval(typeInterval);
                if (callback) callback();
            }
        }, 50);
    }

    function validateUsername() {
        const username = usernameInput.value.trim();
        const errorElement = getOrCreateErrorElement(usernameInput);
        
        if (username === '') {
            showError(usernameInput, errorElement, 'NEURAL ID REQUIRED');
            return false;
        }
        
        if (username.length < 3) {
            showError(usernameInput, errorElement, 'ID MUST BE 3+ CHARACTERS');
            return false;
        }
        
        clearError(usernameInput, errorElement);
        return true;
    }

    function validatePassword() {
        const password = passwordInput.value;
        const errorElement = getOrCreateErrorElement(passwordInput);
        
        if (password.trim() === '') {
            showError(passwordInput, errorElement, 'ENCRYPTION KEY REQUIRED');
            return false;
        }
        
        if (password.length < 6) {
            showError(passwordInput, errorElement, 'KEY MUST BE 6+ CHARACTERS');
            return false;
        }
        
        clearError(passwordInput, errorElement);
        return true;
    }

    function validateForm() {
        const isUsernameValid = validateUsername();
        const isPasswordValid = validatePassword();
        
        return isUsernameValid && isPasswordValid;
    }

    function submitForm() {
        // Clear previous alert box if any
        const alertBox = document.getElementById('loginAlertBox');
        if (alertBox) {
            alertBox.style.display = 'none';
            alertBox.innerHTML = '';
        }

        // Show loading state with cyberpunk effects
        submitButton.classList.add('loading');
        submitButton.disabled = true;
        submitButton.querySelector('.btn-text').textContent = 'INITIATING...';
        
        // Get form data
        const formData = new FormData(loginForm);
        
        // Submit to PHP
        fetch('../php/login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Success - redirect to dashboard
                showSuccessMessage('NEURAL LINK ESTABLISHED! Redirecting to dashboard...');
                // Reset attempts on successful login since user is legitimate
                localStorage.removeItem('loginAttempts');
                clearLockoutState();
                updateForgotPromptDisplay(0);
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 2000);
            } else {
                // Check if this failure is due to single-active Super Admin lock
                if (data.data && (data.data.locked_by_other || data.data.locked)) {
                    // Only show ONE warning in the form card — eliminate redundant top banner
                    if (alertBox) {
                        alertBox.innerHTML = `
                            <div class="login-alert-banner alert-locked">
                                <span class="login-alert-icon">🔒</span>
                                <div class="login-alert-content">
                                    <strong class="login-alert-title">Single Super Admin Active</strong>
                                    <span class="login-alert-text">${escapeHtml(data.message || 'Another Super Admin is currently active. Only 1 Super Admin can be logged in at a time. Please try again after the current Super Admin logs out.')}</span>
                                </div>
                            </div>
                        `;
                        alertBox.style.display = 'block';
                    }
                    // Do not increment failed password attempts for valid credentials
                    return;
                }

                // Ordinary failure (e.g. invalid credentials) - show message in alertBox
                if (alertBox) {
                    alertBox.innerHTML = `
                        <div class="login-alert-banner alert-danger">
                            <span class="login-alert-icon">⚠️</span>
                            <div class="login-alert-content">
                                <strong class="login-alert-title">Authentication Failed</strong>
                                <span class="login-alert-text">${escapeHtml(data.message || 'Invalid username or password. Please try again.')}</span>
                            </div>
                        </div>
                    `;
                    alertBox.style.display = 'block';
                }
                updateLoginAttempts();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (alertBox) {
                alertBox.innerHTML = `
                    <div class="login-alert-banner alert-danger">
                        <span class="login-alert-icon">⚠️</span>
                        <div class="login-alert-content">
                            <strong class="login-alert-title">Network Error</strong>
                            <span class="login-alert-text">Unable to connect to the authentication server. Please check your connection and try again.</span>
                        </div>
                    </div>
                `;
                alertBox.style.display = 'block';
            }
        })
        .finally(() => {
            // Reset button state
            submitButton.classList.remove('loading');
            submitButton.disabled = false;
            submitButton.querySelector('.btn-text').textContent = 'INITIATE LOGIN';
        });
    }

    function updateLoginAttempts() {
        let attempts = parseInt(localStorage.getItem('loginAttempts') || '0');
        attempts++;
        
        // Handle the 10+ reset logic: if attempts reach 10, reset to 7
        if (attempts >= 10) {
            attempts = 7;
        }
        
        localStorage.setItem('loginAttempts', attempts.toString());

        loginAttemptsDiv.style.display = 'flex';
        updateForgotPromptDisplay(attempts);
        // Format display based on attempt count
        if (attempts <= 3) {
            attemptCountSpan.textContent = `${attempts}/3`;
        } else if (attempts <= 6) {
            attemptCountSpan.textContent = `${attempts}/6`;
        } else if (attempts <= 9) {
            attemptCountSpan.textContent = `${attempts}/9`;
        } else {
            // This shouldn't happen due to reset logic, but just in case
            attemptCountSpan.textContent = `${attempts}/9`;
        }

        // Lockout at 3, 6, and 9 attempts
        if (attempts === 3) {
            // First lockout at 3 attempts - 15 seconds
            const duration = 15000;
            const until = Date.now() + duration;
            localStorage.setItem('lockoutUntil', until.toString());
            localStorage.setItem('lockoutLevel', '0');
            applyLockout(until);
        } else if (attempts === 6) {
            // Second lockout at 6 attempts - 30 seconds
            const duration = 30000;
            const until = Date.now() + duration;
            localStorage.setItem('lockoutUntil', until.toString());
            localStorage.setItem('lockoutLevel', '1');
            applyLockout(until);
        } else if (attempts === 9) {
            // Third lockout at 9 attempts - 60 seconds
            const duration = 60000;
            const until = Date.now() + duration;
            localStorage.setItem('lockoutUntil', until.toString());
            localStorage.setItem('lockoutLevel', '2');
            applyLockout(until);
        }
        // For attempts 4, 5, 7, 8 - just show warning, no lockout
    }

    function isLockedOut() {
        const until = parseInt(localStorage.getItem('lockoutUntil') || '0');
        return until && Date.now() < until;
    }

    function restoreLockoutState() {
        const until = parseInt(localStorage.getItem('lockoutUntil') || '0');
        if (until && Date.now() < until) {
            applyLockout(until);
        } else {
            // clear stale lockout and ensure form is interactable
            clearLockoutState();
        }
        // show attempts if any
        const attempts = parseInt(localStorage.getItem('loginAttempts') || '0');
        if (attempts > 0) {
            loginAttemptsDiv.style.display = 'flex';
            // Format display based on attempt count
            if (attempts <= 3) {
                attemptCountSpan.textContent = `${attempts}/3`;
            } else if (attempts <= 6) {
                attemptCountSpan.textContent = `${attempts}/6`;
            } else if (attempts <= 9) {
                attemptCountSpan.textContent = `${attempts}/9`;
            } else {
                attemptCountSpan.textContent = `${attempts}/9`;
            }
        }
        updateForgotPromptDisplay(attempts);
    }

    function clearLockoutState() {
        localStorage.removeItem('lockoutUntil');
        // Don't remove loginAttempts - let them persist for progressive lockout
        localStorage.removeItem('lockoutLevel');
        enableInputsAndLinks();
        timerTextSpan.style.display = 'none';
    }

    function applyLockout(untilTs) {
        // disable inputs and links
        disableInputsAndLinks();
        // show timer
        loginAttemptsDiv.style.display = 'block';
        timerTextSpan.style.display = 'block';
        startCountdown(untilTs);
    }

    function startCountdown(untilTs) {
        function update() {
            const now = Date.now();
            let remaining = Math.max(0, Math.ceil((untilTs - now) / 1000));
            timerTextSpan.textContent = `SYSTEM LOCKED: ${remaining}s`;
            if (remaining <= 0) {
                clearLockoutState();
                return;
            }
            countdownId = setTimeout(update, 1000);
        }
        if (typeof countdownId !== 'undefined') {
            try { clearTimeout(countdownId); } catch(e) {}
        }
        update();
    }

    function disableInputsAndLinks() {
        usernameInput.disabled = true;
        passwordInput.disabled = true;
        submitButton.disabled = true;
        // disable Home and Register links
        document.querySelectorAll('a.cyber-link').forEach(a => {
            if (/login\.php$/i.test(a.getAttribute('href') || '')) return; // allow login
            a.classList.add('disabled');
            a.dataset.href = a.getAttribute('href') || '';
            a.setAttribute('href', 'javascript:void(0)');
            a.addEventListener('click', preventClick, { capture: true, once: false });
        });
    }

    function enableInputsAndLinks() {
        usernameInput.disabled = false;
        passwordInput.disabled = false;
        submitButton.disabled = false;
        document.querySelectorAll('a.cyber-link.disabled').forEach(a => {
            a.classList.remove('disabled');
            if (a.dataset.href) a.setAttribute('href', a.dataset.href);
            a.removeEventListener('click', preventClick, { capture: true });
        });
    }

    function preventClick(e) {
        e.preventDefault();
        e.stopPropagation();
        showErrorMessage('SYSTEM LOCKED. Please wait.');
    }

    function getOrCreateErrorElement(input) {
        let errorElement = document.getElementById(input.id + '-error') ||
                           (input.closest('.input-group') ? input.closest('.input-group').querySelector('.error-message') : null) ||
                           input.parentNode.querySelector('.error-message');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            errorElement.id = input.id + '-error';
            if (input.closest('.input-group')) {
                input.closest('.input-group').appendChild(errorElement);
            } else {
                input.parentNode.appendChild(errorElement);
            }
        }
        return errorElement;
    }

    function showError(input, errorElement, message) {
        input.classList.add('error');
        errorElement.textContent = message;
        errorElement.classList.add('show');
        
        // Add cyberpunk error effect
        input.style.borderColor = '#ff0040';
        input.style.boxShadow = '0 0 20px rgba(255, 0, 64, 0.5)';
        
        // Add glitch effect
        setTimeout(() => {
            input.style.textShadow = '2px 0 #ff0040, -2px 0 #00ffff';
            setTimeout(() => {
                input.style.textShadow = '';
            }, 100);
        }, 200);
    }

    function clearError(input, errorElement) {
        input.classList.remove('error');
        errorElement.classList.remove('show');
        input.style.borderColor = '';
        input.style.boxShadow = '';
        input.style.textShadow = '';
    }

    function updateForgotPromptDisplay(attempts) {
        const container = forgotPrompt ? forgotPrompt.closest('.login-attempts') : null;
        if (!forgotPrompt || !container) return;
        if (attempts >= 2) {
            forgotPrompt.style.display = 'flex';
        } else {
            forgotPrompt.style.display = 'none';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function showSuccessMessage(message) {
        const existing = document.querySelector('.cyber-success-message');
        if (existing) existing.remove();

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const messageDiv = document.createElement('div');
        messageDiv.className = 'cyber-success-message';
        messageDiv.textContent = message;
        messageDiv.style.cssText = `
            position: fixed;
            top: 24px;
            right: 24px;
            max-width: 440px;
            background: ${isDark ? '#1e293b' : '#ffffff'};
            color: ${isDark ? '#34d399' : '#065f46'};
            border: 1px solid ${isDark ? 'rgba(52, 211, 153, 0.3)' : 'rgba(16, 185, 129, 0.22)'};
            border-left: 4px solid ${isDark ? '#34d399' : '#047857'};
            padding: 12px 18px;
            border-radius: 10px;
            z-index: 10000;
            font-family: 'Inter', sans-serif;
            font-size: 0.86rem;
            font-weight: 600;
            line-height: 1.45;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        `;
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateY(-8px)';
            setTimeout(() => {
                messageDiv.remove();
            }, 300);
        }, 3000);
    }

    function showErrorMessage(message) {
        const existing = document.querySelector('.cyber-error-message');
        if (existing) existing.remove();

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const messageDiv = document.createElement('div');
        messageDiv.className = 'cyber-error-message';
        messageDiv.textContent = message;
        messageDiv.style.cssText = `
            position: fixed;
            top: 24px;
            right: 24px;
            max-width: 440px;
            background: ${isDark ? '#1e293b' : '#ffffff'};
            color: ${isDark ? '#f87171' : '#991b1b'};
            border: 1px solid ${isDark ? 'rgba(248, 113, 113, 0.3)' : '#fecaca'};
            border-left: 4px solid #ef4444;
            padding: 12px 18px;
            border-radius: 10px;
            z-index: 10000;
            font-family: 'Inter', sans-serif;
            font-size: 0.86rem;
            font-weight: 600;
            line-height: 1.45;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        `;
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateY(-8px)';
            setTimeout(() => {
                messageDiv.remove();
            }, 300);
        }, 5000);
    }

    // Auto-focus on username field
    usernameInput.focus();
});

// Cyber eye SVG icon
function getEyeSVG(isVisible) {
    const stroke = isVisible ? '#ff00ff' : '#00eaff';
    const glow = isVisible ? 'rgba(255,0,255,0.6)' : 'rgba(0,234,255,0.6)';
    const slash = isVisible ? '<line x1="3" y1="3" x2="21" y2="21" stroke-width="2"></line>' : '';
    return `
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="${stroke}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0 0 6px ${glow}); pointer-events: none; display: block;">
        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
        <circle cx="12" cy="12" r="3"></circle>
        ${slash}
      </svg>
    `;
}
