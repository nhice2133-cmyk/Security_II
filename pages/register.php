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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBER REGISTRATION - Neural Network Access</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/cyberpunk-register.css">
</head>
<body>
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
            <div class="form-panel">
                <div class="cyber-badge">
                    <span class="badge-text">SECURE</span>
                    <span class="badge-accent">ENCRYPTED</span>
                </div>
                <h1 class="cyber-headline">NEURAL REGISTRATION</h1>
                <p class="cyber-lead">Initialize your digital identity in the cyber realm</p>

                <!-- Terminal Frame -->
                <div class="terminal-frame">
                    <div class="terminal-header">
                        <div class="terminal-dots">
                            <div class="dot red"></div>
                            <div class="dot yellow"></div>
                            <div class="dot green"></div>
                        </div>
                        <div class="terminal-title">NEURAL_REGISTRATION.exe</div>
                    </div>
                    
                    <div class="terminal-screen">
                        <div class="terminal-content">
                            <div class="terminal-prompt">
                                <span class="prompt-text">user@neuralnet:~$</span>
                                <span class="cursor-blink">_</span>
                            </div>
                            
                            <div class="registration-section">
                                <h2 class="terminal-title">INITIALIZE USER PROFILE</h2>
                                <p class="terminal-subtitle">Enter your neural network credentials</p>
                                
                                <form class="cyber-form" id="registrationForm" novalidate>
                                    <div class="form-grid">
                                        <!-- Personal Information Section -->
                                        <div class="form-section">
                                            <h3 class="section-title">IDENTITY</h3>
                                            
                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">🆔</span>
                                                    ID NUMBER<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="idNumber" name="idNumber" placeholder="YYYY-XXXX" readonly>
                                                    <span class="field-icon" id="idNumberIcon" style="display: none;">🔒</span>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="idError"></div>
                                            </div>

                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">👤</span>
                                                    LAST NAME<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="lastName" name="lastName">
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="lastNameError"></div>
                                            </div>

                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">👤</span>
                                                    FIRST NAME<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="firstName" name="firstName">
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="firstNameError"></div>
                                            </div>

                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">👤</span>
                                                    MIDDLE NAME <span class="optional">(optional)</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="middleName" name="middleName">
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="middleNameError"></div>
                                            </div>

                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">🏷️</span>
                                                    EXTENSION <span class="optional">(optional)</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="extension" name="extension" placeholder="Jr, Sr, II, III, IV, V">
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="extensionError"></div>
                                            </div>
                                        </div>

                                        <!-- Personal Details Section -->
                                        <div class="form-section">
                                            <h3 class="section-title">PROFILE</h3>
                                            
                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">📅</span>
                                                    BIRTH DATE<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="date" class="cyber-input" id="birthDate" name="birthDate" >
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="birthDateError"></div>
                                            </div>

                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">🔢</span>
                                                    AGE<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="number" class="cyber-input" id="age" name="age" min="1" max="120" required readonly style="pointer-events: none; cursor: not-allowed;">
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="ageError"></div>
                                            </div>

                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">⚧</span>
                                                    SEX<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <select class="cyber-input" id="sex" name="sex" required>
                                                        <option value="">- Select Sex -</option>
                                                        <option value="male">Male</option>
                                                        <option value="female">Female</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="sexError"></div>
                                            </div>
                                        </div>

                                        <!-- Security Section -->
                                        <div class="form-section">
                                            <h3 class="section-title">SECURITY</h3>
                                            
                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">📱</span>
                                                    PHONE NUMBER<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="phoneNumber" maxlength="11" name="phone_number" placeholder="09 XXX XXX XXXX" required>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="phoneNumberError"></div>
                                            </div>

                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">📧</span>
                                                    EMAIL<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="email" class="cyber-input" id="email" name="email" required>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="emailError"></div>
                                            </div>

                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">👤</span>
                                                    USERNAME<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="username" name="username" placeholder="john.1" required>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="usernameError"></div>
                                            </div>

                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">🔐</span>
                                                    PASSWORD<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="password" class="cyber-input" id="password" name="password" required>
                                                    <button type="button" class="show-password" style="background:none; border:none; color:var(--cyber-primary); filter:drop-shadow(0 0 5px rgba(14,165,233,0.5)); cursor:pointer;" onclick="togglePassword(this.previousElementSibling.id, this)"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="passwordError"></div>
                                                <div class="password-strength" id="passwordStrength"></div>
                                            </div>

                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">🔐</span>
                                                    RE-ENTER PASSWORD<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="password" class="cyber-input" id="confirmPassword" name="confirm_password" required>
                                                    <button type="button" class="show-password" style="background:none; border:none; color:var(--cyber-primary); filter:drop-shadow(0 0 5px rgba(14,165,233,0.5)); cursor:pointer;" onclick="togglePassword(this.previousElementSibling.id, this)"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="confirmPasswordError"></div>
                                                <div class="success-message" id="confirmPasswordSuccess"></div>
                                            </div>


                                        </div>

                                        <!-- Address Section -->
                                        <div class="form-section">
                                            <h3 class="section-title">ADDRESS</h3>
                                            <!-- Country field FIRST -->
                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">🌍</span>
                                                    COUNTRY<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <select class="cyber-input" id="country" name="country" required>
                                                        <option value="">- Select Country -</option>
                                                        <option value="Afghanistan">Afghanistan</option>
                                                        <option value="Albania">Albania</option>
                                                        <option value="Algeria">Algeria</option>
                                                        <option value="Andorra">Andorra</option>
                                                        <option value="Angola">Angola</option>
                                                        <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                                                        <option value="Argentina">Argentina</option>
                                                        <option value="Armenia">Armenia</option>
                                                        <option value="Australia">Australia</option>
                                                        <option value="Austria">Austria</option>
                                                        <option value="Azerbaijan">Azerbaijan</option>
                                                        <option value="Bahamas">Bahamas</option>
                                                        <option value="Bahrain">Bahrain</option>
                                                        <option value="Bangladesh">Bangladesh</option>
                                                        <option value="Barbados">Barbados</option>
                                                        <option value="Belarus">Belarus</option>
                                                        <option value="Belgium">Belgium</option>
                                                        <option value="Belize">Belize</option>
                                                        <option value="Benin">Benin</option>
                                                        <option value="Bhutan">Bhutan</option>
                                                        <option value="Bolivia">Bolivia</option>
                                                        <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                                                        <option value="Botswana">Botswana</option>
                                                        <option value="Brazil">Brazil</option>
                                                        <option value="Brunei">Brunei</option>
                                                        <option value="Bulgaria">Bulgaria</option>
                                                        <option value="Burkina Faso">Burkina Faso</option>
                                                        <option value="Burundi">Burundi</option>
                                                        <option value="Cabo Verde">Cabo Verde</option>
                                                        <option value="Cambodia">Cambodia</option>
                                                        <option value="Cameroon">Cameroon</option>
                                                        <option value="Canada">Canada</option>
                                                        <option value="Central African Republic">Central African Republic</option>
                                                        <option value="Chad">Chad</option>
                                                        <option value="Chile">Chile</option>
                                                        <option value="China">China</option>
                                                        <option value="Colombia">Colombia</option>
                                                        <option value="Comoros">Comoros</option>
                                                        <option value="Congo">Congo</option>
                                                        <option value="Costa Rica">Costa Rica</option>
                                                        <option value="Croatia">Croatia</option>
                                                        <option value="Cuba">Cuba</option>
                                                        <option value="Cyprus">Cyprus</option>
                                                        <option value="Czech Republic">Czech Republic</option>
                                                        <option value="Denmark">Denmark</option>
                                                        <option value="Djibouti">Djibouti</option>
                                                        <option value="Dominica">Dominica</option>
                                                        <option value="Dominican Republic">Dominican Republic</option>
                                                        <option value="Ecuador">Ecuador</option>
                                                        <option value="Egypt">Egypt</option>
                                                        <option value="El Salvador">El Salvador</option>
                                                        <option value="Equatorial Guinea">Equatorial Guinea</option>
                                                        <option value="Eritrea">Eritrea</option>
                                                        <option value="Estonia">Estonia</option>
                                                        <option value="Eswatini">Eswatini</option>
                                                        <option value="Ethiopia">Ethiopia</option>
                                                        <option value="Fiji">Fiji</option>
                                                        <option value="Finland">Finland</option>
                                                        <option value="France">France</option>
                                                        <option value="Gabon">Gabon</option>
                                                        <option value="Gambia">Gambia</option>
                                                        <option value="Georgia">Georgia</option>
                                                        <option value="Germany">Germany</option>
                                                        <option value="Ghana">Ghana</option>
                                                        <option value="Greece">Greece</option>
                                                        <option value="Grenada">Grenada</option>
                                                        <option value="Guatemala">Guatemala</option>
                                                        <option value="Guinea">Guinea</option>
                                                        <option value="Guinea-Bissau">Guinea-Bissau</option>
                                                        <option value="Guyana">Guyana</option>
                                                        <option value="Haiti">Haiti</option>
                                                        <option value="Honduras">Honduras</option>
                                                        <option value="Hungary">Hungary</option>
                                                        <option value="Iceland">Iceland</option>
                                                        <option value="India">India</option>
                                                        <option value="Indonesia">Indonesia</option>
                                                        <option value="Iran">Iran</option>
                                                        <option value="Iraq">Iraq</option>
                                                        <option value="Ireland">Ireland</option>
                                                        <option value="Israel">Israel</option>
                                                        <option value="Italy">Italy</option>
                                                        <option value="Jamaica">Jamaica</option>
                                                        <option value="Japan">Japan</option>
                                                        <option value="Jordan">Jordan</option>
                                                        <option value="Kazakhstan">Kazakhstan</option>
                                                        <option value="Kenya">Kenya</option>
                                                        <option value="Kiribati">Kiribati</option>
                                                        <option value="Kosovo">Kosovo</option>
                                                        <option value="Kuwait">Kuwait</option>
                                                        <option value="Kyrgyzstan">Kyrgyzstan</option>
                                                        <option value="Laos">Laos</option>
                                                        <option value="Latvia">Latvia</option>
                                                        <option value="Lebanon">Lebanon</option>
                                                        <option value="Lesotho">Lesotho</option>
                                                        <option value="Liberia">Liberia</option>
                                                        <option value="Libya">Libya</option>
                                                        <option value="Liechtenstein">Liechtenstein</option>
                                                        <option value="Lithuania">Lithuania</option>
                                                        <option value="Luxembourg">Luxembourg</option>
                                                        <option value="Madagascar">Madagascar</option>
                                                        <option value="Malawi">Malawi</option>
                                                        <option value="Malaysia">Malaysia</option>
                                                        <option value="Maldives">Maldives</option>
                                                        <option value="Mali">Mali</option>
                                                        <option value="Malta">Malta</option>
                                                        <option value="Marshall Islands">Marshall Islands</option>
                                                        <option value="Mauritania">Mauritania</option>
                                                        <option value="Mauritius">Mauritius</option>
                                                        <option value="Mexico">Mexico</option>
                                                        <option value="Micronesia">Micronesia</option>
                                                        <option value="Moldova">Moldova</option>
                                                        <option value="Monaco">Monaco</option>
                                                        <option value="Mongolia">Mongolia</option>
                                                        <option value="Montenegro">Montenegro</option>
                                                        <option value="Morocco">Morocco</option>
                                                        <option value="Mozambique">Mozambique</option>
                                                        <option value="Myanmar">Myanmar</option>
                                                        <option value="Namibia">Namibia</option>
                                                        <option value="Nauru">Nauru</option>
                                                        <option value="Nepal">Nepal</option>
                                                        <option value="Netherlands">Netherlands</option>
                                                        <option value="New Zealand">New Zealand</option>
                                                        <option value="Nicaragua">Nicaragua</option>
                                                        <option value="Niger">Niger</option>
                                                        <option value="Nigeria">Nigeria</option>
                                                        <option value="North Korea">North Korea</option>
                                                        <option value="North Macedonia">North Macedonia</option>
                                                        <option value="Norway">Norway</option>
                                                        <option value="Oman">Oman</option>
                                                        <option value="Pakistan">Pakistan</option>
                                                        <option value="Palau">Palau</option>
                                                        <option value="Palestine">Palestine</option>
                                                        <option value="Panama">Panama</option>
                                                        <option value="Papua New Guinea">Papua New Guinea</option>
                                                        <option value="Paraguay">Paraguay</option>
                                                        <option value="Peru">Peru</option>
                                                        <option value="Philippines" selected>Philippines</option>
                                                        <option value="Poland">Poland</option>
                                                        <option value="Portugal">Portugal</option>
                                                        <option value="Qatar">Qatar</option>
                                                        <option value="Romania">Romania</option>
                                                        <option value="Russia">Russia</option>
                                                        <option value="Rwanda">Rwanda</option>
                                                        <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                                                        <option value="Saint Lucia">Saint Lucia</option>
                                                        <option value="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option>
                                                        <option value="Samoa">Samoa</option>
                                                        <option value="San Marino">San Marino</option>
                                                        <option value="Sao Tome and Principe">Sao Tome and Principe</option>
                                                        <option value="Saudi Arabia">Saudi Arabia</option>
                                                        <option value="Senegal">Senegal</option>
                                                        <option value="Serbia">Serbia</option>
                                                        <option value="Seychelles">Seychelles</option>
                                                        <option value="Sierra Leone">Sierra Leone</option>
                                                        <option value="Singapore">Singapore</option>
                                                        <option value="Slovakia">Slovakia</option>
                                                        <option value="Slovenia">Slovenia</option>
                                                        <option value="Solomon Islands">Solomon Islands</option>
                                                        <option value="Somalia">Somalia</option>
                                                        <option value="South Africa">South Africa</option>
                                                        <option value="South Korea">South Korea</option>
                                                        <option value="South Sudan">South Sudan</option>
                                                        <option value="Spain">Spain</option>
                                                        <option value="Sri Lanka">Sri Lanka</option>
                                                        <option value="Sudan">Sudan</option>
                                                        <option value="Suriname">Suriname</option>
                                                        <option value="Sweden">Sweden</option>
                                                        <option value="Switzerland">Switzerland</option>
                                                        <option value="Syria">Syria</option>
                                                        <option value="Taiwan">Taiwan</option>
                                                        <option value="Tajikistan">Tajikistan</option>
                                                        <option value="Tanzania">Tanzania</option>
                                                        <option value="Thailand">Thailand</option>
                                                        <option value="Timor-Leste">Timor-Leste</option>
                                                        <option value="Togo">Togo</option>
                                                        <option value="Tonga">Tonga</option>
                                                        <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                                                        <option value="Tunisia">Tunisia</option>
                                                        <option value="Turkey">Turkey</option>
                                                        <option value="Turkmenistan">Turkmenistan</option>
                                                        <option value="Tuvalu">Tuvalu</option>
                                                        <option value="Uganda">Uganda</option>
                                                        <option value="Ukraine">Ukraine</option>
                                                        <option value="United Arab Emirates">United Arab Emirates</option>
                                                        <option value="United Kingdom">United Kingdom</option>
                                                        <option value="United States">United States</option>
                                                        <option value="Uruguay">Uruguay</option>
                                                        <option value="Uzbekistan">Uzbekistan</option>
                                                        <option value="Vanuatu">Vanuatu</option>
                                                        <option value="Vatican City">Vatican City</option>
                                                        <option value="Venezuela">Venezuela</option>
                                                        <option value="Vietnam">Vietnam</option>
                                                        <option value="Yemen">Yemen</option>
                                                        <option value="Zambia">Zambia</option>
                                                        <option value="Zimbabwe">Zimbabwe</option>
                                                    </select>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="countryError"></div>
                                            </div>
                                            <!-- Province now here -->
                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">🗺️</span>
                                                    PROVINCE<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="province" name="province" required>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="provinceError"></div>
                                            </div>
                                            <!-- City -->
                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">🏙️</span>
                                                    CITY/MUNICIPALITY<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="city" name="city" required>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="cityError"></div>
                                            </div>
                                            <!-- Barangay now after city -->
                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">🏘️</span>
                                                    BARANGAY<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="barangay" name="barangay" required>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="barangayError"></div>
                                            </div>
                                            <!-- Purok last -->
                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">🏠</span>
                                                    PUROK/STREET<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="purok" name="purok" required>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="purokError"></div>
                                            </div>
                                            <!-- Zipcode -->
                                            <div class="input-group">
                                                <label class="cyber-label">
                                                    <span class="label-icon">📮</span>
                                                    ZIP CODE<span class="required-asterisk">*</span>
                                                </label>
                                                <div class="input-container">
                                                    <input type="text" class="cyber-input" id="zipCode" name="zipCode" maxlength="4" placeholder="1234" required>
                                                    <div class="input-glow"></div>
                                                </div>
                                                <div class="error-message" id="zipCodeError"></div>
                                            </div>
                                                </div>
                                            </div>

                                    <!-- Form Actions -->
                                    <div class="form-actions">
                                        <button type="submit" class="cyber-btn">
                                            <div class="btn-glow"></div>
                                            <span class="btn-text">INITIALIZE NEURAL PROFILE</span>
                                        </button>

                                        <div class="form-links">
                                            <p class="switch-form">
                                                Already have a neural profile? 
                                                <a href="login.php" class="cyber-link">LOGIN</a>
                                            </p>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
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
    <script src="../js/cyberpunk-register.js"></script>
    <!-- Auto-correction removed - warnings are shown instead via setupCapitalizationValidation() -->
</body>
</html>
