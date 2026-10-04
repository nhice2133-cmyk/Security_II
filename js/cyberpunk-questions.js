class CyberpunkQuestions {
    constructor() {
        this.form = document.getElementById('questionsForm');
        this.guardAccess();
        this.init();
    }

    guardAccess() {
        const mode = this.getQueryParam('mode');
        if (mode === 'forgot') return; // allow forgot flow without justRegistered
        const flag = sessionStorage.getItem('justRegistered');
        if (!flag) window.location.href = 'register.php';
    }

    init() {
        if (!this.form) return;
        const mode = this.getQueryParam('mode');
        if (mode === 'forgot') {
            this.initForgotFlow();
        } else {
            this.setupValidation();
            this.setupSubmit();
            this.setupQuestionDependencies();
        }
        this.simulateTerminalTyping();
    }

    getQueryParam(key) {
        const params = new URLSearchParams(window.location.search);
        return params.get(key);
    }

    initForgotFlow() {
        const idNumber = sessionStorage.getItem('fp_idNumber');
        if (!idNumber) {
            window.location.href = 'forgot-password.php';
            return;
        }

        // Replace selects with static questions fetched from server
        fetch('../php/get_security_questions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idNumber })
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                alert(res.message || 'Unable to load security questions');
                window.location.href = 'forgot-password.php';
                return;
            }
            const { question1, question2, question3 } = res.data;
            this.setQuestionText('auth_question1', question1);
            this.setQuestionText('auth_question2', question2);
            this.setQuestionText('auth_question3', question3);
        })
        .catch(() => {
            alert('Network error');
            window.location.href = 'forgot-password.php';
        });

        // On submit, verify answers then redirect to change-password.php
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            const a1 = (document.getElementById('auth_answer1')?.value || '').trim();
            const a2 = (document.getElementById('auth_answer2')?.value || '').trim();
            const a3 = (document.getElementById('auth_answer3')?.value || '').trim();
            if (!a1 || !a2 || !a3) { alert('Please answer all questions'); return; }
            fetch('../php/verify_security_answers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idNumber, answer1: a1, answer2: a2, answer3: a3 })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    // store token-less indicator (ID number) and go to change-password
                        sessionStorage.setItem('cp_idNumber', idNumber);
                        sessionStorage.setItem('fp_answers', JSON.stringify({ answer1: a1, answer2: a2, answer3: a3 }));
                    window.location.href = 'change-password.php?mode=forgot';
                } else {
                    alert(res.message || 'Answers did not match');
                }
            })
            .catch(() => alert('Network error'));
        });
    }

    injectNewPasswordFields() {
        const grid = this.form.querySelector('.form-grid');
        if (!grid) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'input-group full';
        wrapper.innerHTML = `
            <label class="cyber-label">NEW PASSWORD*</label>
            <div class="input-container">
                <input type="password" class="cyber-input" id="fp_new_pwd" required>
                <div class="input-glow"></div>
            </div>
            <div class="error-message" id="fp_new_pwd_error"></div>
            <label class="cyber-label">CONFIRM NEW PASSWORD*</label>
            <div class="input-container">
                <input type="password" class="cyber-input" id="fp_new_pwd2" required>
                <div class="input-glow"></div>
            </div>
            <div class="error-message" id="fp_new_pwd2_error"></div>
        `;
        grid.appendChild(wrapper);
    }

    setQuestionText(selectId, text) {
        const sel = document.getElementById(selectId);
        if (sel && sel.tagName === 'SELECT') {
            sel.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = text;
            sel.appendChild(opt);
            sel.disabled = true;
        }
    }

    setupQuestionDependencies() {
        const q1 = document.getElementById('auth_question1');
        const q2 = document.getElementById('auth_question2');
        const q3 = document.getElementById('auth_question3');
        if (!q1 || !q2 || !q3) return;

        // Capture original option sets (excluding the placeholder empty option)
        const original = Array.from(q1.options).map(o => ({ value: o.value, text: o.text }));

        const refresh = () => {
            const s1 = q1.value;
            const s2 = q2.value;

            // Helper to rebuild options with exclusions, keeping placeholder
            const rebuild = (select, excludeValues, selectedValue) => {
                const placeholder = original[0];
                select.innerHTML = '';
                const makeOpt = ({ value, text }) => {
                    const o = document.createElement('option');
                    o.value = value; o.textContent = text; return o;
                };
                if (placeholder && placeholder.value === '') {
                    select.appendChild(makeOpt(placeholder));
                }
                original.slice(1).forEach(item => {
                    if (!excludeValues.includes(item.value)) {
                        select.appendChild(makeOpt(item));
                    }
                });
                // Restore previous selection if still available
                if (selectedValue && Array.from(select.options).some(o => o.value === selectedValue)) {
                    select.value = selectedValue;
                } else {
                    select.value = '';
                }
            };

            // q2 cannot pick q1's choice
            rebuild(q2, [s1].filter(Boolean), s2);
            // q3 cannot pick q1 or q2 choices
            const s3 = q3.value;
            rebuild(q3, [s1, q2.value].filter(Boolean), s3);
        };

        q1.addEventListener('change', refresh);
        q2.addEventListener('change', refresh);
        // Initial pass
        refresh();
    }

    setupValidation() {
        const answers = ['auth_answer1','auth_answer2','auth_answer3'];
        answers.forEach(id => {
            const el = document.getElementById(id);
            const err = document.getElementById(id + '-error');
            if (el && err) {
                el.addEventListener('blur', () => {
                    const val = el.value.trim();
                    if (!val) {
                        this.showError(el, err, 'This field is required');
                    } else {
                        this.clearError(el, err);
                    }
                });
            }
        });
    }

    setupSubmit() {
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();

            // basic required check
            const requiredIds = [
                'auth_question1','auth_answer1',
                'auth_question2','auth_answer2',
                'auth_question3','auth_answer3'
            ];

            for (let i = 0; i < requiredIds.length; i++) {
                const id = requiredIds[i];
                const el = document.getElementById(id);
                if (!el || !el.value || !el.value.trim()) {
                    alert('Please complete all questions and answers');
                    return;
                }
            }

            // Persist to backend: save security questions for logged-in user
            const payload = {
                q1: document.getElementById('auth_question1').value,
                a1: document.getElementById('auth_answer1').value.trim(),
                q2: document.getElementById('auth_question2').value,
                a2: document.getElementById('auth_answer2').value.trim(),
                q3: document.getElementById('auth_question3').value,
                a3: document.getElementById('auth_answer3').value.trim(),
            };

            // Use finalize_registration for initial user creation flow
            const submitBtn = this.form.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.querySelector('.btn-text')?.textContent : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                if (submitBtn.querySelector('.btn-text')) {
                    submitBtn.querySelector('.btn-text').textContent = 'PROCESSING...';
                }
            }

            fetch('../php/finalize_registration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error('Network response was not ok');
                }
                return r.json();
            })
            .then(res => {
                if (res.success) {
                    sessionStorage.removeItem('justRegistered');
                    // Show success message
                    this.showSuccessMessage('Registration successful! You can now login with your credentials.');
                    // Redirect after showing message
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    alert(res.message || 'Failed to save security questions');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (submitBtn.querySelector('.btn-text') && originalText) {
                            submitBtn.querySelector('.btn-text').textContent = originalText;
                        }
                    }
                }
            })
            .catch((error) => {
                console.error('Registration error:', error);
                alert('Network error. Please try again.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    if (submitBtn.querySelector('.btn-text') && originalText) {
                        submitBtn.querySelector('.btn-text').textContent = originalText;
                    }
                }
            });
        });
    }

    showError(el, err, msg) {
        el.style.borderColor = 'var(--cyber-error)';
        el.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
        err.textContent = msg;
        err.classList.add('show');
    }

    clearError(el, err) {
        el.style.borderColor = 'var(--cyber-border)';
        el.style.boxShadow = 'none';
        err.textContent = '';
        err.classList.remove('show');
    }

    showSuccessMessage(message) {
        // Create success message overlay
        const overlay = document.createElement('div');
        overlay.className = 'system-message success';
        overlay.innerHTML = `
            <div class="message-content">
                <div class="message-icon">✓</div>
                <div class="message-text">${message}</div>
            </div>
        `;
        
        // Add styles
        overlay.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.95);
            border: 2px solid #00ff85;
            border-radius: 8px;
            padding: 1.5rem 2rem;
            z-index: 10000;
            color: #ffffff;
            font-family: 'Orbitron', monospace;
            text-align: center;
            box-shadow: 0 0 40px rgba(0, 255, 133, 0.5), inset 0 0 20px rgba(0, 255, 133, 0.1);
            animation: cyber-success-pulse 0.5s ease-out;
        `;
        
        // Add icon and text styles (only if not already added)
        if (!document.getElementById('cyber-success-styles')) {
            const style = document.createElement('style');
            style.id = 'cyber-success-styles';
            style.textContent = `
                .system-message.success .message-icon {
                    font-size: 3rem;
                    color: #00ff85;
                    text-shadow: 0 0 20px #00ff85;
                    margin-bottom: 0.5rem;
                    animation: cyber-success-icon 1s ease-in-out infinite;
                }
                .system-message.success .message-text {
                    font-size: 1rem;
                    font-weight: 700;
                    letter-spacing: 1px;
                    text-transform: uppercase;
                    color: #00ffff;
                    text-shadow: 0 0 10px #00ffff;
                }
                @keyframes cyber-success-pulse {
                    0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
                    100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                }
                @keyframes cyber-success-icon {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(overlay);
    }

    simulateTerminalTyping() {
        const prompt = document.querySelector('.terminal-prompt .prompt-text');
        if (!prompt) return;
        const text = 'user@neuralnet:~$';
        prompt.textContent = '';
        let i = 0;
        const type = () => {
            if (i < text.length) { prompt.textContent += text.charAt(i++); setTimeout(type, 90); }
        };
        setTimeout(type, 800);
    }
}

document.addEventListener('DOMContentLoaded', () => new CyberpunkQuestions());


