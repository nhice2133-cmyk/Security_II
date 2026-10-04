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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password — Neon Recovery</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Rajdhani:wght@400;500;700&display=swap" rel="stylesheet">

  <style>
    /* Step display overrides */
    .form-panel { display: none; }
    #step1 { display: block; }
  </style>

  <!-- Full theme styles -->
  <link rel="stylesheet" href="../css/cyberpunk-questions.css" />
</head>
<body>
  <!-- Animated background -->
  <div class="cyber-grid" aria-hidden="true"></div>
  <div class="neon-particles" aria-hidden="true"></div>
  <div class="scan-lines" aria-hidden="true"></div>

  <!-- Header -->
  <header class="cyber-header">
    <div class="header-content">
      <div class="logo-container"><span class="logo-icon">⚡</span><span class="logo-text">Cyber<span class="accent">Auth</span></span></div>
      <div class="header-actions">
        <a href="index.php" class="cyber-link">Home</a>
        <a href="login.php" class="cyber-link">Login</a>
      </div>
    </div>
  </header>

  <!-- Content -->
  <main class="cyber-main">
    <div class="cyber-container">
      
      <div class="form-panel" id="step1" style="max-width: 600px; margin: 0 auto; padding: 2rem;">
        <h2 class="cyber-headline" style="text-align: center;">Forgot Password</h2>
        <p class="cyber-lead" style="text-align: center; margin-bottom: 2rem;">Enter your registered ID Number to continue.</p>

        <form class="cyber-form" id="forgotPasswordForm" autocomplete="off">
          <div class="input-group">
            <label class="cyber-label" for="idNumber">ID Number</label>
            <div class="input-container">
              <input type="text" class="cyber-input" id="idNumber" name="idNumber" placeholder="XXXX-XXXX" required />
              <div class="input-glow"></div>
            </div>
            <div class="error-message" id="idNumberError" style="display:none; color: var(--cyber-error); margin-top: 5px;"></div>
          </div>

          <div class="form-actions" style="margin-top: 1.5rem;">
            <button type="submit" class="cyber-btn" id="btnStep1">
              <div class="btn-glow"></div>
              <span class="btn-text">SEND OTP</span>
            </button>
            <div class="form-links" style="margin-top: 1rem; text-align: center;">
              <p class="switch-form"><a href="login.php" class="cyber-link">← Back to Login</a></p>
            </div>
          </div>
        </form>
      </div>

      <div class="form-panel" id="step2" style="display: none; max-width: 600px; margin: 0 auto; padding: 2rem;">
        <h2 class="cyber-headline" style="text-align: center;">Verify OTP</h2>
        <p class="cyber-lead" style="text-align: center; margin-bottom: 2rem;">Enter the 6-digit pin sent to your email.</p>

        <form class="cyber-form" id="otpForm" autocomplete="off">
          <div class="input-group">
            <label class="cyber-label" for="otpCode">One Time Pin</label>
            <div class="input-container">
              <input type="text" class="cyber-input" id="otpCode" name="otpCode" placeholder="123456" maxlength="6" required />
              <div class="input-glow"></div>
            </div>
            <div class="error-message" id="otpError" style="display:none; color: var(--cyber-error); margin-top: 5px;"></div>
          </div>
          <div class="form-actions" style="margin-top: 1.5rem;">
            <button type="submit" class="cyber-btn" id="btnStep2">
              <div class="btn-glow"></div>
              <span class="btn-text">VERIFY OTP</span>
            </button>
          </div>
        </form>
      </div>

      <div class="form-panel" id="step3" style="display: none; max-width: 600px; margin: 0 auto; padding: 2rem;">
        <h2 class="cyber-headline" style="text-align: center;">Security Verification</h2>
        <p class="cyber-lead" style="text-align: center; margin-bottom: 2rem;">Answer at least 2 of the 3 security questions correctly to reset your password.</p>

        <form class="cyber-form" id="questionsForm" autocomplete="off">
          <div class="input-group">
            <label class="cyber-label" id="lblQ1">Question 1</label>
            <div class="input-container">
              <input type="password" class="cyber-input" id="ans1" name="ans1" required />
              <div class="input-glow"></div>
            </div>
          </div>
          <div class="input-group">
            <label class="cyber-label" id="lblQ2">Question 2</label>
            <div class="input-container">
              <input type="password" class="cyber-input" id="ans2" name="ans2" required />
              <div class="input-glow"></div>
            </div>
          </div>
          <div class="input-group">
            <label class="cyber-label" id="lblQ3">Question 3</label>
            <div class="input-container">
              <input type="password" class="cyber-input" id="ans3" name="ans3" required />
              <div class="input-glow"></div>
            </div>
            <div class="error-message" id="ansError" style="display:none; color: var(--cyber-error); margin-top: 5px;"></div>
          </div>
          <div class="form-actions" style="margin-top: 1.5rem;">
            <button type="submit" class="cyber-btn" id="btnStep3">
              <div class="btn-glow"></div>
              <span class="btn-text">VERIFY ANSWERS</span>
            </button>
          </div>
        </form>
      </div>

      <div class="form-panel" id="step4" style="display: none; max-width: 600px; margin: 0 auto; padding: 2rem;">
        <h2 class="cyber-headline" style="text-align: center;">Reset Password</h2>
        <p class="cyber-lead" style="text-align: center; margin-bottom: 2rem;">Enter your new password.</p>

        <form class="cyber-form" id="resetForm" autocomplete="off">
          <div class="input-group">
            <label class="cyber-label" for="newPassword">New Password</label>
            <div class="input-container">
              <input type="password" class="cyber-input" id="newPassword" name="newPassword" required />
              <div class="input-glow"></div>
            </div>
          </div>
          <div class="input-group">
            <label class="cyber-label" for="confirmNewPassword">Confirm Password</label>
            <div class="input-container">
              <input type="password" class="cyber-input" id="confirmNewPassword" name="confirmNewPassword" required />
              <div class="input-glow"></div>
            </div>
            <div class="error-message" id="resetError" style="display:none; color: var(--cyber-error); margin-top: 5px;"></div>
          </div>
          <div class="form-actions" style="margin-top: 1.5rem;">
            <button type="submit" class="cyber-btn" id="btnStep4">
              <div class="btn-glow"></div>
              <span class="btn-text">UPDATE PASSWORD</span>
            </button>
          </div>
        </form>
      </div>

    </div>
  </main>

  <!-- Footer -->
  <footer class="cyber-footer">
    <div class="footer-content">
      <div class="footer-text"><span>&copy; 2025 Auth System</span></div>
      <div class="footer-text"><span class="footer-accent">Neon • Secure • Fast</span></div>
    </div>
  </footer>

  <script src="../js/disable-rightclick.js"></script>
  <script src="../js/forgot-password.js"></script>
</body>
</html>
