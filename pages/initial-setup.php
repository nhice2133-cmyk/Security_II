<?php
require_once '../php/config.php';

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

$countries = [
    'Afghanistan','Albania','Algeria','Andorra','Angola','Antigua and Barbuda','Argentina','Armenia','Australia','Austria','Azerbaijan','Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bhutan','Bolivia','Bosnia and Herzegovina','Botswana','Brazil','Brunei','Bulgaria','Burkina Faso','Burundi','Cabo Verde','Cambodia','Cameroon','Canada','Central African Republic','Chad','Chile','China','Colombia','Comoros','Congo','Costa Rica','Croatia','Cuba','Cyprus','Czech Republic','Denmark','Djibouti','Dominica','Dominican Republic','Ecuador','Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Eswatini','Ethiopia','Fiji','Finland','France','Gabon','Gambia','Georgia','Germany','Ghana','Greece','Grenada','Guatemala','Guinea','Guinea-Bissau','Guyana','Haiti','Honduras','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Jamaica','Japan','Jordan','Kazakhstan','Kenya','Kiribati','Kosovo','Kuwait','Kyrgyzstan','Laos','Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg','Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Marshall Islands','Mauritania','Mauritius','Mexico','Micronesia','Moldova','Monaco','Mongolia','Montenegro','Morocco','Mozambique','Myanmar','Namibia','Nauru','Nepal','Netherlands','New Zealand','Nicaragua','Niger','Nigeria','North Korea','North Macedonia','Norway','Oman','Pakistan','Palau','Palestine','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Poland','Portugal','Qatar','Romania','Russia','Rwanda','Saint Kitts and Nevis','Saint Lucia','Saint Vincent and the Grenadines','Samoa','San Marino','Sao Tome and Principe','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia','Solomon Islands','Somalia','South Africa','South Korea','South Sudan','Spain','Sri Lanka','Sudan','Suriname','Sweden','Switzerland','Syria','Taiwan','Tajikistan','Tanzania','Thailand','Timor-Leste','Togo','Tonga','Trinidad and Tobago','Tunisia','Turkey','Turkmenistan','Tuvalu','Uganda','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Uzbekistan','Vanuatu','Vatican City','Venezuela','Vietnam','Yemen','Zambia','Zimbabwe'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>" />
  <title>Initial Setup - CyberAuth System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Rajdhani:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/cyberpunk-edit-profile.css" />
  <style>
    .cyber-main { margin-top: 80px; padding: 2rem; display: flex; justify-content: center; }
    .setup-wrapper { max-width: 900px; width: 100%; }
    .setup-header { margin-bottom: 2rem; text-align: center; }
    .setup-title { font-family: 'Orbitron', monospace; font-size: 2rem; color: var(--cyber-primary); text-transform: uppercase; letter-spacing: 2px; text-shadow: 0 0 10px rgba(0,255,255,0.5); }
    .setup-subtitle { color: var(--cyber-text-dim); font-size: 1.1rem; }
    .wizard-step { display: none; }
    .wizard-step.active { display: block; animation: fadeIn 0.4s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .step-indicator { display: flex; justify-content: space-between; margin-bottom: 2rem; position: relative; }
    .step-indicator::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 2px; background: var(--cyber-border); z-index: 1; }
    .step-dot { position: relative; z-index: 2; background: #000; border: 2px solid var(--cyber-border); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--cyber-text-dim); font-weight: bold; font-family: 'Orbitron'; transition: all 0.3s; }
    .step-dot.active { border-color: var(--cyber-primary); color: var(--cyber-primary); box-shadow: 0 0 15px rgba(0,255,255,0.4); }
    .step-dot.completed { border-color: var(--cyber-secondary); background: rgba(0,255,136,0.1); color: var(--cyber-secondary); }
    .btn-group { display: flex; justify-content: space-between; margin-top: 2rem; gap: 1rem; }
    .cyber-btn.btn-outline { background: transparent; border: 1px solid var(--cyber-border); color: var(--cyber-text); }
    .cyber-btn.btn-outline:hover { border-color: var(--cyber-primary); color: var(--cyber-primary); }
    
    /* Security Questions specific styles */
    .question-block { margin-bottom: 1.5rem; }
    select.cyber-input { background: rgba(0, 0, 0, 0.7); }
    select.cyber-input option { background: #1a1a1a; color: #00ffff; }
  </style>
</head>
<body>
  <div class="cyber-grid" aria-hidden="true"></div>
  <div class="neon-particles" aria-hidden="true"></div>
  <div class="scan-lines" aria-hidden="true"></div>

  <header class="cyber-header">
    <div class="header-content">
      <div class="logo-container">
        <div class="logo-icon">⚡</div>
        <h1 class="logo-text">CYBER<span class="accent">AUTH</span></h1>
      </div>
      <div class="header-actions">
        <a href="../php/logout.php" class="nav-link cyber-link">LOGOUT</a>
      </div>
    </div>
  </header>

  <main class="cyber-main">
    <div class="setup-wrapper">
      <div class="setup-header">
        <h1 class="setup-title">INITIALIZATION PROTOCOL</h1>
        <p class="setup-subtitle">Complete your profile, secure your account, and set recovery options.</p>
      </div>

      <div class="step-indicator">
        <div class="step-dot active" id="dot-1">1</div>
        <div class="step-dot" id="dot-2">2</div>
        <div class="step-dot" id="dot-3">3</div>
      </div>

      <form id="initialSetupForm" class="form-flow" novalidate>
        <div id="msg" class="status-message" role="status"></div>

        <!-- STEP 1: Profile Details -->
        <div class="wizard-step active" id="step-1">
          <section class="profile-panel">
            <header class="panel-header">
              <div class="panel-title"><span class="icon">👤</span><span>Step 1: Profile Details</span></div>
            </header>
            <div class="form-section">
              <div class="form-grid two-col">
                <div class="input-group">
                  <label class="cyber-label">First Name*</label>
                  <input id="firstName" name="firstName" class="cyber-input" type="text" value="<?= $user['first_name'] === 'Initial' ? '' : htmlspecialchars($user['first_name']) ?>" required>
                  <div class="error-message" id="firstNameError"></div>
                </div>
                <div class="input-group">
                  <label class="cyber-label">Last Name*</label>
                  <input id="lastName" name="lastName" class="cyber-input" type="text" value="<?= $user['last_name'] === 'Setup' ? '' : htmlspecialchars($user['last_name']) ?>" required>
                  <div class="error-message" id="lastNameError"></div>
                </div>
                <div class="input-group">
                  <label class="cyber-label">Username*</label>
                  <input id="username" name="username" class="cyber-input" type="text" value="<?= htmlspecialchars($user['username']) ?>" required>
                  <div class="error-message" id="usernameError"></div>
                </div>
                <div class="input-group">
                  <label class="cyber-label">Email* (Read-only)</label>
                  <input id="email" name="email" class="cyber-input" type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                </div>
                <div class="input-group">
                  <label class="cyber-label">Phone Number*</label>
                  <input id="phone" name="phone" class="cyber-input" type="text" placeholder="09XXXXXXXXX" required>
                  <div class="error-message" id="phoneError"></div>
                </div>
                <div class="input-group">
                  <label class="cyber-label">Sex*</label>
                  <select id="sex" name="sex" class="cyber-input" required>
                    <option value="">- Select -</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                  </select>
                  <div class="error-message" id="sexError"></div>
                </div>
                <div class="input-group">
                  <label class="cyber-label">Birth Date*</label>
                  <input id="birthDate" name="birthDate" class="cyber-input" type="date" required>
                  <div class="error-message" id="birthDateError"></div>
                </div>
                <div class="input-group">
                  <label class="cyber-label">Country*</label>
                  <select id="country" name="country" class="cyber-input" required>
                    <option value="">- Select -</option>
                    <?php foreach ($countries as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $c === 'Philippines' ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="error-message" id="countryError"></div>
                </div>
                <div class="input-group">
                  <label class="cyber-label">Province*</label>
                  <input id="province" name="province" class="cyber-input" type="text" required>
                  <div class="error-message" id="provinceError"></div>
                </div>
                <div class="input-group">
                  <label class="cyber-label">City/Municipality*</label>
                  <input id="city" name="city" class="cyber-input" type="text" required>
                  <div class="error-message" id="cityError"></div>
                </div>
                <div class="input-group">
                  <label class="cyber-label">Barangay*</label>
                  <input id="barangay" name="barangay" class="cyber-input" type="text" required>
                  <div class="error-message" id="barangayError"></div>
                </div>
                <div class="input-group">
                  <label class="cyber-label">Purok/Street*</label>
                  <input id="purok" name="purok" class="cyber-input" type="text" required>
                  <div class="error-message" id="purokError"></div>
                </div>
              </div>
              <div class="input-group" style="margin-top: 1rem;">
                <label class="cyber-label">Full Address*</label>
                <input id="address" name="address" class="cyber-input" type="text" required>
                <div class="error-message" id="addressError"></div>
              </div>
            </div>
            <div class="btn-group" style="justify-content: flex-end;">
              <button type="button" class="submit-btn" onclick="nextStep(2)">NEXT: SECURITY ➔</button>
            </div>
          </section>
        </div>

        <!-- STEP 2: Password Update -->
        <div class="wizard-step" id="step-2">
          <section class="profile-panel">
            <header class="panel-header">
              <div class="panel-title"><span class="icon">🔑</span><span>Step 2: Encryption Key Update</span></div>
            </header>
            <div class="form-section">
              <p class="info-hint" style="margin-bottom: 1rem;">Your account was created with a temporary password. Please set a new secure encryption key.</p>
              <div class="form-grid two-col">
                <div class="input-group">
                  <label class="cyber-label">New Password*</label>
                  <div class="input-container" style="position: relative; width: 100%;">
                    <input type="password" class="cyber-input" id="newPassword" name="newPassword" required>
                    <button type="button" class="show-password" style="background:none; border:none; color:var(--cyber-primary); filter:drop-shadow(0 0 5px rgba(14,165,233,0.5)); cursor:pointer; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); z-index: 5;" onclick="togglePassword('newPassword', this)"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>
                  </div>
                  <div class="error-message" id="newPasswordError"></div>
                  <div style="font-size: 0.8rem; color: var(--cyber-text-dim); margin-top: 0.5rem;">
                    Must contain: 8+ chars, uppercase, lowercase, number, special char.
                  </div>
                </div>
                <div class="input-group">
                  <label class="cyber-label">Confirm New Password*</label>
                  <div class="input-container" style="position: relative; width: 100%;">
                    <input type="password" class="cyber-input" id="confirmPassword" name="confirmPassword" required>
                    <button type="button" class="show-password" style="background:none; border:none; color:var(--cyber-primary); filter:drop-shadow(0 0 5px rgba(14,165,233,0.5)); cursor:pointer; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); z-index: 5;" onclick="togglePassword('confirmPassword', this)"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>
                  </div>
                  <div class="error-message" id="confirmPasswordError"></div>
                </div>
              </div>
            </div>
            <div class="btn-group">
              <button type="button" class="submit-btn btn-outline" onclick="prevStep(1)">← BACK</button>
              <button type="button" class="submit-btn" onclick="nextStep(3)">NEXT: RECOVERY ➔</button>
            </div>
          </section>
        </div>

        <!-- STEP 3: Security Questions -->
        <div class="wizard-step" id="step-3">
          <section class="profile-panel">
            <header class="panel-header">
              <div class="panel-title"><span class="icon">🛡️</span><span>Step 3: Account Recovery Setup</span></div>
            </header>
            <div class="form-section">
              <p class="info-hint" style="margin-bottom: 1rem;">Set up security questions to recover your account if you lose access.</p>
              
              <?php for($i=1; $i<=3; $i++): ?>
              <div class="question-block" style="margin-bottom: 1.5rem; background: rgba(0, 0, 0, 0.2); padding: 1.5rem; border: 1px solid rgba(0, 255, 255, 0.1); border-radius: 8px;">
                <div class="input-group" style="margin-bottom: 1rem;">
                  <label class="cyber-label">
                    <span class="label-icon">❓</span>
                    SECURITY QUESTION <?= $i ?><span class="required-asterisk">*</span>
                  </label>
                  <div class="input-container" style="position: relative;">
                    <select class="cyber-input" id="q<?= $i ?>" name="q<?= $i ?>" required style="width: 100%; cursor: pointer;">
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
                  <div class="error-message" id="q<?= $i ?>Error"></div>
                </div>
                
                <div class="input-group">
                  <label class="cyber-label">
                    <span class="label-icon">🔒</span>
                    ANSWER <?= $i ?><span class="required-asterisk">*</span>
                  </label>
                  <div class="input-container" style="position: relative;">
                    <input type="password" class="cyber-input" id="a<?= $i ?>" name="a<?= $i ?>" required style="width: 100%;">
                    <button type="button" class="show-password" style="background:none; border:none; color:var(--cyber-primary); filter:drop-shadow(0 0 5px rgba(14,165,233,0.5)); cursor:pointer; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); z-index: 5;" onclick="togglePassword('a<?= $i ?>', this)"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>
                    <div class="input-glow"></div>
                  </div>
                  <div class="error-message" id="a<?= $i ?>Error"></div>
                </div>
              </div>
              <?php endfor; ?>

            </div>
            <div class="btn-group">
              <button type="button" class="submit-btn btn-outline" onclick="prevStep(2)">← BACK</button>
              <button type="submit" id="submitBtn" class="submit-btn" style="background: var(--cyber-secondary); color: #000; border-color: var(--cyber-secondary);">
                <span>FINALIZE SETUP</span>
              </button>
            </div>
          </section>
        </div>

      </form>
    </div>
  </main>

  <script src="../js/cyberpunk-initial-setup.js"></script>
</body>
</html>