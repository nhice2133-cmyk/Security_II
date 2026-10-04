document.addEventListener('DOMContentLoaded', () => {
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const step4 = document.getElementById('step4');

    const form1 = document.getElementById('forgotPasswordForm');
    const form2 = document.getElementById('otpForm');
    const form3 = document.getElementById('questionsForm');
    const form4 = document.getElementById('resetForm');

    let currentIdNumber = '';

    form1.addEventListener('submit', async (e) => {
        e.preventDefault();
        const idNumber = document.getElementById('idNumber').value;
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
                step1.style.display = 'none';
                step2.style.display = 'block';
            } else {
                document.getElementById('idNumberError').textContent = data.message;
                document.getElementById('idNumberError').style.display = 'block';
            }
        } catch (error) {
            document.getElementById('idNumberError').textContent = 'System error.';
            document.getElementById('idNumberError').style.display = 'block';
        }
        btn.disabled = false;
        btn.textContent = 'SEND OTP';
    });

    form2.addEventListener('submit', async (e) => {
        e.preventDefault();
        const otpCode = document.getElementById('otpCode').value;
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
                // Populate questions
                document.getElementById('lblQ1').textContent = data.data.questions.q1;
                document.getElementById('lblQ2').textContent = data.data.questions.q2;
                document.getElementById('lblQ3').textContent = data.data.questions.q3;
                
                step2.style.display = 'none';
                step3.style.display = 'block';
            } else {
                document.getElementById('otpError').textContent = data.message;
                document.getElementById('otpError').style.display = 'block';
            }
        } catch (error) {
            document.getElementById('otpError').textContent = 'System error.';
            document.getElementById('otpError').style.display = 'block';
        }
        btn.disabled = false;
        btn.textContent = 'VERIFY OTP';
    });

    form3.addEventListener('submit', async (e) => {
        e.preventDefault();
        const ans1 = document.getElementById('ans1').value;
        const ans2 = document.getElementById('ans2').value;
        const ans3 = document.getElementById('ans3').value;
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
                step3.style.display = 'none';
                step4.style.display = 'block';
            } else {
                document.getElementById('ansError').textContent = data.message;
                document.getElementById('ansError').style.display = 'block';
            }
        } catch (error) {
            document.getElementById('ansError').textContent = 'System error.';
            document.getElementById('ansError').style.display = 'block';
        }
        btn.disabled = false;
        btn.textContent = 'VERIFY ANSWERS';
    });

    form4.addEventListener('submit', async (e) => {
        e.preventDefault();
        const newPassword = document.getElementById('newPassword').value;
        const confirmNewPassword = document.getElementById('confirmNewPassword').value;
        
        if (newPassword !== confirmNewPassword) {
            document.getElementById('resetError').textContent = 'Passwords do not match.';
            document.getElementById('resetError').style.display = 'block';
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
                document.getElementById('resetError').textContent = data.message;
                document.getElementById('resetError').style.display = 'block';
            }
        } catch (error) {
            document.getElementById('resetError').textContent = 'System error.';
            document.getElementById('resetError').style.display = 'block';
        }
        btn.disabled = false;
        btn.textContent = 'UPDATE PASSWORD';
    });
});
