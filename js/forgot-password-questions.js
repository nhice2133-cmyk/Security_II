// Forgot Password Questions JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotPasswordQuestionsForm');
    const idNumber = sessionStorage.getItem('fp_idNumber');
    
    if (!idNumber) {
        showSystemError('No ID number found. Please start the forgot password process again.');
        setTimeout(() => {
            window.location.href = 'forgot-password.php';
        }, 2000);
        return;
    }

    // Load user's security questions
    loadSecurityQuestions(idNumber);

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitPasswordReset();
        }
    });

    // Real-time validation for answers
    document.getElementById('answer1').addEventListener('blur', function() {
        if (this.value.trim()) clearError('answer1');
    });
    document.getElementById('answer2').addEventListener('blur', function() {
        if (this.value.trim()) clearError('answer2');
    });
    document.getElementById('answer3').addEventListener('blur', function() {
        if (this.value.trim()) clearError('answer3');
    });

    function loadSecurityQuestions(idNumber) {
        fetch('../php/get_security_questions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idNumber: idNumber })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('question1').value = data.data.question1;
                document.getElementById('question2').value = data.data.question2;
                document.getElementById('question3').value = data.data.question3;
            } else {
                showSystemError('Error loading security questions: ' + data.message);
                setTimeout(() => {
                    window.location.href = 'forgot-password.php';
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showSystemError('Network error. Please try again.');
        });
    }

    function validateForm() {
        // Validate that at least 2 out of 3 answers are provided
        const answer1 = document.getElementById('answer1').value.trim();
        const answer2 = document.getElementById('answer2').value.trim();
        const answer3 = document.getElementById('answer3').value.trim();
        
        const answersProvided = [answer1, answer2, answer3].filter(a => a.length > 0).length;
        
        if (answersProvided < 2) {
            // Show error for empty fields
            if (!answer1) showError('answer1', 'Please answer at least 2 questions');
            if (!answer2) showError('answer2', 'Please answer at least 2 questions');
            if (!answer3) showError('answer3', 'Please answer at least 2 questions');
            return false;
        }
        
        // Clear errors if validation passes
        clearError('answer1');
        clearError('answer2');
        clearError('answer3');
        
        return true;
    }

    function submitPasswordReset() {
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.querySelector('.btn-text').textContent;
        
        // Show loading state
        submitButton.disabled = true;
        submitButton.querySelector('.btn-text').textContent = 'VERIFYING...';
        
        const answers = {
            answer1: document.getElementById('answer1').value.trim(),
            answer2: document.getElementById('answer2').value.trim(),
            answer3: document.getElementById('answer3').value.trim()
        };
        
        // Store answers in session storage for the next step
        sessionStorage.setItem('fp_answers', JSON.stringify(answers));
        
        // Verify answers first
        fetch('../php/verify_security_answers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                idNumber: idNumber,
                ...answers
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to password reset page
                window.location.href = 'reset-password.php';
            } else {
                // Show which answers are wrong
                let errorMessage = data.message || 'At least 2 security answers must be correct. Please try again.';
                
                // Highlight wrong answer fields
                if (data.wrongAnswers && Array.isArray(data.wrongAnswers)) {
                    data.wrongAnswers.forEach(questionNum => {
                        const answerField = document.getElementById(`answer${questionNum}`);
                        if (answerField) {
                            showError(`answer${questionNum}`, `Incorrect answer for Question ${questionNum}`);
                        }
                    });
                }
                
                showSystemError(errorMessage);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showSystemError('Network error. Please try again.');
        })
        .finally(() => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.querySelector('.btn-text').textContent = originalText;
        });
    }

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + '-error');
        
        field.classList.add('error');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    function clearError(fieldId) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + '-error');
        
        field.classList.remove('error');
        errorElement.style.display = 'none';
    }

    function showSystemError(message) {
        // Create system message overlay
        const overlay = document.createElement('div');
        overlay.className = 'system-message error';
        overlay.innerHTML = `
            <div class="message-content">
                <div class="message-icon">⚠</div>
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
            border: 2px solid var(--cyber-error, #ff0040);
            border-radius: 8px;
            padding: 1rem;
            z-index: 10000;
            color: var(--cyber-text, #ffffff);
            font-family: 'Orbitron', monospace;
            text-align: center;
            box-shadow: 0 0 30px rgba(255, 0, 64, 0.3);
        `;
        
        document.body.appendChild(overlay);
        
        // Remove after 3 seconds
        setTimeout(() => {
            overlay.remove();
        }, 3000);
    }

    // Password visibility toggle
    window.togglePassword = function(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.parentNode.querySelector('.show-password');
        
        if (field.type === 'password') {
            field.type = 'text';
            button.textContent = '🙈';
        } else {
            field.type = 'password';
            button.textContent = '👁️';
        }
    };
});
