<?php
require_once '../php/config.php';

// Check if user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Verification — CyberAuth System</title>

    <!-- Pre-render Theme Initialization (Prevents FOUC, defaults to Light) -->
    <script>
      (function() {
        var theme = localStorage.getItem('auth_theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
      })();
    </script>

    <!-- Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="../css/modern-design-system.css">
    <link rel="stylesheet" href="../css/cyberpunk-questions.css">
</head>
<body>
    <div class="cyber-grid" aria-hidden="true"></div>
    <div class="neon-particles" aria-hidden="true"></div>

    <header class="cyber-header">
        <div class="header-content">
            <a href="index.php" class="logo-container">
                <div class="logo-icon">⚡</div>
                <div class="logo-text">CYBER<span class="accent">AUTH</span></div>
            </a>
            <div class="header-actions">
                <a href="login.php" class="nav-link cyber-link">LOGIN</a>
                <a href="index.php" class="nav-link cyber-link">HOME</a>

                <!-- Theme Toggle Switcher -->
                <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle theme" title="Toggle theme">
                    <span class="theme-toggle-icon theme-toggle-sun">☀️</span>
                    <span class="theme-toggle-icon theme-toggle-moon">🌙</span>
                    <span class="theme-toggle-text">Dark</span>
                </button>
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
                <h1 class="cyber-headline">SECURITY VERIFICATION</h1>
                <p class="cyber-lead">Answer your security questions to reset your password</p>

                <div class="terminal-frame">
                    <div class="terminal-header">
                        <div class="terminal-dots">
                            <div class="dot red"></div>
                            <div class="dot yellow"></div>
                            <div class="dot green"></div>
                        </div>
                        <div class="terminal-title">SECURITY_VERIFICATION.exe</div>
                    </div>
                    <div class="terminal-screen">
                        <div class="terminal-content">
                            <div class="terminal-prompt">
                                <span class="prompt-text">user@neuralnet:~$</span>
                                <span class="cursor-blink">_</span>
                            </div>

                            <form class="cyber-form" id="forgotPasswordQuestionsForm" autocomplete="off">
                                <div class="form-grid">
                                    <div class="input-group full">
                                        <label class="cyber-label">SECURITY QUESTION 1*</label>
                                        <div class="input-container">
                                            <input type="text" class="cyber-input" id="question1" readonly>
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="input-container">
                                            <input type="text" class="cyber-input" id="answer1" placeholder="Your answer">
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="error-message" id="answer1-error"></div>
                                    </div>

                                    <div class="input-group full">
                                        <label class="cyber-label">SECURITY QUESTION 2*</label>
                                        <div class="input-container">
                                            <input type="text" class="cyber-input" id="question2" readonly>
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="input-container">
                                            <input type="text" class="cyber-input" id="answer2" placeholder="Your answer">
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="error-message" id="answer2-error"></div>
                                    </div>

                                    <div class="input-group full">
                                        <label class="cyber-label">SECURITY QUESTION 3*</label>
                                        <div class="input-container">
                                            <input type="text" class="cyber-input" id="question3" readonly>
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="input-container">
                                            <input type="text" class="cyber-input" id="answer3" placeholder="Your answer">
                                            <div class="input-glow"></div>
                                        </div>
                                        <div class="error-message" id="answer3-error"></div>
                                    </div>

                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="cyber-btn">
                                        <div class="btn-glow"></div>
                                        <span class="btn-text">VERIFY ANSWERS</span>
                                    </button>
                                    <div class="form-links">
                                        <p class="switch-form"><a href="forgot-password.php" class="cyber-link">← BACK TO FORGOT PASSWORD</a></p>
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
    <script src="../js/forgot-password-questions.js"></script>
    <script src="../js/theme-toggle.js"></script>
</body>
</html>
