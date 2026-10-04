<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | CyberAuth System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/cyberpunk-login.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        .error-container {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 800px;
            padding: 3rem 2rem;
        }

        .error-code {
            font-family: 'Orbitron', monospace;
            font-size: 12rem;
            font-weight: 900;
            color: var(--cyber-primary);
            text-shadow: 
                0 0 5px var(--cyber-primary),
                0 0 10px var(--cyber-primary);
            line-height: 1;
            margin-bottom: 1rem;
        }

        .error-title {
            font-family: 'Orbitron', monospace;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--cyber-secondary);
            text-shadow: 0 0 5px var(--cyber-secondary);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 4px;
        }

        .error-message {
            font-size: 1.3rem;
            color: var(--cyber-text-dim);
            margin-bottom: 3rem;
            line-height: 1.8;
            font-weight: 400;
        }

        .error-message strong {
            color: var(--cyber-error);
        }

        .cyber-btn {
            display: inline-block;
            padding: 18px 45px;
            background: transparent;
            border: 2px solid var(--cyber-primary);
            color: var(--cyber-primary);
            text-decoration: none;
            font-family: 'Orbitron', monospace;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.2);
        }

        .cyber-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, var(--cyber-primary), transparent);
            transition: left 0.5s;
            z-index: -1;
        }

        .cyber-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: var(--cyber-primary);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
            z-index: -1;
            opacity: 0.3;
        }

        .cyber-btn:hover {
            color: var(--cyber-text);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.4);
            transform: translateY(-2px);
            border-color: var(--cyber-primary);
        }

        .cyber-btn:hover::before {
            left: 100%;
        }

        .cyber-btn:hover::after {
            width: 300px;
            height: 300px;
        }

        .cyber-btn:active {
            transform: translateY(-1px);
        }

        .terminal-prompt {
            font-family: 'Orbitron', monospace;
            color: var(--cyber-secondary);
            font-size: 0.95rem;
            margin-top: 3rem;
            opacity: 0.8;
            letter-spacing: 1px;
        }

        .terminal-prompt::before {
            content: 'user@neuralnet:~$ ';
            color: var(--cyber-primary);
        }

        .error-badge {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(255, 0, 64, 0.1);
            border: 1px solid var(--cyber-error);
            color: var(--cyber-error);
            font-family: 'Orbitron', monospace;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 2rem;
            box-shadow: 0 0 8px rgba(255, 0, 64, 0.2);
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 8rem;
            }
            .error-title {
                font-size: 1.8rem;
                letter-spacing: 2px;
            }
            .error-message {
                font-size: 1.1rem;
            }
            .cyber-btn {
                padding: 15px 35px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 6rem;
            }
            .error-title {
                font-size: 1.5rem;
            }
            .error-message {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background (from cyberpunk-login.css) -->
    <div class="cyber-grid"></div>
    <div class="neon-particles"></div>
    <div class="scan-lines"></div>

    <div class="error-container">
        <div class="error-badge">ACCESS DENIED</div>
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">
            The page you are looking for does not exist or you do not have permission to access it.<br>
            <strong>Authentication required.</strong> Please log in to continue.
        </p>
        <a href="login.php" class="cyber-btn">Return to Login</a>
        <div class="terminal-prompt">ERROR: Access denied. Session invalid or expired.</div>
    </div>
</body>
</html>

