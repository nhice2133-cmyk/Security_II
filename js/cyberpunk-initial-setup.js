/**
 * Initial Setup Wizard Controller
 * 3-Step Wizard with real-time field validation, click-to-generate ID, min age 18,
 * password strength meter, eye toggles, mutually exclusive security questions,
 * and AJAX submission with inline error rendering.
 */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('initialSetupWizardForm');
    if (!form) return;

    const step1Pane = document.getElementById('step1Pane');
    const step2Pane = document.getElementById('step2Pane');
    const step3Pane = document.getElementById('step3Pane');
    const panes = [step1Pane, step2Pane, step3Pane];

    const step1NextBtn = document.getElementById('step1NextBtn');
    const step2BackBtn = document.getElementById('step2BackBtn');
    const step2NextBtn = document.getElementById('step2NextBtn');
    const step3BackBtn = document.getElementById('step3BackBtn');
    const completeSetupBtn = document.getElementById('completeSetupBtn');
    const completeSetupText = document.getElementById('completeSetupText');

    // Inputs - Step 1
    const idNumberInput = document.getElementById('idNumber');
    const generateIdBtn = document.getElementById('generateIdBtn');
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

    // Initial username to avoid false positives on username check
    const initialUsername = (usernameInput ? usernameInput.value.trim().toLowerCase() : '');

    // State
    let currentStep = 1;
    let isDirty = false;
    let isSubmitting = false;

    const fieldStates = {
        idNumber: { touched: false, wasBlurred: false, lastError: null },
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
        username: { touched: false, wasBlurred: false, lastError: null, isDuplicate: false, lastCheckedValue: '' },
        password: { touched: false, wasBlurred: false, lastError: null },
        confirmPassword: { touched: false, wasBlurred: false, lastError: null },
        q1: { touched: false, wasBlurred: false, lastError: null },
        a1: { touched: false, wasBlurred: false, lastError: null },
        q2: { touched: false, wasBlurred: false, lastError: null },
        a2: { touched: false, wasBlurred: false, lastError: null },
        q3: { touched: false, wasBlurred: false, lastError: null },
        a3: { touched: false, wasBlurred: false, lastError: null }
    };

    form.addEventListener('input', () => { isDirty = true; });
    form.addEventListener('change', () => { isDirty = true; });

    window.addEventListener('beforeunload', (e) => {
        if (isDirty && !isSubmitting) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // ── Toast Notifications ──────────────────────────────────────────────────
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

    // ── Visual Validation State Helpers ──────────────────────────────────────
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

    function clearAllErrors() {
        document.querySelectorAll('.custom-input, .custom-select').forEach(el => {
            el.classList.remove('is-invalid');
            el.removeAttribute('aria-invalid');
        });
        document.querySelectorAll('.field-error-msg').forEach(el => el.classList.remove('visible'));
    }

    // ── ID Generation ────────────────────────────────────────────────────────
    function fetchAndSetId() {
        if (idNumberInput && idNumberInput.value.trim() !== '') return;
        if (generateIdBtn) {
            generateIdBtn.disabled = true;
            generateIdBtn.textContent = 'Generating...';
        }

        fetch('../php/get_next_id.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.idNumber) {
                    idNumberInput.value = data.idNumber;
                } else {
                    const yr = new Date().getFullYear();
                    idNumberInput.value = `${yr}-${String(Math.floor(Math.random() * 9000) + 1000)}`;
                }
                markFieldValid('idNumber');
                if (generateIdBtn) {
                    generateIdBtn.textContent = '✓ Generated';
                    generateIdBtn.style.opacity = '0.7';
                }
            })
            .catch(() => {
                const yr = new Date().getFullYear();
                idNumberInput.value = `${yr}-${String(Math.floor(Math.random() * 9000) + 1000)}`;
                markFieldValid('idNumber');
                if (generateIdBtn) {
                    generateIdBtn.textContent = '✓ Generated';
                }
            });
    }

    if (generateIdBtn) {
        generateIdBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fetchAndSetId();
        });
    }

    if (idNumberInput && !idNumberInput.value) {
        idNumberInput.style.cursor = 'pointer';
        idNumberInput.addEventListener('click', () => {
            if (!idNumberInput.value) fetchAndSetId();
        });
    } else if (idNumberInput && idNumberInput.value) {
        markFieldValid('idNumber');
    }

    // ── Age Calculation ──────────────────────────────────────────────────────
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

    function updateAge() {
        const val = birthDateInput ? birthDateInput.value : '';
        if (!val) {
            if (ageInput) ageInput.value = '';
            clearFieldError('age');
            return;
        }

        const bDate = new Date(val);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (bDate > today) {
            if (ageInput) ageInput.value = '';
            showFieldError('birthDate', 'Birthdate cannot be in the future');
            showFieldError('age', 'Birthdate cannot be in the future');
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

    // ── Pure Rule Checkers ───────────────────────────────────────────────────
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
        if (val === '' || val.trim() === '') return 'First name is required.';
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

    function getSexError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (!val || val.trim() === '') return 'Gender is required.';
        return null;
    }

    function getBirthDateError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (!val || val.trim() === '') return 'Birthdate is required.';
        const bDate = new Date(val);
        if (isNaN(bDate.getTime())) return 'Invalid birthdate format';

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (bDate > today) return 'Birthdate cannot be in the future';

        const age = calculateAge(val);
        if (age === null || age < 18) return 'Age must be at least 18 years old';
        return null;
    }

    function getStreetError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'Purok / Street is required.';
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

    function getZipError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'Postal / Zip code is required.';
        if (!/^\d{4}$/.test(val)) {
            if (!isBlurred && val.length < 4) return null;
            return 'Zip code must be 4 digits.';
        }
        return null;
    }

    function getUsernameFormatError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'Username is required.';
        if (/\s/.test(val)) return 'Username cannot contain spaces.';
        if (val.length > 20) return 'Username must be between 3 and 20 characters.';
        if (!/^[a-zA-Z0-9._]+$/.test(val)) return 'Letters, numbers, dots, and underscores only.';
        if (val.length < 3) {
            if (!isBlurred) return null;
            return 'Username must be between 3 and 20 characters.';
        }
        return null;
    }

    let usernameCheckTimeout = null;
    function checkUsernameUniqueness(usernameVal) {
        clearTimeout(usernameCheckTimeout);
        const cleanUser = usernameVal.trim().toLowerCase();
        
        // If unchanged from currently assigned username, it's valid
        if (cleanUser === initialUsername) {
            fieldStates.username.isDuplicate = false;
            if (!getUsernameFormatError(usernameVal, true)) {
                markFieldValid('username');
            }
            return;
        }

        usernameCheckTimeout = setTimeout(() => {
            fetch('../php/check_username.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: cleanUser })
            })
            .then(res => res.json())
            .then(data => {
                if (usernameInput.value.trim().toLowerCase() !== cleanUser) return;
                fieldStates.username.lastCheckedValue = cleanUser;
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

    function evaluatePassword(pwd) {
        if (!pwd) return { score: 0, label: '', valid: false };

        const hasLen = pwd.length >= 8;
        const hasUpper = /[A-Z]/.test(pwd);
        const hasLower = /[a-z]/.test(pwd);
        const hasNum = /[0-9]/.test(pwd);
        const hasSym = /[^A-Za-z0-9]/.test(pwd);

        let criteriaMet = 0;
        if (hasLen) criteriaMet++;
        if (hasUpper && hasLower) criteriaMet++;
        if (hasNum) criteriaMet++;
        if (hasSym) criteriaMet++;

        const fullyCompliant = hasLen && hasUpper && hasLower && hasNum && hasSym;

        let label = 'Weak';
        let score = 1;
        if (criteriaMet === 2) { score = 2; label = 'Fair'; }
        else if (criteriaMet === 3) { score = 3; label = 'Good'; }
        else if (criteriaMet >= 4) { score = 4; label = 'Strong'; }

        return { score, label, valid: fullyCompliant };
    }

    function updateStrengthMeter() {
        if (!passwordInput) return;
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

    function getPasswordError(val, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'New password is required.';
        if (val.length < 8) return 'Password must contain at least 8 characters.';
        if (val.length > 128) return 'Password must not exceed 128 characters.';
        if (!/[A-Z]/.test(val)) return 'Include at least one uppercase letter.';
        if (!/[a-z]/.test(val)) return 'Include at least one lowercase letter.';
        if (!/[0-9]/.test(val)) return 'Include at least one number.';
        if (!/[^a-zA-Z0-9]/.test(val)) return 'Include at least one special character.';
        return null;
    }

    function getConfirmPasswordError(val, pwdVal, isBlurred = false) {
        if (!val && !isBlurred) return null;
        if (val === '' || val.trim() === '') return 'Please confirm your new password.';
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
        if (!val || val.trim() === '') return 'Answer is required.';
        return null;
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
            case 'idNumber':
                if (!val || val.trim() === '') {
                    err = 'Please click to generate an ID number';
                }
                break;
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
            if (fieldId === 'username') {
                if (fieldStates.username.lastCheckedValue !== el.value.trim().toLowerCase()) {
                    fieldStates.username.isDuplicate = false;
                }
            }
            validateField(fieldId, false);

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

    // Special Input Helpers
    if (birthDateInput) {
        birthDateInput.addEventListener('change', updateAge);
        birthDateInput.addEventListener('input', updateAge);
        if (birthDateInput.value) {
            updateAge();
        }
    }

    if (zipCodeInput) {
        zipCodeInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', updateStrengthMeter);
    }

    // ── Eye Toggles for Passwords ────────────────────────────────────────────
    document.querySelectorAll('.pwd-eye-btn, .reg-eye-btn').forEach(btn => {
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
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                `;
            }
        });
    });

    // ── Security Questions Synchronization ───────────────────────────────────
    const sqSelects = [q1Select, q2Select, q3Select];
    const sqAnswers = [a1Input, a2Input, a3Input];

    function syncSecurityQuestions() {
        const selectedValues = sqSelects.map(s => (s ? s.value : '')).filter(v => v !== '');

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
                if (opt.value !== select.value && selectedValues.includes(opt.value)) {
                    opt.disabled = true;
                } else {
                    opt.disabled = false;
                }
            });
        });
    }

    sqSelects.forEach((select, idx) => {
        if (!select) return;
        select.addEventListener('change', () => {
            clearFieldError(select.id);
            syncSecurityQuestions();
            validateField(select.id, true);
            if (select.value && sqAnswers[idx]) {
                sqAnswers[idx].focus();
            }
        });
    });

    // Run sync immediately on startup
    syncSecurityQuestions();

    // ── Step 1 Validation ────────────────────────────────────────────────────
    function validateStep1() {
        let isValid = true;
        let firstInvalid = null;

        const check = (id) => {
            const ok = validateField(id, true);
            if (!ok && !firstInvalid) {
                firstInvalid = document.getElementById(id);
            }
            if (!ok) isValid = false;
            return ok;
        };

        check('idNumber');
        check('firstName');
        check('middleInitial');
        check('lastName');
        check('sex');
        const bdOk = check('birthDate');
        if (bdOk) {
            updateAge();
            const ageVal = calculateAge(birthDateInput.value);
            if (ageVal === null || ageVal < 18) {
                showFieldError('birthDate', 'Age must be at least 18 years old');
                showFieldError('age', 'Age must be at least 18 years old');
                isValid = false;
                if (!firstInvalid) firstInvalid = birthDateInput;
            }
        } else {
            if (!firstInvalid) firstInvalid = birthDateInput;
        }

        if (firstInvalid) {
            if (firstInvalid === idNumberInput && generateIdBtn) {
                generateIdBtn.focus();
            } else {
                firstInvalid.focus();
            }
        }
        return isValid;
    }

    // ── Step 2 Validation ────────────────────────────────────────────────────
    function validateStep2() {
        let isValid = true;
        let firstInvalid = null;

        const check = (id) => {
            const ok = validateField(id, true);
            if (!ok && !firstInvalid) {
                firstInvalid = document.getElementById(id);
            }
            if (!ok) isValid = false;
            return ok;
        };

        check('purokStreet');
        check('barangay');
        check('city');
        check('province');
        check('country');
        check('zipCode');

        if (firstInvalid) firstInvalid.focus();
        return isValid;
    }

    // ── Step 3 Validation ────────────────────────────────────────────────────
    function validateStep3() {
        let isValid = true;
        let firstInvalid = null;

        const check = (id) => {
            const ok = validateField(id, true);
            if (!ok && !firstInvalid) {
                firstInvalid = document.getElementById(id);
            }
            if (!ok) isValid = false;
            return ok;
        };

        check('username');
        check('password');
        check('confirmPassword');
        check('q1');
        check('a1');
        check('q2');
        check('a2');
        check('q3');
        check('a3');

        if (q1Select && q2Select && q3Select) {
            const v1 = q1Select.value;
            const v2 = q2Select.value;
            const v3 = q3Select.value;
            if (v1 && v2 && v3 && new Set([v1, v2, v3]).size < 3) {
                showFieldError('q3', 'All 3 security questions must be unique');
                isValid = false;
                if (!firstInvalid) firstInvalid = q3Select;
            }
        }

        if (firstInvalid) firstInvalid.focus();
        return isValid;
    }

    // ── Navigation Transitions ───────────────────────────────────────────────
    function goToStep(targetStep) {
        if (targetStep === currentStep) return;

        // Guardrails: ensure previous steps are valid before progressing forward
        if (targetStep > currentStep) {
            if (currentStep === 1) {
                if (!validateStep1()) return false;
                if (targetStep === 3 && !validateStep2()) return false;
            } else if (currentStep === 2) {
                if (!validateStep2()) return false;
            }
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

            // Update wizard tabs navigation active state
            for (let i = 1; i <= 3; i++) {
                const btn = document.getElementById(`tabBtn${i}`);
                if (btn) {
                    btn.classList.toggle('active', i === currentStep);
                    btn.setAttribute('aria-selected', i === currentStep ? 'true' : 'false');
                }
            }

            if (currentStep === 1 && firstNameInput) firstNameInput.focus();
            else if (currentStep === 2 && purokStreetInput) purokStreetInput.focus();
            else if (currentStep === 3 && passwordInput) passwordInput.focus();
        }, 150);

        return true;
    }

    // Tab buttons click navigation
    document.getElementById('tabBtn1')?.addEventListener('click', () => { goToStep(1); });
    document.getElementById('tabBtn2')?.addEventListener('click', () => { goToStep(2); });
    document.getElementById('tabBtn3')?.addEventListener('click', () => { goToStep(3); });

    if (step1NextBtn) step1NextBtn.addEventListener('click', () => { goToStep(2); });
    if (step2BackBtn) step2BackBtn.addEventListener('click', () => { goToStep(1); });
    if (step2NextBtn) step2NextBtn.addEventListener('click', () => { goToStep(3); });
    if (step3BackBtn) step3BackBtn.addEventListener('click', () => { goToStep(2); });

    form.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (currentStep === 1) goToStep(2);
            else if (currentStep === 2) goToStep(3);
            else if (currentStep === 3) handleInitialSetupSubmit();
        }
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        handleInitialSetupSubmit();
    });

    // ── Submission Handler ───────────────────────────────────────────────────
    function handleInitialSetupSubmit() {
        if (isSubmitting) return;

        if (!validateStep1()) { goToStep(1); return; }
        if (!validateStep2()) { goToStep(2); return; }
        if (!validateStep3()) { return; }

        const metaCsrf = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = metaCsrf ? metaCsrf.getAttribute('content') : '';

        const payload = {
            csrf_token: csrfToken,
            idNumber: idNumberInput ? idNumberInput.value.trim() : '',
            firstName: firstNameInput ? firstNameInput.value.trim() : '',
            middleInitial: middleInitialInput ? middleInitialInput.value.trim() : '',
            lastName: lastNameInput ? lastNameInput.value.trim() : '',
            suffix: suffixSelect ? suffixSelect.value.trim() : '',
            sex: sexSelect ? sexSelect.value.trim() : '',
            birthDate: birthDateInput ? birthDateInput.value.trim() : '',
            purokStreet: purokStreetInput ? purokStreetInput.value.trim() : '',
            barangay: barangayInput ? barangayInput.value.trim() : '',
            city: cityInput ? cityInput.value.trim() : '',
            province: provinceInput ? provinceInput.value.trim() : '',
            country: countryInput ? countryInput.value.trim() : '',
            zipCode: zipCodeInput ? zipCodeInput.value.trim() : '',
            username: usernameInput ? usernameInput.value.trim() : '',
            newPassword: passwordInput ? passwordInput.value : '',
            confirmPassword: confirmPasswordInput ? confirmPasswordInput.value : '',
            q1: q1Select ? q1Select.value : '',
            a1: a1Input ? a1Input.value.trim() : '',
            q2: q2Select ? q2Select.value : '',
            a2: a2Input ? a2Input.value.trim() : '',
            q3: q3Select ? q3Select.value : '',
            a3: a3Input ? a3Input.value.trim() : ''
        };

        isSubmitting = true;
        if (completeSetupBtn) {
            completeSetupBtn.disabled = true;
            completeSetupBtn.innerHTML = `
                <span class="reg-spinner"></span>
                <span>Finalizing Setup...</span>
            `;
        }

        fetch('../php/process-initial-setup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(async (response) => {
            const data = await response.json().catch(() => null);
            if (!data) throw new Error('Invalid server response');
            return { ok: response.ok, status: response.status, data };
        })
        .then(({ ok, status, data }) => {
            if (data.success) {
                isDirty = false;
                showToast(data.message || 'Setup complete! Redirecting...', 'success', 3000);
                if (completeSetupBtn) {
                    completeSetupBtn.innerHTML = `<span>✓ Completed</span>`;
                }
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1200);
            } else {
                isSubmitting = false;
                if (completeSetupBtn) {
                    completeSetupBtn.disabled = false;
                    completeSetupBtn.innerHTML = completeSetupText ? completeSetupText.outerHTML : 'Complete Setup &check;';
                }
                showToast(data.message || 'Failed to complete setup', 'error');

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
            if (completeSetupBtn) {
                completeSetupBtn.disabled = false;
                completeSetupBtn.innerHTML = completeSetupText ? completeSetupText.outerHTML : 'Complete Setup &check;';
            }
            showToast(err.message || 'Network error occurred. Please try again.', 'error');
        });
    }

    if (completeSetupBtn) {
        completeSetupBtn.addEventListener('click', handleInitialSetupSubmit);
    }
});
