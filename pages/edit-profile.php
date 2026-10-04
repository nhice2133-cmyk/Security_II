<?php
require_once '../php/config.php';

if (!isset($_SESSION['user_id'])) {
    requireAuth404();
}

// Fetch current user data
$db = new Database();
$stmt = $db->prepare('SELECT username, email, address, phone, phone_number, first_name, middle_name, last_name, extension, birth_date, age, sex, purok_street, barangay, city, province, country, profile_picture, role, is_active, created_at, status FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) {
    requireAuth404();
}
if ($user['status'] === 'pending_setup') {
    header('Location: initial-setup.php');
    exit;
}

$phoneValue = $user['phone_number'] ?? $user['phone'] ?? '';
$birthDateValue = $user['birth_date'] ?? '';
$computedAge = $user['age'] ?? null;
if ($birthDateValue) {
    try {
        $birthDateObj = new DateTime($birthDateValue);
        $now = new DateTime();
        $ageDiff = $birthDateObj->diff($now)->y;
        $computedAge = $ageDiff;
    } catch (Exception $e) {
        $computedAge = $user['age'] ?? null;
    }
}
$countries = [
    'Afghanistan','Albania','Algeria','Andorra','Angola','Antigua and Barbuda','Argentina','Armenia','Australia','Austria','Azerbaijan','Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bhutan','Bolivia','Bosnia and Herzegovina','Botswana','Brazil','Brunei','Bulgaria','Burkina Faso','Burundi','Cabo Verde','Cambodia','Cameroon','Canada','Central African Republic','Chad','Chile','China','Colombia','Comoros','Congo','Costa Rica','Croatia','Cuba','Cyprus','Czech Republic','Denmark','Djibouti','Dominica','Dominican Republic','Ecuador','Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Eswatini','Ethiopia','Fiji','Finland','France','Gabon','Gambia','Georgia','Germany','Ghana','Greece','Grenada','Guatemala','Guinea','Guinea-Bissau','Guyana','Haiti','Honduras','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Jamaica','Japan','Jordan','Kazakhstan','Kenya','Kiribati','Kosovo','Kuwait','Kyrgyzstan','Laos','Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg','Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Marshall Islands','Mauritania','Mauritius','Mexico','Micronesia','Moldova','Monaco','Mongolia','Montenegro','Morocco','Mozambique','Myanmar','Namibia','Nauru','Nepal','Netherlands','New Zealand','Nicaragua','Niger','Nigeria','North Korea','North Macedonia','Norway','Oman','Pakistan','Palau','Palestine','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Poland','Portugal','Qatar','Romania','Russia','Rwanda','Saint Kitts and Nevis','Saint Lucia','Saint Vincent and the Grenadines','Samoa','San Marino','Sao Tome and Principe','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia','Solomon Islands','Somalia','South Africa','South Korea','South Sudan','Spain','Sri Lanka','Sudan','Suriname','Sweden','Switzerland','Syria','Taiwan','Tajikistan','Tanzania','Thailand','Timor-Leste','Togo','Tonga','Trinidad and Tobago','Tunisia','Turkey','Turkmenistan','Tuvalu','Uganda','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Uzbekistan','Vanuatu','Vatican City','Venezuela','Vietnam','Yemen','Zambia','Zimbabwe'
];
$userCountry = $user['country'] ?? 'Philippines';
if (!$userCountry) {
    $userCountry = 'Philippines';
}
if (!in_array($userCountry, $countries, true)) {
    $countries[] = $userCountry;
    sort($countries);
}
$fullNameParts = array_filter([$user['first_name'] ?? '', $user['middle_name'] ?? '', $user['last_name'] ?? '', $user['extension'] ?? ''], fn($part) => $part !== null && trim($part) !== '');
$displayName = implode(' ', $fullNameParts);
$locationParts = array_filter([
    $user['purok_street'] ?? '',
    $user['barangay'] ?? '',
    $user['city'] ?? '',
    $user['province'] ?? '',
    $userCountry
], fn($part) => $part !== null && trim($part) !== '');
$displayLocation = implode(', ', $locationParts);
$addressValue = $user['address'] ?? '';
if (trim($addressValue) === '' && $displayLocation !== '') {
    $addressValue = $displayLocation;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>" />
  <title>Edit Profile - CyberAuth System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Rajdhani:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/cyberpunk-edit-profile.css" />
</head>
<body>
  <div class="cyber-grid" aria-hidden="true"></div>
  <div class="neon-particles" aria-hidden="true"></div>
  <div class="scan-lines" aria-hidden="true"></div>

  <header class="cyber-header">
    <div class="header-content">
      <div class="logo-container">
        <div class="logo-icon">⚡</div>
        <h1 class="logo-text">EDIT <span class="accent">PROFILE</span></h1>
      </div>
      <div class="header-actions">
        <a href="dashboard.php" class="nav-link cyber-link">BACK TO DASHBOARD</a>
        <a href="../php/logout.php" class="nav-link cyber-link">LOGOUT</a>
      </div>
    </div>
  </header>

  <main class="cyber-main">
    <div class="profile-wrapper">
      <section class="profile-panel snapshot-panel">
        <header class="panel-header">
          <div class="panel-title">
            <span class="icon">👤</span>
            <span>Profile Information</span>
          </div>
          <span class="panel-badge">Editable</span>
        </header>

        <form id="editProfileForm" class="form-flow" novalidate>
          <div id="msg" class="status-message" role="status" aria-live="polite"></div>

          <div class="form-section">
            <h3 class="section-subtitle">Identity</h3>
            <div class="form-grid two-col">
              <div class="input-group">
                <label class="cyber-label" for="firstName">
                  <span class="label-icon">🪪</span>
                  First Name
                </label>
                <div class="input-shell">
                  <input id="firstName" name="firstName" class="cyber-input" type="text" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" autocomplete="given-name" required>
                </div>
                <div class="error-message" id="firstNameError"></div>
              </div>

              <div class="input-group">
                <label class="cyber-label" for="middleName">
                  <span class="label-icon">⚙️</span>
                  Middle Name <span class="info-hint">(optional)</span>
                </label>
                <div class="input-shell">
                  <input id="middleName" name="middleName" class="cyber-input" type="text" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>" autocomplete="additional-name">
                </div>
                <div class="error-message" id="middleNameError"></div>
              </div>

              <div class="input-group">
                <label class="cyber-label" for="lastName">
                  <span class="label-icon">🧬</span>
                  Last Name
                </label>
                <div class="input-shell">
                  <input id="lastName" name="lastName" class="cyber-input" type="text" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" autocomplete="family-name" required>
                </div>
                <div class="error-message" id="lastNameError"></div>
              </div>

              <div class="input-group">
                <label class="cyber-label" for="extension">
                  <span class="label-icon">🔗</span>
                  Extension <span class="info-hint">(Jr, Sr, II, III, IV, V)</span>
                </label>
                <div class="input-shell">
                  <input id="extension" name="extension" class="cyber-input" type="text" value="<?php echo htmlspecialchars($user['extension'] ?? ''); ?>" maxlength="3">
                </div>
                <div class="error-message" id="extensionError"></div>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h3 class="section-subtitle">Account Access</h3>
            <div class="form-grid two-col">
              <div class="input-group">
                <label class="cyber-label" for="username">
                  <span class="label-icon">👾</span>
                  Username
                </label>
                <div class="input-shell">
                  <input id="username" name="username" class="cyber-input" type="text" value="<?php echo htmlspecialchars($user['username']); ?>" autocomplete="username" required>
                </div>
                <div class="info-hint">Only letters, numbers, and underscores. Min 3 characters.</div>
                <div class="error-message" id="usernameError"></div>
              </div>

              <div class="input-group">
                <label class="cyber-label" for="email">
                  <span class="label-icon">📧</span>
                  Email Address
                </label>
                <div class="input-shell">
                  <input id="email" name="email" class="cyber-input" type="email" value="<?php echo htmlspecialchars($user['email']); ?>" autocomplete="email" readonly>
                </div>
                <div class="info-hint">Email is locked for security reasons.</div>
              </div>

              <div class="input-group">
                <label class="cyber-label" for="phone">
                  <span class="label-icon">📱</span>
                  Phone Number
                </label>
                <div class="input-shell">
                  <input id="phone" name="phone" class="cyber-input" type="tel" value="<?php echo htmlspecialchars($phoneValue); ?>" placeholder="09XXXXXXXXX" autocomplete="tel-national" required>
                </div>
                <div class="error-message" id="phoneError"></div>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h3 class="section-subtitle">Profile Details</h3>
            <div class="form-grid three-col">
              <div class="input-group">
                <label class="cyber-label" for="birthDate">
                  <span class="label-icon">🎂</span>
                  Birth Date
                </label>
                <div class="input-shell">
                  <input id="birthDate" name="birthDate" class="cyber-input" type="date" value="<?php echo htmlspecialchars($birthDateValue); ?>" required>
                </div>
                <div class="error-message" id="birthDateError"></div>
              </div>

              <div class="input-group">
                <label class="cyber-label" for="age">
                  <span class="label-icon">🔢</span>
                  Age
                </label>
                <div class="input-shell">
                  <input id="age" name="age" class="cyber-input" type="number" value="<?php echo htmlspecialchars($computedAge ?? ''); ?>" readonly>
                </div>
                <div class="info-hint">Age updates automatically from birth date.</div>
                <div class="error-message" id="ageError"></div>
              </div>

              <div class="input-group">
                <label class="cyber-label" for="sex">
                  <span class="label-icon">⚧</span>
                  Sex
                </label>
                <div class="input-shell">
                  <select id="sex" name="sex" class="cyber-input" required>
                    <option value="">- Select Sex -</option>
                    <option value="male" <?php echo (($user['sex'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo (($user['sex'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?php echo (($user['sex'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                  </select>
                </div>
                <div class="error-message" id="sexError"></div>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h3 class="section-subtitle">Location</h3>
            <div class="form-grid two-col">
              <div class="input-group">
                <label class="cyber-label" for="country">
                  <span class="label-icon">🌍</span>
                  Country
                </label>
                <div class="input-shell">
                  <select id="country" name="country" class="cyber-input" required>
                    <option value="">- Select Country -</option>
                    <?php foreach ($countries as $countryOption): ?>
                      <option value="<?php echo htmlspecialchars($countryOption); ?>" <?php echo ($countryOption === $userCountry) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($countryOption); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="error-message" id="countryError"></div>
              </div>

              <div class="input-group">
                <label class="cyber-label" for="province">
                  <span class="label-icon">🗺️</span>
                  Province
                </label>
                <div class="input-shell">
                  <input id="province" name="province" class="cyber-input" type="text" value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>" required>
                </div>
                <div class="error-message" id="provinceError"></div>
              </div>

              <div class="input-group">
                <label class="cyber-label" for="city">
                  <span class="label-icon">🏙️</span>
                  City / Municipality
                </label>
                <div class="input-shell">
                  <input id="city" name="city" class="cyber-input" type="text" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                </div>
                <div class="error-message" id="cityError"></div>
              </div>

              <div class="input-group">
                <label class="cyber-label" for="barangay">
                  <span class="label-icon">🏘️</span>
                  Barangay
                </label>
                <div class="input-shell">
                  <input id="barangay" name="barangay" class="cyber-input" type="text" value="<?php echo htmlspecialchars($user['barangay'] ?? ''); ?>" required>
                </div>
                <div class="error-message" id="barangayError"></div>
              </div>

              <div class="input-group">
                <label class="cyber-label" for="purok">
                  <span class="label-icon">🏠</span>
                  Purok / Street
                </label>
                <div class="input-shell">
                  <input id="purok" name="purok" class="cyber-input" type="text" value="<?php echo htmlspecialchars($user['purok_street'] ?? ''); ?>" required>
                </div>
                <div class="error-message" id="purokError"></div>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h3 class="section-subtitle">Address</h3>
            <div class="form-grid">
              <div class="input-group full-width">
                <label class="cyber-label" for="address">
                  <span class="label-icon">🗃️</span>
                  Full Address
                </label>
                <div class="input-shell">
                  <textarea id="address" name="address" class="cyber-textarea" rows="3" placeholder="Enter your complete address" autocomplete="street-address"><?php echo htmlspecialchars($addressValue); ?></textarea>
                </div>
                <div class="error-message" id="addressError"></div>
              </div>
            </div>
          </div>

          <div class="action-bar">
            <button type="submit" id="submitBtn" class="submit-btn">
              <span>🔄</span>
              <span>Save Changes</span>
            </button>
          </div>
        </form>
      </section>

      <section class="profile-panel">
        <header class="panel-header">
          <div class="panel-title">
            <span class="icon">ℹ️</span>
            <span>Account Snapshot</span>
          </div>
          <span class="panel-badge">Overview</span>
        </header>

        <div class="avatar-frame">
          <div class="avatar"><?php echo strtoupper(substr($displayName ?: $user['username'], 0, 1)); ?></div>
          <div class="detail-value">
            <?php echo htmlspecialchars($displayName ?: $user['username']); ?>
          </div>
        </div>

        <div class="profile-details">
          <div class="detail-card">
            <span class="detail-label">Username</span>
            <span class="detail-value"><?php echo htmlspecialchars($user['username']); ?></span>
          </div>
          <div class="detail-card">
            <span class="detail-label">Email</span>
            <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
          </div>
          <div class="detail-card">
            <span class="detail-label">Phone</span>
            <span class="detail-value"><?php echo htmlspecialchars($phoneValue !== '' ? $phoneValue : '—'); ?></span>
          </div>
          <div class="detail-card">
            <span class="detail-label">Role</span>
            <span class="detail-value"><?php echo htmlspecialchars(ucfirst($user['role'] ?? 'user')); ?></span>
          </div>
          <div class="detail-card">
            <span class="detail-label">Status</span>
            <span class="status-badge"><?php echo ($user['is_active'] ? 'Active' : 'Inactive'); ?></span>
          </div>
          <div class="detail-card">
            <span class="detail-label">Location</span>
            <span class="detail-value"><?php echo htmlspecialchars($displayLocation !== '' ? $displayLocation : '—'); ?></span>
          </div>
          <div class="detail-card">
            <span class="detail-label">Member Since</span>
            <span class="detail-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
          </div>
        </div>
      </section>
    </div>
  </main>

  <script src="../js/cyberpunk-edit-profile.js"></script>
  <script src="../js/disable-rightclick.js"></script>
</body>
</html>