<?php
require_once __DIR__ . '/../php/config.php';

// Redirect logged-in users
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
    <title>Registration Form — CyberAuth System</title>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">

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
    <link rel="stylesheet" href="../css/cyberpunk-register.css">
</head>
<body>
    <!-- Subtle Ambient Background -->
    <div class="cyber-grid" aria-hidden="true"></div>
    <div class="neon-particles" aria-hidden="true"></div>

    <!-- Top System Navigation Header -->
    <header class="cyber-header">
        <div class="header-content">
            <a href="index.php" class="logo-container">
                <div class="logo-icon">⚡</div>
                <h1 class="logo-text">CYBER<span class="accent">AUTH</span></h1>
            </a>
            <div class="header-actions">
                <a href="index.php" class="nav-link cyber-link">HOME</a>
                <a href="login.php" class="nav-link cyber-link">LOGIN</a>

                <!-- Theme Toggle Switcher -->
                <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle theme" title="Toggle theme">
                    <span class="theme-toggle-icon theme-toggle-sun">☀️</span>
                    <span class="theme-toggle-icon theme-toggle-moon">🌙</span>
                    <span class="theme-toggle-text">Dark</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Toast notification container -->
    <div class="reg-toast-container" id="regToastContainer"></div>

    <!-- Main Content Container Matching Profile Section -->
    <div class="reg-container-wrap">
        <div class="profile-page-header">
            <h1 class="profile-page-title">Personal &amp; Security Details</h1>
            <p class="profile-page-subtitle">Complete your identity, address, and login credentials.</p>
        </div>

        <!-- Wizard Tabs Navigation -->
        <div class="profile-tabs-nav" role="tablist">
            <button type="button" class="profile-tab-btn active" id="tabBtn1" role="tab" aria-selected="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Personal Info</span>
            </button>
            <button type="button" class="profile-tab-btn" id="tabBtn2" role="tab" aria-selected="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>Address</span>
            </button>
            <button type="button" class="profile-tab-btn" id="tabBtn3" role="tab" aria-selected="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>Account &amp; Role</span>
            </button>
        </div>

        <form id="registrationWizardForm" novalidate autocomplete="off">
            <!-- ================= STEP 1: PERSONAL INFORMATION ================= -->
            <div class="wizard-step-pane active" id="step1Pane" data-step="1">
                <div class="form-section-card">
                    <div class="form-card-header">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                            <span>Basic Identity Information</span>
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <!-- ID NUMBER -->
                        <div class="field-group">
                            <label class="field-label" for="idNumber">
                                ID NUMBER <span class="field-hint">(auto-generated)</span>
                            </label>
                            <input type="text" class="custom-input input-mono" id="idNumber" name="idNumber" value="Loading..." readonly>
                        </div>

                        <!-- FIRST NAME * -->
                        <div class="field-group">
                            <label class="field-label" for="firstName">
                                FIRST NAME <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="firstName" name="firstName" placeholder="" autofocus>
                            <div class="field-error-msg" id="firstNameError">
                                <span>⚠</span> <span class="error-text">First name is required</span>
                            </div>
                        </div>

                        <!-- MIDDLE NAME -->
                        <div class="field-group">
                            <label class="field-label" for="middleInitial">
                                MIDDLE NAME <span class="field-hint">(optional)</span>
                            </label>
                            <input type="text" class="custom-input" id="middleInitial" name="middleInitial" maxlength="50" placeholder="e.g. Santos or S">
                            <div class="field-error-msg" id="middleInitialError">
                                <span>⚠</span> <span class="error-text">Please enter valid characters.</span>
                            </div>
                        </div>

                        <!-- LAST NAME * -->
                        <div class="field-group">
                            <label class="field-label" for="lastName">
                                LAST NAME <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="lastName" name="lastName" placeholder="">
                            <div class="field-error-msg" id="lastNameError">
                                <span>⚠</span> <span class="error-text">Last name is required</span>
                            </div>
                        </div>

                        <!-- SUFFIX -->
                        <div class="field-group">
                            <label class="field-label" for="suffix">
                                SUFFIX <span class="field-hint">(optional)</span>
                            </label>
                            <select class="custom-select" id="suffix" name="suffix">
                                <option value="">None</option>
                                <option value="Jr.">Jr.</option>
                                <option value="Sr.">Sr.</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                            </select>
                            <div class="field-error-msg" id="suffixError">
                                <span>⚠</span> <span class="error-text">Invalid suffix</span>
                            </div>
                        </div>

                        <!-- GENDER * -->
                        <div class="field-group">
                            <label class="field-label" for="sex">
                                GENDER <span class="req-star">*</span>
                            </label>
                            <select class="custom-select" id="sex" name="sex">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            <div class="field-error-msg" id="sexError">
                                <span>⚠</span> <span class="error-text">Gender is required</span>
                            </div>
                        </div>

                        <!-- BIRTHDATE * -->
                        <div class="field-group">
                            <label class="field-label" for="birthDate">
                                BIRTHDATE <span class="req-star">*</span>
                            </label>
                            <input type="date" class="custom-input" id="birthDate" name="birthDate">
                            <div class="field-error-msg" id="birthDateError">
                                <span>⚠</span> <span class="error-text">Birthdate is required</span>
                            </div>
                        </div>

                        <!-- CALCULATED AGE -->
                        <div class="field-group">
                            <label class="field-label" for="age">
                                CALCULATED AGE
                            </label>
                            <input type="text" class="custom-input" id="age" name="age" readonly placeholder="Auto-calculated">
                            <div class="field-error-msg" id="ageError">
                                <span>⚠</span> <span class="error-text">Age must be at least 18 years old</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Button -->
                <div class="wizard-btn-row">
                    <button type="button" class="btn-primary" id="step1NextBtn">
                        Next Step &rarr;
                    </button>
                </div>

                <!-- Footer -->
                <div class="profile-footer-link">
                    Already have an account? <a href="login.php" class="login-accent-link">Log In</a>
                </div>
            </div>

            <!-- ================= STEP 2: ADDRESS INFORMATION ================= -->
            <div class="wizard-step-pane" id="step2Pane" data-step="2">
                <div class="form-section-card">
                    <div class="form-card-header">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>Residence &amp; Postal Location</span>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <!-- PUROK / STREET ADDRESS * -->
                        <div class="field-group">
                            <label class="field-label" for="purokStreet">
                                PUROK / STREET ADDRESS <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="purokStreet" name="purokStreet" placeholder="e.g. Purok 4, Main Street">
                            <div class="field-error-msg" id="purokStreetError">
                                <span>⚠</span> <span class="error-text">Purok / Street is required</span>
                            </div>
                        </div>

                        <!-- BARANGAY * -->
                        <div class="field-group">
                            <label class="field-label" for="barangay">
                                BARANGAY <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="barangay" name="barangay" placeholder="e.g. San Isidro">
                            <div class="field-error-msg" id="barangayError">
                                <span>⚠</span> <span class="error-text">Barangay is required</span>
                            </div>
                        </div>

                        <!-- MUNICIPALITY / CITY * -->
                        <div class="field-group">
                            <label class="field-label" for="city">
                                MUNICIPALITY / CITY <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="city" name="city" placeholder="e.g. Tagbilaran City">
                            <div class="field-error-msg" id="cityError">
                                <span>⚠</span> <span class="error-text">Municipality / City is required</span>
                            </div>
                        </div>

                        <!-- PROVINCE * -->
                        <div class="field-group">
                            <label class="field-label" for="province">
                                PROVINCE <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="province" name="province" placeholder="e.g. Bohol">
                            <div class="field-error-msg" id="provinceError">
                                <span>⚠</span> <span class="error-text">Province is required</span>
                            </div>
                        </div>

                        <!-- COUNTRY * -->
                        <div class="field-group">
                            <label class="field-label" for="country">
                                COUNTRY <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="country" name="country" value="Philippines">
                            <div class="field-error-msg" id="countryError">
                                <span>⚠</span> <span class="error-text">Country is required</span>
                            </div>
                        </div>

                        <!-- POSTAL / ZIP CODE * -->
                        <div class="field-group">
                            <label class="field-label" for="zipCode">
                                POSTAL / ZIP CODE <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="zipCode" name="zipCode" maxlength="4" placeholder="0000">
                            <div class="field-error-msg" id="zipCodeError">
                                <span>⚠</span> <span class="error-text">Zip code must be 4 digits</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wizard-btn-row">
                    <button type="button" class="btn-secondary" id="step2BackBtn">&larr; Back</button>
                    <button type="button" class="btn-primary" id="step2NextBtn">Next Step &rarr;</button>
                </div>

                <div class="profile-footer-link">
                    Already have an account? <a href="login.php" class="login-accent-link">Log In</a>
                </div>
            </div>

            <!-- ================= STEP 3: CREDENTIALS & SECURITY ================= -->
            <div class="wizard-step-pane" id="step3Pane" data-step="3">
                <!-- Section 1: Account Credentials -->
                <div class="form-section-card">
                    <div class="form-card-header">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span>Account Credentials &amp; Role</span>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <!-- EMAIL ADDRESS * -->
                        <div class="field-group">
                            <label class="field-label" for="email">
                                EMAIL ADDRESS <span class="req-star">*</span>
                            </label>
                            <input type="email" class="custom-input" id="email" name="email" placeholder="yourname@domain.com">
                            <div class="field-error-msg" id="emailError">
                                <span>⚠</span> <span class="error-text">Valid email is required</span>
                            </div>
                        </div>

                        <!-- USERNAME * -->
                        <div class="field-group">
                            <label class="field-label" for="username">
                                USERNAME <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="username" name="username" placeholder="username" maxlength="20">
                            <div class="field-error-msg" id="usernameError">
                                <span>⚠</span> <span class="error-text">Username is required</span>
                            </div>
                        </div>

                        <!-- PASSWORD * -->
                        <div class="field-group">
                            <label class="field-label" for="password">
                                PASSWORD <span class="req-star">*</span>
                            </label>
                            <div class="pwd-input-wrap">
                                <input type="password" class="custom-input" id="password" name="password" placeholder="••••••••">
                                <button type="button" class="pwd-eye-btn show-password" onclick="togglePassword('password', this)" aria-label="Toggle password visibility"></button>
                            </div>
                            <div class="strength-container" id="strengthContainer">
                                <div class="strength-bar">
                                    <div class="strength-segment" id="strSeg1"></div>
                                    <div class="strength-segment" id="strSeg2"></div>
                                    <div class="strength-segment" id="strSeg3"></div>
                                    <div class="strength-segment" id="strSeg4"></div>
                                </div>
                                <div class="strength-footer">
                                    <span class="strength-hint">Min. 8 chars with uppercase, lowercase, and number</span>
                                    <span class="strength-badge" id="strengthBadge"></span>
                                </div>
                            </div>
                            <div class="field-error-msg" id="passwordError">
                                <span>⚠</span> <span class="error-text">Password requirements not met</span>
                            </div>
                        </div>

                        <!-- CONFIRM PASSWORD * -->
                        <div class="field-group">
                            <label class="field-label" for="confirmPassword">
                                CONFIRM PASSWORD <span class="req-star">*</span>
                            </label>
                            <div class="pwd-input-wrap">
                                <input type="password" class="custom-input" id="confirmPassword" name="confirmPassword" placeholder="••••••••">
                                <button type="button" class="pwd-eye-btn show-password" onclick="togglePassword('confirmPassword', this)" aria-label="Toggle password visibility"></button>
                            </div>
                            <div class="field-error-msg" id="confirmPasswordError">
                                <span>⚠</span> <span class="error-text">Passwords do not match</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Security Recovery Questions -->
                <div class="form-section-card">
                    <div class="form-card-header">
                        <div class="form-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            <span>Security Recovery Questions</span>
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <!-- QUESTION 1 / ANSWER 1 -->
                        <div class="field-group">
                            <label class="field-label" for="q1">
                                QUESTION 1 <span class="req-star">*</span>
                            </label>
                            <select class="custom-select sq-select" id="q1" name="q1" data-index="1">
                                <option value="">Select Question 1</option>
                                <option value="Who is your best friend in Elementary?">Who is your best friend in Elementary?</option>
                                <option value="What is the name of your favorite pet?">What is the name of your favorite pet?</option>
                                <option value="Who is your favorite teacher in high school?">Who is your favorite teacher in high school?</option>
                                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                <option value="In what city were you born?">In what city were you born?</option>
                                <option value="What was the make and model of your first car?">What was the make and model of your first car?</option>
                            </select>
                            <div class="field-error-msg" id="q1Error">
                                <span>⚠</span> <span class="error-text">Please select Question 1</span>
                            </div>

                            <input type="text" class="custom-input" id="a1" name="a1" placeholder="Answer 1" disabled style="margin-top: 0.5rem;">
                            <div class="field-error-msg" id="a1Error">
                                <span>⚠</span> <span class="error-text">Answer 1 is required</span>
                            </div>
                        </div>

                        <!-- QUESTION 2 / ANSWER 2 -->
                        <div class="field-group">
                            <label class="field-label" for="q2">
                                QUESTION 2 <span class="req-star">*</span>
                            </label>
                            <select class="custom-select sq-select" id="q2" name="q2" data-index="2">
                                <option value="">Select Question 2</option>
                                <option value="Who is your best friend in Elementary?">Who is your best friend in Elementary?</option>
                                <option value="What is the name of your favorite pet?">What is the name of your favorite pet?</option>
                                <option value="Who is your favorite teacher in high school?">Who is your favorite teacher in high school?</option>
                                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                <option value="In what city were you born?">In what city were you born?</option>
                                <option value="What was the make and model of your first car?">What was the make and model of your first car?</option>
                            </select>
                            <div class="field-error-msg" id="q2Error">
                                <span>⚠</span> <span class="error-text">Please select Question 2</span>
                            </div>

                            <input type="text" class="custom-input" id="a2" name="a2" placeholder="Answer 2" disabled style="margin-top: 0.5rem;">
                            <div class="field-error-msg" id="a2Error">
                                <span>⚠</span> <span class="error-text">Answer 2 is required</span>
                            </div>
                        </div>

                        <!-- QUESTION 3 / ANSWER 3 -->
                        <div class="field-group">
                            <label class="field-label" for="q3">
                                QUESTION 3 <span class="req-star">*</span>
                            </label>
                            <select class="custom-select sq-select" id="q3" name="q3" data-index="3">
                                <option value="">Select Question 3</option>
                                <option value="Who is your best friend in Elementary?">Who is your best friend in Elementary?</option>
                                <option value="What is the name of your favorite pet?">What is the name of your favorite pet?</option>
                                <option value="Who is your favorite teacher in high school?">Who is your favorite teacher in high school?</option>
                                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                <option value="In what city were you born?">In what city were you born?</option>
                                <option value="What was the make and model of your first car?">What was the make and model of your first car?</option>
                            </select>
                            <div class="field-error-msg" id="q3Error">
                                <span>⚠</span> <span class="error-text">Please select Question 3</span>
                            </div>

                            <input type="text" class="custom-input" id="a3" name="a3" placeholder="Answer 3" disabled style="margin-top: 0.5rem;">
                            <div class="field-error-msg" id="a3Error">
                                <span>⚠</span> <span class="error-text">Answer 3 is required</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back & Complete Registration Buttons -->
                <div class="wizard-btn-row">
                    <button type="button" class="btn-secondary" id="step3BackBtn">
                        &larr; Back
                    </button>
                    <button type="button" class="btn-primary" id="completeRegBtn">
                        <span id="completeRegText">Complete Registration &check;</span>
                    </button>
                </div>

                <!-- Footer -->
                <div class="profile-footer-link">
                    Already have an account? <a href="login.php" class="login-accent-link">Log In</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="../js/cyberpunk-register.js"></script>
    <script src="../js/theme-toggle.js"></script>
</body>
</html>
