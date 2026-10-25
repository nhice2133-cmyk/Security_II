<?php
/**
 * Seeder Script: Generates and inserts 500 realistic random users into auth_system.
 * Compatible with XAMPP MySQL/MariaDB and auth_system schema.
 * Run via CLI: php db/seed_users.php
 */

require_once __DIR__ . '/../php/config.php';

$db = new Database();
$pdo = $db->getConnection();

echo "Starting 500-user database seeding...\n";

// 1. Load existing unique constraints to avoid conflicts
$existingUsernames = $pdo->query("SELECT LOWER(username) FROM users")->fetchAll(PDO::FETCH_COLUMN);
$existingUsernames = array_flip($existingUsernames);

$existingEmails = $pdo->query("SELECT LOWER(email) FROM users")->fetchAll(PDO::FETCH_COLUMN);
$existingEmails = array_flip($existingEmails);

$existingIds = $pdo->query("SELECT id_number FROM users WHERE id_number IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$existingIds = array_flip($existingIds);

// 2. Pre-hashed passwords (cost 10)
$userPasswordHash = '$2y$10$BB2YDJViQZX6lWd2TJ899.j7gjHG6hDLT8QMQlNzg3fJwxyrxJ/cu'; // User@1234
$adminPasswordHash = '$2y$10$dfsUFLafgWRrSwvtG8QoRuotX9iTWh7KYEqKJkhqN/B.t8BTxFTsa'; // Admin@12345!
$securityAnswerHash = '$2y$10$dP.HcyiIITqAifWHuk4ciOejHFTB.IFl0TQSYPwfJ0KyQdlokNSxe'; // admin

$adminPrivileges = json_encode([
    "change_password",
    "profile_management",
    "view_accounts",
    "edit_manage_accounts",
    "block_unblock_users",
    "registration_approval",
    "account_deletion_requests"
]);

$userPrivileges = json_encode([
    "change_password",
    "profile_management"
]);

// 3. Name pools
$maleFirstNames = [
    'Juan', 'Jose', 'Mark', 'John', 'Michael', 'Christian', 'Angelo', 'Daniel', 'Joshua', 'Gabriel',
    'Carl', 'Paolo', 'Andrei', 'Miguel', 'Kevin', 'Vincent', 'Rafael', 'Jericho', 'Justin', 'Dominic',
    'Anthony', 'Francis', 'Adrian', 'Kenneth', 'Patrick', 'Neil', 'Bryan', 'Carlo', 'Kyle', 'Gerald',
    'Lester', 'Ramon', 'Eduardo', 'Ricardo', 'Fernando', 'Manuel', 'Antonio', 'Rodolfo', 'Jaime', 'Rolando',
    'Renato', 'Ernesto', 'Danilo', 'Edgar', 'Romeo', 'Rogelio', 'Allan', 'Arnel', 'Joel', 'Reynaldo',
    'Alexander', 'Christopher', 'David', 'James', 'Joseph', 'Matthew', 'Paul', 'Peter', 'Stephen', 'Timothy'
];

$femaleFirstNames = [
    'Maria', 'Mary', 'Angel', 'Angelica', 'Nicole', 'Christine', 'Princess', 'Camille', 'Kimberly', 'Bea',
    'Patricia', 'Stephanie', 'Andrea', 'Sofia', 'Katherine', 'Chloe', 'Joy', 'Alyssa', 'Samantha', 'Jasmine',
    'Grace', 'Rochelle', 'Erica', 'Althea', 'Katrina', 'Michelle', 'Rose', 'Marites', 'Rowena', 'Lourdes',
    'Corazon', 'Teresa', 'Carmela', 'Janice', 'Jennifer', 'Cheryl', 'Maricel', 'Glenda', 'Vilma', 'Nora',
    'Ana', 'Elena', 'Cristina', 'Bernadette', 'Diana', 'Elizabeth', 'Hannah', 'Irene', 'Jessica', 'Kristine',
    'Leah', 'Marian', 'Natasha', 'Paula', 'Rachel', 'Sarah', 'Trisha', 'Vanessa', 'Wendy', 'Yvonne'
];

$lastNames = [
    'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres', 'Tomas', 'Andrada',
    'Flores', 'Gonzales', 'Ramos', 'Perez', 'Villanueva', 'Castro', 'Rivera', 'Corpuz', 'Aquino', 'Mercado',
    'Delos Santos', 'Salazar', 'Navarro', 'Morales', 'Dela Cruz', 'Fernandez', 'Lopez', 'Castillo', 'Guinto', 'Soriano',
    'Domingo', 'Gutierrez', 'Valenzuela', 'Sarmiento', 'Pascual', 'Manalo', 'Enriquez', 'Aguilar', 'Tolentino', 'Padilla',
    'Cortez', 'De Leon', 'Magno', 'Tan', 'Lim', 'Chua', 'Sy', 'Alvarez', 'Mercado', 'Roxas',
    'Santiago', 'Villar', 'Salas', 'Bernardo', 'Cabrera', 'David', 'Estrada', 'Fajardo', 'Gallardo', 'Hidalgo',
    'Ilagan', 'Jimenez', 'Lagman', 'Miranda', 'Natividad', 'Olegario', 'Panganiban', 'Quizon', 'Robles', 'Salvador'
];

$streets = [
    'Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7',
    'Purok Sampaguita', 'Purok Rosas', 'Purok Narra', 'Purok Mabini', 'Purok Bonifacio',
    'Rizal Street', 'Magsaysay Avenue', 'Quezon Boulevard', 'Luna Street', 'Burgos Street',
    'National Highway', 'MacArthur Highway', 'Osmena Street', 'Lapu-Lapu Street'
];

// 4. Load addresses from address.json
$addressJsonPath = __DIR__ . '/../php/address.json';
$addressPool = [];
if (file_exists($addressJsonPath)) {
    $rawJson = file_get_contents($addressJsonPath);
    $addrData = json_decode($rawJson, true);
    if (is_array($addrData)) {
        foreach ($addrData as $regionName => $provinces) {
            if (!is_array($provinces)) continue;
            foreach ($provinces as $provinceName => $cities) {
                if (!is_array($cities)) continue;
                foreach ($cities as $cityName => $barangays) {
                    if (!is_array($barangays) || empty($barangays)) continue;
                    $addressPool[] = [
                        'province' => $provinceName,
                        'city' => $cityName,
                        'barangays' => $barangays
                    ];
                }
            }
        }
    }
}

// Fallback if addressPool is empty
if (empty($addressPool)) {
    $addressPool[] = [
        'province' => 'Agusan Del Norte',
        'city' => 'Cabadbaran',
        'barangays' => ['Poblacion', 'La Union', 'Mabini', 'Tolosa', 'Comagascas']
    ];
    $addressPool[] = [
        'province' => 'Metro Manila',
        'city' => 'Quezon City',
        'barangays' => ['Batasan Hills', 'Commonwealth', 'Holy Spirit', 'Bagong Silangan']
    ];
    $addressPool[] = [
        'province' => 'Cebu',
        'city' => 'Cebu City',
        'barangays' => ['Lahug', 'Mabolo', 'Guadalupe', 'Banilad', 'Talamban']
    ];
}

$domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'example.com', 'mail.ph'];

