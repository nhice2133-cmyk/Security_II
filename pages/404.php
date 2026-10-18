<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | CyberAuth System</title>

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
    <link rel="stylesheet" href="../css/cyberpunk-login.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
            position: relative;
        }

        .error-theme-toggle {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 100;
        }

        .error-container {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 640px;
            padding: 3.5rem 2.5rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            animation: fadeIn 0.4s ease-out;
        }

        .error-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 1rem;
            background: var(--error-light);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: var(--error);
            font-family: var(--font-mono);
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: var(--radius-full);
            letter-spacing: 0.05em;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
        }

        .error-code {
            font-family: var(--font-display);
            font-size: clamp(5rem, 15vw, 8rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 50%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
        }

        .error-title {
            font-family: var(--font-display);
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .error-message {
            font-size: 1.05rem;
            color: var(--text-muted);
            margin-bottom: 2.25rem;
            line-height: 1.65;
            font-weight: 400;
        }

        .error-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .terminal-prompt {
            font-family: var(--font-mono);
            color: var(--text-dim);
            font-size: 0.85rem;
            margin-top: 2.5rem;
            padding: 0.75rem 1rem;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-subtle);
            display: inline-block;
        }

        .terminal-prompt::before {
            content: 'auth@cyber-core:~$ ';
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Animated Modern Background -->
    <div class="cyber-grid" aria-hidden="true"></div>
    <div class="neon-particles" aria-hidden="true"></div>

    <!-- Theme Switcher -->
    <div class="error-theme-toggle">
        <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle theme" title="Toggle theme">
            <span class="theme-toggle-icon theme-toggle-sun">☀️</span>
            <span class="theme-toggle-icon theme-toggle-moon">🌙</span>
            <span class="theme-toggle-text">Dark</span>
        </button>
    </div>

    <div class="error-container">
        <div class="error-badge-pill">
            <span>🛡️</span> ACCESS RESTRICTED / NOT FOUND
        </div>
        <div class="error-code">404</div>
        <h1 class="error-title">Destination Not Found</h1>
        <p class="error-message">
            The resource you requested could not be located or requires elevated authentication privileges to access.
        </p>
        <div class="error-actions">
            <a href="login.php" class="cyber-btn primary">
                <span class="btn-text">Return to Login &rarr;</span>
            </a>
            <a href="index.php" class="cyber-btn secondary">
                <span class="btn-text">Back to Home</span>
            </a>
        </div>
        <div class="terminal-prompt">STATUS_CODE: 404_PAGE_NOT_FOUND // AUTH_DISPATCH_FALLBACK</div>
    </div>

    <!-- Scripts -->
    <script src="../js/theme-toggle.js"></script>
</body>
</html>
