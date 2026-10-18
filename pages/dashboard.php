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

// Security questions and profile display computations
$secStmt = $db->prepare("SELECT question1, answer1, question2, answer2, question3, answer3 FROM user_security_questions WHERE user_id = ? LIMIT 1");
$secStmt->execute([$_SESSION['user_id']]);
$secQuestions = $secStmt->fetch() ?: [];

$fullNameParts = array_filter([$user['first_name'] ?? '', $user['middle_name'] ?? '', $user['last_name'] ?? '', $user['extension'] ?? ''], fn($p) => trim((string)$p) !== '');
$displayName = !empty($fullNameParts) ? implode(' ', $fullNameParts) : $user['username'];
$firstInitial = strtoupper(substr($user['first_name'] ?: $user['username'] ?: 'U', 0, 1));
$userBirthdate = (!empty($user['birth_date']) && $user['birth_date'] !== '0000-00-00') ? $user['birth_date'] : '';
$userAge = !empty($user['age']) ? ($user['age'] . ' yrs') : '';

// ── Dashboard Overview Telemetry Computations ──────────────────────────────
$dashStatsStmt = $db->query("
    SELECT 
        COUNT(*) AS total_users,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS active_users,
        SUM(CASE WHEN status IN ('pending', 'pending_setup') THEN 1 ELSE 0 END) AS pending_users,
        SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) AS blocked_users,
        SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) AS sa_count,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admin_count,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) AS user_count
    FROM users
");
$dashStats = $dashStatsStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_users' => 0, 'active_users' => 0, 'pending_users' => 0, 'blocked_users' => 0,
    'sa_count' => 0, 'admin_count' => 0, 'user_count' => 0
];

$pendingDelStmt = $db->query("SELECT COUNT(*) FROM delete_requests WHERE status = 'pending'");
$pendingDelCount = (int)$pendingDelStmt->fetchColumn();

$todayLogsStmt = $db->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()");
$todayLogsCount = (int)$todayLogsStmt->fetchColumn();

$recentLogsStmt = $db->query("
    SELECT a.id, a.action, a.details, a.created_at, u.username, u.first_name, u.last_name, u.role
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.id DESC
    LIMIT 6
");
$dashRecentLogs = $recentLogsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php // CSRF token meta tag — read by admin.js to protect all state-changing POST requests ?>
  <meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>" />
  <title>Dashboard — CyberAuth System</title>

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

  <!-- Design System & Stylesheets -->
  <link rel="stylesheet" href="../css/modern-design-system.css?v=<?= filemtime(__DIR__ . '/../css/modern-design-system.css') ?>" />
  <link rel="stylesheet" href="../css/cyberpunk-login.css" />
  <link rel="stylesheet" href="../css/cyberpunk-portal.css" />
  <link rel="stylesheet" href="../css/cyberpunk-register.css" />
  <style>
    html {
      overflow-y: scroll;
      scrollbar-gutter: stable;
    }
    body {
      overflow-x: clip !important;
      overflow-y: visible !important;
    }
    .cyber-main {
      height: auto !important;
      min-height: calc(100vh - 140px);
      margin-top: 70px;
      display: block !important;
      overflow: visible !important;
      padding: 2rem 0;
    }

    /* ── DASHBOARD GRID ─────────────────────────────────────────────────────── */
    .dash-wrap {
      max-width: 1440px;
      width: 100%;
      margin: 0 auto;
      padding: 0 1.75rem 2.5rem;
      display: grid;
      grid-template-columns: 280px minmax(0, 1fr);
      gap: 1.75rem;
      align-items: stretch !important;
      box-sizing: border-box;
      overflow: visible !important;
      position: relative;
    }

    /* ── Sidebar: fixed 280px width, stretches full column height ─────────── */
    .cyber-sidebar-col {
      grid-column: 1;
      width: 280px;
      min-width: 280px;
      max-width: 280px;
      height: 100%;
      align-self: stretch !important;
      position: relative;
      z-index: 50;
      flex-shrink: 0;
      box-sizing: border-box;
    }
    /* Sticky navigation card inside the sidebar column */
    .tabs {
      position: -webkit-sticky !important;
      position: sticky !important;
      top: 90px !important;
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
      background: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 0.85rem;
      backdrop-filter: blur(12px);
      box-shadow: var(--card-shadow);
      box-sizing: border-box;
      z-index: 60;
    }

    /* ── Main content wrapper ──────────────────────────────────────────────── */
    .dash-main-content {
      grid-column: 2;
      width: 100%;
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 1.35rem;
    }

    .dash-main-content > .tab-content {
      width: 100%;
      min-width: 0;
    }

    .welcome {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 1.15rem 1.5rem;
      background: var(--bg-surface);
      box-shadow: var(--card-shadow);
    }
    .pending-setup-banner { width: 100%; }
    .welcome .title {
      font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: var(--text-primary);
      font-size: 1.25rem;
    }
    .welcome .sub {
      color: var(--text-muted);
      font-size: 0.88rem;
      margin-top: 0.2rem;
    }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.3rem 0.75rem;
      border: 1px solid var(--success-border);
      background: var(--success-light);
      border-radius: 9999px;
      color: var(--success);
      font-weight: 700;
      font-size: 0.76rem;
      letter-spacing: 0.03em;
      text-transform: uppercase;
    }
    .sidebar-nav-title {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      color: var(--text-muted);
      padding: 0.4rem 0.65rem 0.65rem;
      border-bottom: 1px solid var(--border-subtle);
      margin-bottom: 0.35rem;
      text-transform: uppercase;
      user-select: none;
    }
    .sidebar-nav-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--primary);
      box-shadow: 0 0 8px var(--primary-glow);
      flex-shrink: 0;
    }
    .tab-btn {
      position: relative;
      display: flex;
      align-items: center;
      gap: 0.65rem;
      width: 100%;
      background: transparent;
      border: 1px solid transparent;
      color: var(--text-secondary);
      padding: 0.7rem 0.85rem;
      cursor: pointer;
      font-family: 'Inter', sans-serif;
      font-size: 0.86rem;
      font-weight: 600;
      border-radius: 10px;
      transition: all 0.2s ease;
      white-space: nowrap;
      text-align: left;
      overflow: hidden;
    }
    .tab-btn .tab-btn-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 18px;
      height: 18px;
      color: var(--text-muted);
      transition: all 0.2s ease;
      flex-shrink: 0;
    }
    .tab-btn:hover {
      color: var(--text-primary);
      background: var(--bg-surface-hover);
    }
    .tab-btn:hover .tab-btn-icon {
      color: var(--primary);
    }
    .tab-btn.active {
      color: var(--primary);
      background: var(--primary-light);
      font-weight: 700;
      border-color: var(--primary-border);
    }
    .tab-btn.active .tab-btn-icon {
      color: var(--primary);
    }
    .tab-btn::before {
      content: '';
      position: absolute;
      left: 0;
      top: 18%;
      bottom: 18%;
      width: 3.5px;
      background: transparent;
      border-radius: 0 3px 3px 0;
      transition: all 0.2s ease;
    }
    .tab-btn.active::before {
      background: var(--primary);
    }

    @keyframes fadeInContent {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .tab-content {
      display: none;
      width: 100%;
      min-height: 620px;
    }
    .tab-content.active {
      display: block;
      animation: fadeInContent 0.2s ease-out forwards;
    }

    /* ── Modern Panels ──────────────────────────────────────────────────────── */
    .panel {
      border: 1px solid var(--border-color);
      border-radius: 18px;
      background: var(--bg-surface);
      overflow: visible; /* Must be visible so sticky controls stick cleanly to viewport */
      margin-bottom: 1.25rem;
      width: 100%;
      min-height: 620px;
      display: flex;
      flex-direction: column;
      box-shadow: var(--card-shadow);
      transition: all 0.2s ease;
      position: relative;
    }
    .panel-hd {
      padding: 1.15rem 1.6rem;
      border-bottom: 1px solid var(--border-subtle);
      border-top-left-radius: 18px;
      border-top-right-radius: 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-shrink: 0;
      background: var(--bg-surface);
    }
    .panel-title {
      font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
      font-weight: 700;
      letter-spacing: -0.01em;
      color: var(--text-primary);
      font-size: 1.15rem;
    }
    .panel-bd {
      padding: 1.5rem;
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: visible;
      position: relative;
    }
    .panel-bd.has-sticky-filter,
    .panel-bd:has(.sticky-search-bar) {
      padding-top: 0 !important;
    }
    .panel-bd > div[style*="overflow-x: auto"] {
      flex: 1;
    }

    /* ── Modern Data Tables ─────────────────────────────────────────────────── */
    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      color: var(--text-primary);
      font-size: 0.88rem;
    }
    th, td {
      padding: 0.85rem 1rem;
      text-align: left;
      border-bottom: 1px solid var(--border-subtle);
      vertical-align: middle;
    }
    th {
      color: var(--text-muted);
      font-family: 'Inter', sans-serif;
      font-size: 0.76rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      background: var(--bg-surface-hover);
      border-top: 1px solid var(--border-subtle);
    }
    th:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
    th:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
    tr:hover td {
      background: var(--bg-surface-hover);
    }

    .btn-small {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      background: var(--bg-surface-hover);
      border: 1px solid var(--border-color);
      color: var(--text-primary);
      padding: 0.38rem 0.85rem;
      cursor: pointer;
      font-family: 'Inter', sans-serif;
      font-size: 0.8rem;
      font-weight: 600;
      border-radius: 8px;
      transition: all 0.2s ease;
    }
    .btn-small:hover {
      background: var(--primary);
      color: #ffffff;
      border-color: var(--primary);
      box-shadow: 0 2px 8px var(--primary-glow);
    }
    .btn-small.btn-primary {
      background: var(--primary) !important;
      border-color: var(--primary) !important;
      color: #ffffff !important;
      box-shadow: 0 2px 8px var(--primary-glow);
    }
    .btn-small.btn-primary:hover {
      background: var(--primary-hover) !important;
      border-color: var(--primary-hover) !important;
      color: #ffffff !important;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px var(--primary-glow);
    }
    .btn-danger {
      border-color: var(--danger-border);
      color: var(--danger);
      background: var(--danger-light);
    }
    .btn-danger:hover {
      background: var(--danger);
      color: #ffffff;
      border-color: var(--danger);
    }

    .controls {
      display: flex;
      gap: 0.75rem;
      margin-bottom: 1.25rem;
      align-items: center;
      flex-wrap: wrap;
    }
    .controls input, .controls select {
      background: var(--input-bg);
      border: 1px solid var(--input-border);
      color: var(--input-color);
      padding: 0.58rem 0.95rem;
      border-radius: 10px;
      font-family: 'Inter', sans-serif;
      font-size: 0.88rem;
      outline: none;
      transition: all 0.2s ease;
    }
    .controls input:focus, .controls select:focus {
      border-color: var(--input-focus-border);
      box-shadow: 0 0 0 3px var(--input-focus-ring);
    }

    /* ── Sticky Table Search & Filter Bar ──────────────────────────────────── */
    .sticky-search-bar {
      position: -webkit-sticky !important;
      position: sticky !important;
      top: 70px !important; /* Immediately flush below the 70px fixed header */
      z-index: 40 !important;
      background: var(--bg-surface) !important;
      padding: 0.95rem 1.5rem !important;
      margin: 0 -1.5rem 1.25rem -1.5rem !important;
      border-bottom: 1px solid var(--border-subtle) !important;
      box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.08), 0 2px 6px -1px rgba(0, 0, 0, 0.04) !important;
      backdrop-filter: blur(16px) !important;
      -webkit-backdrop-filter: blur(16px) !important;
      display: flex !important;
      flex-wrap: wrap !important;
      gap: 0.65rem !important;
      align-items: center !important;
      border-top-left-radius: 0 !important;
      border-top-right-radius: 0 !important;
      transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    /* ── Modern Compact Pagination Bar ──────────────────────────────────────── */
    .pagination {
      margin-top: 1.25rem;
      width: 100%;
    }
    .cyber-pagination-container {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      flex-wrap: wrap !important;
      gap: 0.65rem 1rem !important;
      padding: 0.5rem 1rem !important;
      background: var(--bg-surface-hover) !important;
      border: 1px solid var(--border-color) !important;
      border-radius: 12px !important;
      box-sizing: border-box !important;
      min-height: 48px !important;
    }
    .cyber-pagination-summary {
      font-size: 0.82rem !important;
      color: var(--text-muted) !important;
      font-family: 'Inter', sans-serif !important;
      white-space: nowrap !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 0.25rem !important;
      line-height: normal !important;
    }
    .cyber-pagination-summary .cyber-page-num {
      color: var(--primary) !important;
      font-family: 'JetBrains Mono', monospace !important;
      font-weight: 700 !important;
    }
    .cyber-pagination-nav {
      display: inline-flex !important;
      align-items: center !important;
      gap: 0.45rem !important;
    }
    .cyber-page-nav-btn {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      height: 32px !important;
      padding: 0 0.75rem !important;
      background: var(--bg-surface) !important;
      border: 1px solid var(--border-color) !important;
      color: var(--text-primary) !important;
      border-radius: 8px !important;
      font-size: 0.8rem !important;
      font-weight: 600 !important;
      font-family: 'Inter', sans-serif !important;
      cursor: pointer !important;
      transition: all 0.15s ease !important;
      white-space: nowrap !important;
      user-select: none !important;
      line-height: normal !important;
      box-sizing: border-box !important;
    }
    .cyber-page-nav-btn:hover:not(:disabled) {
      background: var(--primary) !important;
      border-color: var(--primary) !important;
      color: #ffffff !important;
      box-shadow: 0 2px 8px var(--primary-glow) !important;
    }
    .cyber-page-nav-btn:disabled {
      opacity: 0.35 !important;
      cursor: not-allowed !important;
      background: transparent !important;
    }

    /* Smaller, compact Page Selector Box */
    .cyber-page-select-box {
      display: inline-flex !important;
      flex-direction: row !important;
      flex-wrap: nowrap !important;
      align-items: center !important;
      justify-content: center !important;
      white-space: nowrap !important;
      gap: 0.35rem !important;
      background: var(--bg-surface) !important;
      border: 1px solid var(--border-color) !important;
      padding: 0 0.55rem !important;
      border-radius: 8px !important;
      height: 32px !important;
      line-height: normal !important;
      box-sizing: border-box !important;
    }
    .cyber-page-label,
    .cyber-page-total {
      display: inline !important;
      font-size: 0.78rem !important;
      font-weight: 500 !important;
      color: var(--text-muted) !important;
      white-space: nowrap !important;
      user-select: none !important;
      line-height: 1 !important;
      margin: 0 !important;
      padding: 0 !important;
    }
    select.cyber-page-select {
      display: inline-block !important;
      width: auto !important;
      min-width: 44px !important;
      max-width: 62px !important;
      height: 24px !important;
      min-height: 24px !important;
      max-height: 24px !important;
      line-height: 22px !important;
      padding: 0 0.35rem !important;
      font-family: 'JetBrains Mono', monospace !important;
      font-size: 0.8rem !important;
      font-weight: 700 !important;
      color: var(--text-primary) !important;
      background: var(--bg-card) !important;
      border: 1px solid var(--border-color) !important;
      box-shadow: none !important;
      border-radius: 5px !important;
      cursor: pointer !important;
      outline: none !important;
      text-align: center !important;
      text-align-last: center !important;
      margin: 0 !important;
      vertical-align: middle !important;
      box-sizing: border-box !important;
      -webkit-appearance: menulist !important;
      appearance: menulist !important;
    }
    select.cyber-page-select:focus {
      border-color: var(--primary) !important;
      box-shadow: 0 0 0 2px var(--primary-glow) !important;
    }
    select.cyber-page-select option {
      background: var(--bg-surface);
      color: var(--text-primary);
    }

    /* Smaller, compact Page Entries Box */
    .cyber-pagination-limit {
      display: inline-flex !important;
      flex-direction: row !important;
      flex-wrap: nowrap !important;
      align-items: center !important;
      justify-content: center !important;
      white-space: nowrap !important;
      gap: 0.35rem !important;
      background: var(--bg-surface) !important;
      border: 1px solid var(--border-color) !important;
      padding: 0 0.55rem !important;
      border-radius: 8px !important;
      height: 32px !important;
      line-height: normal !important;
      box-sizing: border-box !important;
    }
    .cyber-limit-label {
      display: inline !important;
      font-size: 0.78rem !important;
      font-weight: 500 !important;
      color: var(--text-muted) !important;
      white-space: nowrap !important;
      user-select: none !important;
      line-height: 1 !important;
      margin: 0 !important;
      padding: 0 !important;
    }
    select.cyber-limit-select {
      display: inline-block !important;
      width: auto !important;
      min-width: 48px !important;
      max-width: 66px !important;
      height: 24px !important;
      min-height: 24px !important;
      max-height: 24px !important;
      line-height: 22px !important;
      padding: 0 0.35rem !important;
      font-family: 'JetBrains Mono', monospace !important;
      font-size: 0.8rem !important;
      font-weight: 700 !important;
      color: var(--text-primary) !important;
      background: var(--bg-card) !important;
      border: 1px solid var(--border-color) !important;
      box-shadow: none !important;
      border-radius: 5px !important;
      cursor: pointer !important;
      outline: none !important;
      text-align: center !important;
      text-align-last: center !important;
      margin: 0 !important;
      vertical-align: middle !important;
      box-sizing: border-box !important;
      -webkit-appearance: menulist !important;
      appearance: menulist !important;
    }
    select.cyber-limit-select:focus {
      border-color: var(--primary) !important;
      box-shadow: 0 0 0 2px var(--primary-glow) !important;
    }
    select.cyber-limit-select option {
      background: var(--bg-surface);
      color: var(--text-primary);
    }

    /* ── Super Admin Status Monitor ─────────────────────────── */
    .sa-status-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 1.15rem;
      border-radius: 12px;
      background: var(--bg-surface-hover);
      border: 1px solid var(--border-color);
      transition: all 0.2s ease;
      gap: 0.8rem;
      flex-wrap: wrap;
    }
    .sa-status-row.sa-is-active {
      background: var(--success-light);
      border-color: var(--success-border);
    }
    .sa-status-row-left { display: flex; align-items: center; gap: 0.75rem; }
    .sa-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .sa-dot-active  { background: var(--success); box-shadow: 0 0 8px var(--success-glow); }
    .sa-dot-offline { background: var(--border-hover); }
    .sa-name { font-weight: 600; color: var(--text-primary); font-size: 0.9rem; }
    .sa-sub  { font-size: 0.78rem; color: var(--text-muted); }
    .sa-session-badge {
      padding: 0.25rem 0.7rem;
      border-radius: 9999px;
      font-size: 0.72rem;
      font-family: 'Inter', sans-serif;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      font-weight: 700;
    }
    .sa-badge-active  { background: var(--success-light); color: var(--success); border: 1px solid var(--success-border); }
    .sa-badge-offline { background: var(--bg-surface-hover); color: var(--text-dim); border: 1px solid var(--border-color); }
    .sa-badge-you     { background: var(--primary-light); color: var(--primary); border: 1px solid var(--primary-border); }
  </style>