// 5. Prepare SQL statements
$stmtUser = $pdo->prepare("
    INSERT INTO users (
        username, email, id_number, password, phone_number, phone,
        first_name, last_name, middle_name, extension,
        birth_date, age, sex,
        purok_street, barangay, city, province, country, zip_code, address,
        profile_picture, role, status, is_active, is_former_superadmin, privileges,
        last_login, created_at, updated_at
    ) VALUES (
        :username, :email, :id_number, :password, :phone_number, :phone,
        :first_name, :last_name, :middle_name, :extension,
        :birth_date, :age, :sex,
        :purok_street, :barangay, :city, :province, :country, :zip_code, :address,
        :profile_picture, :role, :status, :is_active, :is_former_superadmin, :privileges,
        :last_login, :created_at, :updated_at
    )
");

$stmtSec = $pdo->prepare("
    INSERT INTO user_security_questions (
        user_id, question1, answer1, question2, answer2, question3, answer3, created_at, updated_at
    ) VALUES (
        :user_id, 'best_friend_elementary', :ans1, 'favorite_pet', :ans2, 'favorite_teacher', :ans3, :created_at, :updated_at
    )
");

$totalToSeed = 500;
$seededCount = 0;

$pdo->beginTransaction();

try {
    for ($i = 1; $i <= $totalToSeed; $i++) {
        // Gender & Names
        $isFemale = ($i % 2 === 0);
        $sex = $isFemale ? 'female' : 'male';
        $firstName = $isFemale 
            ? $femaleFirstNames[array_rand($femaleFirstNames)] 
            : $maleFirstNames[array_rand($maleFirstNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $middleName = $lastNames[array_rand($lastNames)];
        
        $extension = null;
        if (!$isFemale && rand(1, 10) === 1) {
            $exts = ['Jr.', 'III', 'II'];
            $extension = $exts[array_rand($exts)];
        }

        // Clean names for username
        $cleanFirst = preg_replace('/[^a-z0-9]/', '', strtolower($firstName));
        $cleanLast = preg_replace('/[^a-z0-9]/', '', strtolower($lastName));

        // Generate Unique Username
        $unameAttempt = 0;
        do {
            $unameAttempt++;
            if ($unameAttempt === 1) {
                $candidateUname = $cleanFirst . '.' . $cleanLast;
            } elseif ($unameAttempt === 2) {
                $candidateUname = substr($cleanFirst, 0, 1) . $cleanLast . rand(10, 99);
            } else {
                $candidateUname = $cleanFirst . '.' . $cleanLast . rand(100, 9999);
            }
        } while (isset($existingUsernames[$candidateUname]));
        $existingUsernames[$candidateUname] = true;
        $username = $candidateUname;

        // Generate Unique Email
        $emailDomain = $domains[array_rand($domains)];
        $emailCandidate = $username . '@' . $emailDomain;
        $emailAttempt = 0;
        while (isset($existingEmails[$emailCandidate])) {
            $emailAttempt++;
            $emailCandidate = $username . $emailAttempt . '@' . $emailDomain;
        }
        $existingEmails[$emailCandidate] = true;
        $email = $emailCandidate;

        // Generate Unique ID Number (Format: 2026-XXXX)
        do {
            $randIdNum = '2026-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        } while (isset($existingIds[$randIdNum]));
        $existingIds[$randIdNum] = true;
        $idNumber = $randIdNum;

        // Birth date (between 1968 and 2005) & Age
        $birthYear = rand(1968, 2005);
        $birthMonth = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $birthDay = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $birthDate = "$birthYear-$birthMonth-$birthDay";
        $age = 2026 - $birthYear;

        // Phone number
        $phone = '09' . str_pad(rand(10000000, 99999999), 9, '0', STR_PAD_LEFT);

        // Address
        $addrLoc = $addressPool[array_rand($addressPool)];
        $province = $addrLoc['province'];
        $city = $addrLoc['city'];
        $barangay = $addrLoc['barangays'][array_rand($addrLoc['barangays'])];
        $purok = $streets[array_rand($streets)];
        $zipCode = (string)rand(1000, 9800);
        $fullAddress = "$purok, $barangay, $city, $province, Philippines";

        // Role: ~25 admins (5%), 475 users (95%)
        $role = ($i <= 25) ? 'admin' : 'user';

        // Status & Active state
        // 420 approved (active), 35 pending_setup, 25 inactive, 20 blocked
        if ($i <= 420) {
            $status = 'approved';
            $isActive = 1;
        } elseif ($i <= 455) {
            $status = 'pending_setup';
            $isActive = 1;
        } elseif ($i <= 480) {
            $status = 'inactive';
            $isActive = 0;
        } else {
            $status = 'blocked';
            $isActive = 0;
        }

        $passwordHash = ($role === 'admin') ? $adminPasswordHash : $userPasswordHash;
        $privileges = ($role === 'admin') ? $adminPrivileges : $userPrivileges;

        // Timestamps (within last 365 days)
        $createdOffset = rand(1, 365 * 86400);
        $createdAtTime = time() - $createdOffset;
        $createdAt = date('Y-m-d H:i:s', $createdAtTime);

        // Last Login (70% have logged in since created_at)
        $lastLogin = null;
        if ($status === 'approved' && rand(1, 10) <= 7) {
            $loginTime = rand($createdAtTime, time());
            $lastLogin = date('Y-m-d H:i:s', $loginTime);
        }

        // Insert User
        $stmtUser->execute([
            ':username' => $username,
            ':email' => $email,
            ':id_number' => $idNumber,
            ':password' => $passwordHash,
            ':phone_number' => $phone,
            ':phone' => $phone,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':middle_name' => $middleName,
            ':extension' => $extension,
            ':birth_date' => $birthDate,
            ':age' => $age,
            ':sex' => $sex,
            ':purok_street' => $purok,
            ':barangay' => $barangay,
            ':city' => $city,
            ':province' => $province,
            ':country' => 'Philippines',
            ':zip_code' => $zipCode,
            ':address' => $fullAddress,
            ':profile_picture' => null,
            ':role' => $role,
            ':status' => $status,
            ':is_active' => $isActive,
            ':is_former_superadmin' => 0,
            ':privileges' => $privileges,
            ':last_login' => $lastLogin,
            ':created_at' => $createdAt,
            ':updated_at' => $createdAt
        ]);

        $userId = $pdo->lastInsertId();

        // Insert Security Questions
        $stmtSec->execute([
            ':user_id' => $userId,
            ':ans1' => $securityAnswerHash,
            ':ans2' => $securityAnswerHash,
            ':ans3' => $securityAnswerHash,
            ':created_at' => $createdAt,
            ':updated_at' => $createdAt
        ]);

        $seededCount++;
    }

    $pdo->commit();
    echo "SUCCESS: Successfully seeded {$seededCount} users with security questions!\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR during seeding: " . $e->getMessage() . "\n";
    exit(1);
}

// 6. Report Summary
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
echo "Total users in database now: {$totalUsers}\n";
$rolesBreakdown = $pdo->query("SELECT role, COUNT(*) as c FROM users GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
echo "Roles breakdown: " . json_encode($rolesBreakdown) . "\n";
$statusBreakdown = $pdo->query("SELECT status, COUNT(*) as c FROM users GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
echo "Statuses breakdown: " . json_encode($statusBreakdown) . "\n";
