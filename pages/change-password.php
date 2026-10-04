<?php
require_once '../php/config.php';

// Allow forgot-password flow without active session when mode=forgot
$mode = isset($_GET['mode']) ? $_GET['mode'] : '';
if (!isset($_SESSION['user_id']) && $mode !== 'forgot') {
    requireAuth404();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>" />
    <title>CYBER PASSWORD UPDATE - Neural Network Access</title>
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
        </div>
    </header>

    <!-- Main Content -->
    <main class="cyber-main">
        <div class="cyber-container">
            <!-- Form Panel -->
            <div class="form-panel">
                <div class="cyber-badge">
                    <span class="badge-text">SECURE</span>
                    <span class="badge-accent">ENCRYPTED</span>
                </div>
                <h1 class="cyber-headline">NEURAL PASSWORD UPDATE</h1>
                <p class="cyber-lead">Update your encryption keys to maintain security protocols</p>

                <!-- Terminal Frame -->
                <div class="terminal-frame">
                    <div class="terminal-header">
                        <div class="terminal-dots">
                            <div class="dot red"></div>
                            <div class="dot yellow"></div>
                            <div class="dot green"></div>
                        </div>
                        <div class="terminal-title">PASSWORD_UPDATE.exe</div>
                    </div>
                    
                    <div class="terminal-screen">
                        <div class="terminal-content">
                            <div class="terminal-prompt">
                                <span class="prompt-text">admin@neuralnet:~$</span>
                                <span class="cursor-blink">_</span>
                            </div>
                            
                            <div class="password-section">
                                <h2 class="terminal-title">ENCRYPTION KEY UPDATE</h2>
                                <p class="terminal-subtitle">Modify your neural network access credentials</p>
                                
                                <form class="cyber-form" id="changePasswordForm">
                                    <div class="form-grid">
                                        <!-- Current Password -->
                                        <div class="input-group">
                                            <label class="cyber-label">
                                                <span class="label-icon">🔑</span>
                                                CURRENT ENCRYPTION KEY*
                                            </label>
                                            <div class="input-container">
                                                <input type="password" class="cyber-input" id="currentPassword">
                                                <button type="button" class="show-password" onclick="togglePassword('currentPassword')">👁️</button>
                                                <div class="input-glow"></div>
                                            </div>
                                            <div class="error-message" id="currentPasswordError"></div>
                                        </div>

                                        <!-- New Password -->
                                        <div class="input-group">
                                            <label class="cyber-label">
                                                <span class="label-icon">🔐</span>
                                                NEW ENCRYPTION KEY*
                                            </label>
                                            <div class="input-container">
                                                <input type="password" class="cyber-input" id="newPassword">
                                                <button type="button" class="show-password" onclick="togglePassword('newPassword')">👁️</button>
                                                <div class="input-glow"></div>
                                            </div>
                                            <div class="error-message" id="newPasswordError"></div>
                                            <div class="password-strength" id="passwordStrength"></div>
                                        </div>

                                        <!-- Confirm New Password -->
                                        <div class="input-group">
                                            <label class="cyber-label">
                                                <span class="label-icon">🔐</span>
                                                CONFIRM NEW KEY*
                                            </label>
                                            <div class="input-container">
                                                <input type="password" class="cyber-input" id="confirmPassword">
                                                <button type="button" class="show-password" onclick="togglePassword('confirmPassword')">👁️</button>
                                                <div class="input-glow"></div>
                                            </div>
                                            <div class="error-message" id="confirmPasswordError"></div>
                                        </div>
                                    </div>

                                    <!-- Security Requirements -->
                                    <div class="security-requirements">
                                        <h3 class="requirements-title">ENCRYPTION REQUIREMENTS</h3>
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
                                        <button type="submit" class="cyber-btn" id="updateBtn">
                                            <div class="btn-glow"></div>
                                            <span class="btn-text">UPDATE ENCRYPTION KEY</span>
                                        </button>
                                        
                                        <div class="form-links">
                                            <p class="switch-form">
                                                <a href="../php/dashboard.php" class="cyber-link">← BACK TO DASHBOARD</a>
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
                
                <h2 class="cyber-headline">NEURAL SECURITY</h2>
                <p class="cyber-lead">Your encryption keys are protected by quantum security protocols.</p>
                
                <div class="cyber-features-compact">
                    <div class="feature-compact">
                        <span class="feature-icon">🔐</span>
                        <span class="feature-text">Quantum Encryption</span>
                    </div>
                    <div class="feature-compact">
                        <span class="feature-icon">🛡️</span>
                        <span class="feature-text">Multi-Factor Auth</span>
                    </div>
                    <div class="feature-compact">
                        <span class="feature-icon">⚡</span>
                        <span class="feature-text">Real-time Monitoring</span>
                    </div>
                </div>
                
                <!-- Security Tips -->
                <div class="security-tips">
                    <h3 class="tips-title">SECURITY TIPS</h3>
                    <div class="tip-item">
                        <span class="tip-icon">💡</span>
                        <span class="tip-text">Use unique passwords for each account</span>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">💡</span>
                        <span class="tip-text">Avoid personal information in passwords</span>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">💡</span>
                        <span class="tip-text">Change passwords regularly</span>
                    </div>
                </div>
                
                <!-- Cyberpunk Decorations -->
                <div class="cyber-decorations">
                    <div class="circuit-line"></div>
                    <div class="data-stream"></div>
                    <div class="hologram-effect"></div>
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
    <script src="../js/cyberpunk-change-password.js?v=20251013"></script>
    
    <!-- Back button protection disabled during debugging -->
</body>
</html>
