<?php
require_once '../php/config.php';

// Check if user is already logged in, redirect to dashboard
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
    <title>CYBER PASSWORD RESET - Neural Network Access</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/cyberpunk-change-password.css">
</head>
<body>
    <!-- Animated Background -->
    <div class="cyber-grid"></div>
    <div class="neon-particles"></div>
    <div class="scan-lines"></div>

    <!-- Header -->
    <header class="cyber-header">
        <div class="header-content">
            <div class="logo-container">
                <div class="logo-icon">⚡</div>
                <div class="logo-text">NEURAL<span class="accent">NET</span></div>
            </div>
            <div class="header-actions">
                <a href="login.php" class="cyber-link">LOGIN</a>
                <a href="index.php" class="cyber-link">HOME</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="cyber-main">
        <div class="cyber-container">
            <!-- Form Panel -->
            <div class="form-panel">
                <div class="cyber-badge">
                    <span class="badge-text">SECURE</span>
                    <span class="badge-accent">RESET</span>
                </div>
                <h1 class="cyber-headline">PASSWORD RESET</h1>
                <p class="cyber-lead">Create a new secure password for your account</p>

                <!-- Terminal Frame -->
                <div class="terminal-frame">
                    <div class="terminal-header">
                        <div class="terminal-dots">
                            <div class="dot red"></div>
                            <div class="dot yellow"></div>
                            <div class="dot green"></div>
                        </div>
                        <div class="terminal-title">PASSWORD_RESET.exe</div>
                    </div>
                    
                    <div class="terminal-screen">
                        <div class="terminal-content">
                            <div class="terminal-prompt">
                                <span class="prompt-text">user@neuralnet:~$</span>
                                <span class="cursor-blink">_</span>
                            </div>
                            
                            <div class="password-section">
                                <h2 class="terminal-title">NEW PASSWORD CREATION</h2>
                                <p class="terminal-subtitle">Set your new neural network access credentials</p>
                                
                                <form class="cyber-form" id="resetPasswordForm">
                                    <div class="form-grid">
                                        <!-- New Password -->
                                        <div class="input-group">
                                            <label class="cyber-label">
                                                <span class="label-icon">🔐</span>
                                                NEW PASSWORD*
                                            </label>
                                            <div class="input-container">
                                                <input type="password" class="cyber-input" id="newPassword">
                                                <button type="button" class="show-password" onclick="togglePassword('newPassword')" aria-label="Show password"></button>
                                                <div class="input-glow"></div>
                                            </div>
                                            <div class="error-message" id="newPasswordError"></div>
                                            <div class="password-strength" id="passwordStrength"></div>
                                        </div>

                                        <!-- Confirm New Password -->
                                        <div class="input-group">
                                            <label class="cyber-label">
                                                <span class="label-icon">🔐</span>
                                                CONFIRM NEW PASSWORD*
                                            </label>
                                            <div class="input-container">
                                                <input type="password" class="cyber-input" id="confirmPassword">
                                                <button type="button" class="show-password" onclick="togglePassword('confirmPassword')" aria-label="Show password"></button>
                                                <div class="input-glow"></div>
                                            </div>
                                            <div class="error-message" id="confirmPasswordError"></div>
                                        </div>
                                    </div>

                                    <!-- Security Requirements -->
                                    <div class="security-requirements">
                                        <h3 class="requirements-title">PASSWORD REQUIREMENTS</h3>
                                        <div class="requirements-list">
                                            <div class="requirement-item" id="req-length">
                                                <span class="req-icon">❌</span>
                                                <span class="req-text">Minimum 8 characters</span>
                                            </div>
                                            <div class="requirement-item" id="req-uppercase">
                                                <span class="req-icon">❌</span>
                                                <span class="req-text">At least 1 uppercase letter</span>
                                            </div>
                                            <div class="requirement-item" id="req-lowercase">
                                                <span class="req-icon">❌</span>
                                                <span class="req-text">At least 1 lowercase letter</span>
                                            </div>
                                            <div class="requirement-item" id="req-number">
                                                <span class="req-icon">❌</span>
                                                <span class="req-text">At least 1 number</span>
                                            </div>
                                            <div class="requirement-item" id="req-special">
                                                <span class="req-icon">❌</span>
                                                <span class="req-text">At least 1 special character</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="form-actions">
                                        <button type="submit" class="cyber-btn" id="resetBtn">
                                            <div class="btn-glow"></div>
                                            <span class="btn-text">RESET PASSWORD</span>
                                        </button>
                                        
                                        <div class="form-links">
                                            <p class="switch-form">
                                                <a href="forgot-password.php" class="cyber-link">← BACK TO FORGOT PASSWORD</a>
                                            </p>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Panel -->
            <div class="info-panel">
                <div class="cyber-badge">
                    <span class="badge-text">SECURITY</span>
                    <span class="badge-accent">PROTOCOL</span>
                </div>
                
                <h2 class="cyber-headline">PASSWORD SECURITY</h2>
                <p class="cyber-lead">Your new password will be encrypted using quantum security protocols.</p>
                
                <div class="cyber-features-compact">
                    <div class="feature-compact">
                        <span class="feature-icon">🔐</span>
                        <span class="feature-text">Quantum Encryption</span>
                    </div>
                    <div class="feature-compact">
                        <span class="feature-icon">🛡️</span>
                        <span class="feature-text">Secure Hashing</span>
                    </div>
                    <div class="feature-compact">
                        <span class="feature-icon">⚡</span>
                        <span class="feature-text">Instant Update</span>
                    </div>
                </div>
                
                <!-- Security Tips -->
                <div class="security-tips">
                    <h3 class="tips-title">SECURITY TIPS</h3>
                    <div class="tip-item">
                        <span class="tip-icon">💡</span>
                        <span class="tip-text">Use a unique password</span>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">💡</span>
                        <span class="tip-text">Avoid personal information</span>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">💡</span>
                        <span class="tip-text">Use a mix of characters</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
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
    <script src="../js/reset-password.js"></script>
</body>
</html>
