<?php
require_once '../php/config.php';

// Validate session and show 404 if not authenticated
if (!validateSession()) {
    requireAuth404();
}

$db = new Database();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    endSession();
    header('Location: login.php');
    exit;
}

$role = $user['role'];
$_SESSION['role'] = $role; // refresh session role

if (($user['status'] ?? '') === 'pending_setup') {
    header('Location: initial-setup.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php // CSRF token meta tag — read by admin.js to protect all state-changing POST requests ?>
  <meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>" />
  <title>Dashboard - CyberAuth System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Rajdhani:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/cyberpunk-login.css" />
  <link rel="stylesheet" href="../css/cyberpunk-portal.css" />
  <style>
    :root { --cyber-surface-2: rgba(0, 0, 0, 0.5); }
    .cyber-main { height: auto; min-height: calc(100vh - 140px); margin-top: 80px; display: block; }
    .dash-wrap { max-width: 1400px; margin: 0 auto; padding: 0 2rem 2rem; display: grid; grid-template-columns: 250px 1fr; gap: 1.5rem; align-items: start; }
    .welcome { grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; gap: 0.8rem; border: 1px solid var(--cyber-primary); border-radius: 10px; padding: 0.9rem 1rem; background: linear-gradient(135deg, rgba(26,26,26,0.85), rgba(0,0,0,0.95)); box-shadow: 0 0 30px rgba(0, 255, 255, 0.12); }
    .pending-setup-banner { grid-column: 1 / -1; }
    .welcome .title { font-family: 'Orbitron', monospace; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; color: var(--cyber-primary); }
    .welcome .sub { color: var(--cyber-text-dim); font-size: 0.9rem; }
    .badge { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .6rem; border: 1px solid var(--cyber-primary); border-radius: 999px; color: var(--cyber-primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; }

    .tabs { grid-column: 1; display: flex; flex-direction: column; gap: 0.5rem; border-right: 1px solid var(--cyber-border); padding-right: 1rem; }
    .tab-btn { background: none; border: 1px solid transparent; color: var(--cyber-text); padding: 0.8rem 1rem; cursor: pointer; font-family: 'Orbitron', monospace; text-transform: uppercase; border-radius: 4px; transition: all 0.3s; white-space: nowrap; text-align: left; border-left: 3px solid transparent; }
    .tab-btn:hover { border-color: var(--cyber-secondary); color: var(--cyber-secondary); background: rgba(0, 255, 255, 0.05); }
    .tab-btn.active { border-color: transparent; border-left-color: var(--cyber-primary); color: var(--cyber-primary); background: rgba(0, 255, 255, 0.1); }
    @keyframes fadeInSlide {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .tab-content { grid-column: 2; display: none; }
    .tab-content.active { display: block; animation: fadeInSlide 0.3s ease-out forwards; }

    .panel { border: 1px solid var(--cyber-border); border-radius: 10px; background: var(--cyber-surface-2); overflow: hidden; margin-bottom: 1rem; }
    .panel-hd { padding: 0.8rem 1rem; border-bottom: 1px solid var(--cyber-border); display:flex; justify-content: space-between; align-items:center; }
    .panel-title { font-family: 'Orbitron', monospace; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; color: var(--cyber-primary); font-size: 0.95rem; }
    .panel-bd { padding: 1rem; }

    table { width: 100%; border-collapse: collapse; color: var(--cyber-text); font-size: 0.9rem; }
    th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--cyber-border); }
    th { color: var(--cyber-primary); font-family: 'Orbitron', monospace; text-transform: uppercase; }
    tr:hover { background: rgba(0, 255, 255, 0.05); }

    .btn-small { background: transparent; border: 1px solid var(--cyber-primary); color: var(--cyber-primary); padding: 0.3rem 0.6rem; cursor: pointer; font-family: 'Orbitron', monospace; font-size: 0.7rem; border-radius: 3px; }
    .btn-small:hover { background: var(--cyber-primary); color: #000; }
    .btn-danger { border-color: var(--cyber-error); color: var(--cyber-error); }
    .btn-danger:hover { background: var(--cyber-error); color: #fff; }

    .controls { display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center; }
    .controls input, .controls select { background: rgba(0,0,0,0.5); border: 1px solid var(--cyber-border); color: #fff; padding: 0.5rem; border-radius: 4px; }
    .controls input:focus { border-color: var(--cyber-primary); }

    .pagination { display: flex; gap: 0.5rem; justify-content: center; margin-top: 1rem; }
    .page-btn { background: transparent; border: 1px solid var(--cyber-border); color: var(--cyber-text); padding: 0.3rem 0.6rem; cursor: pointer; }
    .page-btn:hover { border-color: var(--cyber-primary); }
    .page-btn.active { border-color: var(--cyber-primary); color: var(--cyber-primary); background: rgba(0,255,255,0.1); }

    /* ── Super Admin Status Monitor ─────────────────────────── */
    @keyframes sa-pulse {
      0%, 100% { opacity: 1; transform: scale(1); box-shadow: 0 0 0 0 rgba(0,255,136,0.5); }
      50%       { opacity: 0.7; transform: scale(1.3); box-shadow: 0 0 0 5px rgba(0,255,136,0); }
    }
    @keyframes sa-active-glow {
      0%, 100% { box-shadow: 0 0 6px rgba(0,255,136,0.4); }
      50%       { box-shadow: 0 0 14px rgba(0,255,136,0.9); }
    }
    .sa-status-row {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0.6rem 0.9rem; border-radius: 7px;
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.07);
      transition: all 0.3s;
      gap: 0.8rem; flex-wrap: wrap;
    }
    .sa-status-row.sa-is-active {
      background: rgba(0,255,136,0.06);
      border-color: rgba(0,255,136,0.3);
      animation: sa-active-glow 2.5s ease-in-out infinite;
    }
    .sa-status-row-left { display:flex; align-items:center; gap: 0.75rem; }
    .sa-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .sa-dot-active  { background: #00ff88; box-shadow: 0 0 8px rgba(0,255,136,0.7); animation: sa-pulse 2s ease-in-out infinite; }
    .sa-dot-offline { background: #555; }
    .sa-name { font-weight: 600; color: #fff; font-size: 0.9rem; }
    .sa-sub  { font-size: 0.78rem; color: rgba(255,255,255,0.4); }
    .sa-session-badge {
      padding: 0.2rem 0.65rem; border-radius: 999px; font-size: 0.72rem;
      font-family: 'Orbitron', monospace; letter-spacing: 0.5px; text-transform: uppercase;
      font-weight: 700;
    }
    .sa-badge-active  { background: rgba(0,255,136,0.15); color: #00ff88; border: 1px solid rgba(0,255,136,0.4); }
    .sa-badge-offline { background: rgba(255,255,255,0.04); color: #666; border: 1px solid rgba(255,255,255,0.1); }
    .sa-badge-you     { background: rgba(0,200,255,0.12); color: #00eaff; border: 1px solid rgba(0,200,255,0.35); }
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
        <span class="cyber-link">Role: <?= strtoupper($role) ?></span>
        <a href="../php/logout.php" class="cyber-link">LOGOUT</a>
      </div>
    </div>
  </header>

  <main class="cyber-main">
    <div class="dash-wrap">
      <section class="welcome">
        <div>
          <div class="title">Neon Dashboard</div>
          <div class="sub">Welcome, <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>!</div>
        </div>
        <div class="badge">System Online</div>
      </section>



<?php
$privs = $user['privileges'] ? json_decode($user['privileges'], true) : [];
if (!is_array($privs)) $privs = [];
?>
      <div class="tabs">
        <button class="tab-btn active" data-tab="profile" onclick="showTab('profile')">My Profile</button>
        <?php if ($role === 'super_admin' || ($role === 'admin' && in_array('view_accounts', $privs))): ?>
          <button class="tab-btn" data-tab="users" onclick="showTab('users')">User Management</button>
        <?php endif; ?>
        <?php if ($role === 'super_admin' || $role === 'admin'): ?>
          <button class="tab-btn" data-tab="logs" onclick="showTab('logs')">Login Logs</button>
        <?php else: ?>
          <button class="tab-btn" data-tab="logs" onclick="showTab('logs')">My Logs</button>
        <?php endif; ?>
        <?php if ($role === 'super_admin' || ($role === 'admin' && in_array('account_deletion_requests', $privs))): ?>
          <button class="tab-btn" data-tab="delete_reqs" onclick="showTab('delete_reqs')">Deletion Requests</button>
        <?php endif; ?>
        <?php if ($role === 'super_admin'): ?>
          <button class="tab-btn" data-tab="privilege_mgmt" onclick="showTab('privilege_mgmt')">Privilege Management</button>
          <button class="tab-btn" data-tab="create_admin_portal" id="portalTabBtn" onclick="showTab('create_admin_portal')">Super Admin Portal</button>
        <?php endif; ?>
      </div>

      <div id="profile" class="tab-content active">
        <div class="panel" style="max-width: 600px;">
          <div class="panel-hd"><div class="panel-title">Overview</div></div>
          <div class="panel-bd">
            <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>ID Number:</strong> <?= htmlspecialchars($user['id_number']) ?></p>
            <br>
            <div style="display: flex; gap: 1rem;">
                <?php if ($role === 'super_admin' || in_array('change_password', $privs)): ?>
                <a class="cyber-btn" href="change-password.php" style="width: auto;">Change Password</a>
                <?php endif; ?>
                <?php if ($role === 'super_admin' || in_array('profile_management', $privs)): ?>
                <a class="cyber-btn secondary" href="edit-profile.php" style="width: auto;">Edit Profile</a>
                <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <?php if ($role === 'super_admin' || ($role === 'admin' && in_array('view_accounts', $privs))): ?>
      <div id="users" class="tab-content">
        <div class="panel">
          <div class="panel-hd">
            <div class="panel-title">Manage Users</div>
            <?php if ($role === 'super_admin'): ?>
              <button class="btn-small" onclick="openCreateAdminModal()">Create Admin</button>
            <?php endif; ?>
          </div>
          <div class="panel-bd">
            <div class="controls">
              <input type="text" id="filterEmpId" placeholder="Filter by Emp ID...">
              <button class="btn-small" onclick="loadUsers()">Search</button>
            </div>
            <div style="overflow-x: auto;">
              <table id="usersTable">
                <thead>
                  <tr>
                    <th>ID Num</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Filled via JS -->
                </tbody>
              </table>
            </div>
            <div class="pagination" id="usersPagination"></div>
          </div>
        </div>
      </div>

      <div id="logs" class="tab-content">
        <div class="panel">
          <div class="panel-hd"><div class="panel-title">Login Logs</div></div>
          <div class="panel-bd">
            <div class="controls">
              <input type="month" id="filterMonth">
              <input type="date" id="filterDate">
              <button class="btn-small" onclick="loadLogs()">Search</button>
            </div>
            <div style="overflow-x: auto;">
              <table id="logsTable">
                <thead>
                  <tr>
                    <th>ID Num</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Filled via JS -->
                </tbody>
              </table>
            </div>
            <div class="pagination" id="logsPagination"></div>
          </div>
        </div>
      </div>
      <?php else: ?>
      <div id="logs" class="tab-content">
        <div class="panel">
          <div class="panel-hd"><div class="panel-title">My Login Logs</div></div>
          <div class="panel-bd">
            <div class="controls">
              <input type="month" id="filterMonth">
              <input type="date" id="filterDate">
              <button class="btn-small" onclick="loadLogs()">Search</button>
            </div>
            <div style="overflow-x: auto;">
              <table id="logsTable">
                <thead>
                  <tr>
                    <th>Time In</th>
                    <th>Time Out</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Filled via JS -->
                </tbody>
              </table>
            </div>
            <div class="pagination" id="logsPagination"></div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($role === 'super_admin' || ($role === 'admin' && in_array('account_deletion_requests', $privs))): ?>
      <div id="delete_reqs" class="tab-content">
        <div class="panel">
          <div class="panel-hd"><div class="panel-title">Deletion Requests</div></div>
          <div class="panel-bd">
            <div style="overflow-x: auto;">
              <table id="delReqTable">
                <thead>
                  <tr>
                    <th>Target User</th>
                    <th>Requested By</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Filled via JS -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($role === 'super_admin'): ?>
      <!-- Privilege Management Tab -->
      <div id="privilege_mgmt" class="tab-content">
        <div class="panel">
          <div class="panel-hd">
            <div class="panel-title">🛡️ Privilege Management / Permitted Functions</div>
            <button class="btn-small" onclick="loadPrivMgmtUsers()">⟳ Refresh Accounts</button>
          </div>
          <div class="panel-bd">
            <!-- Account Selection Control -->
            <div style="background: rgba(0,0,0,0.4); border: 1px solid var(--cyber-border); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
              <label style="display:block; font-family: 'Orbitron', monospace; font-size: 0.8rem; color: var(--cyber-primary); margin-bottom: 0.5rem; text-transform: uppercase;">
                Search & Select Account to Manage:
              </label>
              <div style="margin-bottom: 1rem;">
                <input type="text" id="privMgmtSearchInput" oninput="debouncedFilterPrivMgmtUsers()" placeholder="Search by name, username, or role..." style="width: 100%; max-width: 100%; background: rgba(0,0,0,0.7); border: 1px solid var(--cyber-primary); color: #fff; padding: 0.6rem; border-radius: 6px; font-size: 0.95rem;">
              </div>
              <div id="privMgmtUserList" style="display: grid; gap: 0.5rem; max-height: 250px; overflow-y: auto; padding-right: 0.5rem; margin-bottom: 1rem;">
                <!-- User items will be injected here -->
              </div>
              <input type="hidden" id="privMgmtUserSelect">
              <div id="privMgmtUserInfo" style="display: none; align-items: center; gap: 0.8rem; font-size: 0.9rem; color: var(--cyber-text-dim); padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); flex-wrap: wrap;">
                <span>Managing: <strong id="privUserInfoName" style="color: #fff;"></strong></span>
                <span>|</span>
                <span>ID: <strong id="privUserInfoId" style="color: #fff;"></strong></span>
                <span>|</span>
                <span>Role: <strong id="privUserInfoRole" style="color: var(--cyber-accent); text-transform: uppercase;"></strong></span>
                <span>|</span>
                <span>Status: <strong id="privUserInfoStatus" style="text-transform: uppercase;"></strong></span>
              </div>
            </div>

            <!-- Privilege Configuration Area -->
            <div id="privMgmtConfigArea" style="display: none;">
              <div class="section-header" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.6rem; margin-bottom: 1.2rem;">
                <div class="section-title" style="display:flex; align-items:center; gap: 0.6rem;">
                  <span>⚙ MODULE PERMISSIONS</span>
                  <span class="badge-selected-count" id="privMgmtTabCountBadge">0 Selected</span>
                </div>
                <div class="privilege-header-actions">
                  <button type="button" class="priv-pill-btn recommended" onclick="setPrivMgmtPresets('recommended')">⟳ Recommended</button>
                  <button type="button" class="priv-pill-btn" onclick="setPrivMgmtPresets('all')">✓✓ Select All</button>
                  <button type="button" class="priv-pill-btn" onclick="setPrivMgmtPresets('clear')">⊘ Clear</button>
                </div>
              </div>

              <!-- 5 Module Cards -->
              <div class="modules-grid" style="margin-bottom: 1.5rem;">
                <!-- Module 1: DASHBOARD & ACCOUNT -->
                <div class="module-card">
                  <div class="module-card-title"><span>📊</span> DASHBOARD & ACCOUNT</div>

                  <label class="privilege-item">
                    <input type="checkbox" class="privilege-checkbox priv-mgmt-tab-cb" value="change_password" onchange="updatePrivMgmtTabCount()">
                    <div class="privilege-text"><span class="privilege-name">Change Password</span><span class="privilege-sub">Ability to change account password</span></div>
                  </label>
                  <label class="privilege-item">
                    <input type="checkbox" class="privilege-checkbox priv-mgmt-tab-cb" value="profile_management" onchange="updatePrivMgmtTabCount()">
                    <div class="privilege-text"><span class="privilege-name">Profile Management</span><span class="privilege-sub">View and update user profile</span></div>
                  </label>
                </div>

                <!-- Module 2: USER & STUDENT MANAGEMENT -->
                <div class="module-card" id="privMgmtCard2">
                  <div class="module-card-title"><span>👥</span> USER & STUDENT MANAGEMENT</div>
                  <label class="privilege-item">
                    <input type="checkbox" class="privilege-checkbox priv-mgmt-tab-cb" value="view_accounts" onchange="updatePrivMgmtTabCount()">
                    <div class="privilege-text"><span class="privilege-name">View Accounts List</span><span class="privilege-sub">View user directory & profiles</span></div>
                  </label>
                  <label class="privilege-item">
                    <input type="checkbox" class="privilege-checkbox priv-mgmt-tab-cb" value="edit_manage_accounts" onchange="updatePrivMgmtTabCount()">
                    <div class="privilege-text"><span class="privilege-name">Edit & Manage Accounts</span><span class="privilege-sub">Update student/user account info</span></div>
                  </label>
                  <label class="privilege-item">
                    <input type="checkbox" class="privilege-checkbox priv-mgmt-tab-cb" value="block_unblock_users" onchange="updatePrivMgmtTabCount()">
                    <div class="privilege-text"><span class="privilege-name">Block / Unblock Users</span><span class="privilege-sub">Suspend or reactivate accounts</span></div>
                  </label>
                </div>

                <!-- Module 3: APPROVALS & REQUESTS -->
                <div class="module-card" id="privMgmtCard3">
                  <div class="module-card-title"><span>✍️</span> APPROVALS & REQUESTS</div>
                  <label class="privilege-item">
                    <input type="checkbox" class="privilege-checkbox priv-mgmt-tab-cb" value="registration_approval" onchange="updatePrivMgmtTabCount()">
                    <div class="privilege-text"><span class="privilege-name">Registration Approval</span><span class="privilege-sub">Approve or reject pending signups</span></div>
                  </label>
                  <label class="privilege-item">
                    <input type="checkbox" class="privilege-checkbox priv-mgmt-tab-cb" value="account_deletion_requests" onchange="updatePrivMgmtTabCount()">
                    <div class="privilege-text"><span class="privilege-name">Account Deletion Requests</span><span class="privilege-sub">Submit or manage delete requests</span></div>
                  </label>
                </div>

              </div>

              <!-- Save Button -->
              <button type="button" id="savePrivMgmtTabBtn" class="portal-submit-btn" onclick="savePrivMgmtTabPrivileges()" style="max-width: 400px; margin: 0 auto;">
                <span>💾</span> Save Privileges
              </button>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($role === 'super_admin'): ?>
      <!-- Super Admin Portal / Create Account -->
      <div id="create_admin_portal" class="tab-content">
        <div class="portal-container">
          <!-- Topbar with Back to Directory -->
          <div class="portal-topbar">
            <div class="portal-brand-badge">
              <span>⚡</span>
              <span>CYBERAUTH SYSTEM</span>
            </div>
            <button type="button" class="portal-back-btn" onclick="showTab('users')">
              ← Back to Directory
            </button>
          </div>

          <!-- Super Admin Status Monitor -->
          <div id="saStatusPanel" style="
            background: rgba(0,0,0,0.5);
            border: 1px solid rgba(0,255,255,0.25);
            border-radius: 10px;
            padding: 1.2rem 1.4rem;
            margin-bottom: 1.4rem;
            position: relative;
          ">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
              <div style="font-family:'Orbitron',monospace; font-size:0.8rem; color:var(--cyber-primary); letter-spacing:1px; text-transform:uppercase;">
                🛡️ Super Admin Session Status
              </div>
              <div style="display:flex; align-items:center; gap:0.7rem;">
                <span id="saStatusLastUpdated" style="font-size:0.75rem; color:rgba(255,255,255,0.35);"></span>
                <button type="button" onclick="loadSuperAdminStatus()" style="
                  background:none; border:1px solid rgba(0,255,255,0.3); color:var(--cyber-primary);
                  padding:0.25rem 0.6rem; border-radius:4px; cursor:pointer; font-size:0.75rem;
                  font-family:'Orbitron',monospace; transition:all 0.2s;
                " onmouseover="this.style.background='rgba(0,255,255,0.1)'" onmouseout="this.style.background='none'">
                  ⟳ Refresh
                </button>
              </div>
            </div>
            <div id="saStatusList" style="display:grid; gap:0.55rem;">
              <div style="color:rgba(255,255,255,0.4); font-size:0.85rem; padding:0.5rem;">Loading Super Admin accounts…</div>
            </div>
            <!-- Pulse indicator showing auto-refresh is running -->
            <div style="margin-top:0.9rem; display:flex; align-items:center; gap:0.5rem;">
              <div id="saStatusPulse" style="width:7px; height:7px; border-radius:50%; background:#00ff88; animation:sa-pulse 2s ease-in-out infinite;"></div>
              <span style="font-size:0.72rem; color:rgba(255,255,255,0.3);">Auto-refreshes every 30 seconds</span>
            </div>
          </div>

          <!-- Main Two-Column Card -->
          <div class="portal-card">
            <!-- Left Sidebar: Account Role & Requirements -->
            <div class="portal-sidebar">
              <div>
                <div class="portal-tag">
                  <span>⚡</span> SUPER ADMIN PORTAL
                </div>
                <h2 class="portal-sidebar-title">Create Account</h2>
                <p class="portal-sidebar-desc">Generate initial credentials and configure module privileges.</p>

                <div class="step-label-row">
                  <span class="step-label">1. ACCOUNT ROLE</span>
                  <span class="step-required-badge">REQUIRED</span>
                </div>

                <div class="role-cards-container">
                  <!-- Administrator Option -->
                  <div class="role-card active" id="roleCardAdmin" onclick="selectAccountRole('admin')">
                    <div class="role-card-header">
                      <div class="role-title-group">
                        <span class="role-icon">👤</span>
                        <span class="role-title">Administrator</span>
                      </div>
                      <div class="radio-circle"></div>
                    </div>
                    <p class="role-description">Manage assigned modules (Users, Approvals, Deletions).</p>
                  </div>

                  <!-- Super Administrator Option -->
                  <div class="role-card" id="roleCardSuperAdmin" onclick="selectAccountRole('super_admin')">
                    <div class="role-card-header">
                      <div class="role-title-group">
                        <span class="role-icon">🛡️</span>
                        <span class="role-title">Super Administrator</span>
                      </div>
                      <div class="radio-circle"></div>
                    </div>
                    <p class="role-description">Full authority. (Rule: Only 1 Super Admin active at a time).</p>
                  </div>
                </div>
              </div>

              <!-- First Login Requirement Notice -->
              <div class="first-login-notice">
                <div class="notice-icon">⚠️</div>
                <div class="notice-text">
                  <strong>First Login Requirement:</strong> You set the initial credentials and module privileges. The account owner must complete their profile upon first login.
                </div>
              </div>
            </div>

            <!-- Right Main Area: Credentials & Privilege Management -->
            <div class="portal-main">
              <!-- Hidden role input -->
              <input type="hidden" id="selectedAccountRole" value="admin">

              <!-- Section 2: Initial Credentials -->
              <div class="portal-section">
                <div class="section-header">
                  <div class="section-title">
                    <span>➔</span> 2. Initial Credentials
                  </div>
                  <span class="section-tag">First-Time Login Setup</span>
                </div>

                <div class="credentials-row">
                  <!-- Username Field -->
                  <div class="credential-field">
                    <div class="field-label-row">
                      <span class="field-label">Username <span class="field-req">*</span></span>
                      <span class="field-hint">3-50 chars</span>
                    </div>
                    <div class="input-icon-wrap">
                      <span class="field-icon">👤</span>
                      <input type="text" id="portalUsername" placeholder="e.g. admin_delacruz" maxlength="50" autocomplete="off" required>
                    </div>
                  </div>

                  <!-- Default Password Field -->
                  <div class="credential-field">
                    <div class="field-label-row">
                      <span class="field-label">Default Password <span class="field-req">*</span></span>
                      <span class="field-hint">Min 8 chars</span>
                    </div>
                    <div class="input-icon-wrap">
                      <span class="field-icon">🔒</span>
                      <input type="password" id="portalPassword" placeholder="Temporary password" minlength="8" required>
                      <button type="button" class="pwd-toggle-btn" style="background:none; border:none; color:var(--cyber-primary); filter:drop-shadow(0 0 5px rgba(14,165,233,0.5)); cursor:pointer;" onclick="togglePasswordVisibility('portalPassword', this)" title="Toggle visibility"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>
                    </div>
                  </div>

                  <!-- Confirm Password Field -->
                  <div class="credential-field">
                    <div class="field-label-row">
                      <span class="field-label">Confirm Password <span class="field-req">*</span></span>
                    </div>
                    <div class="input-icon-wrap">
                      <span class="field-icon">🛡️</span>
                      <input type="password" id="portalConfirmPassword" placeholder="Re-enter password" minlength="8" required>
                      <button type="button" class="pwd-toggle-btn" style="background:none; border:none; color:var(--cyber-primary); filter:drop-shadow(0 0 5px rgba(14,165,233,0.5)); cursor:pointer;" onclick="togglePasswordVisibility('portalConfirmPassword', this)" title="Toggle visibility"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section 3: Privilege Management / Permitted Functions -->
              <div class="portal-section">
                <div class="section-header">
                  <div class="section-title">
                    <span>⚙</span> 3. Privilege Management / Permitted Functions
                    <span class="badge-selected-count" id="privilegeCountBadge">8 Selected</span>
                  </div>
                  <div class="privilege-header-actions">
                    <button type="button" class="priv-pill-btn recommended" onclick="selectRecommendedPrivileges()">
                      ⟳ Recommended
                    </button>
                    <button type="button" class="priv-pill-btn" onclick="selectAllPrivileges()">
                      ✓✓ Select All
                    </button>
                    <button type="button" class="priv-pill-btn" onclick="clearAllPrivileges()">
                      ⊘ Clear
                    </button>
                  </div>
                </div>

                <!-- Modules Grid (5 Cards) -->
                <div class="modules-grid">
                  <!-- Module 1: DASHBOARD & ACCOUNT -->
                  <div class="module-card">
                    <div class="module-card-title">
                      <span>📊</span> DASHBOARD & ACCOUNT
                    </div>

                    <label class="privilege-item">
                      <input type="checkbox" class="privilege-checkbox" value="change_password" checked onchange="updatePrivilegeCount()">
                      <div class="privilege-text">
                        <span class="privilege-name">Change Password</span>
                        <span class="privilege-sub">Ability to change account password</span>
                      </div>
                    </label>
                    <label class="privilege-item">
                      <input type="checkbox" class="privilege-checkbox" value="profile_management" checked onchange="updatePrivilegeCount()">
                      <div class="privilege-text">
                        <span class="privilege-name">Profile Management</span>
                        <span class="privilege-sub">View and update user profile</span>
                      </div>
                    </label>
                  </div>

                  <!-- Module 2: USER & STUDENT MANAGEMENT -->
                  <div class="module-card">
                    <div class="module-card-title">
                      <span>👥</span> USER & STUDENT MANAGEMENT
                    </div>
                    <label class="privilege-item">
                      <input type="checkbox" class="privilege-checkbox" value="view_accounts" checked onchange="updatePrivilegeCount()">
                      <div class="privilege-text">
                        <span class="privilege-name">View Accounts List</span>
                        <span class="privilege-sub">View user directory & profiles</span>
                      </div>
                    </label>
                    <label class="privilege-item">
                      <input type="checkbox" class="privilege-checkbox" value="edit_manage_accounts" checked onchange="updatePrivilegeCount()">
                      <div class="privilege-text">
                        <span class="privilege-name">Edit & Manage Accounts</span>
                        <span class="privilege-sub">Update student/user account info</span>
                      </div>
                    </label>
                    <label class="privilege-item">
                      <input type="checkbox" class="privilege-checkbox" value="block_unblock_users" checked onchange="updatePrivilegeCount()">
                      <div class="privilege-text">
                        <span class="privilege-name">Block / Unblock Users</span>
                        <span class="privilege-sub">Suspend or reactivate accounts</span>
                      </div>
                    </label>
                  </div>

                  <!-- Module 3: APPROVALS & REQUESTS -->
                  <div class="module-card">
                    <div class="module-card-title">
                      <span>✍️</span> APPROVALS & REQUESTS
                    </div>
                    <label class="privilege-item">
                      <input type="checkbox" class="privilege-checkbox" value="registration_approval" checked onchange="updatePrivilegeCount()">
                      <div class="privilege-text">
                        <span class="privilege-name">Registration Approval</span>
                        <span class="privilege-sub">Approve or reject pending signups</span>
                      </div>
                    </label>
                    <label class="privilege-item">
                      <input type="checkbox" class="privilege-checkbox" value="account_deletion_requests" checked onchange="updatePrivilegeCount()">
                      <div class="privilege-text">
                        <span class="privilege-name">Account Deletion Requests</span>
                        <span class="privilege-sub">Submit or manage delete requests</span>
                      </div>
                    </label>
                  </div>

                    </div>
                  </div>
                </div>
              </div>

              <!-- Submit Button & Disclaimer -->
              <div class="portal-actions-row">
                <button type="button" id="submitCreateAccountBtn" class="portal-submit-btn" onclick="submitCreatePrivilegedAccount()">
                  <span>⊕</span> Create Account & Save Privileges
                </button>
                <div class="portal-footer-disclaimer">
                  <span>🛡️</span> Account created with status <strong>Pending Setup</strong>. Owner completes profile on first login.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </main>

  <!-- Super Admin Action Password Confirmation Modal -->
  <div id="saPasswordModal" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.85); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(5px);">
    <div style="background:var(--cyber-surface); border:1px solid var(--cyber-border); border-radius:12px; width:100%; max-width:400px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
      <div style="padding:1.5rem 1.5rem 0.5rem 1.5rem; display:flex; justify-content:space-between; align-items:center;">
        <div style="font-family:'Inter', sans-serif; font-size:1.1rem; color:var(--cyber-primary); font-weight:700; display:flex; align-items:center; gap:0.5rem; text-shadow:0 0 10px rgba(14,165,233,0.3);">
          <span>🛡️</span> Identity Verification
        </div>
        <button class="btn-small btn-danger" onclick="closeSaPasswordModal()" style="padding:0.2rem 0.5rem;">×</button>
      </div>
      <div style="padding:1.5rem;">
        <p style="color:var(--cyber-text-dim); font-size:0.9rem; margin-bottom:1.5rem; line-height:1.4;">
          This privileged action requires you to confirm your identity by entering your password.
        </p>
        <div class="controls" style="margin-bottom:1.5rem; display:block;">
          <div style="position: relative; display: flex; align-items: center;">
            <input type="password" id="saConfirmPasswordInput" placeholder="Enter your password" style="width:100%; font-family:'Inter', sans-serif; font-size:0.95rem; padding:0.75rem 40px 0.75rem 1rem; background:rgba(255,255,255,0.05); border:1px solid var(--cyber-border); border-radius:6px; color:var(--cyber-text); transition:border-color 0.2s;" />
            <button type="button" onclick="const p = document.getElementById('saConfirmPasswordInput'); const svg = this.querySelector('svg'); if(p.type==='password'){p.type='text'; svg.innerHTML='<path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21\'></path>';}else{p.type='password'; svg.innerHTML='<path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M15 12a3 3 0 11-6 0 3 3 0 016 0z\'></path><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z\'></path>';}" style="position: absolute; right: 10px; background: none; border: none; color: var(--cyber-primary); cursor: pointer; display: flex; align-items: center; padding: 0; filter: drop-shadow(0 0 5px rgba(14,165,233,0.5)); transition: color 0.2s;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
            </button>
          </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:1rem;">
          <button class="cyber-btn" onclick="closeSaPasswordModal()" style="background:transparent; border:1px solid var(--cyber-text-dim); color:var(--cyber-text-dim); width:auto; padding:0.5rem 1.5rem; box-shadow:none;">Cancel</button>
          <button class="cyber-btn" id="saConfirmPasswordBtn" onclick="submitSaPasswordModal()" style="margin:0; width:auto; padding:0.5rem 1.5rem; font-size:0.9rem;">
            Verify & Proceed
          </button>
        </div>
      </div>
    </div>
  </div>

  
  <!-- Custom Alert Modal -->
  <div id="customAlertModal" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.85); z-index:999999; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
    <div style="background:var(--cyber-surface); border:1px solid var(--cyber-border); border-radius:12px; width:90%; max-width:400px; padding:1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
      <div style="font-family:'Inter', sans-serif; font-size:1.1rem; color:var(--cyber-primary); font-weight:700; margin-bottom:1rem; text-shadow:0 0 10px rgba(14,165,233,0.3);">
        SYSTEM NOTIFICATION
      </div>
      <p id="customAlertMessage" style="color:var(--cyber-text); font-size:0.95rem; line-height:1.5; margin-bottom:1.5rem; word-wrap: break-word; overflow-wrap: anywhere;"></p>
      <div style="display:flex; justify-content:flex-end;">
        <button class="cyber-btn" onclick="closeCustomAlert()" style="width:auto; padding:0.5rem 1.5rem;">OK</button>
      </div>
    </div>
  </div>

  <!-- Custom Confirm Modal -->
  <div id="customConfirmModal" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.85); z-index:999999; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
    <div style="background:var(--cyber-surface); border:1px solid var(--cyber-border); border-radius:12px; width:90%; max-width:400px; padding:1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
      <div style="font-family:'Inter', sans-serif; font-size:1.1rem; color:var(--cyber-warning); font-weight:700; margin-bottom:1rem; text-shadow:0 0 10px rgba(245,158,11,0.3);">
        ACTION REQUIRED
      </div>
      <p id="customConfirmMessage" style="color:var(--cyber-text); font-size:0.95rem; line-height:1.5; margin-bottom:1.5rem; word-wrap: break-word; overflow-wrap: anywhere;"></p>
      <div style="display:flex; justify-content:flex-end; gap:1rem;">
        <button class="cyber-btn" onclick="resolveCustomConfirm(false)" style="background:transparent; border:1px solid var(--cyber-text-dim); color:var(--cyber-text-dim); width:auto; padding:0.5rem 1.5rem; box-shadow:none;">Cancel</button>
        <button class="cyber-btn" onclick="resolveCustomConfirm(true)" style="width:auto; padding:0.5rem 1.5rem;">Confirm</button>
      </div>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div id="editUserModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
    <div class="panel" style="width:100%; max-width:500px;">
      <div class="panel-hd">
        <div class="panel-title">Update User Info</div>
        <button class="btn-small btn-danger" onclick="document.getElementById('editUserModal').style.display='none'">Close</button>
      </div>
      <div class="panel-bd">
        <form id="editUserForm">
          <input type="hidden" id="editUserId">
          <div class="controls" style="flex-direction:column; align-items:stretch;">
            <label style="margin-top:0.5rem; color:var(--cyber-primary); font-size:0.85rem; font-weight:600; letter-spacing:0.5px;">First Name</label>
            <input type="text" id="editFirstName" class="cyber-input" style="padding: 0.5rem; border-radius: 4px;" required>
            <label style="margin-top:0.5rem; color:var(--cyber-primary); font-size:0.85rem; font-weight:600; letter-spacing:0.5px;">Last Name</label>
            <input type="text" id="editLastName" class="cyber-input" style="padding: 0.5rem; border-radius: 4px;" required>
            <label style="margin-top:0.5rem; color:var(--cyber-primary); font-size:0.85rem; font-weight:600; letter-spacing:0.5px;">Email</label>
            <input type="email" id="editEmail" class="cyber-input" style="padding: 0.5rem; border-radius: 4px;" required>
            <label style="margin-top:0.5rem; color:var(--cyber-primary); font-size:0.85rem; font-weight:600; letter-spacing:0.5px;">Phone Number</label>
            <input type="text" id="editPhone" class="cyber-input" style="padding: 0.5rem; border-radius: 4px;" required>
            <?php if ($role === 'super_admin'): ?>
            <label style="margin-top:0.5rem; color:var(--cyber-primary); font-size:0.85rem; font-weight:600; letter-spacing:0.5px;">Reset Password (Optional)</label>
            <div style="position: relative; display: flex; align-items: center;">
                <input type="password" id="editNewPassword" class="cyber-input" placeholder="Leave blank to keep current password" style="width: 100%; padding: 0.5rem 40px 0.5rem 0.5rem; border-radius: 4px;">
                <button type="button" onclick="const p = document.getElementById('editNewPassword'); const svg = this.querySelector('svg'); if(p.type==='password'){p.type='text'; svg.innerHTML='<path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21\'></path>';}else{p.type='password'; svg.innerHTML='<path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M15 12a3 3 0 11-6 0 3 3 0 016 0z\'></path><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z\'></path>';}" style="position: absolute; right: 10px; background: none; border: none; color: var(--cyber-primary); cursor: pointer; display: flex; align-items: center; padding: 0; filter: drop-shadow(0 0 5px rgba(14,165,233,0.5)); transition: color 0.2s;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
            </div>
            <?php endif; ?>
            <button type="button" class="cyber-btn" onclick="submitEditUser()" style="margin-top: 1.5rem; width: auto; align-self: center; padding: 0.6rem 2.5rem; border-radius: 99px;">Save Changes</button>
          </div>
        </form>
      </div>
  <!-- Manage Privileges Modal -->
  <?php if ($role === 'super_admin'): ?>
  <div id="managePrivilegesModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); z-index:99999; place-items:center; overflow-y:auto; padding: 2rem 1rem;">
    <div class="panel" style="width:100%; max-width:850px; margin: auto;">
      <div class="panel-hd" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
          <div class="panel-title" id="modalPrivTitle">🛡️ Manage Account Privileges</div>
          <div style="font-size: 0.8rem; color: var(--cyber-text-dim);" id="modalPrivSubtitle">Configure module access permissions</div>
        </div>
        <button class="btn-small btn-danger" onclick="closeManagePrivilegesModal()">Close</button>
      </div>
      <div class="panel-bd">
        <input type="hidden" id="modalPrivUserId">
        
        <div class="section-header" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.6rem; margin-bottom: 1rem;">
          <div class="section-title">
            <span>⚙ PERMITTED MODULE FUNCTIONS</span>
            <span class="badge-selected-count" id="modalPrivCountBadge">0 Selected</span>
          </div>
          <div class="privilege-header-actions">
            <button type="button" class="priv-pill-btn recommended" onclick="setModalPrivPresets('recommended')">⟳ Recommended</button>
            <button type="button" class="priv-pill-btn" onclick="setModalPrivPresets('all')">✓✓ Select All</button>
            <button type="button" class="priv-pill-btn" onclick="setModalPrivPresets('clear')">⊘ Clear</button>
          </div>
        </div>

        <!-- 5 Module Cards -->
        <div class="modules-grid" style="margin-bottom: 1.5rem;">
          <!-- Module 1: DASHBOARD & ACCOUNT -->
          <div class="module-card">
            <div class="module-card-title"><span>📊</span> DASHBOARD & ACCOUNT</div>

            <label class="privilege-item">
              <input type="checkbox" class="privilege-checkbox modal-priv-cb" value="change_password" onchange="updateModalPrivCount()">
              <div class="privilege-text"><span class="privilege-name">Change Password</span><span class="privilege-sub">Ability to change account password</span></div>
            </label>
            <label class="privilege-item">
              <input type="checkbox" class="privilege-checkbox modal-priv-cb" value="profile_management" onchange="updateModalPrivCount()">
              <div class="privilege-text"><span class="privilege-name">Profile Management</span><span class="privilege-sub">View and update user profile</span></div>
            </label>
          </div>

          <!-- Module 2: USER & STUDENT MANAGEMENT -->
          <div class="module-card" id="modalMgmtCard2">
            <div class="module-card-title"><span>👥</span> USER & STUDENT MANAGEMENT</div>
            <label class="privilege-item">
              <input type="checkbox" class="privilege-checkbox modal-priv-cb" value="view_accounts" onchange="updateModalPrivCount()">
              <div class="privilege-text"><span class="privilege-name">View Accounts List</span><span class="privilege-sub">View user directory & profiles</span></div>
            </label>
            <label class="privilege-item">
              <input type="checkbox" class="privilege-checkbox modal-priv-cb" value="edit_manage_accounts" onchange="updateModalPrivCount()">
              <div class="privilege-text"><span class="privilege-name">Edit & Manage Accounts</span><span class="privilege-sub">Update student/user account info</span></div>
            </label>
            <label class="privilege-item">
              <input type="checkbox" class="privilege-checkbox modal-priv-cb" value="block_unblock_users" onchange="updateModalPrivCount()">
              <div class="privilege-text"><span class="privilege-name">Block / Unblock Users</span><span class="privilege-sub">Suspend or reactivate accounts</span></div>
            </label>
          </div>

          <!-- Module 3: APPROVALS & REQUESTS -->
          <div class="module-card" id="modalMgmtCard3">
            <div class="module-card-title"><span>✍️</span> APPROVALS & REQUESTS</div>
            <label class="privilege-item">
              <input type="checkbox" class="privilege-checkbox modal-priv-cb" value="registration_approval" onchange="updateModalPrivCount()">
              <div class="privilege-text"><span class="privilege-name">Registration Approval</span><span class="privilege-sub">Approve or reject pending signups</span></div>
            </label>
            <label class="privilege-item">
              <input type="checkbox" class="privilege-checkbox modal-priv-cb" value="account_deletion_requests" onchange="updateModalPrivCount()">
              <div class="privilege-text"><span class="privilege-name">Account Deletion Requests</span><span class="privilege-sub">Submit or manage delete requests</span></div>
            </label>
          </div>

        </div>

        <button type="button" id="saveModalPrivBtn" class="portal-submit-btn" onclick="saveModalPrivileges()">
          <span>💾</span> Save & Apply Privileges
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <script>
    const userRole = '<?= $role ?>';
    const userPrivileges = <?= json_encode($privs) ?>;

    function showTab(tabId) {
      sessionStorage.setItem('activeDashboardTab', tabId);
      document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
      
      const targetTab = document.getElementById(tabId);
      if (targetTab) targetTab.classList.add('active');
      
      const activeBtn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`) || 
                        document.querySelector(`.tab-btn[onclick*="'${tabId}'"]`);
      if (activeBtn) activeBtn.classList.add('active');
      
      if (tabId === 'users' && typeof loadUsers === 'function') loadUsers();
      if (tabId === 'logs' && typeof loadLogs === 'function') loadLogs();
      if (tabId === 'delete_reqs' && typeof loadDeleteRequests === 'function') loadDeleteRequests();
      if (tabId === 'privilege_mgmt' && typeof initPrivilegeMgmtTab === 'function') initPrivilegeMgmtTab();
      if (tabId === 'create_admin_portal' && typeof initCreateAdminPortal === 'function') initCreateAdminPortal();
      // Load SA status whenever the portal tab opens
      if (tabId === 'create_admin_portal' && userRole === 'super_admin') loadSuperAdminStatus();
    }

    // ── Super Admin Status Monitor ──────────────────────────────────────────
    let saStatusPollTimer = null;
    const MY_USER_ID = <?= (int)($user['id'] ?? 0) ?>;

    async function loadSuperAdminStatus() {
      const list = document.getElementById('saStatusList');
      const updated = document.getElementById('saStatusLastUpdated');
      if (!list) return;

      try {
        const res = await fetch('../php/admin_actions.php?action=get_super_admins');
        const data = await res.json();

        if (!data.success || !data.data) {
          list.innerHTML = '<div style="color:#ff3366;font-size:0.85rem;padding:0.5rem;">Failed to load Super Admin status.</div>';
          return;
        }

        const admins = data.data.super_admins;
        if (!admins || admins.length === 0) {
          list.innerHTML = '<div style="color:rgba(255,255,255,0.35);font-size:0.85rem;padding:0.5rem;">No Super Admin accounts found.</div>';
          return;
        }

        list.innerHTML = admins.map(sa => {
          const isActive = sa.is_active_session;
          const isMe     = (parseInt(sa.id) === MY_USER_ID);
          const rowCls   = isActive ? 'sa-status-row sa-is-active' : 'sa-status-row';
          const dotCls   = isActive ? 'sa-dot sa-dot-active' : 'sa-dot sa-dot-offline';
          const label    = isActive
            ? (isMe ? '🟢 Active (You)' : '🟢 Active')
            : '⚫ Offline';
          const badgeCls = isActive
            ? (isMe ? 'sa-session-badge sa-badge-you' : 'sa-session-badge sa-badge-active')
            : 'sa-session-badge sa-badge-offline';

          const lastLogin = sa.last_login
            ? new Date(sa.last_login.replace(' ', 'T')).toLocaleString()
            : 'Never';

          return `
            <div class="${rowCls}">
              <div class="sa-status-row-left">
                <div class="${dotCls}"></div>
                <div>
                  <div class="sa-name">
                    ${escHtml(sa.first_name)} ${escHtml(sa.last_name)}
                    ${isMe ? '<span style="font-size:0.72rem;color:#00eaff;margin-left:0.4rem;">(You)</span>' : ''}
                  </div>
                  <div class="sa-sub">@${escHtml(sa.username)} &nbsp;·&nbsp; ID: ${escHtml(sa.id_number || '—')} &nbsp;·&nbsp; Last login: ${escHtml(lastLogin)}</div>
                </div>
              </div>
              <span class="${badgeCls}">${label}</span>
            </div>`;
        }).join('');

        // Update timestamp
        if (updated) {
          const now = new Date();
          updated.textContent = 'Updated ' + now.toLocaleTimeString();
        }

      } catch (err) {
        list.innerHTML = '<div style="color:#ff3366;font-size:0.85rem;padding:0.5rem;">Network error loading status.</div>';
      }
    }

    function escHtml(str) {
      if (!str) return '';
      return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function initSaStatusPoller() {
      loadSuperAdminStatus();
      clearInterval(saStatusPollTimer);
      saStatusPollTimer = setInterval(loadSuperAdminStatus, 30000);
    }

    // Auto-start poller when portal tab is currently active (or will be opened via showTab)
    if (userRole === 'super_admin') {
      document.addEventListener('DOMContentLoaded', () => {
        // If portal tab is visible on load, start now
        const portalTab = document.getElementById('create_admin_portal');
        if (portalTab && portalTab.classList.contains('active')) {
          initSaStatusPoller();
        }
      });
    }
    // JS Logic will be placed in a separate file (js/admin.js) or here
  </script>
  <script src="../js/admin.js"></script>
</body>
</html>