</head>
<body>
  <!-- Subtle Ambient Canvas -->
  <div class="cyber-grid" aria-hidden="true"></div>
  <div class="neon-particles" aria-hidden="true"></div>

  <header class="cyber-header">
    <div class="header-content">
      <a href="dashboard.php" class="logo-container">
        <div class="logo-icon">⚡</div>
        <h1 class="logo-text">CYBER<span class="accent">AUTH</span></h1>
      </a>
      <div class="header-actions">
        <span class="badge" style="font-size:0.75rem;">ROLE: <?= strtoupper(str_replace('_', ' ', $role)) ?></span>
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

  <main class="cyber-main">
    <div class="dash-wrap">
      <!-- ── Dedicated Separated Sidebar Navigation ────────────── -->
      <aside class="cyber-sidebar-col">
<?php
$privs = $user['privileges'] ? json_decode($user['privileges'], true) : [];
if (!is_array($privs)) $privs = [];
?>
        <div class="tabs">
          <div class="sidebar-nav-title">
            <span class="sidebar-nav-dot"></span>
            Navigation
          </div>
          <button class="tab-btn active" data-tab="overview" onclick="showTab('overview')">
            <svg class="tab-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7"></rect>
              <rect x="14" y="3" width="7" height="7"></rect>
              <rect x="14" y="14" width="7" height="7"></rect>
              <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <span>Dashboard</span>
          </button>
          <button class="tab-btn" data-tab="profile" onclick="showTab('profile')">
            <svg class="tab-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>My Profile</span>
          </button>
          <?php if ($role === 'super_admin' || ($role === 'admin' && in_array('view_accounts', $privs))): ?>
            <button class="tab-btn" data-tab="users" onclick="showTab('users')">
              <svg class="tab-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
              <span>User Management</span>
            </button>
          <?php endif; ?>
          <?php if ($role === 'super_admin' || $role === 'admin'): ?>
            <button class="tab-btn" data-tab="logs" onclick="showTab('logs')">
              <svg class="tab-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
              <span>Activity Logs</span>
            </button>
          <?php else: ?>
            <button class="tab-btn" data-tab="logs" onclick="showTab('logs')">
              <svg class="tab-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
              <span>My Activity Logs</span>
            </button>
          <?php endif; ?>
          <?php if ($role === 'super_admin' || ($role === 'admin' && in_array('account_deletion_requests', $privs))): ?>
            <button class="tab-btn" data-tab="delete_reqs" onclick="showTab('delete_reqs')">
              <svg class="tab-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
              <span>Deletion Requests</span>
            </button>
          <?php endif; ?>
        </div>
      </aside>

      <!-- ── Main Content Area ───────────────────────────────── -->
      <div class="dash-main-content">
        <section class="welcome">
          <div>
            <div class="title">Neon Dashboard</div>
            <div class="sub">Welcome, <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>!</div>
          </div>
          <div class="badge">System Online</div>
        </section>

        <!-- ── TAB: Dashboard Overview & Analytics ───────────────── -->
        <div id="overview" class="tab-content active">
          <div class="dash-overview-wrap">
            <!-- Hero Banner -->
            <div class="dash-overview-hero">
              <div>
                <h2 class="dash-hero-title">System Overview &amp; Analytics</h2>
                <p class="dash-hero-sub">Real-time telemetry, user privileges, access metrics, and security audit activity.</p>
              </div>
              <div class="dash-hero-badge">
                <span class="dash-beacon-dot"></span>
                <span>System Operational • Encrypted</span>
              </div>
            </div>

            <!-- 4 Metric KPI Cards -->
            <div class="dash-metrics-grid">
              <div class="dash-metric-card" onclick="showTab('users')" style="cursor:pointer;" title="Click to view User Directory">
                <div class="dash-metric-icon indigo">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                  </svg>
                </div>
                <div>
                  <div class="dash-metric-val" id="dashTotalUsers"><?= (int)$dashStats['total_users'] ?></div>
                  <div class="dash-metric-label">Total Accounts</div>
                  <div class="dash-metric-desc">Registered identity directory</div>
                </div>
              </div>

              <div class="dash-metric-card" onclick="showTab('users')" style="cursor:pointer;" title="Click to view Active Users">
                <div class="dash-metric-icon emerald">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <polyline points="9 12 11 14 15 10"></polyline>
                  </svg>
                </div>
                <div>
                  <div class="dash-metric-val" id="dashActiveUsers" style="color:var(--success);"><?= (int)$dashStats['active_users'] ?></div>
                  <div class="dash-metric-label">Active &amp; Verified</div>
                  <div class="dash-metric-desc">Approved access credentials</div>
                </div>
              </div>

              <div class="dash-metric-card" onclick="showTab('users')" style="cursor:pointer;" title="Click to view Pending/Setup Users">
                <div class="dash-metric-icon amber">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                </div>
                <div>
                  <div class="dash-metric-val" id="dashPendingUsers" style="color:var(--warning);"><?= (int)$dashStats['pending_users'] ?></div>
                  <div class="dash-metric-label">Pending Setup</div>
                  <div class="dash-metric-desc">Awaiting first login or verification</div>
                </div>
              </div>

              <div class="dash-metric-card" onclick="showTab('logs')" style="cursor:pointer;" title="Click to view Activity Logs">
                <div class="dash-metric-icon cyan">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                  </svg>
                </div>
                <div>
                  <div class="dash-metric-val" id="dashTodayLogs" style="color:var(--accent);"><?= $todayLogsCount ?></div>
                  <div class="dash-metric-label">Events Logged Today</div>
                  <div class="dash-metric-desc">Real-time audit security trail</div>
                </div>
              </div>
            </div>

            <!-- Quick Action Shortcuts Bar -->
            <div class="dash-actions-bar">
              <span class="dash-actions-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                Quick Actions
              </span>

              <?php if ($role === 'super_admin'): ?>
                <button type="button" class="dash-action-btn primary" onclick="openCreateAccountModal()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7.5" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                  <span>Create Account</span>
                </button>
              <?php endif; ?>

              <?php if ($role === 'super_admin' || ($role === 'admin' && in_array('view_accounts', $privs))): ?>
                <button type="button" class="dash-action-btn" onclick="showTab('users')">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                  <span>Manage Users</span>
                </button>
              <?php endif; ?>

              <button type="button" class="dash-action-btn" onclick="showTab('logs')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                <span>Audit Logs</span>
              </button>

              <?php if ($role === 'super_admin' || ($role === 'admin' && in_array('account_deletion_requests', $privs))): ?>
                <button type="button" class="dash-action-btn" onclick="showTab('delete_reqs')">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path></svg>
                  <span>Deletion Requests <?= $pendingDelCount > 0 ? "($pendingDelCount)" : "" ?></span>
                </button>
              <?php endif; ?>

              <button type="button" class="dash-action-btn" onclick="showTab('profile')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span>My Security Profile</span>
              </button>
            </div>

            <!-- Two-Column Grid: Distribution & Security Status / Live Activity Feed -->
            <div class="dash-split-grid">
              <!-- Left Column: Role Distribution & System Integrity -->
              <div class="dash-split-col">
                <!-- Card: Account Role Distribution -->
                <div class="dash-card">
                  <div class="dash-card-header">
                    <div class="dash-card-title">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="color:var(--primary);"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7.5" r="4"></circle></svg>
                      <span>Role Distribution</span>
                    </div>
                    <span style="font-size:0.78rem; font-weight:700; color:var(--text-muted);"><?= (int)$dashStats['total_users'] ?> Users</span>
                  </div>

                  <?php
                    $tot = max(1, (int)$dashStats['total_users']);
                    $saPct = round(((int)$dashStats['sa_count'] / $tot) * 100);
                    $adminPct = round(((int)$dashStats['admin_count'] / $tot) * 100);
                    $userPct = round(((int)$dashStats['user_count'] / $tot) * 100);
                  ?>

                  <div class="dash-role-row">
                    <div class="dash-role-info">
                      <span class="dash-role-name">Super Admins</span>
                      <span class="dash-role-count" id="dashRoleSaCount"><?= (int)$dashStats['sa_count'] ?> (<?= $saPct ?>%)</span>
                    </div>
                    <div class="dash-progress-track">
                      <div class="dash-progress-bar sa" id="dashRoleSaBar" style="width: <?= $saPct ?>%;"></div>
                    </div>
                  </div>

                  <div class="dash-role-row">
                    <div class="dash-role-info">
                      <span class="dash-role-name">Administrators</span>
                      <span class="dash-role-count" id="dashRoleAdminCount"><?= (int)$dashStats['admin_count'] ?> (<?= $adminPct ?>%)</span>
                    </div>
                    <div class="dash-progress-track">
                      <div class="dash-progress-bar admin" id="dashRoleAdminBar" style="width: <?= $adminPct ?>%;"></div>
                    </div>
                  </div>

                  <div class="dash-role-row">
                    <div class="dash-role-info">
                      <span class="dash-role-name">Standard Users</span>
                      <span class="dash-role-count" id="dashRoleUserCount"><?= (int)$dashStats['user_count'] ?> (<?= $userPct ?>%)</span>
                    </div>
                    <div class="dash-progress-track">
                      <div class="dash-progress-bar user" id="dashRoleUserBar" style="width: <?= $userPct ?>%;"></div>
                    </div>
                  </div>
                </div>

                <!-- Card: System Health & Security Controls -->
                <div class="dash-card">
                  <div class="dash-card-header">
                    <div class="dash-card-title">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="color:var(--success);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                      <span>Security &amp; Policy Status</span>
                    </div>
                    <span class="dash-health-status ok">Active</span>
                  </div>

                  <div class="dash-health-list">
                    <div class="dash-health-item">
                      <span class="dash-health-label">Authentication State</span>
                      <span class="dash-health-status ok">Session Active</span>
                    </div>
                    <div class="dash-health-item">
                      <span class="dash-health-label">CSRF Protection</span>
                      <span class="dash-health-status ok">Enforced (HMAC)</span>
                    </div>
                    <div class="dash-health-item">
                      <span class="dash-health-label">Super Admin Mutual Lock</span>
                      <span class="dash-health-status info">Protected (1 active)</span>
                    </div>
                    <div class="dash-health-item">
                      <span class="dash-health-label">Password Policy Standard</span>
                      <span class="dash-health-status ok">Strict (8+ Mix)</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right Column: Live Activity Feed -->
              <div class="dash-split-col">
                <div class="dash-card" style="height: 100%; display: flex; flex-direction: column;">
                  <div class="dash-card-header">
                    <div class="dash-card-title">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="color:var(--accent);"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                      <span>Recent Activity Audit</span>
                    </div>
                    <a class="dash-card-link" onclick="showTab('logs')">
                      <span>View All Logs</span>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                  </div>

                  <div class="dash-activity-feed" id="dashActivityFeed">
                    <?php if (empty($dashRecentLogs)): ?>
                      <div style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
                        No activity records found yet.
                      </div>
                    <?php else: ?>
                      <?php foreach ($dashRecentLogs as $log): ?>
                        <?php
                          $act = htmlspecialchars($log['action'] ?? 'ACTIVITY');
                          $badgeCls = 'dash-badge-blue';
                          if (strpos($act, 'LOGIN') !== false || strpos($act, 'CREATED') !== false || strpos($act, 'REGISTERED') !== false) {
                            $badgeCls = 'dash-badge-green';
                          } elseif (strpos($act, 'DELETED') !== false || strpos($act, 'BLOCKED') !== false) {
                            $badgeCls = 'dash-badge-red';
                          } elseif (strpos($act, 'UPDATED') !== false || strpos($act, 'TRANSFER') !== false) {
                            $badgeCls = 'dash-badge-amber';
                          }
                          $logTime = !empty($log['created_at']) ? date('M j, Y • g:i A', strtotime($log['created_at'])) : 'Recent';
                        ?>
                        <div class="dash-activity-item">
                          <span class="dash-activity-badge <?= $badgeCls ?>"><?= $act ?></span>
                          <div class="dash-activity-body">
                            <div class="dash-activity-text"><?= htmlspecialchars($log['details'] ?? 'Action performed') ?></div>
                            <div class="dash-activity-meta">
                              <span>by <strong>@<?= htmlspecialchars($log['username'] ?? 'system') ?></strong></span>
                              <span>&bull;</span>
                              <span><?= $logTime ?></span>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div id="profile" class="tab-content">
        <div class="profile-layout-grid">
          <!-- Left Sidebar Identity Card -->
          <div class="profile-id-card">
            <div class="avatar-wrap-lg">
              <div class="avatar-inner-lg" id="avatarInitial"><?= htmlspecialchars($firstInitial) ?></div>
            </div>
            <h2 class="profile-name-title" id="displayFullName"><?= htmlspecialchars($displayName) ?></h2>
            <div class="profile-handle" id="displayUsername">@<?= htmlspecialchars($user['username']) ?></div>

            <div class="profile-meta-list">
              <div class="meta-row">
                <span class="meta-label">ID Number</span>
                <span class="meta-val id-mono" id="displayIdno"><?= htmlspecialchars($user['id_number'] ?: '----') ?></span>
              </div>
              <div class="meta-row">
                <span class="meta-label">Role</span>
                <span class="meta-val role-badge <?= htmlspecialchars($role) ?>" id="displayRole"><?= strtoupper(str_replace('_', ' ', $role)) ?></span>
              </div>
              <div class="meta-row">
                <span class="meta-label">Gender</span>
                <span class="meta-val" id="displaySex"><?= htmlspecialchars(ucfirst($user['sex'] ?? '----')) ?></span>
              </div>
              <div class="meta-row">
                <span class="meta-label">Birthdate</span>
                <span class="meta-val" id="displayBirthdate"><?= htmlspecialchars($userBirthdate ?: '----') ?></span>
              </div>
              <div class="meta-row">
                <span class="meta-label">Age</span>
                <span class="meta-val" id="displayAge"><?= htmlspecialchars($userAge ?: '----') ?></span>
              </div>
            </div>

            <div class="profile-clearance-box <?= htmlspecialchars($role) ?>">
              <?php if ($role === 'super_admin'): ?>
                <strong>Master SuperAdmin Clearance</strong>
                <span>Single-Active Succession Active</span>
              <?php elseif ($role === 'admin'): ?>
                <strong>Administrative Clearance</strong>
                <span>Elevated Privileges Active</span>
              <?php else: ?>
                <strong>Standard Clearance</strong>
                <span>Verified Neural Account</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Right Details Panel -->
          <div class="profile-details-panel">
            <div class="profile-header-bar">
              <div>
                <h1 class="profile-section-title">Personal &amp; Security Details</h1>
                <p class="profile-section-subtitle">View and manage your identity, address, and login credentials.</p>
              </div>
              <div class="profile-header-actions">
                <button type="button" class="btn-cyber-edit" id="dashEditProfileBtn" onclick="toggleDashProfileEdit()">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                  <span id="dashEditProfileBtnText">Edit Details</span>
                </button>
                <button type="button" class="btn-cyber-save" id="dashSaveProfileBtn" style="display: none;" onclick="saveDashProfileDetails()">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Save Changes</span>
                </button>
                <button type="button" class="btn-cyber-cancel" id="dashCancelProfileBtn" style="display: none;" onclick="cancelDashProfileEdit()">
                  <span>Cancel</span>
                </button>
              </div>
            </div>

            <!-- Profile Tabs Navigation -->
            <div class="profile-tabs-nav" role="tablist">
              <button type="button" class="profile-tab-btn active" data-ptab="dashTabPersonal" onclick="switchDashProfileTab('dashTabPersonal', this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Personal Info</span>
              </button>
              <button type="button" class="profile-tab-btn" data-ptab="dashTabAddress" onclick="switchDashProfileTab('dashTabAddress', this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>Address</span>
              </button>
              <button type="button" class="profile-tab-btn" data-ptab="dashTabAccount" onclick="switchDashProfileTab('dashTabAccount', this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>Account &amp; Role</span>
              </button>
              <button type="button" class="profile-tab-btn" data-ptab="dashTabSecurity" onclick="switchDashProfileTab('dashTabSecurity', this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                <span>Security Questions</span>
              </button>
            </div>

            <!-- Profile Form Content -->
            <form id="dashProfileForm" novalidate onsubmit="return false;">
              <!-- TAB 1: Personal Info -->
              <div class="dash-tab-pane active" id="dashTabPersonal">
                <div class="form-section-card">
                  <div class="form-card-header">
                    <div class="form-card-title">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                      <span>Basic Identity Information</span>
                    </div>
                  </div>

                  <div class="form-grid-3">
                    <div class="field-group">
                      <label class="field-label">ID NUMBER</label>
                      <input type="text" class="custom-input input-mono" id="dash_idno" value="<?= htmlspecialchars($user['id_number'] ?: '') ?>" readonly>
                    </div>
                    <div class="field-group">
                      <label class="field-label">FIRST NAME <span class="req-star">*</span></label>
                      <input type="text" class="custom-input dash-profile-field" id="dash_firstname" value="<?= htmlspecialchars($user['first_name'] ?: '') ?>" readonly required maxlength="30">
                    </div>
                    <div class="field-group">
                      <label class="field-label">MIDDLE NAME</label>
                      <input type="text" class="custom-input dash-profile-field" id="dash_middlename" value="<?= htmlspecialchars($user['middle_name'] ?: '') ?>" readonly maxlength="30">
                    </div>
                    <div class="field-group">
                      <label class="field-label">LAST NAME <span class="req-star">*</span></label>
                      <input type="text" class="custom-input dash-profile-field" id="dash_lastname" value="<?= htmlspecialchars($user['last_name'] ?: '') ?>" readonly required maxlength="30">
                    </div>
                    <div class="field-group">
                      <label class="field-label">SUFFIX</label>
                      <select class="custom-select dash-profile-field" id="dash_suffix" disabled>
                        <option value="" <?= empty($user['extension']) ? 'selected' : '' ?>>None</option>
                        <option value="Jr" <?= in_array(strtolower($user['extension'] ?? ''), ['jr', 'jr.']) ? 'selected' : '' ?>>Jr.</option>
                        <option value="Sr" <?= in_array(strtolower($user['extension'] ?? ''), ['sr', 'sr.']) ? 'selected' : '' ?>>Sr.</option>
                        <option value="II" <?= (strtoupper($user['extension'] ?? '') === 'II') ? 'selected' : '' ?>>II</option>
                        <option value="III" <?= (strtoupper($user['extension'] ?? '') === 'III') ? 'selected' : '' ?>>III</option>
                        <option value="IV" <?= (strtoupper($user['extension'] ?? '') === 'IV') ? 'selected' : '' ?>>IV</option>
                      </select>
                    </div>
                    <div class="field-group">
                      <label class="field-label">GENDER</label>
                      <select class="custom-select dash-profile-field" id="dash_sex" disabled>
                        <option value="male" <?= strtolower($user['sex'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= strtolower($user['sex'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= strtolower($user['sex'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                      </select>
                    </div>
                    <div class="field-group">
                      <label class="field-label">BIRTHDATE</label>
                      <input type="date" class="custom-input dash-profile-field" id="dash_birthdate" value="<?= htmlspecialchars($userBirthdate) ?>" readonly onchange="autoCalcDashAge()">
                    </div>
                    <div class="field-group">
                      <label class="field-label">CALCULATED AGE</label>
                      <input type="text" class="custom-input" id="dash_age" value="<?= htmlspecialchars($userAge) ?>" readonly placeholder="Auto-calculated">
                    </div>
                  </div>
                </div>
              </div>

              <!-- TAB 2: Address -->
              <div class="dash-tab-pane" id="dashTabAddress">
                <div class="form-section-card">
                  <div class="form-card-header">
                    <div class="form-card-title">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                      <span>Residence &amp; Postal Location</span>
                    </div>
                  </div>

                  <div class="form-grid-2">
                    <div class="field-group">
                      <label class="field-label">PUROK / STREET ADDRESS</label>
                      <input type="text" class="custom-input dash-profile-field" id="dash_purok" value="<?= htmlspecialchars($user['purok_street'] ?: '') ?>" readonly maxlength="50" placeholder="e.g. Purok 4, Main Street">
                    </div>
                    <div class="field-group">
                      <label class="field-label">BARANGAY</label>
                      <input type="text" class="custom-input dash-profile-field" id="dash_brgy" value="<?= htmlspecialchars($user['barangay'] ?: '') ?>" readonly maxlength="50" placeholder="e.g. Brgy. San Isidro">
                    </div>
                    <div class="field-group">
                      <label class="field-label">MUNICIPALITY / CITY</label>
                      <input type="text" class="custom-input dash-profile-field" id="dash_city" value="<?= htmlspecialchars($user['city'] ?: '') ?>" readonly maxlength="50" placeholder="e.g. Angeles City">
                    </div>
                    <div class="field-group">
                      <label class="field-label">PROVINCE</label>
                      <input type="text" class="custom-input dash-profile-field" id="dash_province" value="<?= htmlspecialchars($user['province'] ?: '') ?>" readonly maxlength="50" placeholder="e.g. Pampanga">
                    </div>
                    <div class="field-group">
                      <label class="field-label">COUNTRY</label>
                      <input type="text" class="custom-input dash-profile-field" id="dash_country" value="<?= htmlspecialchars($user['country'] ?: 'Philippines') ?>" readonly maxlength="40">
                    </div>
                    <div class="field-group">
                      <label class="field-label">POSTAL / ZIP CODE</label>
                      <input type="text" class="custom-input dash-profile-field" id="dash_zipcode" value="<?= htmlspecialchars($user['zip_code'] ?: '') ?>" readonly maxlength="10" placeholder="e.g. 2009">
                    </div>
                  </div>
                </div>
              </div>

              <!-- TAB 3: Account & Role -->
              <div class="dash-tab-pane" id="dashTabAccount">
                <div class="form-section-card">
                  <div class="form-card-header">
                    <div class="form-card-title">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                      <span>Account Credentials &amp; Role</span>
                    </div>
                  </div>

                  <div class="form-grid-2">
                    <div class="field-group">
                      <label class="field-label">USERNAME <span class="req-star">*</span></label>
                      <input type="text" class="custom-input dash-profile-field" id="dash_username" value="<?= htmlspecialchars($user['username']) ?>" readonly required maxlength="25">
                    </div>
                    <div class="field-group">
                      <label class="field-label">EMAIL ADDRESS <span class="req-star">*</span></label>
                      <input type="email" class="custom-input dash-profile-field" id="dash_email" value="<?= htmlspecialchars($user['email']) ?>" readonly required maxlength="60">
                    </div>
                    <div class="field-group">
                      <label class="field-label">PHONE NUMBER</label>
                      <input type="tel" class="custom-input dash-profile-field" id="dash_phone" value="<?= htmlspecialchars($user['phone_number'] ?: $user['phone'] ?: '') ?>" readonly maxlength="15" placeholder="09XXXXXXXXX">
                    </div>
                    <div class="field-group">
                      <label class="field-label">SYSTEM ROLE</label>
                      <input type="text" class="custom-input" value="<?= strtoupper(str_replace('_', ' ', $role)) ?>" readonly style="color: var(--accent-cyan); font-weight: 700; font-family: var(--font-heading);">
                    </div>
                  </div>

                  <div class="profile-pw-box" id="dashPwBox">
                    <div>
                      <strong style="color: var(--text-primary); font-size: 0.88rem; display: block; font-family: var(--font-heading); margin-bottom: 2px;">System Account Password</strong>
                      <span id="dashPwHint" class="dash-pw-hint locked">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <span>Locked &bull; Click "Edit Details" above to change password</span>
                      </span>
                    </div>
                    <button type="button" class="cyber-btn btn-change-pw-action" id="dashChangePasswordBtn" onclick="handleDashChangePasswordClick()" disabled title="Click 'Edit Details' first to unlock password change">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                      <span>Change Password</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- TAB 4: Security Questions -->
              <div class="dash-tab-pane" id="dashTabSecurity">
                <div class="form-section-card">
                  <div class="form-card-header">
                    <div class="form-card-title">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                      <span>Security Recovery Questions</span>
                    </div>
                  </div>

                  <div class="form-grid-2" style="margin-bottom: 0.9rem;">
                    <div class="field-group">
                      <label class="field-label">QUESTION 1</label>
                      <input type="text" class="custom-input" value="<?= htmlspecialchars($secQuestions['question1'] ?? 'What is your mother\'s maiden name?') ?>" readonly>
                    </div>
                    <div class="field-group">
                      <label class="field-label">ANSWER 1 (MASKED)</label>
                      <input type="password" class="custom-input" value="••••••••••••" readonly>
                    </div>
                  </div>

                  <div class="form-grid-2" style="margin-bottom: 0.9rem;">
                    <div class="field-group">
                      <label class="field-label">QUESTION 2</label>
                      <input type="text" class="custom-input" value="<?= htmlspecialchars($secQuestions['question2'] ?? 'What was the name of your first pet?') ?>" readonly>
                    </div>
                    <div class="field-group">
                      <label class="field-label">ANSWER 2 (MASKED)</label>
                      <input type="password" class="custom-input" value="••••••••••••" readonly>
                    </div>
                  </div>

                  <div class="form-grid-2">
                    <div class="field-group">
                      <label class="field-label">QUESTION 3</label>
                      <input type="text" class="custom-input" value="<?= htmlspecialchars($secQuestions['question3'] ?? 'What is the name of the city where you were born?') ?>" readonly>
                    </div>
                    <div class="field-group">
                      <label class="field-label">ANSWER 3 (MASKED)</label>
                      <input type="password" class="custom-input" value="••••••••••••" readonly>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <?php if ($role === 'super_admin' || ($role === 'admin' && in_array('view_accounts', $privs))): ?>
      <div id="users" class="tab-content">
        <div class="panel">
          <div class="panel-hd" style="display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:0.75rem;">
              <div class="panel-title">Manage Users</div>
              <div id="usersRealtimePill" class="realtime-status-pill" title="Click Refresh to update directory">
                <span class="live-dot-pulse" style="animation: none; opacity: 0.6;"></span>
                <span class="live-status-text">DIRECTORY</span>
              </div>
            </div>
            <div style="display:flex; align-items:center; gap:0.5rem;">
              <button type="button" class="btn-refresh-micro" id="btnUsersManualRefresh" onclick="manualRefreshUsers()" title="Sync Directory Now">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                <span>Refresh</span>
              </button>
            </div>
          </div>
          <div class="panel-bd has-sticky-filter" style="padding-top: 0;">
            <form id="usersDirectoryFilterForm" class="controls sticky-search-bar" role="search" autocomplete="off" onsubmit="event.preventDefault(); loadUsers(1); return false;">
              <input type="search" id="filterEmpId" name="directory_search_query" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore data-form-type="other" placeholder="Search by name, ID, username, email..." onkeyup="if(event.key==='Enter')loadUsers(1)" style="min-width: 220px; flex: 1;">
              <select id="filterUserRole" onchange="loadUsers(1)" style="padding: 0.42rem 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-primary); font-size: 0.82rem; font-family: inherit;">
                <option value="">All Roles</option>
                <option value="super_admin">Super Admin</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
              </select>
              <select id="filterUserStatus" onchange="loadUsers(1)" style="padding: 0.42rem 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-primary); font-size: 0.82rem; font-family: inherit;">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
                <option value="pending">Pending</option>
              </select>
              <button type="submit" class="btn-small">Filter</button>
              <button type="button" class="btn-small btn-secondary" onclick="resetUserFilters()">Reset</button>
              <?php if ($role === 'super_admin'): ?>
                <button type="button" class="btn-small btn-primary" onclick="openCreateAccountModal()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7.5" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                  </svg>
                  <span>Create Account</span>
                </button>
                <button type="button" class="btn-small btn-gold" id="btnTransferSaHeader" onclick="openSuperadminSuccessionModal()" title="Formally transfer Superadmin rights or resign">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                  </svg>
                  <span>Transfer Superadmin</span>
                </button>
              <?php endif; ?>
            </form>
            <div class="table-responsive-wrapper" style="overflow-x: auto;">
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
          <div class="panel-hd"><div class="panel-title">All Activity Logs</div></div>
          <div class="panel-bd has-sticky-filter" style="padding-top: 0;">
            <div class="controls sticky-search-bar">
              <select id="filterLogAction" onchange="loadLogs(1)">
                <option value="">All Actions</option>
                <option value="ACCOUNT_CREATED">Account Created</option>
                <option value="LOGIN">User Login</option>
                <option value="LOGOUT">User Logout</option>
                <option value="ROLE_UPDATED">Role Updated</option>
                <option value="ROLE_TRANSFER">Role Transfer</option>
                <option value="STATUS_UPDATED">Status Updated</option>
                <option value="PRIVILEGES_UPDATED">Privileges Updated</option>
                <option value="ACCOUNT_DELETED">Account Deleted</option>
                <option value="PROFILE_UPDATED">Profile Updated</option>
                <option value="PASSWORD_CHANGED">Password Changed</option>
                <option value="SETUP_COMPLETED">Setup Completed</option>
              </select>
              <input type="text" id="filterLogSearch" placeholder="Search user or details..." onkeyup="if(event.key==='Enter')loadLogs(1)" style="min-width: 200px;">
              <input type="date" id="filterDate">
              <button class="btn-small" onclick="loadLogs(1)">Filter</button>
              <button class="btn-small btn-secondary" onclick="resetLogFilters()">Reset</button>
            </div>
            <div style="overflow-x: auto;">
              <table id="logsTable">
                <thead>
                  <tr>
                    <th>Date & Time</th>
                    <th>ID Num</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Details</th>
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
          <div class="panel-hd"><div class="panel-title">My Activity Logs</div></div>
          <div class="panel-bd has-sticky-filter" style="padding-top: 0;">
            <div class="controls sticky-search-bar">
              <input type="date" id="filterDate">
              <button class="btn-small" onclick="loadLogs(1)">Search</button>
              <button class="btn-small btn-secondary" onclick="resetLogFilters()">Reset</button>
            </div>
            <div style="overflow-x: auto;">
              <table id="logsTable">
                <thead>
                  <tr>
                    <th>Date & Time</th>
                    <th>Action</th>
                    <th>Details</th>
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
      </div> <!-- /dash-main-content -->
    </div> <!-- /dash-wrap -->

  <!-- Create New Account Modal Dialog (Cyberpunk Theme) -->
  <div id="createAccountModal" class="ca-modal-backdrop" onclick="handleCreateAccountBackdropClick(event)">
    <div class="ca-modal-container" role="dialog" aria-modal="true" aria-labelledby="caModalTitle">
      <!-- Modal Header -->
      <div class="ca-modal-header">
        <div class="ca-header-left">
          <div class="ca-header-icon">
            <!-- user-plus icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="8.5" cy="7.5" r="4"></circle>
              <line x1="20" y1="8" x2="20" y2="14"></line>
              <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
          </div>
          <div>
            <h2 id="caModalTitle" class="ca-modal-title">Create New Account</h2>
            <p class="ca-modal-subtitle">Complete the structured profile, credentials, and privilege permissions.</p>
          </div>
        </div>
        <button type="button" class="ca-close-btn" onclick="closeCreateAccountModal()" aria-label="Close dialog" title="Close">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Modal Body (One Section Card) -->
      <div class="ca-modal-body">
        <form id="createAccountForm" onsubmit="submitCreateAccountForm(event)" novalidate>
          <div class="ca-section-card">
            <!-- Section Header Row -->
            <div class="ca-section-header">
              <div class="ca-section-title">
                <!-- lock icon -->
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <span>ACCOUNT CREDENTIALS &amp; ACCESS</span>
              </div>
              <span class="ca-section-tag">Login &amp; Role</span>
            </div>
            <div class="ca-section-divider"></div>

            <!-- Form Layout (2-column grid) -->
            <div class="ca-form-grid">
              <!-- Row 1: ROLE & ID NUMBER -->
              <div class="ca-field-group">
                <label class="ca-label" for="caRoleSelect">
                  ROLE <span class="ca-asterisk">*</span>
                </label>
                <div class="ca-select-wrap">
                  <select id="caRoleSelect" class="ca-select" onchange="validateRoleField()">
                    <option value="user" selected>User</option>
                    <option value="admin">Admin</option>
                    <?php if ($role === 'super_admin'): ?>
                    <option value="super_admin">Super Admin</option>
                    <?php endif; ?>
                  </select>
                  <span class="ca-select-arrow" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                  </span>
                </div>
                <div class="ca-error-msg" id="caRoleError">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                  <span id="caRoleErrorText">Please select a role</span>
                </div>
              </div>

              <div class="ca-field-group">
                <label class="ca-label" for="caIdNumber">
                  ID NUMBER
                </label>
                <input type="text" id="caIdNumber" class="ca-input ca-mono" readonly value="" placeholder="YYYY-NNNN" tabindex="-1">
                <div class="ca-helper-note">Auto-generated system identifier</div>
              </div>

              <!-- Row 2: USERNAME & EMAIL ADDRESS -->
              <div class="ca-field-group">
                <label class="ca-label" for="caUsername">
                  USERNAME <span class="ca-asterisk">*</span>
                </label>
                <input type="text" id="caUsername" class="ca-input" placeholder="Enter username" maxlength="50" autocomplete="off" onblur="validateUsernameField()" oninput="clearFieldError('caUsername')">
                <div class="ca-error-msg" id="caUsernameError">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                  <span id="caUsernameErrorText">Username is required</span>
                </div>
              </div>

              <div class="ca-field-group">
                <label class="ca-label" for="caEmail">
                  EMAIL ADDRESS <span class="ca-asterisk">*</span>
                </label>
                <input type="email" id="caEmail" class="ca-input" placeholder="e.g. user@example.com" maxlength="100" autocomplete="off" onblur="validateEmailField()" oninput="clearFieldError('caEmail')">
                <div class="ca-error-msg" id="caEmailError">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                  <span id="caEmailErrorText">Valid email address is required</span>
                </div>
              </div>

              <!-- Row 3: INITIAL PASSWORD (full width) -->
              <div class="ca-field-group ca-col-full">
                <div class="ca-label-row">
                  <label class="ca-label" for="caPassword" style="margin-bottom:0;">
                    INITIAL PASSWORD
                  </label>
                  <button type="button" class="ca-reset-link" onclick="resetDefaultPassword()" title="Reset to default password">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="1 4 1 10 7 10"></polyline>
                      <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    <span>Reset to @Abcde12345</span>
                  </button>
                </div>
                <div class="ca-password-input-wrap">
                  <input type="password" id="caPassword" class="ca-input ca-mono" value="@Abcde12345" placeholder="Temporary password" oninput="updatePasswordStrength(this.value)">
                  <button type="button" class="ca-eye-btn" onclick="togglePasswordVisibility('caPassword', this)" title="Show/Hide password" aria-label="Toggle password visibility">
                    <svg id="caEyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                </div>

                <!-- Password strength bar & labels -->
                <div class="ca-strength-container">
                  <div class="ca-strength-bar">
                    <div class="ca-strength-segment" id="caSegment1"></div>
                    <div class="ca-strength-segment" id="caSegment2"></div>
                    <div class="ca-strength-segment" id="caSegment3"></div>
                    <div class="ca-strength-segment" id="caSegment4"></div>
                  </div>
                  <div class="ca-strength-footer">
                    <span class="ca-strength-hint">User will be required to change password on first login</span>
                    <span class="ca-strength-badge" id="caStrengthBadge">Strong</span>
                  </div>
                </div>
                <div class="ca-error-msg" id="caPasswordError">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                  <span id="caPasswordErrorText">Password is required</span>
                </div>
              </div>
            </div>

            <!-- Superadmin Handover Setting: only shown when Role === Super Admin -->
            <div id="caSuperadminHandoverGroup" class="ca-handover-setting-box" style="display: none; margin-top: 1.1rem; padding: 1.1rem; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-surface-subtle); box-shadow: var(--shadow-xs);">
              <div style="display: flex; align-items: flex-start; gap: 0.9rem;">
                <div style="padding-top: 3px;">
                  <input type="checkbox" id="caDeactivateOnLogout" class="ca-custom-checkbox" style="width: 19px; height: 19px; cursor: pointer; accent-color: var(--primary);">
                </div>
                <div style="flex: 1;">
                  <label for="caDeactivateOnLogout" style="display: block; font-weight: 600; font-size: 0.92rem; color: var(--text-primary); cursor: pointer; margin-bottom: 0.3rem;">
                    Deactivate My Account After Logout
                  </label>
                  <p style="margin: 0; font-size: 0.83rem; color: var(--text-muted); line-height: 1.5;">
                    If enabled, your account will become Inactive when you log out after creating the new Superadmin. You will not be able to log in again until an authorized Superadmin reactivates your account.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer Buttons -->
          <div class="ca-modal-footer">
            <button type="button" class="ca-btn ca-btn-cancel" onclick="closeCreateAccountModal()">
              Cancel
            </button>
            <button type="submit" id="caSubmitBtn" class="ca-btn ca-btn-submit">
              <span id="caSubmitSpinner" class="ca-spinner" style="display:none;"></span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="ca-submit-icon">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7.5" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
              </svg>
              <span id="caSubmitText">Create Account</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Create Superadmin Handover Confirmation Warning Modal -->
  <div id="createSuperadminConfirmModal" class="modern-modal-backdrop" style="display:none;" onclick="if(event.target===this) closeCreateSuperadminConfirmModal()">
    <div class="modern-modal-card" style="max-width: 580px;">
      <div class="modern-modal-header">
        <div class="modal-header-branding">
          <div class="modal-icon-badge warning" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
              <line x1="12" y1="9" x2="12" y2="13"/>
              <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
          </div>
          <div>
            <h3 class="modal-title" style="font-size: 1.05rem;">Warning: Creating a New Superadmin May Affect Your Account Access</h3>
            <p class="modal-subtitle">Mandatory Handover Consequence &amp; Access Advisory</p>
          </div>
        </div>
        <button type="button" class="modal-close-btn" onclick="closeCreateSuperadminConfirmModal()" aria-label="Close dialog">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>

      <div class="modern-modal-body" style="padding: 1.25rem 1.5rem;">
        <div class="succession-warning-box" style="margin-bottom: 1rem; border-left: 4px solid var(--warning, #f59e0b); background: var(--warning-light, rgba(245, 158, 11, 0.08)); padding: 1.1rem; border-radius: 8px;">
          <div class="warning-title" style="color: var(--warning, #d97706); font-weight: 700; font-size: 0.86rem; letter-spacing: 0.03em; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
            <span>⚠️</span> SUPERADMIN HANDOVER ADVISORY
          </div>
          <p style="color: var(--text-primary); font-size: 0.88rem; line-height: 1.55; margin: 0 0 0.6rem;">
            You are about to create a new Superadmin account. Depending on the selected handover settings, your account may become Inactive after you log out. Once your account becomes Inactive, you will not be able to log in again until the new Superadmin reactivates your account.
          </p>
          <p style="color: var(--text-muted); font-size: 0.84rem; line-height: 1.5; margin: 0;">
            The new Superadmin will be responsible for managing your account status. Make sure you understand these consequences before continuing.
          </p>
        </div>

        <!-- Dynamic Handover Setting Callout -->
        <div id="caConfirmHandoverStatusBox" style="padding: 1rem 1.15rem; border-radius: 10px; font-size: 0.86rem; line-height: 1.5; border: 1px solid var(--border-color);">
          <!-- Populated dynamically via JS -->
        </div>
      </div>

      <div class="modern-modal-footer" style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--border-color);">
        <button type="button" class="btn-modal-cancel" onclick="closeCreateSuperadminConfirmModal()">Cancel</button>
        <button type="button" class="btn-modal-submit btn-primary" id="btnConfirmCreateSuperadmin" onclick="proceedCreateSuperadminWithPassword()">Confirm and Create</button>
      </div>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div id="caToastContainer" class="ca-toast-container"></div>

  </main>

  <!-- Modern Change Account Password Modal -->
  <div id="dashChangePasswordModal" class="modern-modal-backdrop" style="display:none;" onclick="if(event.target===this) closeDashChangePasswordModal()">
    <div class="modern-modal-card">
      <div class="modern-modal-header">
        <div class="modal-header-branding">
          <div class="modal-icon-badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </div>
          <div>
            <h3 class="modal-title">Change Account Password</h3>
            <p class="modal-subtitle">Update your credentials to keep your profile secure</p>
          </div>
        </div>
        <button type="button" class="modal-close-btn" onclick="closeDashChangePasswordModal()" aria-label="Close dialog">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <div class="modern-modal-body">
        <div id="dashPwModalAlert" class="modal-alert-banner" style="display:none;"></div>

        <form id="dashChangePasswordForm" onsubmit="event.preventDefault(); submitDashChangePassword();">
          <!-- Current Password -->
          <div class="modal-field-group">
            <label class="modal-field-label" for="dashModalCurrentPassword">
              <span>CURRENT PASSWORD</span>
              <span class="req-star" style="color:var(--danger)">*</span>
            </label>
            <div class="modal-input-wrapper">
              <input type="password" id="dashModalCurrentPassword" class="modal-custom-input" placeholder="Enter current password" required autocomplete="current-password" />
              <button type="button" class="modal-pw-toggle" onclick="toggleModalPwVisibility('dashModalCurrentPassword', this)" aria-label="Toggle password visibility">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="modal-field-err" id="dashModalCurrentPasswordErr"></div>
          </div>

          <!-- New Password -->
          <div class="modal-field-group">
            <label class="modal-field-label" for="dashModalNewPassword">
              <span>NEW PASSWORD</span>
              <span class="req-star" style="color:var(--danger)">*</span>
            </label>
            <div class="modal-input-wrapper">
              <input type="password" id="dashModalNewPassword" class="modal-custom-input" placeholder="Create strong new password" required autocomplete="new-password" oninput="evaluateDashModalPwStrength()" />
              <button type="button" class="modal-pw-toggle" onclick="toggleModalPwVisibility('dashModalNewPassword', this)" aria-label="Toggle password visibility">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="modal-field-err" id="dashModalNewPasswordErr"></div>

            <!-- Password Strength Bar & Requirements Checklist -->
            <div class="modal-pw-strength-container">
              <div class="strength-meter-header">
                <span class="strength-meter-label">Password Strength:</span>
                <span id="dashModalStrengthText" class="strength-meter-val" style="color:var(--text-muted)">None</span>
              </div>
              <div class="strength-meter-bar">
                <div class="strength-segment" id="dashModalSeg1"></div>
                <div class="strength-segment" id="dashModalSeg2"></div>
                <div class="strength-segment" id="dashModalSeg3"></div>
                <div class="strength-segment" id="dashModalSeg4"></div>
              </div>
              <ul class="strength-req-checklist">
                <li id="reqLength" class="req-item">
                  <span class="req-icon">○</span> At least 8 characters
                </li>
                <li id="reqUpper" class="req-item">
                  <span class="req-icon">○</span> At least 1 uppercase letter (A-Z)
                </li>
                <li id="reqLower" class="req-item">
                  <span class="req-icon">○</span> At least 1 lowercase letter (a-z)
                </li>
                <li id="reqNumber" class="req-item">
                  <span class="req-icon">○</span> At least 1 number (0-9)
                </li>
                <li id="reqSpecial" class="req-item">
                  <span class="req-icon">○</span> At least 1 special character (!@#$%^&*)
                </li>
              </ul>
            </div>
          </div>

          <!-- Confirm New Password -->
          <div class="modal-field-group">
            <label class="modal-field-label" for="dashModalConfirmPassword">
              <span>CONFIRM NEW PASSWORD</span>
              <span class="req-star" style="color:var(--danger)">*</span>
            </label>
            <div class="modal-input-wrapper">
              <input type="password" id="dashModalConfirmPassword" class="modal-custom-input" placeholder="Repeat new password" required autocomplete="new-password" oninput="checkDashModalPwMatch()" />
              <button type="button" class="modal-pw-toggle" onclick="toggleModalPwVisibility('dashModalConfirmPassword', this)" aria-label="Toggle password visibility">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="modal-match-status" id="dashModalMatchStatus"></div>
            <div class="modal-field-err" id="dashModalConfirmPasswordErr"></div>
          </div>

          <!-- Modal Actions Footer -->
          <div class="modern-modal-footer">
            <button type="button" class="btn-modal-cancel" onclick="closeDashChangePasswordModal()">
              Cancel
            </button>
            <button type="submit" class="btn-modal-submit" id="dashModalSubmitBtn">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <span id="dashModalSubmitBtnText">Update Password</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Super Admin Action Password Confirmation Modal -->
  <div id="saPasswordModal" class="modern-modal-backdrop" style="display:none;" onclick="if(event.target===this) closeSaPasswordModal()">
    <div class="modern-modal-card" style="max-width:440px;">
      <div class="modern-modal-header">
        <div class="modal-header-branding">
          <div class="modal-icon-badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
          </div>
          <div>
            <h3 class="modal-title">Identity Verification</h3>
            <p class="modal-subtitle">Confirm your password to authorize this action</p>
          </div>
        </div>
        <button type="button" class="modal-close-btn" onclick="closeSaPasswordModal()" aria-label="Close dialog">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
      <div class="modern-modal-body">
        <p style="color:var(--text-muted); font-size:0.86rem; margin-bottom:1.25rem; line-height:1.5;">
          This privileged administrative operation requires explicit password authorization before proceeding.
        </p>
        <div class="modal-field-group">
          <label class="modal-field-label" for="saConfirmPasswordInput">
            <span>ADMIN PASSWORD</span>
            <span class="req-star" style="color:var(--danger)">*</span>
          </label>
          <div class="modal-input-wrapper">
            <input type="password" id="saConfirmPasswordInput" class="modal-custom-input" placeholder="Enter your password" />
            <button type="button" class="modal-pw-toggle" onclick="toggleModalPwVisibility('saConfirmPasswordInput', this)" aria-label="Toggle password visibility">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
        <div class="modern-modal-footer">
          <button type="button" class="btn-modal-cancel" onclick="closeSaPasswordModal()">Cancel</button>
          <button type="button" class="btn-modal-submit" id="saConfirmPasswordBtn" onclick="submitSaPasswordModal()">
            <span>Verify &amp; Proceed</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  
  <!-- Custom Alert Modal -->
  <div id="customAlertModal" style="display:none; position:fixed; inset:0; background:var(--bg-backdrop, rgba(15, 23, 42, 0.65)); z-index:999999; align-items:center; justify-content:center; backdrop-filter: blur(8px);">
    <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:14px; width:90%; max-width:420px; padding:1.75rem; box-shadow: var(--shadow-xl);">
      <div style="font-family:'Plus Jakarta Sans', 'Inter', sans-serif; font-size:1.05rem; color:var(--primary); font-weight:700; margin-bottom:0.75rem; display: flex; align-items: center; gap: 0.5rem;">
        <span>🔔</span> SYSTEM NOTIFICATION
      </div>
      <p id="customAlertMessage" style="color:var(--text-primary); font-size:0.92rem; line-height:1.55; margin-bottom:1.5rem; word-wrap: break-word; overflow-wrap: anywhere;"></p>
      <div style="display:flex; justify-content:flex-end;">
        <button class="portal-submit-btn" onclick="closeCustomAlert()" style="width:auto; padding:0.48rem 1.6rem; font-size:0.88rem;">OK</button>
      </div>
    </div>
  </div>

  <!-- Custom Confirm Modal -->
  <div id="customConfirmModal" style="display:none; position:fixed; inset:0; background:var(--bg-backdrop, rgba(15, 23, 42, 0.65)); z-index:999999; align-items:center; justify-content:center; backdrop-filter: blur(8px);">
    <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:14px; width:90%; max-width:420px; padding:1.75rem; box-shadow: var(--shadow-xl);">
      <div style="font-family:'Plus Jakarta Sans', 'Inter', sans-serif; font-size:1.05rem; color:var(--warning); font-weight:700; margin-bottom:0.75rem; display: flex; align-items: center; gap: 0.5rem;">
        <span>⚠️</span> ACTION CONFIRMATION
      </div>
      <p id="customConfirmMessage" style="color:var(--text-primary); font-size:0.92rem; line-height:1.55; margin-bottom:1.5rem; word-wrap: break-word; overflow-wrap: anywhere;"></p>
      <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
        <button type="button" class="btn-small" onclick="resolveCustomConfirm(false)" style="padding:0.48rem 1.25rem;">Cancel</button>
        <button type="button" class="portal-submit-btn" onclick="resolveCustomConfirm(true)" style="width:auto; padding:0.48rem 1.6rem; font-size:0.88rem;">Confirm</button>
      </div>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div id="editUserModal" style="display:none; position:fixed; inset:0; background:var(--bg-backdrop, rgba(15, 23, 42, 0.65)); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(8px); padding: 1.5rem;">
    <div class="panel" style="width:100%; max-width:520px; margin: auto; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--shadow-xl);">
      <div class="panel-hd" style="border-bottom: 1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center;">
        <div class="panel-title" style="color: var(--text-primary);">Update User Account Info</div>
        <button type="button" class="btn-small btn-danger" onclick="closeEditModal()">Close</button>
      </div>
      <div class="panel-bd" style="padding: 1.5rem;">
        <form id="editUserForm">
          <input type="hidden" id="editUserId">
          <div class="controls" style="flex-direction:column; align-items:stretch; gap: 0.85rem;">
            <div>
              <label style="display:block; margin-bottom:0.35rem; color:var(--text-secondary); font-size:0.82rem; font-weight:600;">First Name</label>
              <input type="text" id="editFirstName" class="cyber-input" style="width: 100%; box-sizing: border-box;" required>
            </div>
            <div>
              <label style="display:block; margin-bottom:0.35rem; color:var(--text-secondary); font-size:0.82rem; font-weight:600;">Last Name</label>
              <input type="text" id="editLastName" class="cyber-input" style="width: 100%; box-sizing: border-box;" required>
            </div>
            <div>
              <label style="display:block; margin-bottom:0.35rem; color:var(--text-secondary); font-size:0.82rem; font-weight:600;">Email Address</label>
              <input type="email" id="editEmail" class="cyber-input" style="width: 100%; box-sizing: border-box;" required>
            </div>
            <div>
              <label style="display:block; margin-bottom:0.35rem; color:var(--text-secondary); font-size:0.82rem; font-weight:600;">Phone Number</label>
              <input type="text" id="editPhone" class="cyber-input" style="width: 100%; box-sizing: border-box;" required>
            </div>
            <?php if ($role === 'super_admin'): ?>
            <div>
              <label style="display:block; margin-bottom:0.35rem; color:var(--text-secondary); font-size:0.82rem; font-weight:600;">Reset Password (Optional)</label>
              <div style="position: relative; display: flex; align-items: center;">
                <input type="password" id="editNewPassword" class="cyber-input" placeholder="Leave blank to keep current password" style="width: 100%; padding-right: 40px; box-sizing: border-box;">
                <button type="button" onclick="const p = document.getElementById('editNewPassword'); const svg = this.querySelector('svg'); if(p.type==='password'){p.type='text'; svg.innerHTML='<path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21\'></path>';}else{p.type='password'; svg.innerHTML='<path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M15 12a3 3 0 11-6 0 3 3 0 016 0z\'></path><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z\'></path>';}" style="position: absolute; right: 10px; background: none; border: none; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; padding: 0;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                </button>
              </div>
            </div>
            <?php endif; ?>
            <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1rem;">
              <button type="button" class="btn-small" onclick="closeEditModal()">Cancel</button>
              <button type="button" class="portal-submit-btn" onclick="submitEditUser()" style="width: auto; padding: 0.55rem 1.8rem; font-size: 0.88rem;">Save Changes</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Manage Privileges Modal (Clean Integrated Dialog) -->
  <?php if ($role === 'super_admin'): ?>
  <div id="managePrivilegesModal" class="modern-modal-backdrop" style="display:none; position:fixed; inset:0; background:var(--bg-backdrop, rgba(15, 23, 42, 0.65)); backdrop-filter:blur(8px); z-index:99999; align-items:center; justify-content:center; padding: 1.5rem;" onclick="if(event.target===this) closeManagePrivilegesModal()">
    <div class="panel" style="width:100%; max-width:840px; max-height: 90vh; overflow-y:auto; margin: auto; border: 1px solid var(--border-color); border-radius: 18px; box-shadow: var(--shadow-xl); background: var(--bg-surface);">
      <!-- Modal Header -->
      <div class="panel-hd" style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border-subtle); background: var(--bg-surface);">
        <div>
          <div class="panel-title" id="modalPrivTitle" style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-primary);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <span>Manage User Privileges</span>
          </div>
          <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;" id="modalPrivSubtitle">Configure module access permissions for this account</div>
        </div>
        <button type="button" class="btn-small btn-danger" onclick="closeManagePrivilegesModal()" aria-label="Close dialog">Close</button>
      </div>

      <div class="panel-bd" style="padding: 1.5rem; background: var(--bg-surface);">
        <input type="hidden" id="modalPrivUserId">

        <!-- Target User Banner -->
        <div class="priv-modal-user-banner">
          <div class="priv-user-info-left">
            <div class="priv-user-avatar" id="modalPrivAvatar">U</div>
            <div>
              <div class="priv-user-name" id="modalPrivFullName">Selected User</div>
              <div class="priv-user-handle" id="modalPrivUsername">@username</div>
            </div>
          </div>
          <div class="priv-user-badges">
            <span class="priv-id-badge" id="modalPrivIdno">ID: ----</span>
            <span class="priv-role-badge user" id="modalPrivRoleBadge">USER</span>
            <span class="priv-counter-badge" id="modalPrivCountBadge">0 Permitted Functions</span>
          </div>
        </div>

        <!-- Alert Notification Box -->
        <div id="modalPrivAlert" style="display: none; padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.85rem; font-weight: 500; margin-bottom: 1.25rem;"></div>

        <!-- Module Permissions Header & Presets -->
        <div class="priv-presets-row">
          <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.04em;">
            Permitted Module Functions
          </div>
          <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
            <button type="button" class="priv-preset-btn recommended" onclick="setModalPrivPresets('recommended')">⟳ Recommended</button>
            <button type="button" class="priv-preset-btn" onclick="setModalPrivPresets('all')">✓ Select All</button>
            <button type="button" class="priv-preset-btn" onclick="setModalPrivPresets('clear')">⊘ Clear All</button>
          </div>
        </div>

        <!-- Module Permission Cards Grid -->
        <div class="priv-modules-grid">
          <!-- Module 1: PERSONAL & ACCOUNT -->
          <div class="priv-card">
            <div class="priv-card-title">
              <span>📊</span> Personal &amp; Account
            </div>
            <label class="priv-item-label">
              <input type="checkbox" class="priv-cb modal-priv-cb" value="profile_management" onchange="updateModalPrivCount()">
              <div class="priv-item-text">
                <span class="priv-item-name">Profile Management</span>
                <span class="priv-item-desc">View and update user profile information</span>
              </div>
            </label>
            <label class="priv-item-label">
              <input type="checkbox" class="priv-cb modal-priv-cb" value="change_password" onchange="updateModalPrivCount()">
              <div class="priv-item-text">
                <span class="priv-item-name">Change Password</span>
                <span class="priv-item-desc">Update and change account password</span>
              </div>
            </label>
          </div>

          <!-- Module 2: USER & STUDENT MANAGEMENT -->
          <div class="priv-card" id="modalMgmtCard2">
            <div class="priv-card-title">
              <span>👥</span> User &amp; Directory
            </div>
            <label class="priv-item-label">
              <input type="checkbox" class="priv-cb modal-priv-cb" value="view_accounts" onchange="updateModalPrivCount()">
              <div class="priv-item-text">
                <span class="priv-item-name">View Accounts List</span>
                <span class="priv-item-desc">Access user directory and profile summaries</span>
              </div>
            </label>
            <label class="priv-item-label">
              <input type="checkbox" class="priv-cb modal-priv-cb" value="edit_manage_accounts" onchange="updateModalPrivCount()">
              <div class="priv-item-text">
                <span class="priv-item-name">Edit &amp; Manage Accounts</span>
                <span class="priv-item-desc">Modify account credentials and personal details</span>
              </div>
            </label>
            <label class="priv-item-label">
              <input type="checkbox" class="priv-cb modal-priv-cb" value="block_unblock_users" onchange="updateModalPrivCount()">
              <div class="priv-item-text">
                <span class="priv-item-name">Block / Unblock Users</span>
                <span class="priv-item-desc">Suspend or restore account access status</span>
              </div>
            </label>
          </div>

          <!-- Module 3: APPROVALS & REQUESTS -->
          <div class="priv-card" id="modalMgmtCard3">
            <div class="priv-card-title">
              <span>✍️</span> Approvals &amp; Requests
            </div>
            <label class="priv-item-label">
              <input type="checkbox" class="priv-cb modal-priv-cb" value="registration_approval" onchange="updateModalPrivCount()">
              <div class="priv-item-text">
                <span class="priv-item-name">Registration Approvals</span>
                <span class="priv-item-desc">Review and approve or reject signups</span>
              </div>
            </label>
            <label class="priv-item-label">
              <input type="checkbox" class="priv-cb modal-priv-cb" value="account_deletion_requests" onchange="updateModalPrivCount()">
              <div class="priv-item-text">
                <span class="priv-item-name">Deletion Requests</span>
                <span class="priv-item-desc">Review and process account deletion requests</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Role notice for standard users -->
        <div id="modalPrivUserRoleNotice" style="display:none; font-size: 0.8rem; color: var(--text-muted); background: var(--bg-surface-hover); border: 1px dashed var(--border-color); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.25rem;">
          ℹ️ <strong>Standard User Note:</strong> Standard user accounts typically only utilize Personal &amp; Account permissions. Administrative module permissions require elevated role assignment.
        </div>

        <!-- Footer Actions -->
        <div class="priv-modal-footer">
          <button type="button" class="btn-small" onclick="closeManagePrivilegesModal()">Cancel</button>
          <button type="button" id="saveModalPrivBtn" class="portal-submit-btn" style="padding: 0.65rem 2rem; font-size: 0.92rem;" onclick="saveModalPrivileges()">
            <span>💾</span> Save Privileges
          </button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ================================================================= -->
  <!-- SUPERADMIN SUCCESSION & ACCOUNT STATUS MANAGEMENT MODALS           -->
  <!-- ================================================================= -->

  <!-- Superadmin Succession & Resignation Modal -->
  <div id="superadminTransferModal" class="modern-modal-backdrop" style="display:none;" onclick="if(event.target===this) closeSuperadminSuccessionModal()">
    <div class="modern-modal-card" style="max-width:560px;">
      <div class="modern-modal-header">
        <div class="modal-header-branding">
          <div class="modal-icon-badge gold-crown">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
          </div>
          <div>
            <h3 class="modal-title">Superadmin Succession &amp; Role Transfer</h3>
            <p class="modal-subtitle">Transfer master authority to an eligible Administrator</p>
          </div>
        </div>
        <button type="button" class="modal-close-btn" onclick="closeSuperadminSuccessionModal()" aria-label="Close dialog">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>

      <div class="modern-modal-body">
        <!-- Critical Consequence Warning Box -->
        <div class="succession-warning-box">
          <div class="warning-title">
            <span>⚠️</span> PERMANENT ROLE TRANSFER NOTICE
          </div>
          <p class="warning-desc">
            You are initiating a formal succession of the <strong>Superadmin</strong> role.
            Upon execution, you will step down as Superadmin, your master authority will be transferred atomically to the designated successor, and your active privileged session will be terminated immediately.
          </p>
        </div>

        <form id="superadminTransferForm" onsubmit="submitSuperadminSuccession(event)">
          <div id="succModalAlert" class="modal-alert-banner" style="display:none;"></div>

          <!-- Step 1: Select Eligible Successor -->
          <div class="modal-field-group">
            <label class="modal-field-label" for="succSuccessorSelect">
              <span>DESIGNATED SUCCESSOR (ACTIVE ADMINISTRATORS)</span>
              <span class="req-star" style="color:var(--danger)">*</span>
            </label>
            <select id="succSuccessorSelect" class="modal-custom-input" required onchange="onSuccessorSelected()">
              <option value="">-- Select an eligible Active Administrator --</option>
            </select>
            <div class="modal-helper-text" id="succSuccessorHint">Only Active accounts with the Administrator role are eligible for succession.</div>
          </div>

          <!-- Step 2: Post-Resignation Status for Outgoing Superadmin -->
          <div class="modal-field-group">
            <label class="modal-field-label" for="succOutgoingStatus">
              <span>YOUR POST-RESIGNATION ACCOUNT STATUS</span>
              <span class="req-star" style="color:var(--danger)">*</span>
            </label>
            <select id="succOutgoingStatus" class="modal-custom-input" required>
              <option value="active" selected>Active &mdash; Keep account Active with standard Administrator role</option>
              <option value="inactive">Inactive &mdash; Step down and de-activate account (requires future reactivation)</option>
            </select>
            <div class="modal-helper-text">You will be designated as a Former Superadmin and hold the Administrator role.</div>
          </div>

          <!-- Step 3: Reason for Resignation / Transfer -->
          <div class="modal-field-group">
            <label class="modal-field-label" for="succReasonInput">
              <span>REASON FOR TRANSFER / SUCCESSION</span>
              <span class="req-star" style="color:var(--danger)">*</span>
            </label>
            <textarea id="succReasonInput" class="modal-custom-input" rows="3" placeholder="State the formal administrative reason for stepping down / transferring rights..." required style="resize:vertical; min-height:65px;"></textarea>
            <div class="modal-helper-text">This statement is permanently recorded into the immutable audit and succession logs.</div>
          </div>

          <!-- Step 4: Identity Verification (Admin Password) -->
          <div class="modal-field-group">
            <label class="modal-field-label" for="succPasswordInput">
              <span>CONFIRM YOUR SUPERADMIN PASSWORD</span>
              <span class="req-star" style="color:var(--danger)">*</span>
            </label>
            <div class="modal-input-wrapper">
              <input type="password" id="succPasswordInput" class="modal-custom-input" placeholder="Enter your current password to authorize transfer" required autocomplete="current-password" />
              <button type="button" class="modal-pw-toggle" onclick="toggleModalPwVisibility('succPasswordInput', this)" aria-label="Toggle password visibility">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>

          <!-- Step 5: Explicit Checkbox Confirmation -->
          <div class="succ-confirm-check-wrap">
            <label class="succ-confirm-label">
              <input type="checkbox" id="succConfirmCheckbox" required />
              <span>I acknowledge that I am stepping down as Superadmin and transferring all master rights to the selected successor. I understand this action takes effect immediately.</span>
            </label>
          </div>

          <!-- Modal Actions -->
          <div class="modern-modal-footer">
            <button type="button" class="btn-modal-cancel" onclick="closeSuperadminSuccessionModal()">Cancel</button>
            <button type="submit" class="btn-modal-submit btn-danger-glow" id="succSubmitBtn">
              <span id="succSubmitBtnText">Execute Succession &amp; Step Down</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- View User Details Dossier Modal -->
  <div id="viewUserDetailsModal" class="modern-modal-backdrop" style="display:none;" onclick="if(event.target===this) closeUserDetailsModal()">
    <div class="modern-modal-card" style="max-width:680px; max-height:90vh; overflow-y:auto;">
      <div class="modern-modal-header">
        <div class="modal-header-branding">
          <div class="modal-icon-badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </div>
          <div>
            <h3 class="modal-title">Account Dossier &amp; Details</h3>
            <p class="modal-subtitle">Comprehensive identity, role, and clearance telemetry</p>
          </div>
        </div>
        <button type="button" class="modal-close-btn" onclick="closeUserDetailsModal()" aria-label="Close dialog">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>

      <div class="modern-modal-body" id="userDetailsContent">
        <!-- Loaded dynamically via JS -->
      </div>
      <div class="modern-modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeUserDetailsModal()">Close</button>
      </div>
    </div>
  </div>

  <!-- Account Status Management Modal (Deactivate / Reactivate / Suspend / Restore) -->
  <div id="accountStatusModal" class="modern-modal-backdrop" style="display:none;" onclick="if(event.target===this) closeAccountStatusModal()">
    <div class="modern-modal-card" style="max-width:480px;">
      <div class="modern-modal-header">
        <div class="modal-header-branding">
          <div class="modal-icon-badge" id="statusModalIconBadge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <div>
            <h3 class="modal-title" id="statusModalTitle">Account Status Action</h3>
            <p class="modal-subtitle" id="statusModalSubtitle">Authorize account state modification</p>
          </div>
        </div>
        <button type="button" class="modal-close-btn" onclick="closeAccountStatusModal()" aria-label="Close dialog">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>

      <div class="modern-modal-body">
        <div id="statusModalTargetBanner" class="status-target-banner">
          <div class="status-target-avatar" id="statusModalAvatar">U</div>
          <div>
            <div class="status-target-name" id="statusModalTargetName">Target User</div>
            <div class="status-target-meta">
              <span id="statusModalTargetHandle">@username</span>
              <span>&bull;</span>
              <span id="statusModalTargetRole">ROLE</span>
              <span>&bull;</span>
              <span id="statusModalTargetStatus">STATUS</span>
            </div>
          </div>
        </div>

        <div id="statusModalNotice" class="status-notice-box" style="display:none;"></div>

        <form id="accountStatusForm" onsubmit="submitAccountStatusAction(event)">
          <input type="hidden" id="statusModalTargetId" />
          <input type="hidden" id="statusModalActionType" />

          <div class="modal-field-group">
            <label class="modal-field-label" for="statusModalReasonInput">
              <span id="statusModalReasonLabel">REASON FOR ACTION</span>
              <span class="req-star" style="color:var(--danger)">*</span>
            </label>
            <textarea id="statusModalReasonInput" class="modal-custom-input" rows="3" placeholder="Provide a detailed administrative reason..." required style="resize:vertical; min-height:65px;"></textarea>
            <div class="modal-helper-text">This explanation will be permanently recorded in the system audit trail.</div>
          </div>

          <div class="modal-field-group">
            <label class="modal-field-label" for="statusModalPasswordInput">
              <span>ADMINISTRATOR PASSWORD CONFIRMATION</span>
              <span class="req-star" style="color:var(--danger)">*</span>
            </label>
            <div class="modal-input-wrapper">
              <input type="password" id="statusModalPasswordInput" class="modal-custom-input" placeholder="Confirm your password to authorize" required autocomplete="current-password" />
              <button type="button" class="modal-pw-toggle" onclick="toggleModalPwVisibility('statusModalPasswordInput', this)" aria-label="Toggle password visibility">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>

          <div class="modern-modal-footer">
            <button type="button" class="btn-modal-cancel" onclick="closeAccountStatusModal()">Cancel</button>
            <button type="submit" class="btn-modal-submit" id="statusModalSubmitBtn">
              <span id="statusModalSubmitBtnText">Confirm Action</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- User Audit History Modal -->
  <div id="userAuditModal" class="modern-modal-backdrop" style="display:none;" onclick="if(event.target===this) closeUserAuditModal()">
    <div class="modern-modal-card" style="max-width:760px; max-height:90vh; display:flex; flex-direction:column;">
      <div class="modern-modal-header">
        <div class="modal-header-branding">
          <div class="modal-icon-badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
          </div>
          <div>
            <h3 class="modal-title" id="userAuditModalTitle">Account Audit History</h3>
            <p class="modal-subtitle" id="userAuditModalSubtitle">Chronological security events and privilege modifications</p>
          </div>
        </div>
        <button type="button" class="modal-close-btn" onclick="closeUserAuditModal()" aria-label="Close dialog">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>

      <div class="modern-modal-body" style="overflow-y:auto; flex:1;" id="userAuditContent">
        <!-- Loaded dynamically via JS -->
      </div>
      <div class="modern-modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeUserAuditModal()">Close</button>
      </div>
    </div>
  </div>

  <script>
    const userRole = '<?= $role ?>';
    const userPrivileges = <?= json_encode($privs) ?>;
    const currentUsername = <?= json_encode($user['username'] ?? '') ?>;
    window.currentUsername = currentUsername;

    function closeEditModal() {
      const modal = document.getElementById('editUserModal');
      if (modal) modal.style.display = 'none';
    }

    function showTab(tabId) {
      // Close open modals on navigation
      if (typeof closeManagePrivilegesModal === 'function') closeManagePrivilegesModal();
      if (typeof closeEditModal === 'function') closeEditModal();
      if (typeof closeCreateAccountModal === 'function') closeCreateAccountModal();
      if (typeof closeDashChangePasswordModal === 'function') closeDashChangePasswordModal();
      if (typeof closeSaPasswordModal === 'function') closeSaPasswordModal();
      if (typeof closeSuperadminSuccessionModal === 'function') closeSuperadminSuccessionModal();
      if (typeof closeUserDetailsModal === 'function') closeUserDetailsModal();
      if (typeof closeAccountStatusModal === 'function') closeAccountStatusModal();
      if (typeof closeUserAuditModal === 'function') closeUserAuditModal();
      if (typeof closeCreateSuperadminConfirmModal === 'function') closeCreateSuperadminConfirmModal();

      sessionStorage.setItem('activeDashboardTab', tabId);
      document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
      
      const targetTab = document.getElementById(tabId);
      if (targetTab) targetTab.classList.add('active');
      
      const activeBtn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`) || 
                        document.querySelector(`.tab-btn[onclick*="'${tabId}'"]`);
      if (activeBtn) activeBtn.classList.add('active');
      
      // Reload fresh data from backend on each tab switch
      if (tabId === 'overview' && typeof loadDashboardOverview === 'function') {
        loadDashboardOverview();
      }
      if (tabId === 'users') {
        const filterInput = document.getElementById('filterEmpId');
        if (filterInput) filterInput.value = '';
        if (typeof lastUsersPayloadHash !== 'undefined') {
          lastUsersPayloadHash = '';
        }
        if (typeof loadUsers === 'function') {
          loadUsers(1, false);
        } else {
          // If admin.js has not finished parsing/loading yet, queue execution
          document.addEventListener('DOMContentLoaded', () => {
            if (typeof loadUsers === 'function') {
              loadUsers(1, false);
            }
          }, { once: true });
        }
      }
      if (tabId === 'logs' && typeof loadLogs === 'function') {
        loadLogs(1);
      }
      if (tabId === 'delete_reqs' && typeof loadDeleteRequests === 'function') {
        loadDeleteRequests();
      }
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

    // Profile Section Interactive Logic
    function switchDashProfileTab(paneId, btn) {
      document.querySelectorAll('#profile .profile-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('#profile .dash-tab-pane').forEach(p => p.classList.remove('active'));
      if (btn) btn.classList.add('active');
      const pane = document.getElementById(paneId);
      if (pane) pane.classList.add('active');
    }

    let dashProfileBackup = {};

    function toggleDashProfileEdit() {
      const form = document.getElementById('dashProfileForm');
      if (!form) return;

      const fields = form.querySelectorAll('.dash-profile-field');
      dashProfileBackup = {};
      fields.forEach(f => {
        dashProfileBackup[f.id] = f.value;
        if (f.tagName === 'SELECT') {
          f.disabled = false;
        } else {
          f.readOnly = false;
        }
        f.style.borderColor = 'var(--accent-cyan)';
      });

      document.getElementById('dashEditProfileBtn').style.display = 'none';
      document.getElementById('dashSaveProfileBtn').style.display = 'inline-flex';
      document.getElementById('dashCancelProfileBtn').style.display = 'inline-flex';

      // Unlock Change Password button
      setDashChangePasswordEnabled(true);
    }

    function cancelDashProfileEdit() {
      const form = document.getElementById('dashProfileForm');
      if (!form) return;

      const fields = form.querySelectorAll('.dash-profile-field');
      fields.forEach(f => {
        if (dashProfileBackup[f.id] !== undefined) {
          f.value = dashProfileBackup[f.id];
        }
        if (f.tagName === 'SELECT') {
          f.disabled = true;
        } else {
          f.readOnly = true;
        }
        f.style.borderColor = '';
      });

      autoCalcDashAge();

      document.getElementById('dashEditProfileBtn').style.display = 'inline-flex';
      document.getElementById('dashSaveProfileBtn').style.display = 'none';
      document.getElementById('dashCancelProfileBtn').style.display = 'none';

      // Lock Change Password button
      setDashChangePasswordEnabled(false);
    }

    function autoCalcDashAge() {
      const bDateVal = document.getElementById('dash_birthdate').value;
      const ageInput = document.getElementById('dash_age');
      if (!bDateVal) {
        ageInput.value = '';
        return;
      }
      const bDate = new Date(bDateVal);
      const today = new Date();
      if (isNaN(bDate.getTime()) || bDate > today) {
        ageInput.value = '';
        return;
      }
      let age = today.getFullYear() - bDate.getFullYear();
      const m = today.getMonth() - bDate.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < bDate.getDate())) {
        age--;
      }
      ageInput.value = age >= 0 ? `${age} yrs` : '';
    }

    async function saveDashProfileDetails() {
      const firstName = document.getElementById('dash_firstname').value.trim();
      const middleName = document.getElementById('dash_middlename').value.trim();
      const lastName = document.getElementById('dash_lastname').value.trim();
      const extension = document.getElementById('dash_suffix').value;
      const sex = document.getElementById('dash_sex').value;
      const birthDate = document.getElementById('dash_birthdate').value;
      const purok = document.getElementById('dash_purok').value.trim();
      const barangay = document.getElementById('dash_brgy').value.trim();
      const city = document.getElementById('dash_city').value.trim();
      const province = document.getElementById('dash_province').value.trim();
      const country = document.getElementById('dash_country').value.trim();
      const zipCode = document.getElementById('dash_zipcode').value.trim();
      const username = document.getElementById('dash_username').value.trim();
      const email = document.getElementById('dash_email').value.trim();
      const phone = document.getElementById('dash_phone').value.trim();

      if (!firstName || !lastName || !username || !email) {
        alert('Please fill in all required fields marked with *');
        return;
      }

      const saveBtn = document.getElementById('dashSaveProfileBtn');
      const origText = saveBtn.innerHTML;
      saveBtn.innerHTML = '<span>Saving...</span>';
      saveBtn.disabled = true;

      try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('../php/update-profile.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          body: JSON.stringify({
            firstName,
            middleName,
            lastName,
            extension,
            sex,
            birthDate,
            purok,
            barangay,
            city,
            province,
            country,
            zipCode,
            username,
            email,
            phone
          })
        });

        const data = await res.json();
        if (data.success) {
          // Update Identity Card on the left
          const nameParts = [firstName, middleName, lastName, extension].filter(Boolean);
          document.getElementById('displayFullName').textContent = nameParts.join(' ');
          document.getElementById('displayUsername').textContent = '@' + username;
          document.getElementById('avatarInitial').textContent = (firstName || username || 'U')[0].toUpperCase();
          document.getElementById('displaySex').textContent = sex ? (sex.charAt(0).toUpperCase() + sex.slice(1)) : '----';
          document.getElementById('displayBirthdate').textContent = birthDate || '----';
          document.getElementById('displayAge').textContent = document.getElementById('dash_age').value || '----';

          // Lock fields back
          const form = document.getElementById('dashProfileForm');
          form.querySelectorAll('.dash-profile-field').forEach(f => {
            if (f.tagName === 'SELECT') f.disabled = true;
            else f.readOnly = true;
            f.style.borderColor = '';
          });

          document.getElementById('dashEditProfileBtn').style.display = 'inline-flex';
          document.getElementById('dashSaveProfileBtn').style.display = 'none';
          document.getElementById('dashCancelProfileBtn').style.display = 'none';

          // Lock change password button back
          setDashChangePasswordEnabled(false);

          alert('Profile updated successfully!');
        } else {
          alert(data.message || 'Failed to update profile');
        }
      } catch (err) {
        alert('Network error while saving profile');
      } finally {
        saveBtn.innerHTML = origText;
        saveBtn.disabled = false;
      }
    }

    // ── DASHBOARD CHANGE PASSWORD MODAL LOGIC ─────────────────────────────────
    function setDashChangePasswordEnabled(enabled) {
      const pwBtn = document.getElementById('dashChangePasswordBtn');
      const pwBox = document.getElementById('dashPwBox');
      const pwHint = document.getElementById('dashPwHint');
      if (!pwBtn) return;

      if (enabled) {
        pwBtn.disabled = false;
        pwBtn.classList.add('is-unlocked');
        pwBtn.removeAttribute('title');
        if (pwBox) pwBox.classList.add('is-unlocked');
        if (pwHint) {
          pwHint.className = 'dash-pw-hint unlocked';
          pwHint.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg><span>Unlocked &bull; Click to update your password</span>';
        }
      } else {
        pwBtn.disabled = true;
        pwBtn.classList.remove('is-unlocked');
        pwBtn.setAttribute('title', 'Click "Edit Details" first to unlock password change');
        if (pwBox) pwBox.classList.remove('is-unlocked');
        if (pwHint) {
          pwHint.className = 'dash-pw-hint locked';
          pwHint.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg><span>Locked &bull; Click "Edit Details" above to change password</span>';
        }
      }
    }

    function handleDashChangePasswordClick() {
      const pwBtn = document.getElementById('dashChangePasswordBtn');
      if (pwBtn && pwBtn.disabled) {
        alert('Please click "Edit Details" in the top-right header first to unlock password changes.');
        return;
      }
      openDashChangePasswordModal();
    }

    function openDashChangePasswordModal() {
      const modal = document.getElementById('dashChangePasswordModal');
      if (!modal) return;
      resetDashChangePasswordModal();
      modal.style.display = 'flex';
      setTimeout(() => {
        const input = document.getElementById('dashModalCurrentPassword');
        if (input) input.focus();
      }, 60);
    }

    function closeDashChangePasswordModal() {
      const modal = document.getElementById('dashChangePasswordModal');
      if (modal) modal.style.display = 'none';
      resetDashChangePasswordModal();
    }

    function resetDashChangePasswordModal() {
      const form = document.getElementById('dashChangePasswordForm');
      if (form) form.reset();
      const alertBox = document.getElementById('dashPwModalAlert');
      if (alertBox) {
        alertBox.style.display = 'none';
        alertBox.className = 'modal-alert-banner';
        alertBox.textContent = '';
      }
      ['dashModalCurrentPasswordErr', 'dashModalNewPasswordErr', 'dashModalConfirmPasswordErr'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = '';
      });
      const matchStatus = document.getElementById('dashModalMatchStatus');
      if (matchStatus) {
        matchStatus.textContent = '';
        matchStatus.className = 'modal-match-status';
      }
      evaluateDashModalPwStrength();
    }

    function toggleModalPwVisibility(inputId, btn) {
      const input = document.getElementById(inputId);
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      if (btn) {
        btn.innerHTML = isPassword
          ? '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>'
          : '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
      }
    }

    function evaluateDashModalPwStrength() {
      const pw = document.getElementById('dashModalNewPassword')?.value || '';
      const textLabel = document.getElementById('dashModalStrengthText');
      const seg1 = document.getElementById('dashModalSeg1');
      const seg2 = document.getElementById('dashModalSeg2');
      const seg3 = document.getElementById('dashModalSeg3');
      const seg4 = document.getElementById('dashModalSeg4');

      const reqLen = document.getElementById('reqLength');
      const reqUp = document.getElementById('reqUpper');
      const reqLow = document.getElementById('reqLower');
      const reqNum = document.getElementById('reqNumber');
      const reqSp = document.getElementById('reqSpecial');

      const isLen = pw.length >= 8;
      const isUp = /[A-Z]/.test(pw);
      const isLow = /[a-z]/.test(pw);
      const isNum = /[0-9]/.test(pw);
      const isSp = /[^A-Za-z0-9]/.test(pw);

      function updateReq(el, met) {
        if (!el) return;
        if (met) {
          el.className = 'req-item met';
          el.querySelector('.req-icon').textContent = '✓';
        } else {
          el.className = 'req-item';
          el.querySelector('.req-icon').textContent = '○';
        }
      }

      updateReq(reqLen, isLen);
      updateReq(reqUp, isUp);
      updateReq(reqLow, isLow);
      updateReq(reqNum, isNum);
      updateReq(reqSp, isSp);

      const score = [isLen, isUp, isLow, isNum, isSp].filter(Boolean).length;

      [seg1, seg2, seg3, seg4].forEach(s => {
        if (s) s.className = 'strength-segment';
      });

      if (!pw) {
        if (textLabel) {
          textLabel.textContent = 'None';
          textLabel.style.color = 'var(--text-muted)';
        }
        return;
      }

      if (score <= 2) {
        if (textLabel) {
          textLabel.textContent = 'Weak';
          textLabel.style.color = 'var(--danger)';
        }
        if (seg1) seg1.className = 'strength-segment active-weak';
      } else if (score === 3) {
        if (textLabel) {
          textLabel.textContent = 'Fair';
          textLabel.style.color = 'var(--warning)';
        }
        if (seg1) seg1.className = 'strength-segment active-fair';
        if (seg2) seg2.className = 'strength-segment active-fair';
      } else if (score === 4) {
        if (textLabel) {
          textLabel.textContent = 'Good';
          textLabel.style.color = 'var(--accent)';
        }
        if (seg1) seg1.className = 'strength-segment active-good';
        if (seg2) seg2.className = 'strength-segment active-good';
        if (seg3) seg3.className = 'strength-segment active-good';
      } else {
        if (textLabel) {
          textLabel.textContent = 'Strong';
          textLabel.style.color = 'var(--success)';
        }
        if (seg1) seg1.className = 'strength-segment active-strong';
        if (seg2) seg2.className = 'strength-segment active-strong';
        if (seg3) seg3.className = 'strength-segment active-strong';
        if (seg4) seg4.className = 'strength-segment active-strong';
      }

      checkDashModalPwMatch();
    }

    function checkDashModalPwMatch() {
      const newPw = document.getElementById('dashModalNewPassword')?.value || '';
      const confPw = document.getElementById('dashModalConfirmPassword')?.value || '';
      const matchEl = document.getElementById('dashModalMatchStatus');
      if (!matchEl) return;

      if (!confPw) {
        matchEl.textContent = '';
        matchEl.className = 'modal-match-status';
        return;
      }

      const errEl = document.getElementById('dashModalConfirmPasswordErr');
      if (errEl) errEl.textContent = '';

      if (newPw === confPw) {
        matchEl.textContent = '✓ Passwords match';
        matchEl.className = 'modal-match-status match';
      } else {
        matchEl.textContent = '✗ Passwords do not match';
        matchEl.className = 'modal-match-status mismatch';
      }
    }

    // Attach real-time input error-clearing
    document.addEventListener('DOMContentLoaded', () => {
      const curInput = document.getElementById('dashModalCurrentPassword');
      const newInput = document.getElementById('dashModalNewPassword');
      const confInput = document.getElementById('dashModalConfirmPassword');
      if (curInput) {
        curInput.addEventListener('input', () => {
          const err = document.getElementById('dashModalCurrentPasswordErr');
          if (err && curInput.value.trim()) err.textContent = '';
        });
      }
      if (newInput) {
        newInput.addEventListener('input', () => {
          const err = document.getElementById('dashModalNewPasswordErr');
          if (err && newInput.value.trim().length >= 8) err.textContent = '';
        });
      }
      if (confInput) {
        confInput.addEventListener('input', () => {
          const err = document.getElementById('dashModalConfirmPasswordErr');
          if (err && confInput.value === newInput?.value) err.textContent = '';
        });
      }
    });

    async function submitDashChangePassword() {
      const curInput = document.getElementById('dashModalCurrentPassword');
      const newInput = document.getElementById('dashModalNewPassword');
      const confInput = document.getElementById('dashModalConfirmPassword');
      const alertBox = document.getElementById('dashPwModalAlert');
      const submitBtn = document.getElementById('dashModalSubmitBtn');
      const btnText = document.getElementById('dashModalSubmitBtnText');

      const currentPassword = curInput?.value || '';
      const newPassword = newInput?.value || '';
      const confirmNewPassword = confInput?.value || '';

      ['dashModalCurrentPasswordErr', 'dashModalNewPasswordErr', 'dashModalConfirmPasswordErr'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = '';
      });
      if (alertBox) {
        alertBox.style.display = 'none';
        alertBox.className = 'modal-alert-banner';
        alertBox.textContent = '';
      }

      let hasErr = false;
      if (!currentPassword.trim()) {
        document.getElementById('dashModalCurrentPasswordErr').textContent = 'Please enter your current password.';
        hasErr = true;
      }

      if (!newPassword.trim()) {
        document.getElementById('dashModalNewPasswordErr').textContent = 'Please enter a new password.';
        hasErr = true;
      } else if (newPassword.length < 8) {
        document.getElementById('dashModalNewPasswordErr').textContent = 'Password must be at least 8 characters.';
        hasErr = true;
      } else if (!/[A-Z]/.test(newPassword)) {
        document.getElementById('dashModalNewPasswordErr').textContent = 'Password must contain at least 1 uppercase letter.';
        hasErr = true;
      } else if (!/[a-z]/.test(newPassword)) {
        document.getElementById('dashModalNewPasswordErr').textContent = 'Password must contain at least 1 lowercase letter.';
        hasErr = true;
      } else if (!/[0-9]/.test(newPassword)) {
        document.getElementById('dashModalNewPasswordErr').textContent = 'Password must contain at least 1 number.';
        hasErr = true;
      } else if (!/[^A-Za-z0-9]/.test(newPassword)) {
        document.getElementById('dashModalNewPasswordErr').textContent = 'Password must contain at least 1 special character.';
        hasErr = true;
      } else if (currentPassword && newPassword === currentPassword) {
        document.getElementById('dashModalNewPasswordErr').textContent = 'New password must be different from current password.';
        hasErr = true;
      }

      if (!confirmNewPassword.trim()) {
        document.getElementById('dashModalConfirmPasswordErr').textContent = 'Please confirm your new password.';
        hasErr = true;
      } else if (newPassword !== confirmNewPassword) {
        document.getElementById('dashModalConfirmPasswordErr').textContent = 'Passwords do not match.';
        hasErr = true;
      }

      if (hasErr) return;

      if (submitBtn) submitBtn.disabled = true;
      if (btnText) btnText.textContent = 'Updating...';

      try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const params = new URLSearchParams();
        params.append('current_password', currentPassword);
        params.append('new_password', newPassword);
        params.append('confirm_new_password', confirmNewPassword);
        params.append('csrf_token', csrfToken);

        const res = await fetch('../php/change-password.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
          },
          body: params.toString()
        });

        const data = await res.json();
        if (data.success) {
          if (alertBox) {
            alertBox.className = 'modal-alert-banner alert-success';
            alertBox.textContent = '✓ ' + (data.message || 'Password updated successfully!');
            alertBox.style.display = 'block';
          }
          if (curInput) curInput.value = '';
          if (newInput) newInput.value = '';
          if (confInput) confInput.value = '';
          evaluateDashModalPwStrength();

          setTimeout(() => {
            closeDashChangePasswordModal();
            alert('Your password has been updated successfully.');
          }, 1200);
        } else {
          if (alertBox) {
            alertBox.className = 'modal-alert-banner alert-error';
            alertBox.textContent = data.message || 'Failed to update password. Please check your credentials.';
            alertBox.style.display = 'block';
          }
        }
      } catch (err) {
        if (alertBox) {
          alertBox.className = 'modal-alert-banner alert-error';
          alertBox.textContent = 'Network or server error occurred. Please try again.';
          alertBox.style.display = 'block';
        }
      } finally {
        if (submitBtn) submitBtn.disabled = false;
        if (btnText) btnText.textContent = 'Update Password';
      }
    }
  </script>
  <script src="../js/admin.js?v=<?= filemtime(__DIR__ . '/../js/admin.js') ?>"></script>
  <script src="../js/theme-toggle.js"></script>
</body>
</html>
