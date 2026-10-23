/**
 * Public Registration Form - 3-Step Wizard Controller
 * Handles client-side navigation, real-time input validation, age calculation,
 * password strength evaluation, security question synchronization, and AJAX submission.
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── Elements ─────────────────────────────────────────────────────────────
    const form = document.getElementById('registrationWizardForm');
    if (!form) return;

    const step1Pane = document.getElementById('step1Pane');
    const step2Pane = document.getElementById('step2Pane');
    const step3Pane = document.getElementById('step3Pane');
    const panes = [step1Pane, step2Pane, step3Pane];

    const step1NextBtn = document.getElementById('step1NextBtn');
    const step2BackBtn = document.getElementById('step2BackBtn');
    const step2NextBtn = document.getElementById('step2NextBtn');
    const step3BackBtn = document.getElementById('step3BackBtn');
    const completeRegBtn = document.getElementById('completeRegBtn');
    const completeRegText = document.getElementById('completeRegText');

    // Inputs - Step 1
    const idNumberInput = document.getElementById('idNumber');
    const firstNameInput = document.getElementById('firstName');
    const middleInitialInput = document.getElementById('middleInitial');
    const lastNameInput = document.getElementById('lastName');
    const suffixSelect = document.getElementById('suffix');
    const sexSelect = document.getElementById('sex');
    const birthDateInput = document.getElementById('birthDate');
    const ageInput = document.getElementById('age');

    // Inputs - Step 2
    const purokStreetInput = document.getElementById('purokStreet');
    const barangayInput = document.getElementById('barangay');
    const cityInput = document.getElementById('city');
    const provinceInput = document.getElementById('province');
    const countryInput = document.getElementById('country');
    const zipCodeInput = document.getElementById('zipCode');

    // Inputs - Step 3
    const emailInput = document.getElementById('email');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const q1Select = document.getElementById('q1');
    const a1Input = document.getElementById('a1');
    const q2Select = document.getElementById('q2');
    const a2Input = document.getElementById('a2');
    const q3Select = document.getElementById('q3');
    const a3Input = document.getElementById('a3');

    // Strength Meter
    const strSeg1 = document.getElementById('strSeg1');
    const strSeg2 = document.getElementById('strSeg2');
    const strSeg3 = document.getElementById('strSeg3');
    const strSeg4 = document.getElementById('strSeg4');
    const strengthBadge = document.getElementById('strengthBadge');
    const toastContainer = document.getElementById('regToastContainer');

    // ── State ────────────────────────────────────────────────────────────────
    let currentStep = 1;
    let isDirty = false;
    let isSubmitting = false;

    // Track field interaction states
    const fieldStates = {
        firstName: { touched: false, wasBlurred: false, lastError: null },
        middleInitial: { touched: false, wasBlurred: false, lastError: null },
        lastName: { touched: false, wasBlurred: false, lastError: null },
        suffix: { touched: false, wasBlurred: false, lastError: null },
        sex: { touched: false, wasBlurred: false, lastError: null },
        birthDate: { touched: false, wasBlurred: false, lastError: null },
        age: { touched: false, wasBlurred: false, lastError: null },
        purokStreet: { touched: false, wasBlurred: false, lastError: null },
        barangay: { touched: false, wasBlurred: false, lastError: null },
        city: { touched: false, wasBlurred: false, lastError: null },
        province: { touched: false, wasBlurred: false, lastError: null },
        country: { touched: false, wasBlurred: false, lastError: null },
        zipCode: { touched: false, wasBlurred: false, lastError: null },
        email: { touched: false, wasBlurred: false, lastError: null, isDuplicate: false },
        username: { touched: false, wasBlurred: false, lastError: null, isDuplicate: false },
        password: { touched: false, wasBlurred: false, lastError: null },
        confirmPassword: { touched: false, wasBlurred: false, lastError: null },
        q1: { touched: false, wasBlurred: false, lastError: null },
        a1: { touched: false, wasBlurred: false, lastError: null },
        q2: { touched: false, wasBlurred: false, lastError: null },
        a2: { touched: false, wasBlurred: false, lastError: null },
        q3: { touched: false, wasBlurred: false, lastError: null },
        a3: { touched: false, wasBlurred: false, lastError: null }
    };

    // Track dirty form
    form.addEventListener('input', () => { isDirty = true; });
    form.addEventListener('change', () => { isDirty = true; });

    window.addEventListener('beforeunload', (e) => {
        if (isDirty && !isSubmitting) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Setup accessibility attributes
    document.querySelectorAll('.custom-input, .custom-select').forEach(input => {
        if (input.id) {
            input.setAttribute('aria-describedby', `${input.id}Error`);
        }
    });

    // ── Load Next Available ID Preview ───────────────────────────────────────
    fetch('../php/get_next_id.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.idNumber) {
                idNumberInput.value = data.idNumber;
            } else {
                const curYear = new Date().getFullYear();
                idNumberInput.value = `${curYear}-0001`;
            }
        })
        .catch(() => {
            const curYear = new Date().getFullYear();
            idNumberInput.value = `${curYear}-0001`;
        });

    // ── Helpers: Toast Notifications ─────────────────────────────────────────
    function showToast(message, type = 'info', duration = 4000) {
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = `reg-toast ${type === 'success' ? 'reg-toast-success' : type === 'error' ? 'reg-toast-error' : ''}`;
        
        const icon = type === 'success' ? '✓' : type === 'error' ? '⚠' : 'ℹ';
        toast.innerHTML = `<span style="font-size: 1.1rem; color: ${type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#00e5ff'};">${icon}</span> <span>${escapeHtml(message)}</span>`;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(40px)';
            setTimeout(() => { toast.remove(); }, 300);
        }, duration);
    }

    function escapeHtml(str) {
        return (str || '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }

    // ── Visual Validation State Rendering ────────────────────────────────────
    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errContainer = document.getElementById(`${fieldId}Error`);
        if (field) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            field.setAttribute('aria-invalid', 'true');
        }
        if (errContainer) {
            const textEl = errContainer.querySelector('.error-text');
            if (textEl) textEl.textContent = message;
            errContainer.classList.add('visible');
        }
    }

    function markFieldValid(fieldId) {
        const field = document.getElementById(fieldId);
        const errContainer = document.getElementById(`${fieldId}Error`);
        if (field) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            field.setAttribute('aria-invalid', 'false');
        }
        if (errContainer) {
            errContainer.classList.remove('visible');
            const textEl = errContainer.querySelector('.error-text');
            if (textEl) textEl.textContent = '';
        }
    }

    function clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        const errContainer = document.getElementById(`${fieldId}Error`);
        if (field) {
            field.classList.remove('is-invalid');
            field.classList.remove('is-valid');
            field.removeAttribute('aria-invalid');
        }
        if (errContainer) {
            errContainer.classList.remove('visible');
            const textEl = errContainer.querySelector('.error-text');
            if (textEl) textEl.textContent = '';
        }
    }

    // ── Pure Validation Rule Checkers ────────────────────────────────────────

    /**
     * Name validation rules:
     * 1. Required: "This field is required."
     * 2. Leading/trailing space: "Remove spaces at the beginning or end."
     * 3. Consecutive spaces (/ {2,}/): "Double spaces are not allowed."
     * 4. Three consecutive identical characters (/(.)\1\1/i): "Three identical characters in a row are not allowed."
     * 5. Capitalization (/^\p{Lu}/u): "The first letter must be capitalized."
     * 6. Allowed characters (/^[\p{L}][\p{L}\p{M} '\u2019.-]*$/u): "Please enter valid characters."
     * 7. Min 2 letters: "Name must contain at least 2 letters."
     * 8. Max 50 characters: "Name must not exceed 50 characters."
     */
    function checkWordCapitalization(val) {
        const words = val.split(/[\s\-_.'\u2019]+/).filter(Boolean);
        for (const w of words) {
            const wLetters = w.match(/\p{L}/gu) || [];
            if (wLetters.length === 0) continue;
            if (!/^\p{Lu}/u.test(w)) {
                return 'The first letter must be capitalized.';
            }
            const caps = w.match(/\p{Lu}/gu) || [];
            if (caps.length > 1) {
                return 'Each word must contain only 1 capital letter.';
            }
        }
        return null;
    }

    function getFirstNameError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'This field is required.';
        if (val.startsWith(' ') || (isBlurred && val.endsWith(' '))) {
            return 'Remove spaces at the beginning or end.';
        }
        if (/ {2,}/.test(val)) return 'Double spaces are not allowed.';
        if (/(.)\1\1/i.test(val)) return 'Three identical characters in a row are not allowed.';
        if (!/^\p{Lu}/u.test(val)) return 'The first letter must be capitalized.';
        if (!/^[\p{L}][\p{L}\p{M} '\u2019.-]*$/u.test(val)) return 'Please enter valid characters.';
        
        const capErr = checkWordCapitalization(val);
        if (capErr) return capErr;
        
        const letters = val.match(/\p{L}/gu) || [];
        if (letters.length < 2) {
            if (!isBlurred && val.length < 2) return null;
            return 'Name must contain at least 2 letters.';
        }
        if (Array.from(val).length > 50) return 'Name must not exceed 50 characters.';
        if (val.endsWith(' ')) return 'Remove spaces at the beginning or end.';
        return null;
    }

    function getMiddleNameError(val, isBlurred = false) {
        if (!val || val.trim() === '') return null; // Optional
        if (val.startsWith(' ') || (isBlurred && val.endsWith(' '))) {
            return 'Remove spaces at the beginning or end.';
        }
        if (/ {2,}/.test(val)) return 'Double spaces are not allowed.';
        if (/(.)\1\1/i.test(val)) return 'Three identical characters in a row are not allowed.';
        if (!/^\p{Lu}/u.test(val)) return 'The first letter must be capitalized.';
        if (!/^[\p{L}][\p{L}\p{M} '\u2019.-]*$/u.test(val)) return 'Please enter valid characters.';

        const capErr = checkWordCapitalization(val);
        if (capErr) return capErr;

        if (Array.from(val).length > 50) return 'Name must not exceed 50 characters.';
        if (val.endsWith(' ')) return 'Remove spaces at the beginning or end.';
        return null;
    }

    function getLastNameError(val, isBlurred = false) {
        return getFirstNameError(val, isBlurred);
    }

    /**
     * Location validation rules (Barangay, City, Province, Country):
     * Same as names, but allows numbers (e.g. Barangay 10).
     */
    function getLocationError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'This field is required.';
        if (val.startsWith(' ') || (isBlurred && val.endsWith(' '))) {
            return 'Remove spaces at the beginning or end.';
        }
        if (/ {2,}/.test(val)) return 'Double spaces are not allowed.';
        if (/(.)\1\1/i.test(val)) return 'Three identical characters in a row are not allowed.';
        if (!/^\p{Lu}/u.test(val)) return 'The first letter must be capitalized.';
        if (!/^[\p{L}][\p{L}0-9\p{M} '\u2019.-]*$/u.test(val)) return 'Please enter valid characters.';

        const capErr = checkWordCapitalization(val);
        if (capErr) return capErr;

        const letters = val.match(/\p{L}/gu) || [];
        if (letters.length < 2) {
            if (!isBlurred && val.length < 2) return null;
            return 'Name must contain at least 2 letters.';
        }
        if (Array.from(val).length > 50) return 'Name must not exceed 50 characters.';
        if (val.endsWith(' ')) return 'Remove spaces at the beginning or end.';
        return null;
    }

    /**
     * Street address validation:
     * Min 2 chars, max 100 chars, no double spaces, no 3 identical chars.
     */
    function getStreetError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'This field is required.';
        if (val.startsWith(' ') || (isBlurred && val.endsWith(' '))) {
            return 'Remove spaces at the beginning or end.';
        }
        if (/ {2,}/.test(val)) return 'Double spaces are not allowed.';
        if (/(.)\1\1/i.test(val)) return 'Three identical characters in a row are not allowed.';
        if (Array.from(val).length < 2) {
            if (!isBlurred) return null;
            return 'Address must contain at least 2 characters.';
        }
        if (Array.from(val).length > 100) return 'Address must not exceed 100 characters.';
        if (!/^[\p{L}0-9\p{M} .,\/'#\-]+$/u.test(val)) return 'Please enter valid characters.';
        if (val.endsWith(' ')) return 'Remove spaces at the beginning or end.';
        return null;
    }

    function getZipError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'This field is required.';
        if (!/^\d{4}$/.test(val)) {
            if (!isBlurred && val.length < 4) return null;
            return 'Zip code must be 4 digits.';
        }
        return null;
    }

    function getSexError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (!val || val.trim() === '') return 'This field is required.';
        return null;
    }

    function calculateAge(birthDateStr) {
        if (!birthDateStr) return null;
        const birthDate = new Date(birthDateStr);
        if (isNaN(birthDate.getTime())) return null;

        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age;
    }

    function getBirthDateError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (!val || val.trim() === '') return 'This field is required.';
        const bDate = new Date(val);
        if (isNaN(bDate.getTime())) return 'Invalid birthdate format';

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (bDate > today) return 'Birthdate cannot be in the future';

        const age = calculateAge(val);
        if (age === null || age < 18) return 'Age must be at least 18 years old';
        return null;
    }

    function updateAge() {
        const val = birthDateInput ? birthDateInput.value : '';
        if (!val) {
            if (ageInput) ageInput.value = '';
            return;
        }

        const bDate = new Date(val);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (bDate > today) {
            if (ageInput) ageInput.value = '';
            showFieldError('birthDate', 'Birthdate cannot be in the future');
            return;
        }

        const computed = calculateAge(val);
        if (computed !== null && computed >= 0) {
            if (ageInput) ageInput.value = `${computed} yrs`;
            if (computed < 18) {
                showFieldError('birthDate', 'Age must be at least 18 years old');
                showFieldError('age', 'Age must be at least 18 years old');
            } else {
                markFieldValid('birthDate');
                markFieldValid('age');
            }
        } else {
            if (ageInput) ageInput.value = '';
        }
    }

    /**
     * Email validation rules:
     * Valid format, no whitespace, max 254 chars, uniqueness check.
     */
    function getEmailFormatError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'This field is required.';
        if (/\s/.test(val)) return 'Enter a valid email address.';
        if (val.length > 254) return 'Enter a valid email address.';
        const emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;
        if (!emailRegex.test(val)) {
            if (!isBlurred) return null;
            return 'Enter a valid email address.';
        }
        return null;
    }

    /**
     * Username validation rules:
     * 3-20 chars, letters, numbers, dots, underscores, uniqueness check.
     */
    function getUsernameFormatError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'This field is required.';
        if (/\s/.test(val)) return 'Username does not meet the requirements.';
        if (val.length > 20) return 'Username does not meet the requirements.';
        if (!/^[a-zA-Z0-9._]+$/.test(val)) return 'Username does not meet the requirements.';
        if (val.length < 3) {
            if (!isBlurred) return null;
            return 'Username does not meet the requirements.';
        }
        return null;
    }

    /**
     * Password validation rules (do NOT trim):
     * Min 8 chars, max 128 chars, at least one uppercase, one lowercase, one number.
     */
    function getPasswordError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'This field is required.';
        if (val.length < 8) return 'Password must contain at least 8 characters.';
        if (val.length > 128) return 'Password must not exceed 128 characters.';
        if (!/[A-Z]/.test(val)) return 'Include at least one uppercase letter.';
        if (!/[a-z]/.test(val)) return 'Include at least one lowercase letter.';
        if (!/[0-9]/.test(val)) return 'Include at least one number.';
        return null;
    }

    function getConfirmPasswordError(val, pwdVal, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'This field is required.';
        if (val !== pwdVal) return 'Passwords do not match.';
        return null;
    }

    function getSecurityQuestionError(val, num, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (!val || val.trim() === '') return `Please select Question ${num}`;
        return null;
    }

    function getSecurityAnswerError(val, qVal, isBlurred = false) {
        if (!qVal) return null;
        if (!val && !isBlurred) return null;
        if (!val || val.trim() === '') return 'This field is required.';
        return null;
    }

    // ── Debounced Uniqueness Checkers ────────────────────────────────────────
    let emailCheckTimeout = null;
    let usernameCheckTimeout = null;

    function checkEmailUniqueness(emailVal) {
        clearTimeout(emailCheckTimeout);
        emailCheckTimeout = setTimeout(() => {
            fetch('../php/check_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: emailVal })
            })
            .then(res => res.json())
            .then(data => {
                if (emailInput.value.trim().toLowerCase() !== emailVal.toLowerCase()) return;
                fieldStates.email.lastCheckedValue = emailVal.toLowerCase();
                const isTaken = (data && data.exists === true) || (data && data.data && data.data.exists === true);
                if (isTaken) {
                    fieldStates.email.isDuplicate = true;
                    showFieldError('email', 'This email address is already registered.');
                } else {
                    fieldStates.email.isDuplicate = false;
                    if (!getEmailFormatError(emailInput.value, true)) {
                        markFieldValid('email');
                    }
                }
            })
            .catch(() => {});
        }, 350);
    }

    function checkUsernameUniqueness(usernameVal) {
        clearTimeout(usernameCheckTimeout);
        usernameCheckTimeout = setTimeout(() => {
            fetch('../php/check_username.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: usernameVal })
            })
            .then(res => res.json())
            .then(data => {
                if (usernameInput.value.trim().toLowerCase() !== usernameVal.toLowerCase()) return;
                fieldStates.username.lastCheckedValue = usernameVal.toLowerCase();
                const isTaken = (data && data.exists === true) || (data && data.data && data.data.exists === true);
                if (isTaken) {
                    fieldStates.username.isDuplicate = true;
                    showFieldError('username', 'This username is already taken.');
                } else {
                    fieldStates.username.isDuplicate = false;
                    if (!getUsernameFormatError(usernameInput.value, true)) {
                        markFieldValid('username');
                    }
                }
            })
            .catch(() => {});
        }, 350);
    }

    // ── Field Validation Dispatcher ──────────────────────────────────────────
    function validateField(fieldId, isBlurred = false) {
        const state = fieldStates[fieldId];
        if (!state) return true;

        if (isBlurred) {
            state.wasBlurred = true;
            state.touched = true;
        }

        const field = document.getElementById(fieldId);
        if (!field) return true;

        if (field.disabled) {
            clearFieldError(fieldId);
            return true;
        }

        const val = field.value;
        let err = null;

        switch (fieldId) {
            case 'firstName':
                err = getFirstNameError(val, state.wasBlurred || isBlurred);
                break;
            case 'middleInitial':
                err = getMiddleNameError(val, state.wasBlurred || isBlurred);
                break;
            case 'lastName':
                err = getLastNameError(val, state.wasBlurred || isBlurred);
                break;
            case 'sex':
                err = getSexError(val, state.wasBlurred || isBlurred);
                break;
            case 'birthDate':
                err = getBirthDateError(val, state.wasBlurred || isBlurred);
                break;
            case 'purokStreet':
                err = getStreetError(val, state.wasBlurred || isBlurred);
                break;
            case 'barangay':
            case 'city':
            case 'province':
            case 'country':
                err = getLocationError(val, state.wasBlurred || isBlurred);
                break;
            case 'zipCode':
                err = getZipError(val, state.wasBlurred || isBlurred);
                break;
            case 'email':
                err = getEmailFormatError(val, state.wasBlurred || isBlurred);
                if (!err && val.trim() !== '') {
                    if (state.isDuplicate) {
                        err = 'This email address is already registered.';
                    } else {
                        checkEmailUniqueness(val.trim());
                    }
                }
                break;
            case 'username':
                err = getUsernameFormatError(val, state.wasBlurred || isBlurred);
                if (!err && val.trim() !== '') {
                    if (state.isDuplicate) {
                        err = 'This username is already taken.';
                    } else {
                        checkUsernameUniqueness(val.trim());
                    }
                }
                break;
            case 'password':
                err = getPasswordError(val, state.wasBlurred || isBlurred);
                break;
            case 'confirmPassword':
                err = getConfirmPasswordError(val, passwordInput ? passwordInput.value : '', state.wasBlurred || isBlurred);
                break;
            case 'q1':
                err = getSecurityQuestionError(val, 1, state.wasBlurred || isBlurred);
                break;
            case 'a1':
                err = getSecurityAnswerError(val, q1Select ? q1Select.value : '', state.wasBlurred || isBlurred);
                break;
            case 'q2':
                err = getSecurityQuestionError(val, 2, state.wasBlurred || isBlurred);
                break;
            case 'a2':
                err = getSecurityAnswerError(val, q2Select ? q2Select.value : '', state.wasBlurred || isBlurred);
                break;
            case 'q3':
                err = getSecurityQuestionError(val, 3, state.wasBlurred || isBlurred);
                if (!err && q1Select && q2Select && q3Select) {
                    if (q1Select.value && q2Select.value && q3Select.value) {
                        if (new Set([q1Select.value, q2Select.value, q3Select.value]).size < 3) {
                            err = 'All 3 security questions must be unique';
                        }
                    }
                }
                break;
            case 'a3':
                err = getSecurityAnswerError(val, q3Select ? q3Select.value : '', state.wasBlurred || isBlurred);
                break;
        }

        if (err) {
            state.lastError = err;
            showFieldError(fieldId, err);
            return false;
        } else {
            state.lastError = null;
            if (val !== '' || fieldId === 'middleInitial') {
                markFieldValid(fieldId);
            } else {
                clearFieldError(fieldId);
            }
            return true;
        }
    }

    // ── Bind Real-Time Listeners to All Fields ───────────────────────────────
    Object.keys(fieldStates).forEach(fieldId => {
        const el = document.getElementById(fieldId);
        if (!el) return;

        el.addEventListener('input', () => {
            fieldStates[fieldId].touched = true;
            // Clear duplicate flag only if the value actually changed from the flagged duplicate
            if (fieldId === 'email' || fieldId === 'username') {
                if (fieldStates[fieldId].lastCheckedValue !== el.value.trim().toLowerCase()) {
                    fieldStates[fieldId].isDuplicate = false;
                }
            }
            validateField(fieldId, false);

            // Cross-field revalidation
            if (fieldId === 'password' && confirmPasswordInput && confirmPasswordInput.value) {
                validateField('confirmPassword', false);
            }
        });

        el.addEventListener('blur', () => {
            fieldStates[fieldId].touched = true;
            fieldStates[fieldId].wasBlurred = true;
            validateField(fieldId, true);
        });

        el.addEventListener('change', () => {
            fieldStates[fieldId].touched = true;
            validateField(fieldId, true);
        });
    });

    // Zip code input: allow only digits up to 4
    if (zipCodeInput) {
        zipCodeInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
        });
    }

    // BirthDate input: auto-update age
    if (birthDateInput) {
        birthDateInput.addEventListener('input', updateAge);
        birthDateInput.addEventListener('change', updateAge);
    }

    // ── Password Visibility Toggles ──────────────────────────────────────────
    document.querySelectorAll('.pwd-eye-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            if (!targetInput) return;

            const isPassword = targetInput.type === 'password';
            targetInput.type = isPassword ? 'text' : 'password';

            if (isPassword) {
                btn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                `;
            } else {
                btn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                `;
            }
        });
    });

    // ── Password Strength Evaluation ─────────────────────────────────────────
    function evaluatePassword(pwd) {
        if (!pwd) return { score: 0, label: '', valid: false };

        const hasLen = pwd.length >= 8 && pwd.length <= 128;
        const hasUpper = /[A-Z]/.test(pwd);
        const hasLower = /[a-z]/.test(pwd);
        const hasNum = /[0-9]/.test(pwd);
        const hasSym = /[^A-Za-z0-9]/.test(pwd);

        let criteriaMet = 0;
        if (hasLen) criteriaMet++;
        if (hasUpper) criteriaMet++;
        if (hasLower) criteriaMet++;
        if (hasNum) criteriaMet++;
        if (hasSym) criteriaMet++;

        const fullyCompliant = hasLen && hasUpper && hasLower && hasNum;

        let score = 1;
        let label = 'Weak';
        if (criteriaMet >= 5) {
            score = 4;
            label = 'Strong';
        } else if (criteriaMet === 4) {
            score = 3;
            label = 'Good';
        } else if (criteriaMet === 3) {
            score = 2;
            label = 'Fair';
        } else {
            score = 1;
            label = 'Weak';
        }

        return { score, label, valid: fullyCompliant };
    }

    function updateStrengthMeter() {
        const val = passwordInput.value;
        const segments = [strSeg1, strSeg2, strSeg3, strSeg4];
        segments.forEach(seg => {
            if (seg) seg.className = 'strength-segment';
        });

        if (!val) {
            if (strengthBadge) strengthBadge.textContent = '';
            return;
        }

        const { score, label } = evaluatePassword(val);
        if (strengthBadge) {
            strengthBadge.textContent = label;
            if (score === 1) strengthBadge.style.color = '#ef4444';
            else if (score === 2) strengthBadge.style.color = '#f59e0b';
            else if (score === 3) strengthBadge.style.color = '#06b6d4';
            else if (score === 4) strengthBadge.style.color = '#22c55e';
        }

        let activeClass = 'active-weak';
        if (score === 2) activeClass = 'active-fair';
        if (score === 3) activeClass = 'active-good';
        if (score === 4) activeClass = 'active-strong';

        for (let i = 0; i < score; i++) {
            if (segments[i]) segments[i].classList.add(activeClass);
        }
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', updateStrengthMeter);
    }

    // ── Security Questions Synchronization ───────────────────────────────────
    const sqSelects = [q1Select, q2Select, q3Select];
    const sqAnswers = [a1Input, a2Input, a3Input];

    function syncSecurityQuestions() {
        const selectedValues = sqSelects.map(s => s ? s.value : '').filter(v => v !== '');

        sqSelects.forEach((select, idx) => {
            if (!select) return;
            const answerInput = sqAnswers[idx];
            
            if (select.value) {
                if (answerInput) answerInput.disabled = false;
            } else {
                if (answerInput) {
                    answerInput.disabled = true;
                    answerInput.value = '';
                    clearFieldError(answerInput.id);
                }
            }

            Array.from(select.options).forEach(opt => {
                if (!opt.value) return;
                const isSelectedElsewhere = (opt.value !== select.value && selectedValues.includes(opt.value));
                if (isSelectedElsewhere) {
                    opt.disabled = true;
                    opt.hidden = true;
                    opt.style.display = 'none';
                    opt.classList.add('sq-option-hidden');
                } else {
                    opt.disabled = false;
                    opt.hidden = false;
                    opt.style.display = '';
                    opt.classList.remove('sq-option-hidden');
                }
            });
        });
    }

    sqSelects.forEach((select, idx) => {
        if (!select) return;
        select.addEventListener('change', () => {
            syncSecurityQuestions();
            validateField(select.id, true);
            if (select.value && sqAnswers[idx]) {
                sqAnswers[idx].focus();
            }
        });
    });

    // Run sync immediately on startup
    syncSecurityQuestions();

    // ── Step Validation Gates ────────────────────────────────────────────────
    function validateStep1(markAll = false) {
        let isValid = true;
        let firstInvalid = null;
        const step1Keys = ['firstName', 'middleInitial', 'lastName', 'sex', 'birthDate'];
        
        updateAge();

        step1Keys.forEach(key => {
            if (markAll) {
                fieldStates[key].wasBlurred = true;
                fieldStates[key].touched = true;
            }
            const ok = validateField(key, markAll);
            if (!ok) {
                isValid = false;
                if (!firstInvalid) firstInvalid = document.getElementById(key);
            }
        });

        const bVal = birthDateInput ? birthDateInput.value : '';
        if (bVal) {
            const computed = calculateAge(bVal);
            if (computed === null || computed < 18) {
                showFieldError('age', 'Age must be at least 18 years old');
                isValid = false;
                if (!firstInvalid) firstInvalid = birthDateInput;
            } else {
                markFieldValid('age');
            }
        }

        if (firstInvalid) {
            firstInvalid.focus();
        }
        return isValid;
    }

    function validateStep2(markAll = false) {
        let isValid = true;
        let firstInvalid = null;
        const step2Keys = ['purokStreet', 'barangay', 'city', 'province', 'country', 'zipCode'];

        step2Keys.forEach(key => {
            if (markAll) {
                fieldStates[key].wasBlurred = true;
                fieldStates[key].touched = true;
            }
            const ok = validateField(key, markAll);
            if (!ok) {
                isValid = false;
                if (!firstInvalid) firstInvalid = document.getElementById(key);
            }
        });

        if (firstInvalid) {
            firstInvalid.focus();
        }
        return isValid;
    }

    function validateStep3(markAll = false) {
        let isValid = true;
        let firstInvalid = null;
        const step3Keys = ['email', 'username', 'password', 'confirmPassword', 'q1', 'a1', 'q2', 'a2', 'q3', 'a3'];

        step3Keys.forEach(key => {
            if (markAll) {
                fieldStates[key].wasBlurred = true;
                fieldStates[key].touched = true;
            }
            const ok = validateField(key, markAll);
            if (!ok) {
                isValid = false;
                if (!firstInvalid) firstInvalid = document.getElementById(key);
            }
        });

        if (q1Select && q2Select && q3Select && q1Select.value && q2Select.value && q3Select.value) {
            if (new Set([q1Select.value, q2Select.value, q3Select.value]).size < 3) {
                showFieldError('q3', 'All 3 security questions must be unique');
                isValid = false;
                if (!firstInvalid) firstInvalid = q3Select;
            }
        }

        if (firstInvalid) {
            firstInvalid.focus();
        }
        return isValid;
    }

    // ── Wizard Navigation Controller ─────────────────────────────────────────
    function goToStep(targetStep) {
        if (targetStep === currentStep) return;

        // If moving forward, enforce step validation
        if (targetStep > currentStep) {
            if (currentStep === 1 && !validateStep1(true)) return false;
            if (currentStep === 2 && !validateStep2(true)) return false;
        }

        const prevPane = panes[currentStep - 1];
        const nextPane = panes[targetStep - 1];
        if (!prevPane || !nextPane) return;

        prevPane.style.opacity = '0';
        prevPane.style.transform = targetStep > currentStep ? 'translateX(-12px)' : 'translateX(12px)';

        setTimeout(() => {
            prevPane.classList.remove('active');
            prevPane.style.transform = '';

            nextPane.classList.add('active');
            nextPane.style.opacity = '0';
            nextPane.style.transform = targetStep > currentStep ? 'translateX(12px)' : 'translateX(-12px)';

            void nextPane.offsetWidth;

            nextPane.style.opacity = '1';
            nextPane.style.transform = 'translateX(0)';

            currentStep = targetStep;

            for (let i = 1; i <= 3; i++) {
                const btn = document.getElementById(`tabBtn${i}`);
                if (btn) {
                    btn.classList.toggle('active', i === currentStep);
                    btn.setAttribute('aria-selected', i === currentStep ? 'true' : 'false');
                }
            }

            if (currentStep === 1 && firstNameInput) firstNameInput.focus();
            else if (currentStep === 2 && purokStreetInput) purokStreetInput.focus();
            else if (currentStep === 3 && emailInput) emailInput.focus();
        }, 150);

        return true;
    }

    // Navigation buttons
    document.getElementById('tabBtn1')?.addEventListener('click', () => { goToStep(1); });
    document.getElementById('tabBtn2')?.addEventListener('click', () => { goToStep(2); });
    document.getElementById('tabBtn3')?.addEventListener('click', () => { goToStep(3); });

    step1NextBtn?.addEventListener('click', () => { goToStep(2); });
    step2BackBtn?.addEventListener('click', () => { goToStep(1); });
    step2NextBtn?.addEventListener('click', () => { goToStep(3); });
    step3BackBtn?.addEventListener('click', () => { goToStep(2); });

    // Enter key handling
    form.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (currentStep === 1) {
                goToStep(2);
            } else if (currentStep === 2) {
                goToStep(3);
            } else if (currentStep === 3) {
                handleRegistrationSubmit();
            }
        }
    });

    // ── Form Submission ──────────────────────────────────────────────────────
    async function handleRegistrationSubmit() {
        if (isSubmitting) return;

        // Immediately block if email or username was already flagged duplicate
        if (fieldStates.email && fieldStates.email.isDuplicate) {
            showFieldError('email', 'This email address is already registered.');
            goToStep(3);
            if (emailInput) emailInput.focus();
            return;
        }

        if (fieldStates.username && fieldStates.username.isDuplicate) {
            showFieldError('username', 'This username is already taken.');
            goToStep(3);
            if (usernameInput) usernameInput.focus();
            return;
        }

        // Validate all 3 steps before submitting
        const s1Ok = validateStep1(true);
        if (!s1Ok) {
            goToStep(1);
            return;
        }

        const s2Ok = validateStep2(true);
        if (!s2Ok) {
            goToStep(2);
            return;
        }

        const s3Ok = validateStep3(true);
        if (!s3Ok) {
            return;
        }

        // Clear any pending debounces and do a live pre-flight check
        clearTimeout(emailCheckTimeout);
        clearTimeout(usernameCheckTimeout);

        const emailVal = emailInput ? emailInput.value.trim() : '';
        if (emailVal) {
            try {
                const res = await fetch('../php/check_email.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: emailVal })
                });
                const data = await res.json();
                const isTaken = (data && data.exists === true) || (data && data.data && data.data.exists === true);
                if (isTaken) {
                    fieldStates.email.isDuplicate = true;
                    fieldStates.email.lastCheckedValue = emailVal.toLowerCase();
                    showFieldError('email', 'This email address is already registered.');
                    goToStep(3);
                    if (emailInput) emailInput.focus();
                    return;
                }
            } catch (e) {}
        }

        const usernameVal = usernameInput ? usernameInput.value.trim() : '';
        if (usernameVal) {
            try {
                const res = await fetch('../php/check_username.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: usernameVal })
                });
                const data = await res.json();
                const isTaken = (data && data.exists === true) || (data && data.data && data.data.exists === true);
                if (isTaken) {
                    fieldStates.username.isDuplicate = true;
                    fieldStates.username.lastCheckedValue = usernameVal.toLowerCase();
                    showFieldError('username', 'This username is already taken.');
                    goToStep(3);
                    if (usernameInput) usernameInput.focus();
                    return;
                }
            } catch (e) {}
        }

        const metaCsrf = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = metaCsrf ? metaCsrf.getAttribute('content') : '';

        const payload = {
            csrf_token: csrfToken,
            firstName: firstNameInput.value,
            middleInitial: middleInitialInput ? middleInitialInput.value : '',
            lastName: lastNameInput.value,
            suffix: suffixSelect ? suffixSelect.value : '',
            sex: sexSelect ? sexSelect.value : '',
            birthDate: birthDateInput ? birthDateInput.value : '',
            purokStreet: purokStreetInput ? purokStreetInput.value : '',
            barangay: barangayInput ? barangayInput.value : '',
            city: cityInput ? cityInput.value : '',
            province: provinceInput ? provinceInput.value : '',
            country: countryInput ? countryInput.value : '',
            zipCode: zipCodeInput ? zipCodeInput.value : '',
            email: emailInput ? emailInput.value : '',
            username: usernameInput ? usernameInput.value : '',
            password: passwordInput ? passwordInput.value : '',
            confirmPassword: confirmPasswordInput ? confirmPasswordInput.value : '',
            q1: q1Select ? q1Select.value : '',
            a1: a1Input ? a1Input.value : '',
            q2: q2Select ? q2Select.value : '',
            a2: a2Input ? a2Input.value : '',
            q3: q3Select ? q3Select.value : '',
            a3: a3Input ? a3Input.value : ''
        };

        isSubmitting = true;
        if (completeRegBtn) {
            completeRegBtn.disabled = true;
            completeRegBtn.innerHTML = `
                <span class="reg-spinner"></span>
                <span>Registering...</span>
            `;
        }

        fetch('../php/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(async (response) => {
            const data = await response.json().catch(() => null);
            if (!data) {
                throw new Error('Invalid server response');
            }
            return { ok: response.ok, status: response.status, data };
        })
        .then(({ ok, status, data }) => {
            if (data.success) {
                isDirty = false;
                showToast(data.message || 'Registration successful!', 'success', 5000);
                if (completeRegBtn) {
                    completeRegBtn.innerHTML = `<span>✓ Completed</span>`;
                }
                setTimeout(() => {
                    window.location.href = 'login.php?registered=1';
                }, 2000);
            } else {
                isSubmitting = false;
                if (completeRegBtn) {
                    completeRegBtn.disabled = false;
                    completeRegBtn.innerHTML = completeRegText ? completeRegText.outerHTML : 'Complete Registration &check;';
                }

                showToast(data.message || 'Please resolve the highlighted validation errors.', 'error');

                if (data.fieldErrors) {
                    Object.entries(data.fieldErrors).forEach(([fieldId, msg]) => {
                        showFieldError(fieldId, msg);
                    });

                    if (data.step && data.step !== currentStep) {
                        goToStep(data.step);
                    }
                }
            }
        })
        .catch(err => {
            isSubmitting = false;
            if (completeRegBtn) {
                completeRegBtn.disabled = false;
                completeRegBtn.innerHTML = completeRegText ? completeRegText.outerHTML : 'Complete Registration &check;';
            }
            showToast(err.message || 'Network error occurred. Please try again.', 'error');
        });
    }

    if (completeRegBtn) {
        completeRegBtn.addEventListener('click', handleRegistrationSubmit);
    }

    // Initialize cyber-themed eye icons for password fields
    document.querySelectorAll('.pwd-eye-btn, .show-password').forEach(btn => {
        btn.setAttribute('aria-label', 'Show password');
        btn.setAttribute('aria-pressed', 'false');
        btn.innerHTML = getEyeSVG(false);
    });
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
window.getEyeSVG = getEyeSVG;

function togglePassword(fieldId, btn) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    const button = btn || (field.parentElement ? field.parentElement.querySelector('.pwd-eye-btn, .show-password') : null);
    const isPass = field.type === 'password';
    field.type = isPass ? 'text' : 'password';
    if (button) {
        button.innerHTML = getEyeSVG(isPass);
        button.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', isPass ? 'true' : 'false');
    }
}
window.togglePassword = togglePassword;
