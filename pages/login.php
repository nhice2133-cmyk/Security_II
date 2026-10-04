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
    <title>Login - CyberAuth System</title>
    <link rel="stylesheet" href="../css/cyberpunk-login.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="cyberpunk-bg">

<!-- Animated Background Elements -->
<div class="cyber-grid"></div>
<div class="neon-particles"></div>
<div class="scan-lines"></div>

<header class="cyber-header">
    <div class="header-content">
        <div class="logo-container">
            <div class="logo-icon">⚡</div>
            <h1 class="logo-text">CYBER<span class="accent">AUTH</span></h1>
        </div>
        <div class="header-actions">
            <a href="index.php" class="nav-link cyber-link">HOME</a>
            <a href="register.php" class="nav-link cyber-link">REGISTER</a>
        </div>
    </div>
</header>

<main class="cyber-main">
    <div class="cyber-container">
        <!-- Left Side - Compact Info Panel -->
        <div class="info-panel">
            <div class="cyber-badge">
                <span class="badge-text">SECURE</span>
                <span class="badge-accent">ENCRYPTED</span>
            </div>
            
            <h2 class="cyber-headline">NEURAL NETWORK ACCESS</h2>
            <p class="cyber-lead">Enter the digital realm. Your credentials are protected by quantum encryption.</p>
            
            <div class="cyber-features-compact">
                <div class="feature-compact">
                    <span class="feature-icon">🔐</span>
                    <span class="feature-text">Quantum Encryption</span>
                </div>
                <div class="feature-compact">
                    <span class="feature-icon">⚡</span>
                    <span class="feature-text">Lightning Fast</span>
                </div>
                <div class="feature-compact">
                    <span class="feature-icon">🛡️</span>
                    <span class="feature-text">Neural Firewall</span>
                </div>
            </div>
            
            <div class="cyber-stats-compact">
                <div class="stat-compact">
                    <span class="stat-number">99.9%</span>
                    <span class="stat-label">Uptime</span>
                </div>
                <div class="stat-compact">
                    <span class="stat-number">256-bit</span>
                    <span class="stat-label">Encryption</span>
                </div>
                <div class="stat-compact">
                    <span class="stat-number">0ms</span>
                    <span class="stat-label">Latency</span>
                </div>
            </div>
            
            <!-- Cyberpunk Decorations -->
            <div class="cyber-decorations">
                <div class="circuit-line"></div>
                <div class="data-stream"></div>
                <div class="hologram-effect"></div>
            </div>
        </div>

        <!-- Right Side - Compact Login Terminal -->
        <div class="terminal-container">
            <div class="terminal-frame">
                <div class="terminal-header">
                    <div class="terminal-dots">
                        <span class="dot red"></span>
                        <span class="dot yellow"></span>
                        <span class="dot green"></span>
                    </div>
                    <div class="terminal-title">CYBERAUTH TERMINAL v2.1.7</div>
                </div>
                
                <div class="terminal-screen">
                    <div class="terminal-content">
                        <div class="terminal-prompt">
                            <span class="prompt-text">root@cyberauth:~$</span>
                            <span class="cursor-blink">_</span>
                        </div>
                        
                        <div class="login-section">
                            <h3 class="terminal-title">ACCESS PROTOCOL INITIATED</h3>
                            <p class="terminal-subtitle">Enter your neural credentials</p>
                            
                            <form class="cyber-form" id="loginForm" action="../php/login.php" method="POST">
                                <div class="input-group">
                                    <label for="username" class="cyber-label">
                                        <span class="label-icon">👤</span>
                                        USERNAME
                                    </label>
                                    <div class="input-container">
                                        <input type="text" id="username" name="username" class="cyber-input" />
                                        <div class="input-glow"></div>
                                    </div>
                                    <div class="error-message" id="username-error"></div>
                                </div>
                                
                                <div class="input-group">
                                    <label for="password" class="cyber-label">
                                        <span class="label-icon">🔑</span>
                                        PASSWORD
                                    </label>
                                    <div class="input-container">
                                        <input type="password" id="password" name="password" class="cyber-input" />
                                        <button type="button" class="show-password cyber-btn-small" id="showPassword">
                                            <span class="eye-icon" style="color:var(--cyber-primary); filter:drop-shadow(0 0 5px rgba(14,165,233,0.5));"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></span>
                                        </button>
                                        <div class="input-glow"></div>
                                    </div>
                                    <div class="error-message" id="password-error"></div>
                                </div>
                                
                                <div class="login-attempts" id="loginAttempts">
                                    <div class="attempts-warning">
                                        <span class="warning-icon">⚠️</span>
                                        <span class="attempts-text">ACCESS DENIED: <span id="attemptCount">0</span>
                                    </div>
                                    <div class="timer-text" id="timerText" style="display: none;"></div>
                                    <div class="forgot-prompt" id="forgotPrompt">
                                        <span class="forgot-text">Forgot Password?</span>
                                        <a href="forgot-password.php" class="cyber-link" id="forgotPromptLink">Reset</a>
                                    </div>
                                </div>
                                
                                <button type="submit" class="cyber-btn" id="loginBtn">
                                    <span class="btn-text">INITIATE LOGIN</span>
                                    <div class="btn-glow"></div>
                                </button>
                                
                                <div class="form-links">
                                    <p class="switch-form">
                                        New to the network? 
                                        <a href="register.php" class="cyber-link" id="registerLink">REGISTER</a>
                                    </p>
                                    <a href="forgot-password.php" class="cyber-link">RECOVER CREDENTIALS</a>
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
            <span>&copy; 2025 CYBERAUTH SYSTEM</span>
            <span class="footer-accent">NEURAL NETWORK v2.1.7</span>
        </div>
        <div class="footer-status">
            <span class="status-indicator online"></span>
            <span class="status-text">SYSTEM ONLINE</span>
        </div>
    </div>
</footer>

<script src="../js/disable-rightclick.js"></script>
<script src="../js/login-validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
