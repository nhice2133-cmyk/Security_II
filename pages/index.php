<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Home - CyberAuth System</title>
    <!-- Reuse the same stylesheet used by other pages to keep design consistent -->
    <link rel="stylesheet" href="../css/cyberpunk-login.css" />
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
</head>
<body class="cyberpunk-bg">

<!-- Animated Background Elements (same as other pages) -->
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
            <a href="login.php" class="nav-link cyber-link">LOGIN</a>
            <a href="register.php" class="nav-link cyber-link">REGISTER</a>
        </div>
    </div>
</header>

<main class="cyber-main">
    <div class="cyber-container">
        <!-- Left: Info/Marketing Panel (reusing same component styling) -->
        <div class="info-panel">
            <div>
                <div class="cyber-badge">
                    <span class="badge-text">SECURE</span>
                    <span class="badge-accent">ENCRYPTED</span>
                </div>
                <h2 class="cyber-headline">WELCOME TO CYBERAUTH</h2>
                <p class="cyber-lead">Authenticate with confidence. Our system uses hashed passwords, prepared statements, and strict session controls for a hardened access flow.</p>

                <div class="cyber-features-compact">
                    <div class="feature-compact">
                        <span class="feature-icon">🔐</span>
                        <span class="feature-text">Password Hashing</span>
                    </div>
                    <div class="feature-compact">
                        <span class="feature-icon">🧠</span>
                        <span class="feature-text">Session Hardening</span>
                    </div>
                    <div class="feature-compact">
                        <span class="feature-icon">🛡️</span>
                        <span class="feature-text">Prepared Statements</span>
                    </div>
                </div>
            </div>

            <div>
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
                        <span class="stat-label">Latency*</span>
                    </div>
                </div>
                <p class="cyber-lead" style="margin-top: .4rem;">*Perceived latency optimized via lightweight frontend and JSON endpoints.</p>
            </div>

            <!-- Cyberpunk Decorations (same visuals) -->
            <div class="cyber-decorations" aria-hidden="true">
                <div class="circuit-line"></div>
                <div class="data-stream"></div>
                <div class="hologram-effect"></div>
            </div>
        </div>

        <!-- Right: Terminal-styled CTA panel (reusing terminal components) -->
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

                        <section class="login-section" aria-label="Quick actions">
                            <h3 class="terminal-title">ACCESS GATEWAY</h3>
                            <p class="terminal-subtitle">Choose your route</p>

                            <div style="display: grid; gap: 0.6rem; margin-top: 0.2rem;">
                                <a class="cyber-btn" href="login.php">
                                    <span class="btn-text">LOGIN</span>
                                    <div class="btn-glow"></div>
                                </a>
                                <a class="cyber-btn" href="register.php">
                                    <span class="btn-text">CREATE ACCOUNT</span>
                                    <div class="btn-glow"></div>
                                </a>
                                <a class="cyber-link" href="forgot-password.php" style="text-align:center;">RECOVER CREDENTIALS</a>
                            </div>

                            <div style="margin-top: 1rem;">
                                <h3 class="terminal-title">SYSTEM FEATURES</h3>
                                <ul style="margin-left: 1rem; margin-top: 0.4rem; color: var(--cyber-text-dim); font-size: 0.7rem; line-height: 1.3;">
                                    <li>Registration with client-side validation</li>
                                    <li>Login with throttling for brute-force protection</li>
                                    <li>Password change and recovery flows</li>
                                    <li>AJAX JSON endpoints via PHP (PDO)</li>
                                </ul>
                            </div>
                        </section>
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
</body>
</html>
