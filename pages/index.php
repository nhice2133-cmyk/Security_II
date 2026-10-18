<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberAuth — Modern Secure Authentication Platform</title>

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />

    <!-- Modern Design System & Stylesheets -->
    <link rel="stylesheet" href="../css/modern-design-system.css" />
    <link rel="stylesheet" href="../css/cyberpunk-login.css" />
</head>
<body>

<!-- Modern Subtle Ambient Canvas -->
<div class="cyber-grid" aria-hidden="true"></div>
<div class="neon-particles" aria-hidden="true"></div>

<!-- Top Navigation Header -->
<header class="cyber-header">
    <div class="header-content">
        <a href="index.php" class="logo-container">
            <div class="logo-icon">⚡</div>
            <h1 class="logo-text">CYBER<span class="accent">AUTH</span></h1>
        </a>
        <div class="header-actions">
            <a href="index.php" class="nav-link cyber-link">HOME</a>
            <a href="login.php" class="nav-link cyber-link">LOGIN</a>
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
        <!-- Left: Hero Overview & Security Features -->
        <div class="info-panel">
            <div>
                <div class="cyber-badge">
                    <span class="badge-text">ENTERPRISE SECURITY</span>
                    <span class="badge-accent">ZERO-TRUST</span>
                </div>
                <h2 class="cyber-headline">Secure Identity &amp; Access Infrastructure</h2>
                <p class="cyber-lead">Authenticate with complete confidence. Engineered with salted password hashing, PDO prepared statements, and intelligent session hardening.</p>

                <div class="cyber-features-compact">
                    <div class="feature-compact">
                        <span class="feature-icon">🔐</span>
                        <span class="feature-text">Cryptographic Password Hashing</span>
                    </div>
                    <div class="feature-compact">
                        <span class="feature-icon">🛡️</span>
                        <span class="feature-text">Brute-Force &amp; Throttling Defense</span>
                    </div>
                    <div class="feature-compact">
                        <span class="feature-icon">⚡</span>
                        <span class="feature-text">Strict Prepared Statement Isolation</span>
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

        <!-- Right: Access Gateway Card -->
        <div class="terminal-container">
            <div class="terminal-frame">
                <section class="login-section" aria-label="Quick actions">
                    <h3 class="terminal-title">Access Gateway</h3>
                    <p class="terminal-subtitle">Select an action to access your account workspace.</p>

                    <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 0.5rem;">
                        <a class="cyber-btn" href="login.php">
                            <span class="btn-text">Sign In to Account</span>
                        </a>
                        <a class="cyber-btn-secondary" href="register.php">
                            <span>Create New Account</span>
                        </a>
                        <a class="cyber-link" href="forgot-password.php" style="text-align: center; margin-top: 0.5rem; justify-content: center;">Forgot or Need to Recover Password?</a>
                    </div>

                    <div style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color);">
                        <h4 style="font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.75rem;">Platform Highlights</h4>
                        <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem; color: var(--text-muted); font-size: 0.85rem;">
                            <li style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="color: var(--success); font-weight: bold;">✓</span> Multi-step registration with real-time validation
                            </li>
                            <li style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="color: var(--success); font-weight: bold;">✓</span> Super admin, admin, and role privilege governance
                            </li>
                            <li style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="color: var(--success); font-weight: bold;">✓</span> Real-time lockout protection and security questions
                            </li>
                            <li style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="color: var(--success); font-weight: bold;">✓</span> Audit activity logging and deletion request workflows
                            </li>
                        </ul>
                    </div>
                </section>
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
<script src="../js/theme-toggle.js"></script>
</body>
</html>
