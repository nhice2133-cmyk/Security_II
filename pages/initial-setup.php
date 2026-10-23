<?php
require_once __DIR__ . '/../php/config.php';

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    requireAuth404();
}

$db = new Database();
$stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) {
    requireAuth404();
}

// Only allow if pending_setup
if ($user['status'] !== 'pending_setup') {
    header('Location: dashboard.php');
    exit;
}

// Pre-populate existing fields if available, otherwise blank
$hasId = !empty($user['id_number']);
$idVal = $hasId ? $user['id_number'] : '';

$fnVal = (!empty($user['first_name']) && $user['first_name'] !== 'Initial') ? $user['first_name'] : '';
$miVal = (!empty($user['middle_name'])) ? $user['middle_name'] : '';
$lnVal = (!empty($user['last_name']) && $user['last_name'] !== 'Setup') ? $user['last_name'] : '';
$suffixVal = $user['extension'] ?? '';
$sexVal = strtolower($user['sex'] ?? '');
$bdVal = (!empty($user['birth_date']) && $user['birth_date'] !== '0000-00-00') ? $user['birth_date'] : '';
$ageVal = (!empty($user['age'])) ? ($user['age'] . ' yrs') : '';

$purokVal = $user['purok_street'] ?? '';
$brgyVal = $user['barangay'] ?? '';
$cityVal = $user['city'] ?? '';
$provVal = $user['province'] ?? '';
$countryVal = !empty($user['country']) ? $user['country'] : 'Philippines';
$zipVal = $user['zip_code'] ?? '';

