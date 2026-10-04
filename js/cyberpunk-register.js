const ALLOWED_EXTENSIONS = ['jr', 'sr', 'ii', 'iii', 'iv', 'v'];
const ALLOWED_EXTENSION_SET = new Set(ALLOWED_EXTENSIONS);
const EXTENSION_LABELS = {
    jr: 'Jr',
    sr: 'Sr',
    ii: 'II',
    iii: 'III',
    iv: 'IV',
    v: 'V'
};
const EXTENSION_DISPLAY_LIST = 'Jr, Sr, II, III, IV, V';

function isValidExtensionValue(value) {
    if (!value) return false;
    return ALLOWED_EXTENSION_SET.has(value.toLowerCase());
}

function matchesExtensionPrefix(value) {
    if (!value) return true;
    const lower = value.toLowerCase();
    return ALLOWED_EXTENSIONS.some(ext => ext.startsWith(lower));
}

function formatExtensionValue(value) {
    if (!value) return '';
    const lower = value.toLowerCase();
    return EXTENSION_LABELS[lower] || '';
}

// Cyberpunk Registration System
class CyberpunkRegistration {
    constructor() {
        this.form = document.getElementById('registrationForm');
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupPasswordStrength();
        this.setupRealTimeValidation();
        this.setupCapitalizationValidation();
        this.addCyberpunkEffects();
        this.initializeShowPasswordIcons();
    }

    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Real-time validation
        const inputs = this.form.querySelectorAll('.cyber-input');
        const fieldsWithSpecificValidation = ['firstName', 'lastName', 'middleName', 'extension', 'purok', 'barangay', 'city', 'province', 'email', 'username'];
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            // Only clear errors on input for fields without specific validation
            if (!fieldsWithSpecificValidation.includes(input.id)) {
                input.addEventListener('input', () => this.clearError(input));
            }
        });

        // Email validation on blur - show warnings for invalid characters
        const email = document.getElementById('email');
        if (email) {
            email.addEventListener('blur', () => {
                const value = email.value;
                const errorElement = document.getElementById('emailError');
                if (!value || !value.trim()) {
                    // Show required error when field is empty
                    if (errorElement) {
                        errorElement.textContent = 'Email is required';
                        errorElement.classList.add('show');
                    }
                    email.style.borderColor = 'var(--cyber-error)';
                    email.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                } else {
                    // Check for specific warnings in priority order
                    const warningMsg = this.getEmailWarning(value);
                    if (warningMsg) {
                        if (errorElement) {
                            this.showWarning(email, errorElement, warningMsg);
                        }
                    } else if (!this.isValidEmail(value.trim())) {
                        // Only show error if email format is invalid after trimming
                        if (errorElement) {
                            errorElement.textContent = 'Invalid email format';
                            errorElement.classList.add('show');
                        }
                        email.style.borderColor = 'var(--cyber-error)';
                        email.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                    } else {
                        this.clearError(email);
                    }
                }
            });
            email.addEventListener('input', () => {
                // Only clear errors/warnings, don't auto-correct
                this.clearError(email);
            });
        }

        // Username validation - real-time validation for "lowercase.#" format
        const username = document.getElementById('username');
        if (username) {
            // Real-time validation on input
            username.addEventListener('input', () => {
                const value = username.value;
                const errorElement = document.getElementById('usernameError');
                
                if (!value) {
                    this.clearError(username);
                    return;
                }
                
                // Real-time validation - check for errors
                const errorMsg = this.getUsernameError(value);
                const warningMsg = this.getUsernameWarning(value);
                
                if (errorMsg) {
                    if (errorElement) {
                        errorElement.textContent = errorMsg;
                        errorElement.classList.add('show');
                    }
                    username.style.borderColor = 'var(--cyber-error)';
                    username.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                } else if (warningMsg) {
                    if (errorElement) {
                        this.showWarning(username, errorElement, warningMsg);
                    }
                } else {
                    this.clearError(username);
                }
            });
            
            username.addEventListener('blur', () => {
                const value = username.value;
                const errorElement = document.getElementById('usernameError');
                if (!value || !value.trim()) {
                    // Show required error when field is empty
                    if (errorElement) {
                        errorElement.textContent = 'Username is required';
                        errorElement.classList.add('show');
                    }
                    username.style.borderColor = 'var(--cyber-error)';
                    username.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                } else {
                    // Check for specific issues in priority order - show only one warning at a time
                    const warningMsg = this.getUsernameWarning(value);
                    if (warningMsg) {
                        if (errorElement) {
                            this.showWarning(username, errorElement, warningMsg);
                        }
                    } else {
                        const errorMsg = this.getUsernameError(value);
                        if (errorMsg) {
                            if (errorElement) {
                                errorElement.textContent = errorMsg;
                                errorElement.classList.add('show');
                            }
                            username.style.borderColor = 'var(--cyber-error)';
                            username.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                        } else {
                            this.clearError(username);
                        }
                    }
                }
            });
        }

        // Password confirmation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        
        if (password && confirmPassword) {
            confirmPassword.addEventListener('input', () => {
                this.validatePasswordMatch();
            });
        }

        // Age auto-calculation
        const birthDate = document.getElementById('birthDate');
        const age = document.getElementById('age');
        
        if (birthDate && age) {
            birthDate.addEventListener('change', () => {
                this.calculateAge();
            });
            birthDate.addEventListener('input', () => {
                this.calculateAge();
            });
        }

        // Phone number validation (same as other fields)
        const phoneNumber = document.getElementById('phoneNumber');
        if (phoneNumber) {
            phoneNumber.addEventListener('blur', () => {
                this.validateField(phoneNumber);
            });
            phoneNumber.addEventListener('input', function(e) {
                let val = this.value.replace(/\D/g, '');
                if (val.length > 11) val = val.slice(0, 11);
                this.value = val;
            });
        }

        // Auto-populate full address
        const addressField = document.getElementById('address');
        const updateAddress = () => {
            const parts = [
                document.getElementById('purok')?.value.trim(),
                document.getElementById('barangay')?.value.trim(),
                document.getElementById('city')?.value.trim(),
                document.getElementById('province')?.value.trim(),
                document.getElementById('country')?.value.trim()
            ].filter(Boolean);
            if (addressField) {
                addressField.value = parts.join(', ');
            }
        };

        ['purok', 'barangay', 'city', 'province', 'country'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', updateAddress);
                el.addEventListener('change', updateAddress);
            }
        });
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

    setupCapitalizationValidation() {
        const capitalizationFieldIds = ['extension'];
        capitalizationFieldIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                const isOptional = (id === 'extension');
                const validateField = () => {
                    const result = this.validateCapitalization(el, isOptional);
                    const errorElement = document.getElementById(id + 'Error');
                    if (!result.ok) {
                        if (errorElement) {
                            this.showError(el, errorElement, result.message || 'Invalid format');
                        }
                    } else if (result.warning) {
                        if (errorElement) {
                            this.showWarning(el, errorElement, result.message || 'Invalid format');
                        }
                    } else {
                        this.clearError(el);
                    }
                };
                el.addEventListener('blur', validateField);
                el.addEventListener('input', () => {
                    // Only validate if field has value (to avoid showing errors while typing)
                    if (el.value.trim().length >= 1) {
                        validateField();
                    } else {
                        this.clearError(el);
                    }
                });
            }
        });
    }

    validateCapitalization(field, isOptional = false) {
        // Read value without modifying it - use original value including spaces
        const raw = field.value;
        const trimmed = raw.trim();
        if (!trimmed && isOptional) return { ok: true };
        if (!trimmed && !isOptional) {
            // Show field-specific required message
            const fieldName = field.id === 'firstName' ? 'First name' : 
                            field.id === 'lastName' ? 'Last name' : 
                            field.id === 'middleName' ? 'Middle name' : 'This field';
            return { ok: false, message: `${fieldName} is Required` };
        }

        const fieldId = field.id;

        // Name fields validation (firstName, lastName, middleName): show warnings in priority order (like address fields)
        if (fieldId === 'firstName' || fieldId === 'lastName' || fieldId === 'middleName') {
            // Priority 1: Check for leading/trailing spaces
            if (raw !== trimmed) {
                return { ok: true, warning: true, message: 'Warning: Remove leading or trailing spaces' };
            }
            
            // Priority 2: Check for multiple spaces
            if (/\s{2,}/.test(raw)) {
                return { ok: true, warning: true, message: 'Warning: Use single spaces between words' };
            }
            
            // Priority 3: Check for capitalization (Title Case)
            if (trimmed.length > 0 && !this.isProperTitleCase(trimmed)) {
                const warningMsg = this.getTitleCaseWarning(trimmed);
                return { ok: true, warning: true, message: warningMsg || 'Warning: Use Title Case format' };
            }
            
            // Priority 4: Check for invalid characters
            // Allow: letters (a-z, A-Z), spaces, hyphens (-), apostrophes ('), and periods (.)
            if (/[^a-zA-Z\s\-'\.]/.test(raw)) {
                const invalidChars = raw.match(/[^a-zA-Z\s\-'\.]/g);
                if (invalidChars) {
                    const uniqueChars = [...new Set(invalidChars)];
                    return { ok: true, warning: true, message: `Warning: Invalid characters: ${uniqueChars.join(', ')}` };
                }
            }
            
            // Priority 5: Check if contains numbers
            if (/\d/.test(raw)) {
                return { ok: true, warning: true, message: 'Warning: Numbers are not allowed in name fields' };
            }
        }

        // Address fields validation
        if (fieldId === 'purok') {
            // Purok: similar to other address fields but can start with a number
            // Priority 1: Check for leading/trailing spaces
            if (raw !== trimmed) {
                return { ok: true, warning: true, message: 'Warning: Remove leading or trailing spaces' };
            }
            
            // Priority 2: Check for multiple spaces
            if (/\s{2,}/.test(raw)) {
                return { ok: true, warning: true, message: 'Warning: Use single spaces between words' };
            }
            
            // Priority 3: Check for capitalization (Title Case - first letter of each word capitalized, rest lowercase)
            if (trimmed.length > 0 && !this.isProperTitleCase(trimmed)) {
                const warningMsg = this.getTitleCaseWarning(trimmed);
                return { ok: true, warning: true, message: warningMsg || 'Warning: Use Title Case format' };
            }
            
            // Priority 4: Check for invalid characters
            if (/[^a-zA-Z0-9\s\-'\.\,]/.test(raw)) {
                const invalidChars = raw.match(/[^a-zA-Z0-9\s\-'\.\,]/g);
                if (invalidChars) {
                    const uniqueChars = [...new Set(invalidChars)];
                    return { ok: true, warning: true, message: `Warning: Invalid characters: ${uniqueChars.join(', ')}` };
            }
            }
            
            // Note: Purok CAN start with a number (unlike other address fields)
        } else if (fieldId === 'extension') {
            // Check for spaces in extension
            if (/\s/.test(raw)) {
                return { ok: true, warning: true, message: 'Warning: Extension cannot contain spaces' };
            }
            // Extension must be 1-3 letters only
            if (!/^[A-Za-z]{1,3}$/.test(trimmed)) {
                return { ok: false, message: 'Extension must be 1-3 letters (letters only)' };
            }
            if (!isValidExtensionValue(trimmed)) {
                return { ok: false, message: `Extension must be one of: ${EXTENSION_DISPLAY_LIST}` };
            }
            // Check capitalization for extension
            const expectedFormat = formatExtensionValue(trimmed);
            // For Jr and Sr, require exact case match (not lowercase)
            if (trimmed.toLowerCase() === 'jr' || trimmed.toLowerCase() === 'sr') {
                if (trimmed !== expectedFormat) {
                    return { ok: false, message: `Extension must be "${expectedFormat}" (not "${trimmed}")` };
                }
            } else if (trimmed !== expectedFormat) {
                // For other extensions (II, III, IV, V), show warning
                return { ok: true, warning: true, message: `Warning: Extension should be formatted as "${expectedFormat}"` };
            }
        } else if (fieldId === 'province' || fieldId === 'city' || fieldId === 'barangay') {
            // Address fields: show warnings in priority order (like name fields)
            // Priority 1: Check for leading/trailing spaces
            if (raw !== trimmed) {
                return { ok: true, warning: true, message: 'Warning: Remove leading or trailing spaces' };
            }
            
            // Priority 2: Check for multiple spaces
            if (/\s{2,}/.test(raw)) {
                return { ok: true, warning: true, message: 'Warning: Use single spaces between words' };
            }
            
            // Priority 3: Check for capitalization (Title Case - first letter of each word capitalized, rest lowercase)
            if (trimmed.length > 0 && !this.isProperTitleCase(trimmed)) {
                const warningMsg = this.getTitleCaseWarning(trimmed);
                return { ok: true, warning: true, message: warningMsg || 'Warning: Use Title Case format' };
            }
            
            // Priority 4: Check for invalid characters
            if (/[^a-zA-Z0-9\s\-'\.\,]/.test(raw)) {
                const invalidChars = raw.match(/[^a-zA-Z0-9\s\-'\.\,]/g);
                if (invalidChars) {
                    const uniqueChars = [...new Set(invalidChars)];
                    return { ok: true, warning: true, message: `Warning: Invalid characters: ${uniqueChars.join(', ')}` };
                }
            }
            
            // Priority 5: Check if starts with number
            if (/^\d/.test(raw)) {
                return { ok: true, warning: true, message: 'Warning: Cannot start with a number' };
            }
        }

        // Check for 3 consecutive letters (for name fields and address fields including purok)
        // This is a critical error, not a warning
        const lowerValue = trimmed.toLowerCase();
        const skipConsecutiveCheck = (fieldId === 'extension' && lowerValue === 'iii');

        if (!skipConsecutiveCheck && this.hasConsecutiveLetters(lowerValue)) {
            return { ok: false, message: 'Cannot contain 3 consecutive letters' };
        }

        return { ok: true };
    }

    setupPasswordStrength() {
        const password = document.getElementById('password');
        const strengthIndicator = document.getElementById('passwordStrength');
        
        if (password && strengthIndicator) {
            password.addEventListener('input', () => {
                const strength = this.calculatePasswordStrength(password.value);
                this.updatePasswordStrength(strength, strengthIndicator);
            });
        }
    }

    setupRealTimeValidation() {
        // ID Number - auto-correction removed, validation with warnings is handled elsewhere
        const idNumber = document.getElementById('idNumber');
        if (idNumber) {
            idNumber.addEventListener('input', (e) => {
                // Only clear errors/warnings, don't auto-correct
                this.clearError(e.target);
            });
        }

        // Zip code - validation and input restriction
        const zipCode = document.getElementById('zipCode');
        if (zipCode) {
            // Restrict input to numbers only
            zipCode.addEventListener('input', (e) => {
                const value = e.target.value;
                const errorElement = document.getElementById('zipCodeError');
                
                // Remove any non-digit characters
                const digitsOnly = value.replace(/\D/g, '');
                if (value !== digitsOnly) {
                    e.target.value = digitsOnly;
                }
                
                // Limit to 4 digits
                if (digitsOnly.length > 4) {
                    e.target.value = digitsOnly.substring(0, 4);
                }
                
                // Clear errors on input
                this.clearError(e.target);
            });
            
            // Validate on blur
            zipCode.addEventListener('blur', () => {
                this.validateField(zipCode);
            });
            
            // Block wheel changes on number inputs
            zipCode.addEventListener('wheel', (e) => e.preventDefault(), { passive: false });
        }

        // Phone number - check for letters in real-time
        const phoneOnly = document.getElementById('phoneNumber');
        if (phoneOnly) {
            phoneOnly.addEventListener('input', (e) => {
                const value = e.target.value;
                const errorElement = document.getElementById('phoneNumberError');
                
                // Check for letters in real-time
                if (/[a-zA-Z]/.test(value)) {
                    if (errorElement) {
                        errorElement.textContent = 'Phone number cannot contain letters';
                        errorElement.classList.add('show');
                    }
                    phoneOnly.style.borderColor = 'var(--cyber-error)';
                    phoneOnly.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                } else {
                    // Clear error if no letters
                    this.clearError(phoneOnly);
                }
            });
            // Block wheel changes on number-like inputs in some browsers
            phoneOnly.addEventListener('wheel', (e) => e.preventDefault(), { passive: false });
        }

        // Extension field - auto-correction removed, validation with warnings is handled in setupCapitalizationValidation()
        const extension = document.getElementById('extension');
        if (extension) {
            extension.addEventListener('input', (e) => {
                // Only clear errors/warnings, don't auto-correct
                this.clearError(e.target);
            });
            // Remove keypress prevention - let users type freely
        }

        // Name fields (firstName, lastName, middleName): real-time sequential validation
        const self = this;
        ['firstName', 'lastName', 'middleName'].forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                const options = { isOptional: fieldId === 'middleName' };
                field.addEventListener('input', () => {
                    // Only validate if field has value, otherwise clear error while typing
                    if (field.value.trim().length > 0) {
                    self.validateNameInput(field, options);
                    } else {
                        // Clear error while typing if field becomes empty
                        self.clearError(field);
                    }
                });
                field.addEventListener('blur', () => {
                    // Always validate on blur, even if empty (to show required error)
                    self.validateNameInput(field, options);
                });
            }
        });

        // Address fields (barangay, city, province): sequential validation (cannot start with number)
        ['barangay', 'city', 'province'].forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                // Set user-friendly labels
                const fieldLabels = {
                    'province': 'Province',
                    'city': 'City',
                    'barangay': 'Barangay'
                };
                const options = {
                    label: fieldLabels[fieldId] || fieldId.charAt(0).toUpperCase() + fieldId.slice(1),
                    allowLeadingNumbers: false,
                    allowedPattern: /[^a-zA-Z0-9\s\-'\.,]/g,
                    invalidMessage: 'Invalid char',
                    requireTitleCase: true
                };
                field.addEventListener('input', () => {
                    self.validateAddressInput(field, options);
                });
                field.addEventListener('blur', () => {
                    self.validateAddressInput(field, options);
                });
            }
        });

        // Purok field: similar to other address fields but can start with a number
        const purok = document.getElementById('purok');
        if (purok) {
            // Prevent typing spaces at the start (like other address fields)
            purok.addEventListener('keypress', (e) => {
                const value = purok.value;
                const cursorPos = purok.selectionStart;
                
                // Allow control keys (Ctrl, Cmd, Alt, etc.)
                if (e.ctrlKey || e.metaKey || e.altKey) {
                    return;
                }
                
                // Allow navigation and deletion keys
                if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Home', 'End'].includes(e.key)) {
                    return;
                }
                
                // Prevent leading spaces (but allow numbers at start)
                if (e.key === ' ' && (value.length === 0 || cursorPos === 0)) {
                    e.preventDefault();
                    const errorElement = document.getElementById('purokError');
                    if (errorElement) {
                        errorElement.textContent = 'No leading space';
                        errorElement.classList.add('show');
                        setTimeout(() => {
                            errorElement.textContent = '';
                            errorElement.classList.remove('show');
                        }, 2000);
                    }
                }
            });
            
            // Block wheel changes on number inputs
            purok.addEventListener('wheel', (e) => e.preventDefault(), { passive: false });

            const purokOptions = {
                label: 'Purok/Street',
                allowLeadingNumbers: true,
                allowedPattern: /[^A-Za-z0-9\s\-]/g,
                invalidMessage: 'Invalid char',
                requireTitleCase: true
            };
            purok.addEventListener('input', () => {
                self.validateAddressInput(purok, purokOptions);
            });
            purok.addEventListener('blur', () => {
                self.validateAddressInput(purok, purokOptions);
            });
        }

        // Province, City, Barangay: must NOT start with a number
        ['province', 'city', 'barangay'].forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                // Prevent typing digits at the start
                field.addEventListener('keypress', (e) => {
                    const value = field.value;
                    const cursorPos = field.selectionStart;
                    
                    // Allow control keys (Ctrl, Cmd, Alt, etc.)
                    if (e.ctrlKey || e.metaKey || e.altKey) {
                        return;
                    }
                    
                    // Allow navigation and deletion keys
                    if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Home', 'End'].includes(e.key)) {
                        return;
                    }
                    
                    // If field is empty or cursor is at start, don't allow digits
                    if ((value.length === 0 || cursorPos === 0) && /\d/.test(e.key)) {
                        e.preventDefault();
                        // Show error message
                        const errorElement = document.getElementById(fieldId + 'Error');
                        if (errorElement) {
                            errorElement.textContent = 'Cannot start with number';
                            errorElement.classList.add('show');
                            setTimeout(() => {
                                errorElement.textContent = '';
                                errorElement.classList.remove('show');
                            }, 2000);
                        }
                    }
                });
                
                // Also check on input to handle paste operations
                field.addEventListener('input', (e) => {
                    // Only clear errors/warnings, don't auto-correct
                    this.clearError(e.target);
                });
            }
        });
    }

    addCyberpunkEffects() {
        // Add glitch effect to inputs on focus
        const inputs = document.querySelectorAll('.cyber-input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                this.addGlitchEffect(input);
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

    calculateAge() {
        const birthDate = document.getElementById('birthDate');
        const age = document.getElementById('age');
        
        if (birthDate && age) {
            if (birthDate.value) {
                const today = new Date();
                const birth = new Date(birthDate.value);
                let calculatedAge = today.getFullYear() - birth.getFullYear();
                const monthDiff = today.getMonth() - birth.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                    calculatedAge--;
                }
                
                age.value = calculatedAge;
                // Clear any errors when age is calculated
                this.clearError(age);
            } else {
                // Clear age if birthdate is cleared
                age.value = '';
            }
        }
    }


    calculatePasswordStrength(password) {
        let strength = 0;
        const checks = {
            length: password.length >= 8,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            numbers: /\d/.test(password),
            symbols: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        strength = Object.values(checks).filter(Boolean).length;
        return { strength, checks };
    }

    updatePasswordStrength(strengthData, indicator) {
        const { strength } = strengthData;
        indicator.className = 'password-strength';
        
        if (strength <= 2) {
            indicator.classList.add('strength-weak');
            indicator.textContent = 'WEAK SECURITY';
        } else if (strength <= 3) {
            indicator.classList.add('strength-medium');
            indicator.textContent = 'MEDIUM SECURITY';
        } else {
            indicator.classList.add('strength-strong');
            indicator.textContent = 'STRONG SECURITY';
        }
    }

    validatePasswordMatch() {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        const errorElement = document.getElementById('confirmPasswordError');
        const successElement = document.getElementById('confirmPasswordSuccess');
        
        if (password && confirmPassword && errorElement) {
            const confirmValue = confirmPassword.value;
            if (confirmValue && password.value !== confirmValue) {
                this.showError(confirmPassword, errorElement, 'Passwords do not match');
                if (successElement) {
                    successElement.textContent = '';
                    successElement.classList.remove('show');
                }
                confirmPassword.style.borderColor = 'var(--cyber-error)';
                confirmPassword.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
            } else if (confirmValue && password.value === confirmValue) {
                this.clearError(confirmPassword);
                if (successElement) {
                    successElement.textContent = 'Passwords match';
                    successElement.classList.add('show');
                }
                confirmPassword.style.borderColor = 'var(--cyber-success)';
                confirmPassword.style.boxShadow = '0 0 10px rgba(0, 255, 0, 0.3)';
            } else {
                this.clearError(confirmPassword);
                if (successElement) {
                    successElement.textContent = '';
                    successElement.classList.remove('show');
                }
            }
        }
    }

    validateField(field) {
        const fieldId = field.id;
        const value = field.value.trim();
        // Special case: idNumber field uses 'idError' instead of 'idNumberError'
        const errorElementId = fieldId === 'idNumber' ? 'idError' : (fieldId + 'Error');
        const errorElement = document.getElementById(errorElementId);
        
        if (!errorElement) return;

        let isValid = true;
        let errorMessage = '';

        switch (fieldId) {
            case 'idNumber':
                if (!value) {
                    errorMessage = 'ID Number is required';
                    isValid = false;
                } else if (!/^\d{4}-\d{4}$/.test(value)) {
                    errorMessage = 'Invalid ID format (XXXX-XXXX)';
                    isValid = false;
                }
                break;
                
            case 'firstName':
            case 'lastName':
            case 'middleName':
                return this.validateNameInput(field, { isOptional: fieldId === 'middleName' });
            
            case 'extension':
                // Extension is optional; if provided, it must match allowed list
                if (value) {
                    if (/[^a-zA-Z]/.test(value)) {
                        errorMessage = 'Extension allows letters only';
                        isValid = false;
                    } else if (value.length > 3) {
                        errorMessage = 'Extension allows up to 3 letters';
                        isValid = false;
                    } else if (!isValidExtensionValue(value)) {
                        errorMessage = `Extension must be one of: ${EXTENSION_DISPLAY_LIST}`;
                        isValid = false;
                    } else {
                        // Check capitalization - Jr and Sr must be capitalized
                        const expectedFormat = formatExtensionValue(value);
                        if (value.toLowerCase() === 'jr' || value.toLowerCase() === 'sr') {
                            if (value !== expectedFormat) {
                                errorMessage = `Extension must be "${expectedFormat}" (not "${value}")`;
                                isValid = false;
                            }
                        } else if (this.hasConsecutiveLetters(value.toLowerCase()) && value.toLowerCase() !== 'iii') {
                            errorMessage = 'Cannot contain 3 consecutive letters';
                            isValid = false;
                        }
                    }
                }
                break;
                
            case 'phoneNumber':
                if (!value) {
                    errorMessage = 'Phone number is required';
                    isValid = false;
                } else {
                    // Check for letters first
                    if (/[a-zA-Z]/.test(value)) {
                        errorMessage = 'Phone number cannot contain letters';
                    isValid = false;
                } else {
                    const digitsOnly = value.replace(/\D/g, '');
                    if (!/^09\d{9}$/.test(digitsOnly)) {
                        errorMessage = 'Phone number must start with 09 and be 11 digits';
                        isValid = false;
                        }
                    }
                }
                break;
                
            case 'email':
                if (!value) {
                    errorMessage = 'Email is required';
                    isValid = false;
                } else {
                    // Check for warnings first
                    const warningMsg = this.getEmailWarning(value);
                    if (warningMsg) {
                        // Warning found, but don't block submission
                        // The blur event will show the warning
                    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())) {
                        errorMessage = 'Invalid email format';
                        isValid = false;
                    }
                }
                break;
                
            case 'username':
                if (!value) {
                    errorMessage = 'Username is required';
                    isValid = false;
                } else {
                    const usernameError = this.getUsernameError(value);
                    if (usernameError) {
                        errorMessage = usernameError;
                        isValid = false;
                    }
                }
                break;
                
            case 'password':
                if (!value) {
                    errorMessage = 'Password is required';
                    isValid = false;
                } else if (value.length < 8) {
                    errorMessage = 'Password must be at least 8 characters';
                    isValid = false;
                } else if (!/[a-z]/.test(value)) {
                    errorMessage = 'Password must include a lowercase letter';
                    isValid = false;
                } else if (!/[A-Z]/.test(value)) {
                    errorMessage = 'Password must include an uppercase letter';
                    isValid = false;
                } else if (!/\d/.test(value)) {
                    errorMessage = 'Password must include a number';
                    isValid = false;
                }
                break;
                
            case 'confirmPassword':
                if (!value) {
                    errorMessage = 'Please re-enter your password';
                    isValid = false;
                }
                break;
                
            case 'age':
                if (!value) {
                    errorMessage = 'Age is required';
                    isValid = false;
                } else if (value < 1 || value > 120) {
                    errorMessage = 'Age must be between 1 and 120';
                    isValid = false;
                }
                break;
                
            case 'sex':
                if (!value) {
                    errorMessage = 'Please select your sex';
                    isValid = false;
                }
                break;
                
            case 'birthDate':
                if (!value) {
                    errorMessage = 'Birth date is required';
                    isValid = false;
                } else {
                    const birthDate = new Date(value);
                    const today = new Date();
                    if (birthDate > today) {
                        errorMessage = 'Birth date cannot be in the future';
                        isValid = false;
                    }
                }
                break;
                
            case 'purok':
                if (!value) {
                    errorMessage = 'This field is required';
                    isValid = false;
                } else if (/[^a-zA-Z0-9\s\-'\.\,]/.test(value)) {
                    errorMessage = 'Invalid characters';
                    isValid = false;
                } else if (this.hasConsecutiveLetters(value.toLowerCase())) {
                    errorMessage = 'Cannot contain 3 consecutive letters';
                    isValid = false;
                }
                // Note: Purok CAN start with a number (unlike other address fields)
                break;
                
            case 'barangay':
            case 'city':
            case 'province':
                if (!value) {
                    errorMessage = 'This field is required';
                    isValid = false;
                } else if (/^\d/.test(value.trim())) {
                    errorMessage = 'Cannot start with a number';
                    isValid = false;
                } else if (/[^a-zA-Z0-9\s\-'\.\,]/.test(value)) {
                    errorMessage = 'Invalid characters';
                    isValid = false;
                } else if (this.hasConsecutiveLetters(value.toLowerCase())) {
                    errorMessage = 'Cannot contain 3 consecutive letters';
                    isValid = false;
                }
                break;
                
            case 'country':
                if (!value) {
                    errorMessage = 'This field is required';
                    isValid = false;
                }
                break;
                
            case 'zipCode':
                if (!value) {
                    errorMessage = 'Zip code is required';
                    isValid = false;
                } else if (!/^\d{4}$/.test(value)) {
                    errorMessage = 'Invalid zip code format';
                    isValid = false;
                }
                break;
        }

        if (!isValid) {
            this.showError(field, errorElement, errorMessage);
        } else {
            this.clearError(field);
        }

        return isValid;
    }

    validateNameInput(field, options = {}) {
        const { isOptional = false } = options;
        const raw = field.value || '';
        const trimmed = raw.trim();
        const errorElement = document.getElementById(field.id + 'Error');

        if (!errorElement) {
            return true;
        }

        const showIssue = (type, message) => {
            if (type === 'warning') {
                this.showWarning(field, errorElement, message);
            } else {
                this.showError(field, errorElement, message);
            }
        };

        if (!trimmed) {
            if (isOptional) {
                this.clearError(field);
                return true;
            }
            // Show field-specific required message
            const fieldName = field.id === 'firstName' ? 'First name' : 
                            field.id === 'lastName' ? 'Last name' : 
                            field.id === 'middleName' ? 'Middle name' : 'This field';
            showIssue('error', `${fieldName} is Required`);
            return false;
        }

        if (/\d/.test(raw)) {
            showIssue('error', 'Numbers not allowed');
            return false;
        }

        // Title case check (ignoring invalid characters so we can surface casing issues before other errors)
        const caseSafeValue = trimmed.replace(/[^a-zA-Z\s\-'\.]/g, ' ').replace(/\s+/g, ' ').trim();
        if (caseSafeValue) {
            const titleWarning = this.getTitleCaseWarning(caseSafeValue);
            if (titleWarning) {
                showIssue('warning', titleWarning);
                return true;
            }
        }

        const invalidChars = raw.match(/[^a-zA-Z\s\-'\.]/g);
        if (invalidChars) {
            const uniqueChars = [...new Set(invalidChars)];
            showIssue('error', `Invalid chars: ${uniqueChars.join(', ')}`);
            return false;
        }

        if (!isOptional && raw.length < 2) {
            showIssue('error', 'Min 2 letters');
            return false;
        }

        if (this.hasConsecutiveLetters(raw.toLowerCase())) {
            showIssue('error', 'No 3+ repeats');
            return false;
        }

        if (raw !== trimmed) {
            showIssue('warning', 'Trim spaces');
            return true;
        }

        if (/\s{2,}/.test(raw)) {
            showIssue('warning', 'Single spaces only');
            return true;
        }

        const titleWarning = this.getTitleCaseWarning(trimmed);
        if (titleWarning) {
            showIssue('warning', titleWarning);
            return true;
        }

        this.clearError(field);
        return true;
    }

    validateAddressInput(field, options = {}) {
        const {
            isOptional = false,
            allowLeadingNumbers = false,
            allowedPattern = /[^a-zA-Z0-9\s\-'\.,]/g,
            invalidMessage = 'Invalid chars',
            requireTitleCase = true,
            label = 'This field'
        } = options;

        const raw = field.value || '';
        const trimmed = raw.trim();
        const errorElement = document.getElementById(field.id + 'Error');

        if (!errorElement) {
            return true;
        }

        const showIssue = (type, message) => {
            if (type === 'warning') {
                this.showWarning(field, errorElement, message);
            } else {
                this.showError(field, errorElement, message);
            }
        };

        if (!trimmed) {
            if (isOptional) {
                this.clearError(field);
                return true;
            }
            showIssue('error', `${label} is Required`);
            return false;
        }

        if (raw !== trimmed) {
            showIssue('warning', 'Trim spaces');
            return true;
        }

        if (/\s{2,}/.test(raw)) {
            showIssue('warning', 'Single spaces only');
            return true;
        }

        if (!allowLeadingNumbers && /^\d/.test(trimmed)) {
            showIssue('error', 'Cannot start with number');
            return false;
        }

        if (requireTitleCase) {
            const caseSafeValue = trimmed.replace(/[^a-zA-Z\s\-'\.,]/g, ' ').replace(/\s+/g, ' ').trim();
            if (caseSafeValue) {
                const titleWarning = this.getTitleCaseWarning(caseSafeValue);
                if (titleWarning) {
                    showIssue('warning', titleWarning);
                    return true;
                }
            }
        }

        const invalidChars = raw.match(allowedPattern);
        if (invalidChars) {
            const uniqueChars = [...new Set(invalidChars)];
            const firstChar = uniqueChars[0];
            const extraCount = uniqueChars.length - 1;
            const extraText = extraCount > 0 ? ` (+${extraCount} more)` : '';
            showIssue('error', `${invalidMessage}: "${firstChar}"${extraText}.`);
            return false;
        }

        if (this.hasConsecutiveLetters(trimmed.toLowerCase())) {
            showIssue('error', 'No 3+ repeats');
            return false;
        }

        this.clearError(field);
        return true;
    }

    updateBodyScrollState() {
        const body = document.body;
        const formPanel = document.querySelector('.form-panel');
        if (!body || !formPanel) return;
        const headerHeight = document.querySelector('.cyber-header')?.offsetHeight || 0;
        const footerHeight = document.querySelector('.cyber-footer')?.offsetHeight || 0;
        const buffer = 80;
        const totalHeight = formPanel.offsetHeight + headerHeight + footerHeight + buffer;
        const needsScroll = totalHeight > window.innerHeight;
        body.classList.toggle('scroll-active', needsScroll);
    }

    showError(field, errorElement, message) {
        field.style.borderColor = 'var(--cyber-error)';
        field.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }

    showWarning(field, errorElement, message) {
        field.style.borderColor = 'var(--cyber-error)';
        field.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
        errorElement.textContent = message;
        errorElement.classList.add('show');
        errorElement.style.color = 'var(--cyber-error)';
    }

    clearError(field) {
        const errorElement = document.getElementById(field.id + 'Error');
        if (errorElement) {
            field.style.borderColor = '';
            field.style.boxShadow = '';
            field.classList.remove('error-field');
            errorElement.textContent = '';
            errorElement.classList.remove('show');
            errorElement.style.color = '';
        }
    }

    validateForm() {
        // Get all required fields
        const requiredFields = [
            'idNumber', 'lastName', 'firstName', 'middleName', 'birthDate', 'age', 'sex',
            'phoneNumber', 'email', 'username', 'password', 'confirmPassword',
            'country', 'province', 'city', 'barangay', 'purok', 'zipCode'
        ];
        
        let isValid = true;

        // Validate each required field
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                // For fields with blur validation (name fields, extension, address fields), trigger their validation
                if (['firstName', 'lastName', 'middleName'].includes(fieldId)) {
                    if (!this.validateNameInput(field, { isOptional: fieldId === 'middleName' })) {
                        isValid = false;
                    }
                } else if (fieldId === 'extension') {
                    const result = this.validateCapitalization(field, true);
                    const errorElement = document.getElementById(fieldId + 'Error');
                    if (!result.ok) {
                        if (errorElement) {
                            this.showError(field, errorElement, result.message || 'Invalid format');
                        }
                        isValid = false;
                    } else if (result.warning) {
                        if (errorElement) {
                            this.showWarning(field, errorElement, result.message || 'Invalid format');
                        }
                    } else {
                        this.clearError(field);
                    }
                } else if (['barangay', 'city', 'province'].includes(fieldId)) {
                    // Set user-friendly labels
                    const fieldLabels = {
                        'province': 'Province',
                        'city': 'City',
                        'barangay': 'Barangay'
                    };
                    const options = {
                        label: fieldLabels[fieldId] || fieldId.charAt(0).toUpperCase() + fieldId.slice(1),
                        allowLeadingNumbers: false,
                        allowedPattern: /[^a-zA-Z0-9\s\-'\.,]/g,
                        invalidMessage: 'Invalid char',
                        allowedDescription: "letters, numbers, spaces, hyphen (-), apostrophe ('), period (.), comma (,)",
                        requireTitleCase: true
                    };
                    if (!this.validateAddressInput(field, options)) {
                        isValid = false;
                    }
                } else if (fieldId === 'purok') {
                    const options = {
                        label: 'Purok/Street',
                        allowLeadingNumbers: true,
                        allowedPattern: /[^A-Za-z0-9\s\-]/g,
                        invalidMessage: 'Invalid char',
                        allowedDescription: 'letters, numbers, spaces, hyphen (-)',
                        requireTitleCase: true
                    };
                    if (!this.validateAddressInput(field, options)) {
                        isValid = false;
                    }
                } else if (fieldId === 'username') {
                    // Trigger username validation
                    const value = field.value;
                    const errorElement = document.getElementById('usernameError');
                    if (value) {
                        const warningMsg = this.getUsernameWarning(value);
                        if (warningMsg) {
                            if (errorElement) {
                                this.showWarning(field, errorElement, warningMsg);
                            }
                        } else {
                            const errorMsg = this.getUsernameError(value);
                            if (errorMsg) {
                                if (errorElement) {
                                    errorElement.textContent = errorMsg;
                                    errorElement.classList.add('show');
                                }
                                field.style.borderColor = 'var(--cyber-error)';
                                field.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                                isValid = false;
                            } else {
                                this.clearError(field);
                            }
                        }
                    } else {
                        if (errorElement) {
                            errorElement.textContent = 'Username is required';
                            errorElement.classList.add('show');
                        }
                        field.style.borderColor = 'var(--cyber-error)';
                        field.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                        isValid = false;
                    }
                } else if (fieldId === 'email') {
                    // Trigger email validation
                    const value = field.value;
                    const errorElement = document.getElementById('emailError');
                    if (value) {
                        const warningMsg = this.getEmailWarning(value);
                        if (warningMsg) {
                            if (errorElement) {
                                this.showWarning(field, errorElement, warningMsg);
                            }
                        } else if (!this.isValidEmail(value.trim())) {
                            if (errorElement) {
                                errorElement.textContent = 'Invalid email format';
                                errorElement.classList.add('show');
                            }
                            field.style.borderColor = 'var(--cyber-error)';
                            field.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                            isValid = false;
                        } else {
                            this.clearError(field);
                        }
                    } else {
                        if (errorElement) {
                            errorElement.textContent = 'Email is required';
                            errorElement.classList.add('show');
                        }
                        field.style.borderColor = 'var(--cyber-error)';
                        field.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                        isValid = false;
                    }
                } else if (fieldId === 'confirmPassword') {
                    // Validate confirmPassword separately to show blank error
                    const value = field.value;
                    const errorElement = document.getElementById('confirmPasswordError');
                    if (!value) {
                        if (errorElement) {
                            errorElement.textContent = 'Please re-enter your password';
                            errorElement.classList.add('show');
                        }
                        field.style.borderColor = 'var(--cyber-error)';
                        field.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                        isValid = false;
                    } else {
                        // Then check password match
                        this.validatePasswordMatch();
                    }
                } else {
                    // Use standard validateField for other fields (including idNumber)
                    if (!this.validateField(field)) {
                        isValid = false;
                    }
                }
            }
        });

        // Validate password match (if confirmPassword has value)
        const confirmPassword = document.getElementById('confirmPassword');
        if (confirmPassword && confirmPassword.value) {
            this.validatePasswordMatch();
        }

        return isValid;
    }

    getEmailWarning(value) {
        if (!value) return null;
        
        // Priority 1: Check for spaces
        if (/\s/.test(value)) {
            return 'Warning: Email cannot contain spaces';
        }
        
        // Priority 2: Check for invalid characters (excluding @ which is required)
        // Invalid: ! # $ % ^ & * = { } [ ] / \ | : ; " ' < > , ?
        const invalidChars = value.match(/[!#$%^&*={}\[\]\/\\|:;"'<>,?]/g);
        if (invalidChars) {
            const uniqueChars = [...new Set(invalidChars)];
            return `Warning: Invalid characters: ${uniqueChars.join(', ')}`;
        }
        
        return null;
    }

    isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    isAllUppercaseEmail(value) {
        const letters = value.replace(/[^a-zA-Z]/g, '');
        if (!letters) return false;
        return letters === letters.toUpperCase() && letters !== letters.toLowerCase();
    }

    isValidLocalPartCase(value) {
        const atIndex = value.indexOf('@');
        if (atIndex <= 0) return true;
        const local = value.slice(0, atIndex);
        const letters = local.replace(/[^a-zA-Z]/g, '');
        if (!letters) return true;
        const first = letters.charAt(0);
        const rest = letters.slice(1);
        const allLower = letters === letters.toLowerCase();
        const firstUpperRestLower = /^[A-Z]$/.test(first) && rest === rest.toLowerCase();
        return allLower || firstUpperRestLower;
    }

    isValidDomainCase(value) {
        const atIndex = value.indexOf('@');
        if (atIndex <= 0) return true;
        const domain = value.slice(atIndex + 1);
        const letters = domain.replace(/[^a-zA-Z]/g, '');
        if (!letters) return true;
        // Domain should be all lowercase
        return letters === letters.toLowerCase();
    }

    isProperTitleCase(str) {
        if (typeof str !== 'string' || !str.trim()) return true;
        // Expected Title Case: First letter of each word is uppercase, rest is lowercase
        // Allow hyphens, apostrophes, and periods
        const words = str.split(/\s+/);
        for (let word of words) {
            if (!word) continue;
            
            // Skip words that are purely numeric (numbers don't need capitalization)
            if (/^\d+$/.test(word)) {
                continue;
            }
            
            // Skip words that don't contain any letters
            if (!/[a-zA-Z]/.test(word)) {
                continue;
            }
            
            // Split by hyphens, apostrophes, and periods to check each part
            const parts = word.split(/([-'.])/);
            for (let i = 0; i < parts.length; i++) {
                const part = parts[i];
                if (!part || /^[-'.]$/.test(part)) continue; // Skip separators
                
                // Skip purely numeric parts
                if (/^\d+$/.test(part)) {
                    continue;
                }
                
                // Skip parts without letters
                if (!/[a-zA-Z]/.test(part)) {
                    continue;
                }
                
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

    getTitleCaseWarning(str) {
        if (typeof str !== 'string' || !str.trim()) return null;
        const words = str.split(/\s+/).filter(w => w.trim());
        const issues = [];
        const correctedExamples = [];
        
        words.forEach((word, index) => {
            if (!word) return;
            
            // Skip words that are purely numeric (numbers don't need capitalization)
            if (/^\d+$/.test(word)) {
                return;
            }
            
            // Skip words that don't contain any letters (only numbers and special chars)
            if (!/[a-zA-Z]/.test(word)) {
                return;
            }
            
            const parts = word.split(/([-'.])/);
            let hasIssue = false;
            
            for (let i = 0; i < parts.length; i++) {
                const part = parts[i];
                if (!part || /^[-'.]$/.test(part)) continue;
                
                // Skip purely numeric parts
                if (/^\d+$/.test(part)) {
                    continue;
                }
                
                // Skip parts without letters
                if (!/[a-zA-Z]/.test(part)) {
                    continue;
                }
                
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
                // Create corrected version of the word, preserving numbers and special chars
                // Capitalize first letter of each letter sequence, keep numbers as-is
                const correctedWord = word.replace(/\b[a-z]+/gi, (match) => {
                    return match.charAt(0).toUpperCase() + match.slice(1).toLowerCase();
                });
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

    hasConsecutiveChars(str) {
        // Check for 3 consecutive letters (abc, bcd, etc.)
        for (let i = 0; i < str.length - 2; i++) {
            const char1 = str.charCodeAt(i);
            const char2 = str.charCodeAt(i + 1);
            const char3 = str.charCodeAt(i + 2);
            
            // Check if all three are letters and consecutive
            if (char1 >= 97 && char1 <= 122 && // a-z
                char2 >= 97 && char2 <= 122 &&
                char3 >= 97 && char3 <= 122 &&
                char2 === char1 + 1 &&
                char3 === char2 + 1) {
                return true;
            }
            
            // Check if all three are numbers and consecutive
            if (char1 >= 48 && char1 <= 57 && // 0-9
                char2 >= 48 && char2 <= 57 &&
                char3 >= 48 && char3 <= 57 &&
                char2 === char1 + 1 &&
                char3 === char2 + 1) {
                return true;
            }
        }
        return false;
    }

    hasConsecutiveLetters(str) {
        // Check for 3 consecutive letters (abc, bcd, etc.) or 3 identical letters (aaa, ddd, etc.)
        for (let i = 0; i < str.length - 2; i++) {
            const char1 = str.charCodeAt(i);
            const char2 = str.charCodeAt(i + 1);
            const char3 = str.charCodeAt(i + 2);
            
            // Check if all three are letters
            if (char1 >= 97 && char1 <= 122 && // a-z
                char2 >= 97 && char2 <= 122 &&
                char3 >= 97 && char3 <= 122) {
                
                // Check for 3 identical consecutive letters (aaa, ddd, etc.)
                if (char1 === char2 && char2 === char3) {
                    return true;
                }
                
                // Check for 3 consecutive letters in sequence (abc, bcd, etc.)
                if (char2 === char1 + 1 && char3 === char2 + 1) {
                    return true;
                }
            }
        }
        return false;
    }

    getUsernameWarning(value) {
        if (!value) return null;
        
        // No warnings for username - all issues are errors
        // Uppercase and format issues are handled in getUsernameError()
        return null;
    }

    getUsernameError(value) {
        if (!value) {
            return 'Username is required';
        }

        // Format must be: lowercase letters, dot, numbers (e.g., "john.1")
        // Pattern: ^[a-z]+\.[0-9]+$
        
        // Check for uppercase letters (show as error, but allow input)
        if (/[A-Z]/.test(value)) {
            const uppercaseChars = value.match(/[A-Z]/g);
            if (uppercaseChars) {
                const uniqueChars = [...new Set(uppercaseChars)];
                return `Uppercase letters not allowed: ${uniqueChars.join(', ')}. Format must be "lowercase.#" (e.g., john.1)`;
            }
        }

        // Check if format matches "lowercase.#" pattern
        if (!/^[a-z]+\.[0-9]+$/.test(value)) {
            // Provide specific error messages based on what's wrong
            if (!/^[a-z]/.test(value)) {
                if (/^[0-9]/.test(value)) {
                    return 'Username must start with lowercase letters, not numbers. Format: "lowercase.#" (e.g., john.1)';
                }
                if (/^[A-Z]/.test(value)) {
                    return 'Username must start with lowercase letters. Format: "lowercase.#" (e.g., john.1)';
                }
                return 'Username must start with lowercase letters. Format: "lowercase.#" (e.g., john.1)';
            }
            
            if (!/\./.test(value)) {
                return 'Username must contain a dot (.) between letters and numbers. Format: "lowercase.#" (e.g., john.1)';
            }
            
            // Check if dot is in wrong position
            const dotIndex = value.indexOf('.');
            if (dotIndex === 0) {
                return 'Username cannot start with a dot. Format: "lowercase.#" (e.g., john.1)';
            }
            if (dotIndex === value.length - 1) {
                return 'Username must have numbers after the dot. Format: "lowercase.#" (e.g., john.1)';
            }
            
            // Check parts before and after dot
            const parts = value.split('.');
            if (parts.length > 2) {
                return 'Username can only have one dot. Format: "lowercase.#" (e.g., john.1)';
            }
            
            const beforeDot = parts[0];
            const afterDot = parts[1];
            
            if (!/^[a-z]+$/.test(beforeDot)) {
                if (/[0-9]/.test(beforeDot)) {
                    return 'Letters before the dot must be lowercase only, no numbers. Format: "lowercase.#" (e.g., john.1)';
                }
                if (/[^a-z]/.test(beforeDot)) {
                    const invalidChars = beforeDot.match(/[^a-z]/g);
                    if (invalidChars) {
                        const uniqueChars = [...new Set(invalidChars)];
                        return `Invalid characters before dot: ${uniqueChars.join(', ')}. Format: "lowercase.#" (e.g., john.1)`;
                    }
                }
            }
            
            if (!/^[0-9]+$/.test(afterDot)) {
                if (/[a-zA-Z]/.test(afterDot)) {
                    return 'After the dot, only numbers are allowed. Format: "lowercase.#" (e.g., john.1)';
                }
                if (/[^0-9]/.test(afterDot)) {
                    const invalidChars = afterDot.match(/[^0-9]/g);
                    if (invalidChars) {
                        const uniqueChars = [...new Set(invalidChars)];
                        return `Invalid characters after dot: ${uniqueChars.join(', ')}. Format: "lowercase.#" (e.g., john.1)`;
                    }
                }
            }
        }

        // Length constraints
        if (value.length < 3) {
            return 'Username must be at least 3 characters (e.g., john.1)';
        }
        if (value.length > 50) {
            return 'Username must be at most 50 characters';
        }

        // Check if it matches the pattern now
        if (!/^[a-z]+\.[0-9]+$/.test(value)) {
            return 'Username format must be "lowercase.#" (e.g., john.1)';
        }

        // Not a reserved username
        if (this.isReservedUsername && this.isReservedUsername(value.split('.')[0])) {
            return 'This username is reserved and cannot be used';
        }

        return null; // No error
    }

    isValidUsername(value) {
        if (!value) return false;

        // Format must be: lowercase letters, dot, numbers (e.g., "john.1")
        // Pattern: ^[a-z]+\.[0-9]+$
        
        // Check for uppercase letters
        if (/[A-Z]/.test(value)) return false;

        // Check if format matches "lowercase.#" pattern
        if (!/^[a-z]+\.[0-9]+$/.test(value)) return false;

        // Length constraints
        if (value.length < 3 || value.length > 50) return false;

        // Not a reserved username
        const beforeDot = value.split('.')[0];
        if (this.isReservedUsername && this.isReservedUsername(beforeDot)) return false;

        return true;
    }

    isReservedUsername(name) {
        const reserved = new Set([
            'admin','administrator','root','system','support','help','contact','service','services','security','owner','staff','moderator','mod','test','testing','null','undefined','superuser'
        ]);
        return reserved.has(name);
    }

    handleSubmit(e) {
        e.preventDefault();
        
        // Validate all required fields before submission
        if (!this.validateForm()) {
            return;
        }
        
        this.submitForm();
    }

    submitForm() {
        const submitBtn = this.form.querySelector('.cyber-btn');
        const originalText = submitBtn.querySelector('.btn-text').textContent;
        
        // Show loading state
        submitBtn.querySelector('.btn-text').textContent = 'INITIALIZING...';
        submitBtn.disabled = true;
        
        // Simulate cyberpunk loading effect
        this.simulateLoading();
        
        // Collect form data
        const formData = new FormData(this.form);
        const data = Object.fromEntries(formData);
        
        // Send to server: stash registration first (defer DB insert)
        fetch('../php/register_step1.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
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
                this.showSuccess('Neural profile Step 1 complete. Proceeding to security questions...');
                // Show generated ID if provided
                if (data.idNumber) {
                    const idInput = document.getElementById('idNumber');
                    if (idInput) idInput.value = data.idNumber;
                }
                setTimeout(() => {
                    window.location.href = 'register_security.php';
                }, 1500);
            } else {
                if (data.fieldErrors) {
                    this.showFieldErrors(data.fieldErrors);
                }
                this.showSystemError(data.message || 'Registration failed');
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
        // Clear all existing errors first
        this.clearAllErrors();
        
        // Show field-specific errors
        Object.keys(fieldErrors).forEach(fieldName => {
            const errorMessage = fieldErrors[fieldName];
            this.showFieldError(fieldName, errorMessage);
        });
    }

    showFieldError(fieldName, errorMessage) {
        const field = document.getElementById(fieldName);
        const errorElement = document.getElementById(fieldName + 'Error');
        
        if (field) {
            // Highlight the field with error styling
            field.style.borderColor = 'var(--cyber-error)';
            field.style.boxShadow = '0 0 10px rgba(255, 107, 107, 0.3)';
            field.classList.add('error-field');
        }
        
        if (errorElement) {
            errorElement.textContent = errorMessage;
            errorElement.classList.add('show');
        }
    }

    clearAllErrors() {
        // Clear all field errors
        const errorElements = document.querySelectorAll('.error-message');
        errorElements.forEach(element => {
            element.textContent = '';
            element.classList.remove('show');
        });
        // Clear all success messages
        const successElements = document.querySelectorAll('.success-message');
        successElements.forEach(element => {
            element.textContent = '';
            element.classList.remove('show');
        });
        
        // Clear all field highlighting
        const fields = document.querySelectorAll('.cyber-input');
        fields.forEach(field => {
            field.style.borderColor = '';
            field.style.boxShadow = '';
            field.classList.remove('error-field');
        });
    }

    showSystemMessage(message, type) {
        // Create system message overlay
        const overlay = document.createElement('div');
        overlay.className = `system-message ${type}`;
        overlay.innerHTML = `
            <div class="message-content">
                <div class="message-icon">${type === 'success' ? '✓' : '⚠'}</div>
                <div class="message-text">${message}</div>
            </div>
        `;
        
        // Add styles
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
        
        // Remove after 3 seconds
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

function autoFixNameSpaces(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/\s+/g, ' ').trim();
}
function toTitleCase(str) {
    if (typeof str !== 'string') return '';
    const lower = str.toLowerCase();
    return lower.replace(/(?:^|[\s\-'])\w/g, function(match) {
        return match.toUpperCase();
    });
}

function capitalizeFirstLetter(str) {
    if (typeof str !== 'string') return '';
    const trimmed = str.trim();
    if (!trimmed) return '';
    return trimmed.charAt(0).toUpperCase() + trimmed.slice(1);
}
// Name fields now show warnings instead of auto-correcting
// Auto-correction removed - validation with warnings is handled in setupCapitalizationValidation()

// Extension field auto-correction removed - validation with warnings is handled in setupCapitalizationValidation()

// Address fields (province, city, barangay, purok) auto-correction removed
// Validation with warnings is handled in setupCapitalizationValidation()

// Normalize username: trim, collapse spaces, replace spaces with underscore, lowercase, remove invalids, collapse underscores
function normalizeUsername(str) {
    if (!str) return '';
    let v = str.replace(/\s+/g, ' ').trim();          // collapse spaces and trim
    v = v.replace(/\s+/g, '_');                       // spaces -> underscore
    v = v.toLowerCase();                               // force lowercase
    v = v.replace(/[^a-z0-9_]/g, '');                  // keep only a-z0-9_
    v = v.replace(/_+/g, '_');                         // collapse multiple underscores
    v = v.replace(/^[0-9]+/, '');                      // drop leading digits
    return v;
}
// Initialize the cyberpunk registration system
document.addEventListener('DOMContentLoaded', () => {
    const app = new CyberpunkRegistration();
    app.updateBodyScrollState();
    let resizeTimeout = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => app.updateBodyScrollState(), 150);
    });
    
    // Add cyberpunk sound effects (optional)
    const inputs = document.querySelectorAll('.cyber-input');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            // Add subtle visual feedback
            input.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', () => {
            input.parentElement.style.transform = 'scale(1)';
        });
    });

    const idInput = document.getElementById('idNumber');
    const idIcon = document.getElementById('idNumberIcon');
    if (idInput) {
        // Check if ID already exists (page reload)
        if (idInput.value && idInput.value.trim() !== '') {
            // ID already exists, show icon and disable click
            if (idIcon) {
                idIcon.style.display = 'block';
            }
            idInput.classList.add('locked');
            idInput.style.pointerEvents = 'none';
            idInput.style.cursor = 'not-allowed';
            idInput.style.paddingRight = '2rem';
        } else {
            // ID doesn't exist, allow click to generate
            idInput.addEventListener('click', function () {
                // Prevent multiple clicks
                if (idInput.classList.contains('locked')) {
                    return;
                }
                
                fetch('../php/get_next_id.php')
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.idNumber) {
                            idInput.value = result.idNumber;
                            
                            // Show blocked icon
                            if (idIcon) {
                                idIcon.style.display = 'block';
                            }
                            
                            // Lock the field and prevent further clicks
                            idInput.classList.add('locked');
                            idInput.style.pointerEvents = 'none';
                            idInput.style.cursor = 'not-allowed';
                            idInput.style.paddingRight = '2rem';
                            
                            // Clear any previous errors and styling
                            const errorElement = document.getElementById('idError');
                            if (errorElement) {
                                errorElement.textContent = '';
                                errorElement.classList.remove('show');
                            }
                            // Clear error styling from input
                            idInput.style.borderColor = '';
                            idInput.style.boxShadow = '';
                            idInput.classList.remove('error-field');
                        } else {
                            const errorElement = document.getElementById('idError');
                            if (errorElement) {
                                errorElement.textContent = 'Unable to retrieve ID number. Please try again.';
                                errorElement.classList.add('show');
                            }
                            idInput.style.borderColor = 'var(--cyber-error)';
                            idInput.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                        }
                    })
                    .catch(() => {
                        const errorElement = document.getElementById('idError');
                        if (errorElement) {
                            errorElement.textContent = 'Unable to retrieve ID number. Please try again.';
                            errorElement.classList.add('show');
                        }
                        idInput.style.borderColor = 'var(--cyber-error)';
                        idInput.style.boxShadow = '0 0 10px rgba(255, 0, 64, 0.3)';
                    });
            });
        }
    }
});
