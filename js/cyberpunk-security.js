class CyberpunkSecurity {
    constructor() {
        this.form = document.getElementById('securityForm');
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.addCyberpunkEffects();
        this.initializeShowPasswordIcons();
    }

    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Real-time validation
        const inputs = this.form.querySelectorAll('.cyber-input');
        inputs.forEach(input => {
            if(input.type === 'password' && !input.readOnly) {
                input.addEventListener('blur', () => this.validateField(input));
                input.addEventListener('input', () => this.clearError(input));
            } else if (input.tagName === 'SELECT') {
                input.addEventListener('change', () => this.clearError(input));
            }
        });

        // Security Questions mutually exclusive selection
        const qSelects = [
            document.getElementById('auth_question1'),
            document.getElementById('auth_question2'),
            document.getElementById('auth_question3')
        ].filter(Boolean);

        const updateQuestionOptions = () => {
            const selectedValues = qSelects.map(s => s.value).filter(v => v !== "");
            qSelects.forEach(select => {
                Array.from(select.options).forEach(option => {
                    if (option.value === "") return;
                    if (selectedValues.includes(option.value) && select.value !== option.value) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });
            });
        };

        qSelects.forEach(select => {
            select.addEventListener('change', updateQuestionOptions);
        });
        if (qSelects.length > 0) updateQuestionOptions();
    }

    initializeShowPasswordIcons() {
        // Initialize SVG icons for all show-password buttons
        const buttons = this.form.querySelectorAll('.show-password');
        buttons.forEach(btn => {
            btn.setAttribute('aria-label', 'Show password');
            btn.setAttribute('aria-pressed', 'false');
            btn.innerHTML = getEyeSVG(false);
        });
    }

    addCyberpunkEffects() {
        // Add glitch effect to inputs on focus
        const inputs = document.querySelectorAll('.cyber-input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                this.addGlitchEffect(input);
                input.parentElement.style.transform = 'scale(1.02)';
            });
            input.addEventListener('blur', () => {
                input.parentElement.style.transform = 'scale(1)';
            });
        });

        // Add terminal typing effect
        this.simulateTerminalTyping();
    }

    simulateTerminalTyping() {
        const prompt = document.querySelector('.terminal-prompt .prompt-text');
        if (prompt) {
            const text = 'user@neuralnet:~$';
            prompt.textContent = '';
            
            let i = 0;
            const typeWriter = () => {
                if (i < text.length) {
                    prompt.textContent += text.charAt(i);
                    i++;
                    setTimeout(typeWriter, 100);
                }
            };
            
            setTimeout(typeWriter, 1000);
        }
    }

    addGlitchEffect(element) {
        element.style.textShadow = '2px 0 #ff0080, -2px 0 #00ffff';
        setTimeout(() => {
            element.style.textShadow = 'none';
        }, 200);
    }

    validateField(field) {
        const fieldId = field.id;
        const value = field.value.trim();
        const errorElement = document.getElementById(fieldId + 'Error');
        
        if (!errorElement) return true;

        let isValid = true;
        let errorMessage = '';

        const isQuestion = fieldId.includes('question');
        const fieldName = isQuestion ? 'Question' : 'Answer';

        if (!value) {
            errorMessage = `${fieldName} is required`;
            isValid = false;
        } else if (!isQuestion && value.length < 3) {
            errorMessage = 'Answer must be at least 3 characters';
            isValid = false;
        }

        if (!isValid) {
            this.showError(field, errorElement, errorMessage);
        } else {
            this.clearError(field);
        }

        return isValid;
    }

    showError(field, errorElement, message) {
        field.style.borderColor = 'var(--cyber-error)';
        field.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }

    clearError(field) {
        const errorElement = document.getElementById(field.id + 'Error');
        if (errorElement) {
            field.style.borderColor = '';
            field.style.boxShadow = '';
            field.classList.remove('error-field');
            errorElement.textContent = '';
            errorElement.classList.remove('show');
        }
    }

    validateForm() {
        let isValid = true;
        const fields = ['auth_question1', 'answer1', 'auth_question2', 'answer2', 'auth_question3', 'answer3'];
        
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && !this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }
        
        this.submitForm();
    }

    submitForm() {
        const submitBtn = this.form.querySelector('button[type="submit"]');
        const originalText = submitBtn.querySelector('.btn-text').textContent;
        
        // Show loading state
        submitBtn.querySelector('.btn-text').textContent = 'FINALIZING...';
        submitBtn.disabled = true;
        
        // Simulate cyberpunk loading effect
        this.simulateLoading();
        
        // Collect form data
        const formData = new FormData(this.form);
        const data = Object.fromEntries(formData);
        
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        if (csrfToken) {
            data.csrf_token = csrfToken;
        }

        fetch('../php/register_final.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(data)
        })
        .then(async response => {
            const text = await response.text();
            try {
                const json = JSON.parse(text);
                return { ok: response.ok, json, raw: text };
            } catch (e) {
                return { ok: response.ok, json: null, raw: text };
            }
        })
        .then(result => {
            if (!result.ok || !result.json) {
                console.error('Registration response not OK or invalid JSON:', result.raw);
                this.showSystemError('System error. Please try again.');
                return;
            }
            const data = result.json;
            if (data.success) {
                this.showSuccess('Neural profile completely initialized!');
                sessionStorage.setItem('justRegistered', '1');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
            } else {
                if (data.fieldErrors) {
                    this.showFieldErrors(data.fieldErrors);
                }
                this.showSystemError(data.message || 'Registration failed');
                
                // If session expired, redirect to step 1
                if (data.message && data.message.includes('Session expired')) {
                    setTimeout(() => {
                        window.location.href = 'register.php';
                    }, 2000);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showSystemError('System error. Please try again.');
        })
        .finally(() => {
            // Reset button
            submitBtn.querySelector('.btn-text').textContent = originalText;
            submitBtn.disabled = false;
        });
    }

    simulateLoading() {
        const terminal = document.querySelector('.terminal-prompt .cursor-blink');
        if (terminal) {
            let dots = 0;
            const interval = setInterval(() => {
                dots = (dots + 1) % 4;
                terminal.textContent = '.'.repeat(dots) + '_';
            }, 500);
            
            setTimeout(() => {
                clearInterval(interval);
                terminal.textContent = '_';
            }, 3000);
        }
    }

    showSuccess(message) {
        this.showSystemMessage(message, 'success');
    }

    showSystemError(message) {
        this.showSystemMessage(message, 'error');
    }

    showFieldErrors(fieldErrors) {
        this.clearAllErrors();
        
        Object.keys(fieldErrors).forEach(fieldName => {
            const field = document.getElementById(fieldName);
            const errorElement = document.getElementById(fieldName + 'Error');
            
            if (field) {
                field.style.borderColor = 'var(--cyber-error)';
                field.style.boxShadow = '0 0 10px rgba(255, 107, 107, 0.3)';
                field.classList.add('error-field');
            }
            
            if (errorElement) {
                errorElement.textContent = fieldErrors[fieldName];
                errorElement.classList.add('show');
            }
        });
    }

    clearAllErrors() {
        const errorElements = document.querySelectorAll('.error-message');
        errorElements.forEach(element => {
            element.textContent = '';
            element.classList.remove('show');
        });
        
        const fields = document.querySelectorAll('.cyber-input');
        fields.forEach(field => {
            field.style.borderColor = '';
            field.style.boxShadow = '';
            field.classList.remove('error-field');
        });
    }

    showSystemMessage(message, type) {
        const overlay = document.createElement('div');
        overlay.className = `system-message ${type}`;
        overlay.innerHTML = `
            <div class="message-content">
                <div class="message-icon">${type === 'success' ? '✓' : '⚠'}</div>
                <div class="message-text">${message}</div>
            </div>
        `;
        
        overlay.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.9);
            border: 2px solid ${type === 'success' ? 'var(--cyber-success)' : 'var(--cyber-error)'};
            border-radius: 8px;
            padding: 1rem;
            z-index: 10000;
            color: var(--cyber-text);
            font-family: 'Orbitron', monospace;
            text-align: center;
            box-shadow: 0 0 30px ${type === 'success' ? 'rgba(0, 255, 0, 0.3)' : 'rgba(255, 0, 64, 0.3)'};
        `;
        
        document.body.appendChild(overlay);
        
        setTimeout(() => {
            overlay.remove();
        }, 3000);
    }
}

// Password toggle functionality with cyber SVG
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

document.addEventListener('DOMContentLoaded', () => {
    new CyberpunkSecurity();
});