$emailVal = $user['email'] ?? '';
$usernameVal = $user['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal &amp; Security Details — Initial Setup</title>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

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
    <link rel="stylesheet" href="../css/cyberpunk-register.css?v=<?= filemtime(__DIR__ . '/../css/cyberpunk-register.css') ?>">
</head>
<body>
    <!-- Animated Modern Ambient Background -->
    <div class="cyber-grid" aria-hidden="true"></div>
    <div class="neon-particles" aria-hidden="true"></div>

    <!-- Top System Header -->
    <header class="cyber-header">
        <div class="header-content">
            <div class="logo-container">
                <div class="logo-icon">⚡</div>
                <h1 class="logo-text">CYBER<span class="accent">AUTH</span></h1>
            </div>
            <div class="header-actions">
                <a href="../php/logout.php" class="nav-link cyber-link">LOGOUT</a>

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
            <p class="profile-page-subtitle">View and manage your identity, address, and login credentials.</p>
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

        <form id="initialSetupWizardForm" novalidate autocomplete="off">
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
                        <!-- ID NUMBER (clickable to generate if blank) -->
                        <div class="field-group">
                            <label class="field-label" for="idNumber">
                                ID NUMBER <span class="field-hint"><?= $hasId ? '(assigned)' : '(click to generate)' ?></span>
                            </label>
                            <div class="id-input-wrap">
                                <input type="text" class="custom-input input-mono" id="idNumber" name="idNumber" value="<?= htmlspecialchars($idVal) ?>" placeholder="<?= $hasId ? '' : 'Click button to generate ID' ?>" readonly>
                                <?php if (!$hasId): ?>
                                    <button type="button" class="id-gen-btn" id="generateIdBtn">⚡ Generate ID</button>
                                <?php endif; ?>
                            </div>
                            <div class="field-error-msg" id="idNumberError">
                                <span>⚠</span> <span class="error-text">Please click to generate an ID number</span>
                            </div>
                        </div>

                        <!-- FIRST NAME * -->
                        <div class="field-group">
                            <label class="field-label" for="firstName">
                                FIRST NAME <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="firstName" name="firstName" value="<?= htmlspecialchars($fnVal) ?>" placeholder="">
                            <div class="field-error-msg" id="firstNameError">
                                <span>⚠</span> <span class="error-text">First name is required</span>
                            </div>
                        </div>

                        <!-- MIDDLE NAME -->
                        <div class="field-group">
                            <label class="field-label" for="middleInitial">
                                MIDDLE NAME <span class="field-hint">(optional)</span>
                            </label>
                            <input type="text" class="custom-input" id="middleInitial" name="middleInitial" maxlength="50" value="<?= htmlspecialchars($miVal) ?>" placeholder="e.g. Santos or S">
                            <div class="field-error-msg" id="middleInitialError">
                                <span>⚠</span> <span class="error-text">Please enter valid characters.</span>
                            </div>
                        </div>

                        <!-- LAST NAME * -->
                        <div class="field-group">
                            <label class="field-label" for="lastName">
                                LAST NAME <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="lastName" name="lastName" value="<?= htmlspecialchars($lnVal) ?>" placeholder="">
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
                                <option value="Jr." <?= strcasecmp($suffixVal, 'Jr.') === 0 || strcasecmp($suffixVal, 'Jr') === 0 ? 'selected' : '' ?>>Jr.</option>
                                <option value="Sr." <?= strcasecmp($suffixVal, 'Sr.') === 0 || strcasecmp($suffixVal, 'Sr') === 0 ? 'selected' : '' ?>>Sr.</option>
                                <option value="II" <?= $suffixVal === 'II' ? 'selected' : '' ?>>II</option>
                                <option value="III" <?= $suffixVal === 'III' ? 'selected' : '' ?>>III</option>
                                <option value="IV" <?= $suffixVal === 'IV' ? 'selected' : '' ?>>IV</option>
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
                                <option value="male" <?= $sexVal === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= $sexVal === 'female' ? 'selected' : '' ?>>Female</option>
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
                            <input type="date" class="custom-input" id="birthDate" name="birthDate" value="<?= htmlspecialchars($bdVal) ?>">
                            <div class="field-error-msg" id="birthDateError">
                                <span>⚠</span> <span class="error-text">Birthdate is required</span>
                            </div>
                        </div>

                        <!-- CALCULATED AGE -->
                        <div class="field-group">
                            <label class="field-label" for="age">
                                CALCULATED AGE
                            </label>
                            <input type="text" class="custom-input" id="age" name="age" value="<?= htmlspecialchars($ageVal) ?>" readonly placeholder="Auto-calculated">
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
                    Logged in as <strong>@<?= htmlspecialchars($user['username']) ?></strong> &bull; <a href="../php/logout.php" class="login-accent-link">Log out</a>
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
                            <input type="text" class="custom-input" id="purokStreet" name="purokStreet" value="<?= htmlspecialchars($purokVal) ?>" placeholder="e.g. Purok 4, Main Street">
                            <div class="field-error-msg" id="purokStreetError">
                                <span>⚠</span> <span class="error-text">Purok / Street is required</span>
                            </div>
                        </div>

                        <!-- BARANGAY * -->
                        <div class="field-group">
                            <label class="field-label" for="barangay">
                                BARANGAY <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="barangay" name="barangay" value="<?= htmlspecialchars($brgyVal) ?>" placeholder="e.g. San Isidro">
                            <div class="field-error-msg" id="barangayError">
                                <span>⚠</span> <span class="error-text">Barangay is required</span>
                            </div>
                        </div>

                        <!-- MUNICIPALITY / CITY * -->
                        <div class="field-group">
                            <label class="field-label" for="city">
                                MUNICIPALITY / CITY <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="city" name="city" value="<?= htmlspecialchars($cityVal) ?>" placeholder="e.g. Tagbilaran City">
                            <div class="field-error-msg" id="cityError">
                                <span>⚠</span> <span class="error-text">Municipality / City is required</span>
                            </div>
                        </div>

                        <!-- PROVINCE * -->
                        <div class="field-group">
                            <label class="field-label" for="province">
                                PROVINCE <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="province" name="province" value="<?= htmlspecialchars($provVal) ?>" placeholder="e.g. Bohol">
                            <div class="field-error-msg" id="provinceError">
                                <span>⚠</span> <span class="error-text">Province is required</span>
                            </div>
                        </div>

                        <!-- COUNTRY * -->
                        <div class="field-group">
                            <label class="field-label" for="country">
                                COUNTRY <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="country" name="country" value="<?= htmlspecialchars($countryVal) ?>">
                            <div class="field-error-msg" id="countryError">
                                <span>⚠</span> <span class="error-text">Country is required</span>
                            </div>
                        </div>

                        <!-- POSTAL / ZIP CODE * -->
                        <div class="field-group">
                            <label class="field-label" for="zipCode">
                                POSTAL / ZIP CODE <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="zipCode" name="zipCode" maxlength="4" value="<?= htmlspecialchars($zipVal) ?>" placeholder="0000">
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
                    Logged in as <strong>@<?= htmlspecialchars($user['username']) ?></strong> &bull; <a href="../php/logout.php" class="login-accent-link">Log out</a>
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
                        <!-- EMAIL ADDRESS (read-only) -->
                        <div class="field-group">
                            <label class="field-label" for="email">
                                EMAIL ADDRESS <span class="field-hint">(read-only)</span>
                            </label>
                            <input type="email" class="custom-input" id="email" name="email" value="<?= htmlspecialchars($emailVal) ?>" readonly>
                        </div>

                        <!-- USERNAME * -->
                        <div class="field-group">
                            <label class="field-label" for="username">
                                USERNAME <span class="req-star">*</span>
                            </label>
                            <input type="text" class="custom-input" id="username" name="username" value="<?= htmlspecialchars($usernameVal) ?>" placeholder="username" maxlength="20">
                            <div class="field-error-msg" id="usernameError">
                                <span>⚠</span> <span class="error-text">Username is required</span>
                            </div>
                        </div>

                        <!-- NEW PASSWORD * -->
                        <div class="field-group">
                            <label class="field-label" for="password">
                                NEW PASSWORD <span class="req-star">*</span>
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
                                    <span class="strength-hint">Min. 8 chars with upper, lower, number, symbol</span>
                                    <span class="strength-badge" id="strengthBadge"></span>
                                </div>
                            </div>
                            <div class="field-error-msg" id="passwordError">
                                <span>⚠</span> <span class="error-text">Password requirements not met</span>
                            </div>
                        </div>

                        <!-- CONFIRM NEW PASSWORD * -->
                        <div class="field-group">
                            <label class="field-label" for="confirmPassword">
                                CONFIRM NEW PASSWORD <span class="req-star">*</span>
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

                <!-- Back & Complete Setup Buttons -->
                <div class="wizard-btn-row">
                    <button type="button" class="btn-secondary" id="step3BackBtn">
                        &larr; Back
                    </button>
                    <button type="button" class="btn-primary" id="completeSetupBtn">
                        <span id="completeSetupText">Complete Setup &check;</span>
                    </button>
                </div>

                <!-- Footer -->
                <div class="profile-footer-link">
                    Logged in as <strong>@<?= htmlspecialchars($user['username']) ?></strong> &bull; <a href="../php/logout.php" class="login-accent-link">Log out</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="../js/cyberpunk-initial-setup.js?v=<?= time() ?>"></script>
    <script src="../js/theme-toggle.js?v=<?= time() ?>"></script>
</body>
</html>