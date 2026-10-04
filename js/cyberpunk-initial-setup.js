let currentStep = 1;

function showStep(stepIndex) {
    document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.step-dot').forEach(el => {
        el.classList.remove('active');
        const num = parseInt(el.textContent);
        if (num < stepIndex) {
            el.classList.add('completed');
        } else {
            el.classList.remove('completed');
        }
    });

    document.getElementById(`step-${stepIndex}`).classList.add('active');
    document.getElementById(`dot-${stepIndex}`).classList.add('active');
    currentStep = stepIndex;
}

function nextStep(stepIndex) {
    // Validate Step 1 before moving to Step 2
    if (stepIndex === 2) {
        const step1 = document.getElementById('step-1');
        const inputs = step1.querySelectorAll('input[required], select[required]');
        let valid = true;
        for (const input of inputs) {
            if (!input.checkValidity()) {
                input.reportValidity();
                valid = false;
                break;
            }
        }
        if (!valid) return;
    }

    // Validate Step 2 before moving to Step 3
    if (stepIndex === 3) {
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const errorEl = document.getElementById('newPasswordError');
        const confirmErrorEl = document.getElementById('confirmPasswordError');
        
        errorEl.textContent = '';
        confirmErrorEl.textContent = '';
        
        const pwd = newPassword.value;
        const cpwd = confirmPassword.value;
        
        if (pwd.length < 8 || !/[A-Z]/.test(pwd) || !/[a-z]/.test(pwd) || !/[0-9]/.test(pwd) || !/[^a-zA-Z0-9]/.test(pwd)) {
            errorEl.textContent = 'Password must meet all security requirements.';
            errorEl.style.color = 'var(--cyber-error)';
            errorEl.style.display = 'block';
            newPassword.focus();
            return;
        }
        
        if (pwd !== cpwd) {
            confirmErrorEl.textContent = 'Passwords do not match.';
            confirmErrorEl.style.color = 'var(--cyber-error)';
            confirmErrorEl.style.display = 'block';
            confirmPassword.focus();
            return;
        }
    }

    showStep(stepIndex);
}

function prevStep(stepIndex) {
    showStep(stepIndex);
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('initialSetupForm');
    const msg = document.getElementById('msg');
    const submitBtn = document.getElementById('submitBtn');

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

    // Security Questions mutually exclusive selection
    const qSelects = [
        document.getElementById('q1'),
        document.getElementById('q2'),
        document.getElementById('q3')
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

    const phoneField = document.getElementById('phone');
    if (phoneField) {
        phoneField.addEventListener('input', function(e) {
            let val = this.value.replace(/\D/g, '');
            if (val.length > 11) val = val.slice(0, 11);
            this.value = val;
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        msg.textContent = '';
        msg.className = 'status-message';

        const payload = {
            firstName: form.firstName.value,
            lastName: form.lastName.value,
            username: form.username.value,
            phone: form.phone.value,
            birthDate: form.birthDate.value,
            sex: form.sex.value,
            country: form.country.value,
            province: form.province.value,
            city: form.city.value,
            barangay: form.barangay.value,
            purok: form.purok.value,
            address: form.address.value,
            newPassword: form.newPassword.value,
            confirmPassword: form.confirmPassword.value,
            q1: form.q1.value,
            a1: form.a1.value,
            q2: form.q2.value,
            a2: form.a2.value,
            q3: form.q3.value,
            a3: form.a3.value
        };

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        if (csrfToken) {
            payload.csrf_token = csrfToken; // Keep it in body for fallback
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = 'PROCESSING...';

        try {
            const response = await fetch('../php/process-initial-setup.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            });
            const data = await response.json();

            if (data.success) {
                msg.textContent = data.message;
                msg.classList.add('success');
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1500);
            } else {
                msg.textContent = data.message;
                msg.classList.add('error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'FINALIZE SETUP';
            }
        } catch (error) {
            msg.textContent = 'Network error. Please try again.';
            msg.classList.add('error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'FINALIZE SETUP';
        }
    });

    // Initialize SVG icons for all show-password buttons
    const buttons = form.querySelectorAll('.show-password');
    buttons.forEach(btn => {
        btn.setAttribute('aria-label', 'Show password');
        btn.setAttribute('aria-pressed', 'false');
        btn.innerHTML = getEyeSVG(false);
    });
});

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
