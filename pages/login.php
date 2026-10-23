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
    <title>Sign In — CyberAuth System</title>

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

    <!-- Modern Design System & Login Styles -->
    <link rel="stylesheet" href="../css/modern-design-system.css?v=<?= filemtime(__DIR__ . '/../css/modern-design-system.css') ?>">
    <link rel="stylesheet" href="../css/cyberpunk-login.css?v=<?= filemtime(__DIR__ . '/../css/cyberpunk-login.css') ?>">
</head>
<body>

<!-- Modern Subtle Ambient Canvas -->
<div class="cyber-grid" aria-hidden="true"></div>
<div class="neon-particles" aria-hidden="true"></div>

<header class="cyber-header">
    <div class="header-content">
        <a href="index.php" class="logo-container">
            <div class="logo-icon">⚡</div>
            <h1 class="logo-text">CYBER<span class="accent">AUTH</span></h1>
        </a>
        <div class="header-actions">
            <a href="index.php" class="nav-link cyber-link">HOME</a>
            <a href="register.php" class="nav-link cyber-link">REGISTER</a>

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
        <!-- Left Side - Modern Info Panel -->
        <div class="info-panel">
            <div>
                <div class="cyber-badge">
                    <span class="badge-text">SECURE LOGIN</span>
                    <span class="badge-accent">ENCRYPTED</span>
                </div>
                
                <h2 class="cyber-headline">Welcome back to CyberAuth</h2>
                <p class="cyber-lead">Access your protected workspace. Your credentials are guarded by state-of-the-art security algorithms and intelligent session monitoring.</p>
                
                <div class="cyber-features-compact">
                    <div class="feature-compact">
                        <span class="feature-icon">🔐</span>
                        <span class="feature-text">End-to-End Cryptographic Security</span>
                    </div>
                    <div class="feature-compact">
                        <span class="feature-icon">⚡</span>
                        <span class="feature-text">High Performance Low-Latency Sessions</span>
                    </div>
                    <div class="feature-compact">
                        <span class="feature-icon">🛡️</span>
                        <span class="feature-text">Continuous Session Integrity Shield</span>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="cyber-stats-compact">
                    <div class="stat-compact">
                        <span class="stat-number">99.99%</span>
                        <span class="stat-label">Uptime</span>
                    </div>
                    <div class="stat-compact">
                        <span class="stat-number">256-bit</span>
                        <span class="stat-label">Encryption</span>
                    </div>
                    <div class="stat-compact">
                        <span class="stat-number">&lt; 15ms</span>
                        <span class="stat-label">Latency</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Modern Login Card -->
        <div class="terminal-container">
            <div class="terminal-frame">
                <div class="terminal-screen">
                    <div class="terminal-content">
                        <div class="login-section">
                            <h3 class="terminal-title">Account Sign In</h3>
                            <p class="terminal-subtitle">Enter your account username and password.</p>
                            
                            <?php if (isset($_GET['notice']) && $_GET['notice'] === 'rights_transferred'): ?>
                                <div style="margin-bottom: 1.25rem; padding: 0.85rem 1.1rem; border: 1px solid var(--danger-border); background: var(--danger-light); color: var(--danger); border-radius: 12px; font-size: 0.88rem; display: flex; align-items: center; gap: 0.75rem; box-shadow: var(--shadow-xs);">
                                    <span style="font-size: 1.25rem;">🔒</span>
                                    <div>
                                        <strong style="color: var(--text-primary); display: block; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.84rem;">RIGHTS TRANSFERRED</strong>
                                        <span>Super Admin rights were transferred. You have been safely logged out. Please sign in with your user credentials.</span>
                                    </div>
                                </div>
                            <?php elseif (isset($_GET['notice']) && $_GET['notice'] === 'handover_deactivated'): ?>
                                <div style="margin-bottom: 1.25rem; padding: 0.85rem 1.1rem; border: 1px solid var(--warning-border, #f59e0b); background: var(--warning-light, rgba(245,158,11,0.1)); color: var(--warning, #d97706); border-radius: 12px; font-size: 0.88rem; display: flex; align-items: center; gap: 0.75rem; box-shadow: var(--shadow-xs);">
                                    <span style="font-size: 1.25rem;">⏸️</span>
                                    <div>
                                        <strong style="color: var(--text-primary); display: block; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.84rem;">HANDOVER COMPLETED &mdash; ACCOUNT INACTIVE</strong>
                                        <span>Your account was deactivated upon logout as part of the authorized Superadmin handover. Please contact the current Superadmin to request reactivation.</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div id="loginAlertBox" style="display: none; margin-bottom: 1.25rem;"></div>

                            <form class="cyber-form" id="loginForm" action="../php/login.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>" />
                                <div class="input-group">
                                    <label for="username" class="cyber-label">
                                        <span class="label-icon">👤</span>
                                        USERNAME
                                    </label>
                                    <div class="input-container">
                                        <input type="text" id="username" name="username" class="cyber-input" placeholder="Enter your username or email" autofocus />
                                    </div>
                                    <div class="error-message" id="username-error"></div>
                                </div>
                                
                                <div class="input-group">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                                        <label for="password" class="cyber-label" style="margin-bottom: 0;">
                                            <span class="label-icon">🔑</span>
                                            PASSWORD
                                        </label>
                                        <a href="forgot-password.php" class="cyber-link" style="font-size: 0.78rem; padding: 0;">Forgot?</a>
                                    </div>
                                    <div class="input-container">
                                        <input type="password" id="password" name="password" class="cyber-input" placeholder="Enter your password" />
                                        <button type="button" class="show-password" id="showPassword" aria-label="Toggle password visibility"></button>
                                    </div>
                                    <div class="error-message" id="password-error"></div>
                                </div>
                                
                                <div class="login-attempts" id="loginAttempts">
                                    <div class="attempts-warning">
                                        <span class="warning-icon">⚠️</span>
                                        <span class="attempts-text">ACCESS DENIED: <span id="attemptCount">0</span></span>
                                    </div>
                                    <div class="timer-text" id="timerText" style="display: none;"></div>
                                    <div class="forgot-prompt" id="forgotPrompt">
                                        <span class="forgot-text">Forgot Password?</span>
                                        <a href="forgot-password.php" class="cyber-link" id="forgotPromptLink">Reset Now</a>
                                    </div>
                                </div>
                                
                                <button type="submit" class="cyber-btn" id="loginBtn">
                                    <span class="btn-text">Sign In</span>
                                </button>
                                
                                <div class="form-links">
                                    <p class="switch-form">
                                        New to the platform? 
                                        <a href="register.php" class="cyber-link" id="registerLink">Create an account</a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="cyber-footer">
    <div class="footer-content">
        <div class="footer-text">
            <span>&copy; 2026 CyberAuth Security System</span>
            <span class="footer-accent">Identity Access Protocol v3.0</span>
        </div>
        <div class="footer-status">
            <span class="status-indicator online"></span>
            <span class="status-text">All Systems Operational</span>
        </div>
    </div>
</footer>

<script src="../js/disable-rightclick.js"></script>
<script src="../js/login-validation.js?v=<?php echo time(); ?>"></script>
<script src="../js/theme-toggle.js"></script>
</body>
</html>
