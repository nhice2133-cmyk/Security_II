<?php
require_once '../php/config.php';

// Check if user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Redirect if step 1 is not completed
if (!isset($_SESSION['reg_step1'])) {
    header('Location: register.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBER REGISTRATION - Neural Network Access</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/cyberpunk-register.css">
</head>
<body class="scroll-active">
    <!-- Animated Background -->
    <div class="cyber-grid"></div>
    <div class="neon-particles"></div>
    <div class="scan-lines"></div>

    <!-- Header -->
    <header class="cyber-header">
        <div class="header-content">
            <div class="logo-container">
                <div class="logo-icon">⚡</div>
                <div class="logo-text">CYBER<span class="accent">AUTH</span></div>
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
            <div class="form-panel" style="height: auto; max-width: 600px; margin: 0 auto; padding: 2rem;">
                <div class="cyber-badge" style="text-align: center; margin: 0 auto 1rem; display: table;">
                    <span class="badge-text">SECURE</span>
                    <span class="badge-accent">ENCRYPTED</span>
                </div>
                <h1 class="cyber-headline" style="text-align: center; margin-bottom: 0.5rem;">SECURITY QUESTIONS</h1>
                <p class="cyber-lead" style="text-align: center; margin-bottom: 2rem;">Answer these to recover your neural profile later</p>
                
                <form class="cyber-form" id="securityForm" novalidate>
                    <div class="form-grid" style="grid-template-columns: 1fr;">
                        <!-- Security Section -->
                                        <div class="form-section">
                                            
                                            <?php for($i=1; $i<=3; $i++): ?>
                                            <div class="input-group" style="margin-bottom: 1rem;">
                                                <label class="cyber-label">
                                                    <span class="label-icon">❓</span>
                                                    SECURITY QUESTION <?= $i ?> <span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <select class="cyber-input" id="auth_question<?= $i ?>" name="question<?= $i ?>" required style="cursor: pointer; width: 100%;">
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
                                                <div class="error-message" id="auth_question<?= $i ?>Error"></div>
                                            </div>
                                            
                                            <div class="input-group" style="margin-bottom: <?= $i<3 ? '2rem' : '0' ?>;">
                                                <label class="cyber-label">
                                                    <span class="label-icon">🔒</span>
                                                    ANSWER <?= $i ?><span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="password" class="cyber-input" id="answer<?= $i ?>" name="answer<?= $i ?>" required>
                                                    <button type="button" class="show-password" onclick="togglePassword('answer<?= $i ?>')">👁️</button>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="answer<?= $i ?>Error"></div>
                                            </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="form-actions">
                                        <button type="button" class="cyber-btn secondary" onclick="window.location.href='register.php'">
                                            <div class="btn-glow"></div>
                                            <span class="btn-text">BACK</span>
                                        </button>
                                        <button type="submit" class="cyber-btn">
                                            <div class="btn-glow"></div>
                                            <span class="btn-text">COMPLETE REGISTRATION</span>
                                        </button>
                                    </div>
                                </form>
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
    <script src="../js/cyberpunk-security.js"></script>
</body>
</html>
