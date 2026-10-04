<?php
require_once '../php/config.php';

// If already logged in, go back to dashboard; allow unauthenticated for registration/forgot flows
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBER SECURITY QUESTIONS - Neural Network Access</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/cyberpunk-questions.css">
</head>
<body>
    <div class="cyber-grid"></div>
    <div class="neon-particles"></div>
    <div class="scan-lines"></div>

    <header class="cyber-header">
        <div class="header-content">
            <div class="logo-container">
                <div class="logo-icon">⚡</div>
                <div class="logo-text">CYBER<span class="accent">AUTH</span></div>
            </div>
        </div>
    </header>

    <main class="cyber-main">
        <div class="cyber-container">
            <div class="form-panel">
                <div class="cyber-badge">
                    <span class="badge-text">SECURE</span>
                    <span class="badge-accent">VERIFICATION</span>
                </div>
                <h1 class="cyber-headline">SECURITY QUESTIONS</h1>
                <p class="cyber-lead">Set up recovery questions for identity verification</p>

                <div class="terminal-frame">
                    <div class="terminal-header">
                        <div class="terminal-dots">
                            <div class="dot red"></div>
                            <div class="dot yellow"></div>
                            <div class="dot green"></div>
                        </div>
                        <div class="terminal-title">SECURITY_SETUP.exe</div>
                    </div>
                    <div class="terminal-screen">
                        <div class="terminal-content">
                            <div class="terminal-prompt">
                                <span class="prompt-text">user@neuralnet:~$</span>
                                <span class="cursor-blink">_</span>
                            </div>

                            <form class="cyber-form" id="questionsForm" autocomplete="off">
                                <div class="form-grid">
                                    <div class="input-group question-block">
                                        <label class="cyber-label">QUESTION 1*</label>
                                        <div class="input-container">
                                            <select class="cyber-input" id="auth_question1">
                                                <option value="">-- Select Question --</option>
                                                <option value="best_friend_elementary">Who is your best friend in Elementary?</option>
                                                <option value="favorite_pet">What is the name of your favorite pet?</option>
                                                <option value="favorite_teacher">Who is your favorite teacher in high school?</option>
                                                <option value="mother_maiden_name">What is your mother's maiden name?</option>
                                                <option value="birth_city">In what city were you born?</option>
                                                <option value="first_car">What was the make and model of your first car?</option>
                                            </select>
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="input-container">
                                            <input type="text" class="cyber-input" id="auth_answer1" placeholder="Your answer">
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="error-message" id="auth_answer1-error"></div>
                                    </div>

                                    <div class="input-group question-block">
                                        <label class="cyber-label">QUESTION 2*</label>
                                        <div class="input-container">
                                            <select class="cyber-input" id="auth_question2">
                                                <option value="">-- Select Question --</option>
                                                <option value="best_friend_elementary">Who is your best friend in Elementary?</option>
                                                <option value="favorite_pet">What is the name of your favorite pet?</option>
                                                <option value="favorite_teacher">Who is your favorite teacher in high school?</option>
                                                <option value="mother_maiden_name">What is your mother's maiden name?</option>
                                                <option value="birth_city">In what city were you born?</option>
                                                <option value="first_car">What was the make and model of your first car?</option>
                                            </select>
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="input-container">
                                            <input type="text" class="cyber-input" id="auth_answer2" placeholder="Your answer">
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="error-message" id="auth_answer2-error"></div>
                                    </div>

                                    <div class="input-group question-block">
                                        <label class="cyber-label">QUESTION 3*</label>
                                        <div class="input-container">
                                            <select class="cyber-input" id="auth_question3">
                                                <option value="">-- Select Question --</option>
                                                <option value="best_friend_elementary">Who is your best friend in Elementary?</option>
                                                <option value="favorite_pet">What is the name of your favorite pet?</option>
                                                <option value="favorite_teacher">Who is your favorite teacher in high school?</option>
                                                <option value="mother_maiden_name">What is your mother's maiden name?</option>
                                                <option value="birth_city">In what city were you born?</option>
                                                <option value="first_car">What was the make and model of your first car?</option>
                                            </select>
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="input-container">
                                            <input type="text" class="cyber-input" id="auth_answer3" placeholder="Your answer">
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="error-message" id="auth_answer3-error"></div>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="cyber-btn">
                                        <div class="btn-glow"></div>
                                        <span class="btn-text">FINALIZE SECURITY</span>
                                    </button>
                                    <div class="form-links">
                                        <p class="switch-form"><a href="register.php" class="cyber-link">← BACK TO REGISTRATION</a></p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="cyber-footer">
        <div class="footer-content">
            <div class="footer-text">
                <span>© 2025 Neural Network Systems</span>
                <span class="footer-accent">All rights reserved</span>
            </div>
            <div class="footer-status">
                <div class="status-indicator online"></div>
                <span class="status-text">SYSTEM ONLINE</span>
            </div>
        </div>
    </footer>

    <script src="../js/disable-rightclick.js"></script>
    <script src="../js/cyberpunk-questions.js"></script>
</body>
</html>
