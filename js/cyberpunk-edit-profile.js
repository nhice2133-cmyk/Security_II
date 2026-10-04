const ALLOWED_EXTENSIONS = ['jr', 'sr', 'ii', 'iii', 'iv', 'v'];
const ALLOWED_EXTENSION_SET = new Set(ALLOWED_EXTENSIONS);
const EXTENSION_LABELS = {
    jr: 'Jr',
    sr: 'Sr',
    ii: 'II',
    iii: 'III',
    iv: 'IV',
    v: 'V',
};

const NAME_REGEX = /^[a-zA-Z\s\-'.]+$/;

function formatExtension(value) {
    if (!value) return '';
    const key = value.toLowerCase();
    return EXTENSION_LABELS[key] || '';
}

function sanitizeName(value) {
    if (!value) return '';
    let result = value.replace(/[^a-zA-Z\s\-'.]/g, '');
    result = result.replace(/\s{2,}/g, ' ');
    result = result.replace(/-{2,}/g, '-');
    result = result.trim();
    return result
        .split(' ')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
        .join(' ');
}

function normalizeUsername(value) {
    if (!value) return '';
    let v = value.replace(/\s+/g, ' ').trim();
    v = v.replace(/\s+/g, '_');
    v = v.toLowerCase();
    v = v.replace(/[^a-z0-9_]/g, '');
    v = v.replace(/_+/g, '_');
    v = v.replace(/^[0-9]+/, '');
    return v;
}

function sanitizeRegionValue(value) {
    if (!value) return '';
    let v = value.replace(/[^a-zA-Z0-9\s\-'\.,]/g, ' ');
    v = v.replace(/\s{2,}/g, ' ');
    return v.trim();
}

function isProperTitleCase(str) {
    if (typeof str !== 'string' || !str.trim()) return true;
    // Expected Title Case: First letter of each word is uppercase, rest is lowercase
    // Allow hyphens, apostrophes, and periods
    const words = str.split(/\s+/);
    for (let word of words) {
        if (!word) continue;
        // Split by hyphens, apostrophes, and periods to check each part
        const parts = word.split(/([-'.])/);
        for (let i = 0; i < parts.length; i++) {
            const part = parts[i];
            if (!part || /^[-'.]$/.test(part)) continue; // Skip separators
            // Check if first letter is uppercase and rest is lowercase
            if (part.length > 0) {
                const first = part.charAt(0);
                const rest = part.slice(1);
                if (!/^[A-Z]$/.test(first) || rest !== rest.toLowerCase()) {
                    return false;
                }
            }
        }
    }
    return true;
}

function getTitleCaseWarning(str) {
    if (typeof str !== 'string' || !str.trim()) return null;
    const words = str.split(/\s+/).filter(w => w.trim());
    const issues = [];
    const correctedExamples = [];
    
    words.forEach((word, index) => {
        if (!word) return;
        const parts = word.split(/([-'.])/);
        let hasIssue = false;
        
        for (let i = 0; i < parts.length; i++) {
            const part = parts[i];
            if (!part || /^[-'.]$/.test(part)) continue;
            
            if (part.length > 0) {
                const first = part.charAt(0);
                const rest = part.slice(1);
                
                if (!/^[A-Z]$/.test(first)) {
                    hasIssue = true;
                    break;
                }
                if (rest !== rest.toLowerCase()) {
                    hasIssue = true;
                    break;
                }
            }
        }
        
        if (hasIssue) {
            // Create corrected version of the word
            const correctedWord = word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
            issues.push(`"${word}"`);
            correctedExamples.push(correctedWord);
        }
    });
    
    if (issues.length === 0) return null;
    
    if (issues.length === 1) {
        return `Warning: Should be uppercase "${correctedExamples[0]}"`;
    } else {
        const correctedExample = correctedExamples.join(' ');
        return `Warning: Should be uppercase "${correctedExample}"`;
    }
}

function calculateAgeFromDate(dateValue) {
    if (!dateValue) return null;
    const birth = new Date(dateValue);
    if (Number.isNaN(birth.getTime())) return null;
    const today = new Date();
    let years = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        years -= 1;
    }
    return years;
}

function setError(input, message) {
    const errorElement = document.getElementById(`${input.id}Error`);
    if (errorElement) {
        errorElement.textContent = message || '';
    }
    if (message) {
        input.classList.add('error');
        input.style.borderColor = 'var(--cyber-error)';
        errorElement.style.color = '';
    } else {
        input.classList.remove('error');
        input.style.borderColor = '';
        errorElement.style.color = '';
    }
}

function setWarning(input, message) {
    const errorElement = document.getElementById(`${input.id}Error`);
    if (errorElement) {
        errorElement.textContent = message || '';
        errorElement.style.color = 'var(--cyber-error)';
    }
    input.style.borderColor = 'var(--cyber-error)';
    input.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
}

function clearMessage(element) {
    element.textContent = '';
    element.classList.remove('success', 'error');
}

function setMessage(element, text, ok) {
    element.textContent = text;
    element.classList.toggle('success', ok);
    element.classList.toggle('error', !ok);
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('editProfileForm');
    if (!form) return;

    const msg = document.getElementById('msg');
    const submitBtn = document.getElementById('submitBtn');

    const firstName = form.firstName;
    const middleName = form.middleName;
    const lastName = form.lastName;
    const extension = form.extension;
    const username = form.username;
    const phone = form.phone;
    const address = form.address;
    const birthDate = form.birthDate;
    const age = form.age;
    const sex = form.sex;
    const country = form.country;
    const province = form.province;
    const city = form.city;
    const barangay = form.barangay;
    const purok = form.purok;

    const updateAddress = () => {
        const parts = [
            purok?.value.trim(),
            barangay?.value.trim(),
            city?.value.trim(),
            province?.value.trim(),
            country?.value.trim()
        ].filter(Boolean);
        if (address) {
            address.value = parts.join(', ');
        }
    };

    [purok, barangay, city, province, country].forEach(el => {
        if (el) {
            el.addEventListener('input', updateAddress);
            el.addEventListener('change', updateAddress);
        }
    });

    if (phone) {
        phone.addEventListener('input', function(e) {
            let val = this.value.replace(/\D/g, '');
            if (val.length > 11) val = val.slice(0, 11);
            this.value = val;
        });
    }

    function validateNameField(input, { required = true } = {}) {
        // Don't auto-correct anything - just validate
        const value = input.value;
        const trimmed = value.trim();

        if (!trimmed) {
            if (required) {
                setError(input, 'This field is required');
                return false;
            }
            setError(input, '');
            return true;
        }

        // Check for leading/trailing spaces and show warning
        if (value !== trimmed) {
            setWarning(input, 'Warning: Remove leading or trailing spaces');
            return true; // Still valid, just a warning
        }

        // Check for multiple spaces and show warning
        if (/\s{2,}/.test(value)) {
            setWarning(input, 'Warning: Use single spaces between words');
            return true; // Still valid, just a warning
        }

        // Check for invalid characters
        if (!NAME_REGEX.test(value)) {
            setError(input, 'Only letters, spaces, hyphen, apostrophe, and period allowed');
            return false;
        }

        if (/([a-zA-Z])\1\1/.test(value.replace(/\s+/g, ''))) {
            setError(input, 'Cannot contain 3 consecutive identical letters');
            return false;
        }

        // Check for proper Title Case and show warning instead of auto-correcting
        if (!isProperTitleCase(value)) {
            const warningMsg = getTitleCaseWarning(value);
            setWarning(input, warningMsg || 'Warning: Use Title Case format');
            return true; // Still valid, just a warning
        }

        setError(input, '');
        return true;
    }

    function validateExtensionField() {
        // Don't auto-correct anything - just validate
        const value = extension.value || '';
        const trimmed = value.trim();

        if (!trimmed) {
            setError(extension, '');
            return true;
        }

        // Check for leading/trailing spaces
        if (value !== trimmed) {
            setWarning(extension, 'Warning: Remove leading or trailing spaces');
            return true; // Still valid, just a warning
        }

        // Check for spaces in extension
        if (/\s/.test(value)) {
            setWarning(extension, 'Warning: Extension cannot contain spaces');
            return true; // Still valid, just a warning
        }

        // Check for invalid characters (non-letters)
        if (/[^a-zA-Z]/.test(value)) {
            setWarning(extension, 'Warning: Extension should contain only letters');
            return true; // Still valid, just a warning
        }

        const sanitized = value.toLowerCase();

        if (sanitized.length > 3) {
            setError(extension, 'Extension allows up to 3 letters');
            return false;
        }

        if (!ALLOWED_EXTENSION_SET.has(sanitized)) {
            setError(extension, `Extension must be one of: ${ALLOWED_EXTENSIONS.map((ext) => ext.toUpperCase()).join(', ')}`);
            return false;
        }

        // Check if format is correct (should be like "Jr", "Sr", "II", etc.)
        const expectedFormat = formatExtension(sanitized);
        // For Jr and Sr, require exact case match (not lowercase)
        if (sanitized === 'jr' || sanitized === 'sr') {
            if (value !== expectedFormat) {
                setError(extension, `Extension must be "${expectedFormat}" (not "${value}")`);
                return false;
            }
        } else if (value !== expectedFormat && value.toLowerCase() !== sanitized) {
            // For other extensions (II, III, IV, V), show warning
            setWarning(extension, `Warning: Extension should be formatted as "${expectedFormat}"`);
            return true; // Still valid, just a warning
        }

        setError(extension, '');
        return true;
    }

    function getUsernameWarning(value) {
        if (!value) return null;
        
        // Priority 1: Check for uppercase letters first (most important)
        if (value !== value.toLowerCase()) {
            return `Warning: Should be lowercase (e.g., "${value.toLowerCase()}")`;
        }
        
        // Priority 2: Check for spaces
        if (/\s/.test(value)) {
            const withUnderscores = value.replace(/\s+/g, '_');
            return `Warning: Use underscores instead of spaces (e.g., "${withUnderscores}")`;
        }
        
        // Priority 3: Check if starts with number (but only if no uppercase or spaces)
        if (/^[0-9]/.test(value)) {
            return 'Warning: Cannot start with a number';
        }
        
        // Priority 4: Check for invalid characters (but only if no uppercase, spaces, or leading number)
        if (/[^a-z0-9_]/.test(value)) {
            const invalidChars = value.match(/[^a-z0-9_]/g);
            if (invalidChars) {
                const uniqueChars = [...new Set(invalidChars)];
                return `Warning: Invalid characters: ${uniqueChars.join(', ')}`;
            }
        }
        
        return null;
    }

    function validateUsernameField() {
        const value = (username.value || '').trim();

        if (!value) {
            setError(username, 'Username is required');
            return false;
        }

        // Check for specific warnings in priority order
        const warningMsg = getUsernameWarning(value);
        if (warningMsg) {
            setWarning(username, warningMsg);
            return true; // Still valid, just a warning
        }

        if (value.length < 3) {
            setError(username, 'Username must be at least 3 characters');
            return false;
        }

        setError(username, '');
        return true;
    }

    function validatePhoneField() {
        const value = (phone.value || '').trim();

        if (!value) {
            setError(phone, 'Phone number is required');
            return false;
        }

        // Check for non-digit characters
        if (/\D/.test(value)) {
            setWarning(phone, 'Warning: Phone number should contain only digits');
            return true; // Still valid, just a warning
        }

        if (!/^09\d{9}$/.test(value)) {
            setError(phone, 'Phone number must start with 09 and be 11 digits');
            return false;
        }

        setError(phone, '');
        return true;
    }

    function validateAddressField() {
        const value = (address.value || '').trim();
        address.value = value;
        if (!value) {
            setError(address, 'Address is required');
            return false;
        }
        setError(address, '');
        return true;
    }

    function validateBirthDateField() {
        const value = (birthDate.value || '').trim();
        if (!value) {
            setError(birthDate, 'Birth date is required');
            setError(age, '');
            age.value = '';
            return false;
        }

        const birth = new Date(value);
        if (Number.isNaN(birth.getTime())) {
            setError(birthDate, 'Invalid birth date');
            setError(age, '');
            age.value = '';
            return false;
        }

        const today = new Date();
        if (birth > today) {
            setError(birthDate, 'Birth date cannot be in the future');
            setError(age, '');
            age.value = '';
            return false;
        }

        const years = calculateAgeFromDate(value);
        if (years === null) {
            setError(birthDate, 'Unable to determine age');
            setError(age, '');
            age.value = '';
            return false;
        }

        if (years < 1 || years > 120) {
            setError(birthDate, 'Age must be between 1 and 120');
            setError(age, 'Age must be between 1 and 120');
            age.value = years;
            return false;
        }

        setError(birthDate, '');
        setError(age, '');
        age.value = years;
        return true;
    }

    function validateSexField() {
        const value = (sex.value || '').trim();
        if (!value) {
            setError(sex, 'Please select your sex');
            return false;
        }
        const allowed = ['male', 'female', 'other'];
        if (!allowed.includes(value)) {
            setError(sex, 'Invalid selection');
            return false;
        }
        setError(sex, '');
        return true;
    }

    function validateCountryField() {
        const value = (country.value || '').trim();
        if (!value) {
            setError(country, 'Country is required');
            return false;
        }
        setError(country, '');
        return true;
    }

    function validateRegionField(input, { allowLeadingDigits = false } = {}) {
        // Don't auto-correct anything - just validate
        const value = input.value;
        const trimmed = value.trim();

        if (!trimmed) {
            setError(input, 'This field is required');
            return false;
        }
        
        // Priority 1: Check for leading/trailing spaces
        if (value !== trimmed) {
            setWarning(input, 'Warning: Remove leading or trailing spaces');
            return true; // Still valid, just a warning
        }
        
        // Priority 2: Check for multiple spaces
        if (/\s{2,}/.test(value)) {
            setWarning(input, 'Warning: Use single spaces between words');
            return true; // Still valid, just a warning
        }
        
        // Priority 3: Check for capitalization (Title Case - first letter capitalized)
        if (value.length > 0 && /^[a-z]/.test(value)) {
            const corrected = value.charAt(0).toUpperCase() + value.slice(1);
            setWarning(input, `Warning: Should start with capital letter (e.g., "${corrected}")`);
            return true; // Still valid, just a warning
        }
        
        // Priority 4: Check for invalid characters
        if (/[^a-zA-Z0-9\s\-'\.\,]/.test(value)) {
            const invalidChars = value.match(/[^a-zA-Z0-9\s\-'\.\,]/g);
            if (invalidChars) {
                const uniqueChars = [...new Set(invalidChars)];
                setWarning(input, `Warning: Invalid characters: ${uniqueChars.join(', ')}`);
                return true; // Still valid, just a warning
            }
        }
        
        // Priority 5: Check if starts with number
        if (!allowLeadingDigits && /^\d/.test(value)) {
            setWarning(input, 'Warning: Cannot start with a number');
            return true; // Still valid, just a warning
        }
        
        if (/([a-zA-Z])\1\1/.test(value.replace(/\s+/g, ''))) {
            setError(input, 'Cannot contain 3 consecutive identical letters');
            return false;
        }
        
        setError(input, '');
        return true;
    }

    function validatePurokField() {
        // Don't auto-correct anything - just validate
        const value = purok.value || '';
        
        if (!value) {
            setError(purok, 'This field is required');
            return false;
        }
        
        // Check for invalid characters
        if (/[^A-Za-z0-9\s\-]/.test(value)) {
            setWarning(purok, 'Warning: This field should only contain letters, numbers, spaces, and hyphens');
            return true; // Still valid, just a warning
        }
        
        setError(purok, '');
        return true;
    }

    firstName.addEventListener('blur', () => validateNameField(firstName, { required: true }));
    lastName.addEventListener('blur', () => validateNameField(lastName, { required: true }));
    middleName.addEventListener('blur', () => validateNameField(middleName, { required: false }));

    [firstName, middleName, lastName].forEach((input) => {
        input.addEventListener('input', () => {
            setError(input, ''); // This will also clear warnings
            input.style.borderColor = '';
            input.style.boxShadow = '';
        });
    });

    extension.addEventListener('input', () => {
        // Only clear errors/warnings, don't auto-correct
        setError(extension, '');
        extension.style.borderColor = '';
        extension.style.boxShadow = '';
    });
    extension.addEventListener('blur', validateExtensionField);

    username.addEventListener('input', () => {
        // Only clear errors/warnings, don't auto-correct
        setError(username, '');
        username.style.borderColor = '';
        username.style.boxShadow = '';
    });
    username.addEventListener('blur', validateUsernameField);

    phone.addEventListener('input', () => {
        // Only clear errors/warnings, don't auto-correct
        setError(phone, '');
        phone.style.borderColor = '';
        phone.style.boxShadow = '';
    });
    phone.addEventListener('blur', validatePhoneField);

    if (birthDate) {
        birthDate.addEventListener('input', () => {
            setError(birthDate, '');
            setError(age, '');
        });
        birthDate.addEventListener('change', validateBirthDateField);
        birthDate.addEventListener('blur', validateBirthDateField);
    }

    if (sex) {
        sex.addEventListener('change', () => {
            setError(sex, '');
            validateSexField();
        });
        sex.addEventListener('blur', validateSexField);
    }

    if (country) {
        country.addEventListener('change', () => {
            setError(country, '');
            validateCountryField();
        });
        country.addEventListener('blur', validateCountryField);
    }

    const regionFields = [province, city, barangay].filter(Boolean);
    regionFields.forEach((field) => {
        field.addEventListener('input', () => {
            // Only clear errors/warnings, don't auto-correct
            setError(field, '');
            field.style.borderColor = '';
            field.style.boxShadow = '';
        });
        field.addEventListener('blur', () => validateRegionField(field));
    });

    if (purok) {
        purok.addEventListener('input', () => {
            // Only clear errors/warnings, don't auto-correct
            setError(purok, '');
            purok.style.borderColor = '';
            purok.style.boxShadow = '';
        });
        purok.addEventListener('blur', validatePurokField);
    }

    address.addEventListener('input', () => setError(address, ''));
    address.addEventListener('blur', validateAddressField);

    if (birthDate) {
        validateBirthDateField();
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearMessage(msg);

        const validations = [
            validateNameField(firstName, { required: true }),
            validateNameField(middleName, { required: false }),
            validateNameField(lastName, { required: true }),
            validateExtensionField(),
            validateUsernameField(),
            validatePhoneField(),
            validateBirthDateField(),
            validateSexField(),
            validateCountryField(),
            validateRegionField(province),
            validateRegionField(city),
            validateRegionField(barangay),
            validatePurokField(),
            validateAddressField(),
        ];

        if (validations.includes(false)) {
            setMessage(msg, 'Please correct the highlighted fields.', false);
            return;
        }

        submitBtn.disabled = true;
        submitBtn.classList.add('loading');

        const payload = {
            firstName: firstName.value.trim(),
            middleName: middleName.value.trim(),
            lastName: lastName.value.trim(),
            extension: extension.value.trim(),
            username: username.value.trim(),
            email: form.email.value.trim(),
            phone: phone.value.trim(),
            birthDate: birthDate.value.trim(),
            age: age.value.trim(),
            sex: sex.value.trim(),
            country: country.value.trim(),
            province: province.value.trim(),
            city: city.value.trim(),
            barangay: barangay.value.trim(),
            purok: purok.value.trim(),
            address: address.value.trim(),
        };

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        if (csrfToken) {
            payload.csrf_token = csrfToken; // Keep it in body for fallback
        }

        try {
            const response = await fetch('../php/update-profile.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();
            if (data.success) {
                setMessage(msg, data.message || 'Profile updated successfully.', true);
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1200);
            } else {
                setMessage(msg, data.message || 'Failed to update profile.', false);
            }
        } catch (error) {
            setMessage(msg, 'Network error. Please try again.', false);
        } finally {
            submitBtn.disabled = false;
            submitBtn.classList.remove('loading');
        }
    });
});

