document.addEventListener('DOMContentLoaded', () => {
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const step4 = document.getElementById('step4');

    const form1 = document.getElementById('forgotPasswordForm');
    const form2 = document.getElementById('otpForm');
    const form3 = document.getElementById('questionsForm');
    const form4 = document.getElementById('resetForm');

    const idInput = document.getElementById('idNumber');
    const otpInput = document.getElementById('otpCode');
    const ans1Input = document.getElementById('ans1');
    const ans2Input = document.getElementById('ans2');
    const ans3Input = document.getElementById('ans3');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmNewPasswordInput = document.getElementById('confirmNewPassword');

    let currentIdNumber = '';

    function showError(inputId, errorId, message) {
        if (inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                input.classList.add('error');
                input.style.borderColor = 'var(--cyber-error)';
            }
        }
        const errEl = document.getElementById(errorId);
        if (errEl) {
            errEl.textContent = message;
            errEl.style.display = 'block';
        }
    }

    function clearError(inputId, errorId) {
        if (inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                input.classList.remove('error');
                input.style.borderColor = '';
            }
        }
        const errEl = document.getElementById(errorId);
        if (errEl) {
            errEl.textContent = '';
            errEl.style.display = 'none';
        }
    }

    // Live error clearing on input
    if (idInput) {
        idInput.addEventListener('input', () => clearError('idNumber', 'idNumberError'));
    }
    if (otpInput) {
        otpInput.addEventListener('input', () => clearError('otpCode', 'otpError'));
    }
    [ans1Input, ans2Input, ans3Input].forEach(inp => {
        if (inp) {
            inp.addEventListener('input', () => {
                clearError(inp.id, 'ansError');
            });
        }
    });
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', () => clearError('newPassword', 'resetError'));
    }
    if (confirmNewPasswordInput) {
        confirmNewPasswordInput.addEventListener('input', () => clearError('confirmNewPassword', 'resetError'));
    }

    // Step 1: Request OTP
    form1.addEventListener('submit', async (e) => {
        e.preventDefault();
        const idNumber = idInput.value.trim();

        if (!idNumber) {
            showError('idNumber', 'idNumberError', 'Please enter your ID Number');
            return;
        }

        const btn = document.getElementById('btnStep1');
        btn.disabled = true;
        btn.textContent = 'SENDING...';

        try {
            const formData = new FormData();
            formData.append('idNumber', idNumber);
            const response = await fetch('../php/forgot-password-step1.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                currentIdNumber = idNumber;
                clearError('idNumber', 'idNumberError');
                step1.style.display = 'none';
                step2.style.display = 'block';
                if (otpInput) otpInput.focus();
            } else {
                showError('idNumber', 'idNumberError', data.message || 'ID Number not found.');
            }
        } catch (error) {
            showError('idNumber', 'idNumberError', 'System error occurred. Please try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'SEND OTP';
        }
    });

    // Step 2: Verify OTP
    form2.addEventListener('submit', async (e) => {
        e.preventDefault();
        const otpCode = otpInput.value.trim();

        if (!otpCode) {
            showError('otpCode', 'otpError', 'Please enter the 6-digit OTP code');
            return;
        }

        if (!/^\d{6}$/.test(otpCode)) {
            showError('otpCode', 'otpError', 'OTP must be exactly 6 digits');
            return;
        }

        const btn = document.getElementById('btnStep2');
        btn.disabled = true;
        btn.textContent = 'VERIFYING...';

        try {
            const formData = new FormData();
            formData.append('idNumber', currentIdNumber);
            formData.append('otp', otpCode);
            const response = await fetch('../php/forgot-password-step2.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                clearError('otpCode', 'otpError');
                // Populate questions
                document.getElementById('lblQ1').textContent = data.data.questions.q1;
                document.getElementById('lblQ2').textContent = data.data.questions.q2;
                document.getElementById('lblQ3').textContent = data.data.questions.q3;
                
                step2.style.display = 'none';
                step3.style.display = 'block';
                if (ans1Input) ans1Input.focus();
            } else {
                showError('otpCode', 'otpError', data.message || 'Invalid OTP code.');
            }
        } catch (error) {
            showError('otpCode', 'otpError', 'System error occurred. Please try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'VERIFY OTP';
        }
    });

    // Step 3: Verify Security Answers
    form3.addEventListener('submit', async (e) => {
        e.preventDefault();
        const ans1 = ans1Input.value.trim();
        const ans2 = ans2Input.value.trim();
        const ans3 = ans3Input.value.trim();

        if (!ans1 || !ans2 || !ans3) {
            showError(null, 'ansError', 'All 3 security answers are required');
            return;
        }

        const btn = document.getElementById('btnStep3');
        btn.disabled = true;
        btn.textContent = 'VERIFYING...';

        try {
            const formData = new FormData();
            formData.append('idNumber', currentIdNumber);
            formData.append('ans1', ans1);
            formData.append('ans2', ans2);
            formData.append('ans3', ans3);
            
            const response = await fetch('../php/forgot-password-step3.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                clearError(null, 'ansError');
                step3.style.display = 'none';
                step4.style.display = 'block';
                if (newPasswordInput) newPasswordInput.focus();
            } else {
                showError(null, 'ansError', data.message || 'Security answers are incorrect.');
            }
        } catch (error) {
            showError(null, 'ansError', 'System error occurred. Please try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'VERIFY ANSWERS';
        }
    });

    // Step 4: Reset Password
    form4.addEventListener('submit', async (e) => {
        e.preventDefault();
        const newPassword = newPasswordInput.value;
        const confirmNewPassword = confirmNewPasswordInput.value;

        if (!newPassword.trim()) {
            showError('newPassword', 'resetError', 'New password is required');
            return;
        }

        if (newPassword.length < 8) {
            showError('newPassword', 'resetError', 'Password must be at least 8 characters long');
            return;
        }
        if (!/[A-Z]/.test(newPassword)) {
            showError('newPassword', 'resetError', 'Password must contain at least one uppercase letter');
            return;
        }
        if (!/[a-z]/.test(newPassword)) {
            showError('newPassword', 'resetError', 'Password must contain at least one lowercase letter');
            return;
        }
        if (!/[0-9]/.test(newPassword)) {
            showError('newPassword', 'resetError', 'Password must contain at least one number');
            return;
        }
        if (!/[^a-zA-Z0-9]/.test(newPassword)) {
            showError('newPassword', 'resetError', 'Password must contain at least one special character');
            return;
        }
        
        if (newPassword !== confirmNewPassword) {
            showError('confirmNewPassword', 'resetError', 'Passwords do not match');
            return;
        }

        const btn = document.getElementById('btnStep4');
        btn.disabled = true;
        btn.textContent = 'UPDATING...';

        try {
            const formData = new FormData();
            formData.append('idNumber', currentIdNumber);
            formData.append('password', newPassword);
            
            const response = await fetch('../php/forgot-password-step4.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                alert('Password reset successful. You can now login.');
                window.location.href = 'login.php';
            } else {
                showError('newPassword', 'resetError', data.message || 'Failed to update password.');
            }
        } catch (error) {
            showError('newPassword', 'resetError', 'System error occurred. Please try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'UPDATE PASSWORD';
        }
    });
});
