/**
 * escapeHtml — centralized output encoding for inserting untrusted data into HTML.
 * Use this whenever user-controlled content must appear inside innerHTML.
 * Prefer textContent / setAttribute where possible (avoids encoding entirely).
 */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

window.customPrompt = function(msg) {
    // For simplicity, fallback to native prompt, but you can build a custom prompt modal if needed.
    return Promise.resolve(prompt(msg));
};
// --- Custom Modals ---
let alertResolve = null;
let alertEnterHandler = null;

window.customAlert = function(msg, type = 'info', title = null, subtitle = null) {
    return new Promise(resolve => {
        const msgEl = document.getElementById('customAlertMessage');
        if (msgEl) msgEl.innerText = msg;

        const modal = document.getElementById('customAlertModal');
        if (!modal) {
            console.warn('customAlertModal not found:', msg);
            resolve();
            return;
        }

        const iconContainer = document.getElementById('customAlertIcon');
        const titleEl = document.getElementById('customAlertTitle');
        const subtitleEl = document.getElementById('customAlertSubtitle');
        const badge = document.getElementById('customAlertBadge');
        const okBtn = document.getElementById('customAlertOkBtn') || modal.querySelector('button');

        const configs = {
            success: {
                title: title || 'SUCCESS',
                subtitle: subtitle || 'Operation completed successfully',
                icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
                badgeBg: 'rgba(16, 185, 129, 0.16)',
                badgeBorder: 'rgba(16, 185, 129, 0.35)',
                color: '#10b981',
                btnBg: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                btnShadow: '0 4px 14px rgba(16, 185, 129, 0.35)'
            },
            error: {
                title: title || 'SYSTEM ERROR',
                subtitle: subtitle || 'Action could not be completed',
                icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
                badgeBg: 'rgba(239, 68, 68, 0.16)',
                badgeBorder: 'rgba(239, 68, 68, 0.35)',
                color: '#ef4444',
                btnBg: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                btnShadow: '0 4px 14px rgba(239, 68, 68, 0.35)'
            },
            warning: {
                title: title || 'ATTENTION REQUIRED',
                subtitle: subtitle || 'Please review required details',
                icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                badgeBg: 'rgba(245, 158, 11, 0.16)',
                badgeBorder: 'rgba(245, 158, 11, 0.35)',
                color: '#f59e0b',
                btnBg: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                btnShadow: '0 4px 14px rgba(245, 158, 11, 0.35)'
            },
            info: {
                title: title || 'SYSTEM NOTIFICATION',
                subtitle: subtitle || 'CyberAuth Security Advisory',
                icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
                badgeBg: 'rgba(59, 130, 246, 0.16)',
                badgeBorder: 'rgba(59, 130, 246, 0.35)',
                color: '#3b82f6',
                btnBg: '',
                btnShadow: ''
            }
        };

        const cfg = configs[type] || configs.info;
        if (titleEl) titleEl.textContent = cfg.title;
        if (subtitleEl) subtitleEl.textContent = cfg.subtitle;
        if (badge) {
            badge.style.background = cfg.badgeBg;
            badge.style.borderColor = cfg.badgeBorder;
            badge.style.color = cfg.color;
        }
        if (iconContainer) iconContainer.innerHTML = cfg.icon;

        if (okBtn) {
            if (cfg.btnBg) {
                okBtn.style.background = cfg.btnBg;
                okBtn.style.boxShadow = cfg.btnShadow;
                okBtn.style.borderColor = 'transparent';
                okBtn.style.color = '#ffffff';
            } else {
                okBtn.style.background = '';
                okBtn.style.boxShadow = '';
                okBtn.style.borderColor = '';
                okBtn.style.color = '';
            }
        }

        modal.style.display = 'flex';
        if (okBtn) setTimeout(() => okBtn.focus(), 25);

        if (alertEnterHandler) document.removeEventListener('keydown', alertEnterHandler);
        alertEnterHandler = function(e) {
            if (e.key === 'Enter' || e.key === 'Escape') {
                e.preventDefault();
                closeCustomAlert();
            }
        };
        document.addEventListener('keydown', alertEnterHandler);

        alertResolve = () => {
            if (alertEnterHandler) document.removeEventListener('keydown', alertEnterHandler);
            alertEnterHandler = null;
            resolve();
        };
    });
};
window.closeCustomAlert = function() {
    const modal = document.getElementById('customAlertModal');
    if (modal) modal.style.display = 'none';
    if (alertResolve) { alertResolve(); alertResolve = null; }
};

let confirmResolve = null;
let confirmEnterHandler = null;

window.customConfirm = function(msg) {
    return new Promise(resolve => {
        document.getElementById('customConfirmMessage').innerText = msg;
        const modal = document.getElementById('customConfirmModal');
        modal.style.display = 'flex';
        
        const btns = modal.querySelectorAll('button');
        if (btns.length > 1) setTimeout(() => btns[1].focus(), 10);

        if (confirmEnterHandler) document.removeEventListener('keydown', confirmEnterHandler);
        confirmEnterHandler = function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                window.resolveCustomConfirm(true);
            }
        };
        document.addEventListener('keydown', confirmEnterHandler);

        confirmResolve = (result) => {
            if (confirmEnterHandler) document.removeEventListener('keydown', confirmEnterHandler);
            confirmEnterHandler = null;
            resolve(result);
        };
    });
};
window.resolveCustomConfirm = function(result) {
    document.getElementById('customConfirmModal').style.display = 'none';
    if (confirmResolve) { confirmResolve(result); confirmResolve = null; }
};

let usersPage = 1;
let usersPerPage = parseInt(sessionStorage.getItem('dashboardUsersPerPage')) || 10;
let logsPage = 1;
let logsPerPage = parseInt(sessionStorage.getItem('dashboardLogsPerPage')) || 10;

// ── User Directory State & Synchronization ──────────────────────────────────
let usersPollTimer = null;
let lastUsersPayloadHash = '';
let isUsersPollingActive = false;
let usersSearchDebounceTimer = null;
let userInteractedWithSearch = false;

function setRealtimeSyncState(state) {
    const pill = document.getElementById('usersRealtimePill');
    if (!pill) return;
    const text = pill.querySelector('.live-status-text');
    const dot = pill.querySelector('.live-dot-pulse');

    pill.classList.remove('is-syncing', 'is-paused', 'is-error');

    if (state === 'syncing') {
        pill.classList.add('is-syncing');
        if (text) text.textContent = 'SYNCING...';
        if (dot) { dot.style.animation = ''; dot.style.opacity = '1'; }
        pill.title = 'Synchronizing accounts directory with server...';
    } else if (state === 'error') {
        pill.classList.add('is-error');
        if (text) text.textContent = 'ERROR';
        if (dot) { dot.style.animation = 'none'; dot.style.opacity = '0.4'; }
        pill.title = 'Temporary network disconnect. Click Refresh to retry.';
    } else {
        if (text) text.textContent = 'DIRECTORY';
        if (dot) { dot.style.animation = 'none'; dot.style.opacity = '0.6'; }
        pill.title = 'Click Refresh or change filters to update directory';
    }
}

function startUsersRealtimePoll() {
    stopUsersRealtimePoll();
    // Background polling timer is intentionally disabled per user requirement:
    // "just refresh it when I have clicked refresh or I change something so that the user wont disappear"
    isUsersPollingActive = false;
    setRealtimeSyncState('idle');
}

function stopUsersRealtimePoll() {
    if (usersPollTimer) {
        clearInterval(usersPollTimer);
        usersPollTimer = null;
    }
    isUsersPollingActive = false;
    setRealtimeSyncState('idle');
}

async function manualRefreshUsers() {
    const refreshBtn = document.getElementById('btnUsersManualRefresh');
    if (refreshBtn) refreshBtn.classList.add('is-spinning');
    setRealtimeSyncState('syncing');
    try {
        lastUsersPayloadHash = '';
        await loadUsers(usersPage, false);
        setRealtimeSyncState('idle');
    } catch (e) {
        setRealtimeSyncState('error');
    } finally {
        if (refreshBtn) {
            setTimeout(() => refreshBtn.classList.remove('is-spinning'), 600);
        }
    }
}

function setupLiveSearchDebounce() {
    const filterInput = document.getElementById('filterEmpId');
    if (!filterInput || filterInput.dataset.liveSearchBound) return;
    filterInput.dataset.liveSearchBound = 'true';

    // Clear on initialization to prevent residual autofill
    filterInput.value = '';
    userInteractedWithSearch = false;

    filterInput.addEventListener('keydown', () => {
        userInteractedWithSearch = true;
    });

    filterInput.addEventListener('input', (e) => {
        // Guard against browser credential manager autofilling without user keydown
        if (!e.isTrusted && !userInteractedWithSearch) {
            const val = filterInput.value.trim().toLowerCase();
            const currUser = (window.currentUsername || 'superadmin').toLowerCase();
            if (val === currUser || val === 'superadmin') {
                filterInput.value = '';
                return;
            }
        }
        userInteractedWithSearch = true;
        if (usersSearchDebounceTimer) {
            clearTimeout(usersSearchDebounceTimer);
        }
        usersSearchDebounceTimer = setTimeout(() => {
            loadUsers(1);
        }, 320);
    });
}

// Keep directory view steady when switching browser tabs (no background polling)
document.addEventListener('visibilitychange', () => {
    // No-op: keep current view stable without unprompted background reloads
});

function changeUsersLimit(newLimit) {
    usersPerPage = parseInt(newLimit) || 10;
    sessionStorage.setItem('dashboardUsersPerPage', usersPerPage);
    ['usersLimitSelect', 'usersPagination_per_page'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = usersPerPage;
    });
    loadUsers(1);
}

function changeLogsLimit(newLimit) {
    logsPerPage = parseInt(newLimit) || 10;
    sessionStorage.setItem('dashboardLogsPerPage', logsPerPage);
    ['logsLimitSelect', 'myLogsLimitSelect', 'logsPagination_per_page'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = logsPerPage;
    });
    loadLogs(1);
}

document.addEventListener('DOMContentLoaded', () => {
    // Initialize debounced live search for users table
    setupLiveSearchDebounce();

    const searchInput = document.getElementById('filterEmpId');
    if (searchInput) {
        searchInput.value = '';
    }
    userInteractedWithSearch = false;

    // Delayed safeguard against browsers that inject saved credentials after DOMContentLoaded
    setTimeout(() => {
        const si = document.getElementById('filterEmpId');
        if (si && !userInteractedWithSearch && si.value !== '') {
            si.value = '';
            loadUsers(usersPage || 1, false);
        }
    }, 250);

    // Show default tab or saved tab
    const savedTab = sessionStorage.getItem('activeDashboardTab');
    const canManageUsers = (userRole === 'super_admin' || (userRole === 'admin' && typeof userPrivileges !== 'undefined' && Array.isArray(userPrivileges) && userPrivileges.includes('view_accounts')));
    
    if (savedTab && document.getElementById(savedTab)) {
        showTab(savedTab);
        if (savedTab === 'users') {
            lastUsersPayloadHash = '';
            loadUsers(usersPage || 1, false);
        }
    } else if (canManageUsers && document.getElementById('users')) {
        showTab('users');
    } else if (document.getElementById('overview')) {
        showTab('overview');
    } else {
        showTab('profile');
    }

    // Add Enter key listener for Identity Verification
    const saInput = document.getElementById('saConfirmPasswordInput');
    if (saInput) {
        saInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitSaPasswordModal();
            }
        });
    }

    // Succession checkbox confirmation toggle listener
    const succCb = document.getElementById('succConfirmCheckbox');
    if (succCb) {
        succCb.addEventListener('change', onSuccConfirmCheckboxChange);
    }
});

async function loadDashboardOverview() {
    try {
        const res = await apiCall('get_dashboard_stats');
        if (!res || !res.success || !res.data) return;

        const { stats, pending_deletions, today_logs, recent_logs } = res.data;

        // Update KPI Metric Cards
        const totalUsersEl = document.getElementById('dashTotalUsers');
        if (totalUsersEl && stats && stats.total_users !== undefined) {
            totalUsersEl.textContent = stats.total_users;
        }

        const activeUsersEl = document.getElementById('dashActiveUsers');
        if (activeUsersEl && stats && stats.active_users !== undefined) {
            activeUsersEl.textContent = stats.active_users;
        }

        const pendingUsersEl = document.getElementById('dashPendingUsers');
        if (pendingUsersEl && stats && stats.pending_users !== undefined) {
            pendingUsersEl.textContent = stats.pending_users;
        }

        const todayLogsEl = document.getElementById('dashTodayLogs');
        if (todayLogsEl && today_logs !== undefined) {
            todayLogsEl.textContent = today_logs;
        }

        // Update Role Distribution
        if (stats) {
            const tot = Math.max(1, parseInt(stats.total_users) || 1);
            const saCnt = parseInt(stats.sa_count) || 0;
            const adminCnt = parseInt(stats.admin_count) || 0;
            const userCnt = parseInt(stats.user_count) || 0;

            const saPct = Math.round((saCnt / tot) * 100);
            const adminPct = Math.round((adminCnt / tot) * 100);
            const userPct = Math.round((userCnt / tot) * 100);

            const roleSaCount = document.getElementById('dashRoleSaCount');
            if (roleSaCount) roleSaCount.textContent = `${saCnt} (${saPct}%)`;
            const roleSaBar = document.getElementById('dashRoleSaBar');
            if (roleSaBar) roleSaBar.style.width = `${saPct}%`;

            const roleAdminCount = document.getElementById('dashRoleAdminCount');
            if (roleAdminCount) roleAdminCount.textContent = `${adminCnt} (${adminPct}%)`;
            const roleAdminBar = document.getElementById('dashRoleAdminBar');
            if (roleAdminBar) roleAdminBar.style.width = `${adminPct}%`;

            const roleUserCount = document.getElementById('dashRoleUserCount');
            if (roleUserCount) roleUserCount.textContent = `${userCnt} (${userPct}%)`;
            const roleUserBar = document.getElementById('dashRoleUserBar');
            if (roleUserBar) roleUserBar.style.width = `${userPct}%`;
        }

        // Update Recent Activity Feed
        const feed = document.getElementById('dashActivityFeed');
        if (feed && Array.isArray(recent_logs)) {
            if (recent_logs.length === 0) {
                feed.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.88rem;">No activity records found yet.</div>';
            } else {
                let html = '';
                recent_logs.forEach(log => {
                    const rawAct = log.action || 'ACTIVITY';
                    const actUpper = rawAct.toUpperCase();
                    let badgeCls = 'dash-badge-blue';
                    if (actUpper.includes('LOGIN') || actUpper.includes('CREATED') || actUpper.includes('REGISTERED') || actUpper.includes('APPROVED')) {
                        badgeCls = 'dash-badge-green';
                    } else if (actUpper.includes('DELETED') || actUpper.includes('BLOCKED') || actUpper.includes('REJECTED')) {
                        badgeCls = 'dash-badge-red';
                    } else if (actUpper.includes('UPDATED') || actUpper.includes('TRANSFER') || actUpper.includes('PASSWORD')) {
                        badgeCls = 'dash-badge-amber';
                    }

                    let timeStr = 'Recent';
                    if (log.created_at) {
                        try {
                            const d = new Date(log.created_at.replace(/-/g, '/'));
                            if (!isNaN(d.getTime())) {
                                timeStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' • ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                            } else {
                                timeStr = log.created_at;
                            }
                        } catch (e) {
                            timeStr = log.created_at;
                        }
                    }

                    const safeAction = escapeHtml(rawAct);
                    const safeDetails = escapeHtml(log.details || 'Action performed');
                    const safeUser = escapeHtml(log.username || 'system');

                    html += `
                        <div class="dash-activity-item">
                            <span class="dash-activity-badge ${badgeCls}">${safeAction}</span>
                            <div class="dash-activity-body">
                                <div class="dash-activity-text">${safeDetails}</div>
                                <div class="dash-activity-meta">
                                    <span>by <strong>@${safeUser}</strong></span>
                                    <span>&bull;</span>
                                    <span>${timeStr}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                feed.innerHTML = html;
            }
        }
    } catch (e) {
        console.error('Error refreshing dashboard overview:', e);
    }
}

async function apiCall(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    for (const key in data) {
        formData.append(key, data[key]);
    }
    // Attach CSRF token from the meta tag injected by the server-side page
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        formData.append('csrf_token', csrfMeta.getAttribute('content'));
    }
    try {
        const response = await fetch('../php/admin_actions.php', {
            method: 'POST',
            body: formData
        });
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (jsonErr) {
            console.error('API call (' + action + ') failed to parse JSON response:', text);
            return { success: false, message: 'Server communication error: ' + (text.slice(0, 100) || response.statusText) };
        }
    } catch (netErr) {
        console.error('API call (' + action + ') network request failed:', netErr);
        throw netErr;
    }
}

// ----------------------------------------------------
// Super Admin Password Confirmation Modal Logic
// ----------------------------------------------------
let saPasswordResolve = null;
let saPasswordReject = null;

function promptSaPassword() {
    return new Promise((resolve, reject) => {
        const modal = document.getElementById('saPasswordModal');
        const input = document.getElementById('saConfirmPasswordInput');
        if (!modal || !input) {
            customAlert('Password confirmation modal not found in DOM.');
            return reject('Modal not found');
        }
        
        saPasswordResolve = resolve;
        saPasswordReject = reject;
        
        input.value = '';
        modal.style.display = 'flex';
        input.focus();
    });
}

function closeSaPasswordModal() {
    const modal = document.getElementById('saPasswordModal');
    if (modal) modal.style.display = 'none';
    if (saPasswordReject) {
        saPasswordReject('cancelled');
        saPasswordReject = null;
        saPasswordResolve = null;
    }
}

async function submitSaPasswordModal() {
    const input = document.getElementById('saConfirmPasswordInput');
    const btn = document.getElementById('saConfirmPasswordBtn');
    const password = input.value;
    
    if (!password) {
        await customAlert('Please enter your password.');
        input.focus();
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = 'Verifying...';
    
    try {
        const res = await apiCall('verify_action_password', { action_confirm_password: password });
        if (res.success) {
            const modal = document.getElementById('saPasswordModal');
            if (modal) modal.style.display = 'none';
            if (saPasswordResolve) {
                saPasswordResolve(password);
                saPasswordResolve = null;
                saPasswordReject = null;
            }
        } else {
            await customAlert(res.message || 'Incorrect password.');
            input.value = '';
            input.focus();
        }
    } catch (e) {
        await customAlert('A network error occurred while verifying password.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Verify & Proceed';
    }
}

async function loadUsers(page = null, isSilent = false) {
    if (page === null) {
        page = parseInt(sessionStorage.getItem('dashboardUsersPage')) || 1;
    }
    sessionStorage.setItem('dashboardUsersPage', page);
    usersPage = page;
    const filterInput = document.getElementById('filterEmpId');
    if (filterInput && !userInteractedWithSearch) {
        filterInput.value = '';
    }
    const empId = filterInput ? filterInput.value.trim() : '';
    const roleFilter = document.getElementById('filterUserRole')?.value || '';
    const statusFilter = document.getElementById('filterUserStatus')?.value || '';

    const topLimitSel = document.getElementById('usersLimitSelect');
    if (topLimitSel) topLimitSel.value = usersPerPage;
    
    const tbody = document.querySelector('#usersTable tbody');
    if (!tbody) return;

    if (!isSilent) {
        tbody.innerHTML = '<tr><td colspan="6" class="table-state-cell"><div class="table-loading-spinner"></div><div>Loading accounts directory...</div></td></tr>';
        setRealtimeSyncState('syncing');
    }

    try {
        const response = await fetch(`../php/admin_actions.php?action=get_users&emp_id=${encodeURIComponent(empId)}&role=${encodeURIComponent(roleFilter)}&status=${encodeURIComponent(statusFilter)}&page=${page}&limit=${usersPerPage}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const rawUsers = data.data.users || [];
            const payloadSignature = JSON.stringify({
                total: data.data.total,
                pages: data.data.pages,
                users: rawUsers.map(u => ({
                    id: u.id,
                    display_status: u.display_status,
                    status: u.status,
                    role: u.role,
                    is_active: u.is_active,
                    is_former_superadmin: u.is_former_superadmin,
                    is_current_superadmin: u.is_current_superadmin,
                    is_active_session: u.is_active_session,
                    username: u.username,
                    first_name: u.first_name,
                    last_name: u.last_name,
                    id_number: u.id_number,
                    email: u.email,
                    phone_number: u.phone_number
                }))
            });

            const hasExistingUserRows = tbody.querySelector('tr:not(.table-state-cell)') !== null;
            if (isSilent && payloadSignature === lastUsersPayloadHash && hasExistingUserRows) {
                // Server data is strictly identical and rows are already in DOM, avoid unnecessary churn
                setRealtimeSyncState('idle');
                return;
            }

            // If an action menu or modal was opened while request was in-flight, pause replacing DOM rows
            if (isSilent) {
                const dropdownOpen = document.querySelector('.actions-dropdown-menu.show');
                const modalVisible = document.querySelector(
                    '.modal[style*="display: flex"], .modal[style*="display: block"], ' +
                    '.modern-modal-backdrop[style*="display: flex"], .modern-modal-backdrop[style*="display: block"]'
                );
                if (dropdownOpen || modalVisible) {
                    setRealtimeSyncState('paused');
                    return;
                }
            }

            lastUsersPayloadHash = payloadSignature;

            while (tbody.firstChild) tbody.removeChild(tbody.firstChild);

            if (rawUsers.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="6" class="table-state-cell"><div style="font-size:1.5rem; margin-bottom:0.4rem;">🔍</div><div>No user accounts found matching your query.</div></td>';
                tbody.appendChild(tr);
                renderPagination('usersPagination', 0, 1, 'loadUsers', 0, usersPerPage);
                setRealtimeSyncState('idle');
                return;
            }

            rawUsers.forEach(u => {
                const actionsCell = document.createElement('td');
                const actionsWrapper = document.createElement('div');
                actionsWrapper.className = 'actions-cell-wrapper';

                const isSuperAdmin = userRole === 'super_admin';
                const isAdmin = userRole === 'admin';
                const isTargetCurrentSa = !!u.is_current_superadmin;
                const isTargetFormerSa = !!u.is_former_superadmin;
                const displayStatus = u.display_status || u.status || 'pending';
                const normStatus = displayStatus.toLowerCase();
                const isStatusActive = (normStatus === 'active' || normStatus === 'approved');
                const isStatusInactive = (normStatus === 'inactive' || normStatus === 'suspended' || normStatus === 'blocked');
                const isStatusPending = (normStatus === 'pending' || normStatus === 'pending_setup');

                const isTargetAdmin = (u.role || '').toLowerCase() === 'admin';
                const isTargetSa = (u.role || '').toLowerCase() === 'super_admin';
                const canEdit = isSuperAdmin ? !isTargetCurrentSa : (isAdmin && userPrivileges.includes('edit_manage_accounts') && !isTargetSa);
                const canManagePriv = isSuperAdmin ? !isTargetCurrentSa : (isAdmin && userPrivileges.includes('edit_manage_accounts') && !isTargetSa && !isTargetAdmin);
                const isEligibleSuccessor = Boolean(u.is_eligible_successor || ((u.role || '').toLowerCase() === 'admin' && isStatusActive && !isTargetCurrentSa && !u.is_me));
                const canTransfer = isSuperAdmin && isEligibleSuccessor && !isTargetCurrentSa;
                const canDeactivate = (isSuperAdmin ? (!isTargetCurrentSa && !u.is_me) : (isAdmin && userPrivileges.includes('block_unblock_users') && !isTargetSa && !isTargetAdmin)) && isStatusActive;
                const canReactivate = (isSuperAdmin ? (!isTargetCurrentSa && !u.is_me) : (isAdmin && userPrivileges.includes('block_unblock_users') && !isTargetSa && !isTargetFormerSa)) && isStatusInactive;
                const canApprove = isSuperAdmin || (isAdmin && userPrivileges.includes('registration_approval'));
                const canBlock = isSuperAdmin || (isAdmin && userPrivileges.includes('block_unblock_users') && !isTargetCurrentSa && !isTargetSa);

                const fullName = ((u.first_name || '') + ' ' + (u.last_name || '')).trim() || u.username;

                function makeBtn(label, cls, action, title = '') {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `table-action-btn ${cls}`;
                    btn.textContent = label;
                    btn.dataset.action = action;
                    btn.dataset.userId = u.id;
                    btn.dataset.fullname = fullName;
                    btn.dataset.role = u.role || '';
                    btn.dataset.status = displayStatus;
                    btn.dataset.formerSa = isTargetFormerSa ? '1' : '0';
                    if (title) btn.title = title;
                    return btn;
                }

                // 1. Primary Action: View Details
                const viewBtn = makeBtn('View', 'btn-action-view', 'view_details', 'View Comprehensive Account Dossier');
                actionsWrapper.appendChild(viewBtn);

                // 2. Primary Action: Edit User
                if (canEdit) {
                    const editBtn = makeBtn('✏️ Edit', 'btn-action-edit', 'edit', 'Edit User Details');
                    editBtn.dataset.firstName = u.first_name || '';
                    editBtn.dataset.lastName = u.last_name || '';
                    editBtn.dataset.email = u.email || '';
                    editBtn.dataset.phone = u.phone_number || '';
                    editBtn.dataset.username = u.username || '';
                    editBtn.dataset.role = u.role || '';
                    editBtn.dataset.idno = u.id_number || '----';
                    actionsWrapper.appendChild(editBtn);
                }

                // Dropdown Menu: Grouped into sleek '•••' menu
                const moreWrap = document.createElement('div');
                moreWrap.className = 'actions-dropdown-wrap';

                const moreBtn = document.createElement('button');
                moreBtn.type = 'button';
                moreBtn.className = 'table-action-btn btn-action-more';
                moreBtn.dataset.toggle = 'actions-dropdown';
                moreBtn.title = 'More Options';
                moreBtn.setAttribute('aria-label', 'More Options');
                moreBtn.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="12" cy="5" r="2.2"></circle>
                        <circle cx="12" cy="12" r="2.2"></circle>
                        <circle cx="12" cy="19" r="2.2"></circle>
                    </svg>
                `;
                moreWrap.appendChild(moreBtn);

                const menu = document.createElement('div');
                menu.className = 'actions-dropdown-menu';

                function makeDropdownItem(label, cls, action, title = '') {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = `dropdown-item ${cls}`;
                    item.innerHTML = label;
                    item.dataset.action = action;
                    item.dataset.userId = u.id;
                    item.dataset.fullname = fullName;
                    item.dataset.role = u.role || '';
                    item.dataset.status = displayStatus;
                    item.dataset.formerSa = isTargetFormerSa ? '1' : '0';
                    item.dataset.username = u.username || '';
                    item.dataset.idno = u.id_number || '----';
                    if (title) item.title = title;
                    return item;
                }

                let hasSecondary = false;

                // Header in menu if target is Current Superadmin
                if (isTargetCurrentSa) {
                    const saHeader = document.createElement('div');
                    saHeader.className = 'dropdown-sa-header';
                    saHeader.innerHTML = '👑 Primary Superadmin (Active)';
                    menu.appendChild(saHeader);
                    hasSecondary = true;
                }

                // Transfer Superadmin Role action
                if (canTransfer) {
                    menu.appendChild(makeDropdownItem('<span>👑</span> Transfer Superadmin', 'item-transfer', 'transfer_superadmin', 'Formally transfer Superadmin rights to this Administrator'));
                    hasSecondary = true;
                }

                // Account Status Actions (Deactivate / Reactivate)
                if (canDeactivate) {
                    menu.appendChild(makeDropdownItem('<span>⏸️</span> Deactivate Account', 'item-deactivate', 'deactivate', 'Deactivate account login access'));
                    hasSecondary = true;
                }
                if (canReactivate) {
                    menu.appendChild(makeDropdownItem('<span>▶️</span> Reactivate Account', 'item-reactivate', 'reactivate', isTargetFormerSa ? 'Reactivate as Admin (does not grant Superadmin)' : 'Reactivate account'));
                    hasSecondary = true;
                }

                // Pending registration approval
                if (u.status === 'pending' || u.status === 'pending_setup') {
                    if (canApprove) {
                        menu.appendChild(makeDropdownItem('<span>✓</span> Approve Registration', 'item-approve', 'approve', 'Approve Registration'));
                        hasSecondary = true;
                    }
                    if (canBlock) {
                        menu.appendChild(makeDropdownItem('<span>🚫</span> Reject / Block', 'item-block', 'block', 'Reject and block registration'));
                        hasSecondary = true;
                    }
                }

                // Role Selection (Super Admin only, strictly on non-Superadmin accounts)
                if (isSuperAdmin && !isTargetCurrentSa) {
                    if (hasSecondary) {
                        const div = document.createElement('div');
                        div.className = 'dropdown-divider';
                        menu.appendChild(div);
                    }
                    const roleBox = document.createElement('div');
                    roleBox.className = 'dropdown-role-box';
                    
                    const roleLbl = document.createElement('span');
                    roleLbl.className = 'dropdown-role-label';
                    roleLbl.textContent = 'Change Role:';
                    roleBox.appendChild(roleLbl);

                    const sel = document.createElement('select');
                    sel.className = 'dropdown-role-select';
                    sel.dataset.action = 'change_role';
                    sel.dataset.userId = u.id;
                    sel.title = 'Change User Role';
                    const safeRole = (u.role || '').trim().toLowerCase();
                    ['user', 'admin'].forEach(rv => {
                        const opt = document.createElement('option');
                        opt.value = rv;
                        opt.textContent = rv.replace('_', ' ').toUpperCase();
                        if (safeRole === rv) {
                            opt.selected = true;
                            opt.defaultSelected = true;
                        }
                        sel.appendChild(opt);
                    });
                    sel.value = safeRole === 'super_admin' ? 'admin' : safeRole;
                    roleBox.appendChild(sel);
                    menu.appendChild(roleBox);
                    hasSecondary = true;
                }

                // Delete User / Request Deletion
                if (isSuperAdmin && !isTargetCurrentSa) {
                    if (hasSecondary) {
                        const div = document.createElement('div');
                        div.className = 'dropdown-divider';
                        menu.appendChild(div);
                    }
                    menu.appendChild(makeDropdownItem('<span>🗑️</span> Delete User', 'item-delete', 'delete', 'Permanently Delete User'));
                    hasSecondary = true;
                } else if (isAdmin && userPrivileges.includes('account_deletion_requests') && !isTargetCurrentSa) {
                    if (hasSecondary) {
                        const div = document.createElement('div');
                        div.className = 'dropdown-divider';
                        menu.appendChild(div);
                    }
                    menu.appendChild(makeDropdownItem('<span>🗑️</span> Request Deletion', 'item-delete', 'req_delete', 'Submit Deletion Request'));
                    hasSecondary = true;
                }

                // Edit Privileges option in dropdown menu
                if (canManagePriv) {
                    if (hasSecondary) {
                        const div = document.createElement('div');
                        div.className = 'dropdown-divider';
                        menu.appendChild(div);
                    }
                    menu.appendChild(makeDropdownItem('<span>🛡️</span> Edit Privileges', 'item-privileges', 'privileges', 'Configure account module privileges'));
                    hasSecondary = true;
                }

                // View Audit History (always available for admins)
                if (hasSecondary) {
                    const div = document.createElement('div');
                    div.className = 'dropdown-divider';
                    menu.appendChild(div);
                }
                menu.appendChild(makeDropdownItem('<span>📜</span> View Audit History', 'item-audit', 'audit_history', 'View chronological security audit history'));
                hasSecondary = true;

                if (hasSecondary) {
                    moreWrap.appendChild(menu);
                    actionsWrapper.appendChild(moreWrap);
                }

                actionsCell.appendChild(actionsWrapper);

                // Build status badge
                let statusBadgeHtml = '';
                if (isStatusActive) {
                    statusBadgeHtml = '<span class="user-status-badge status-active">Active</span>';
                } else if (isStatusInactive) {
                    statusBadgeHtml = '<span class="user-status-badge status-inactive">Inactive</span>';
                } else if (normStatus === 'pending_setup') {
                    statusBadgeHtml = '<span class="user-status-badge status-pending-setup" title="Account created — needs to login first to complete setup">Pending Setup</span>';
                } else {
                    statusBadgeHtml = `<span class="user-status-badge status-pending">${escapeHtml(u.status || 'Pending')}</span>`;
                }

                // Build table row
                const tr = document.createElement('tr');

                function makeTextCell(val, isMono = false) {
                    const td = document.createElement('td');
                    td.textContent = val || '—';
                    if (isMono) td.style.fontFamily = "'JetBrains Mono', monospace";
                    return td;
                }

                // 1. ID Number
                tr.appendChild(makeTextCell(u.id_number || '----', true));

                // 2. Name with badges
                const nameTd = document.createElement('td');
                const nameSpan = document.createElement('span');
                nameSpan.textContent = fullName;
                nameSpan.style.fontWeight = '600';
                nameTd.appendChild(nameSpan);
                if (isTargetCurrentSa) {
                    const crown = document.createElement('span');
                    crown.className = 'sa-crown-badge';
                    crown.title = 'Primary Authorized Superadmin';
                    crown.textContent = '👑';
                    nameTd.appendChild(crown);
                } else if (isTargetFormerSa) {
                    const fBadge = document.createElement('span');
                    fBadge.className = 'former-sa-badge';
                    fBadge.title = 'Former Superadmin';
                    fBadge.textContent = 'Former SA';
                    nameTd.appendChild(fBadge);
                }
                tr.appendChild(nameTd);

                // 3. Username with session indicator
                const userTd = document.createElement('td');
                const userSpan = document.createElement('span');
                userSpan.textContent = u.username ? `@${u.username}` : '—';
                userTd.appendChild(userSpan);
                if (u.is_active_session) {
                    const sDot = document.createElement('span');
                    sDot.className = 'session-active-dot';
                    sDot.title = 'Active Session Live';
                    userTd.appendChild(sDot);
                }
                tr.appendChild(userTd);
                
                // 4. Role Cell
                const roleTd = document.createElement('td');
                const rClean = (u.role || 'user').toLowerCase();
                if (isTargetCurrentSa) {
                    const saRoleBadge = document.createElement('span');
                    saRoleBadge.className = 'priv-role-badge role-current-superadmin';
                    saRoleBadge.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> PRIMARY SUPER ADMIN';
                    roleTd.appendChild(saRoleBadge);
                } else if (isTargetFormerSa) {
                    const regRoleBadge = document.createElement('span');
                    regRoleBadge.className = `priv-role-badge ${rClean}`;
                    regRoleBadge.textContent = rClean.replace('_', ' ').toUpperCase();
                    roleTd.appendChild(regRoleBadge);
                    const formerBadge = document.createElement('span');
                    formerBadge.className = 'priv-role-badge role-former-superadmin';
                    formerBadge.style.marginLeft = '0.3rem';
                    formerBadge.textContent = 'FORMER SA';
                    roleTd.appendChild(formerBadge);
                } else {
                    const roleBadge = document.createElement('span');
                    roleBadge.className = `priv-role-badge ${rClean}`;
                    roleBadge.textContent = rClean.replace('_', ' ').toUpperCase();
                    roleTd.appendChild(roleBadge);
                }
                tr.appendChild(roleTd);

                // 5. Status Cell
                const statusTd = document.createElement('td');
                statusTd.innerHTML = statusBadgeHtml;
                tr.appendChild(statusTd);

                // 6. Actions Cell
                tr.appendChild(actionsCell);
                tbody.appendChild(tr);
            });

            attachUserTableListeners();
            renderPagination('usersPagination', data.data.pages, usersPage, 'loadUsers', data.data.total, usersPerPage);
            setRealtimeSyncState('idle');
        } else {
            if (!isSilent && tbody) {
                tbody.innerHTML = `<td colspan="6" class="table-state-cell" style="color:var(--danger);">${escapeHtml(data.message || 'Failed to load user records.')}</td>`;
            }
            setRealtimeSyncState('error');
        }
    } catch (err) {
        console.error('Error fetching users:', err);
        if (!isSilent && tbody) {
            tbody.innerHTML = '<td colspan="6" class="table-state-cell" style="color:var(--danger);">Network error loading accounts. Please refresh or try again.</td>';
        }
        setRealtimeSyncState('error');
    }
}

// Delegate user-table action clicks cleanly
function attachUserTableListeners() {
    const tbody = document.querySelector('#usersTable tbody');
    if (!tbody) return;
    const fresh = tbody.cloneNode(true);
    tbody.parentNode.replaceChild(fresh, tbody);

    fresh.addEventListener('click', async (e) => {
        // Dropdown toggle click
        const toggleBtn = e.target.closest('[data-toggle="actions-dropdown"]');
        if (toggleBtn) {
            e.stopPropagation();
            const wrap = toggleBtn.closest('.actions-dropdown-wrap');
            const menu = wrap ? wrap.querySelector('.actions-dropdown-menu') : null;
            if (!menu) return;
            const wasOpen = menu.classList.contains('show');

            // Close all open menus
            document.querySelectorAll('.actions-dropdown-menu.show').forEach(m => m.classList.remove('show'));

            if (!wasOpen) {
                menu.classList.add('show');
                // Auto flip if overflowing below window
                const rect = menu.getBoundingClientRect();
                if (rect.bottom > (window.innerHeight || document.documentElement.clientHeight) - 15) {
                    menu.classList.add('dropdown-flip-up');
                } else {
                    menu.classList.remove('dropdown-flip-up');
                }
            }
            return;
        }

        // Avoid closing if clicking inside the role select box
        if (e.target.closest('.dropdown-role-box')) {
            return;
        }

        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        // Dismiss open dropdowns
        document.querySelectorAll('.actions-dropdown-menu.show').forEach(m => m.classList.remove('show'));

        const action = btn.dataset.action;
        const uid    = btn.dataset.userId;
        const fullname = btn.dataset.fullname || '';
        const role = btn.dataset.role || '';
        const status = btn.dataset.status || '';
        const isFormerSa = btn.dataset.formerSa === '1';

        if (action === 'view_details') {
            openUserDetailsModal(uid);
        } else if (action === 'transfer_superadmin') {
            openSuperadminSuccessionModal(uid);
        } else if (action === 'deactivate') {
            openAccountStatusModal(uid, 'deactivate', fullname, role, status, isFormerSa);
        } else if (action === 'reactivate') {
            openAccountStatusModal(uid, 'reactivate', fullname, role, status, isFormerSa);
        } else if (action === 'audit_history') {
            openUserAuditModal(uid);
        } else if (action === 'approve') {
            await updateStatus(uid, 'approved');
        } else if (action === 'block') {
            await updateStatus(uid, 'blocked');
        } else if (action === 'unblock') {
            await updateStatus(uid, 'approved');
        } else if (action === 'delete') {
            await deleteUser(uid, false);
        } else if (action === 'req_delete') {
            await deleteUser(uid, true);
        } else if (action === 'edit') {
            openEditModal(uid, btn.dataset.firstName, btn.dataset.lastName, btn.dataset.email, btn.dataset.phone, btn.dataset.username, btn.dataset.role, btn.dataset.idno);
        } else if (action === 'privileges') {
            openManagePrivilegesModal(uid, btn.dataset.username, btn.dataset.role, btn.dataset.fullname, btn.dataset.idno);
        }
    });

    fresh.addEventListener('change', async (e) => {
        const sel = e.target.closest('select[data-action="change_role"]');
        if (!sel) return;
        const targetUserId = sel.dataset.userId;
        const targetRole = sel.value;
        if (!await customConfirm(`Are you sure you want to change this account's role to ${targetRole.replace('_', ' ').toUpperCase()}?`)) {
            loadUsers(usersPage);
            return;
        }
        await updateRole(targetUserId, targetRole);
    });
}

// Global click outside and escape key dismissers for table dropdowns
document.addEventListener('click', (e) => {
    if (!e.target.closest('.actions-dropdown-wrap')) {
        document.querySelectorAll('.actions-dropdown-menu.show').forEach(m => m.classList.remove('show'));
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.actions-dropdown-menu.show').forEach(m => m.classList.remove('show'));
    }
});

let currentEditUserData = {};
function openEditModal(id, firstName, lastName, email, phone, username = '', role = 'user', idno = '') {
    currentEditUserData = { id, firstName, lastName, email, phone, username, role, idno };
    document.getElementById('editUserId').value = id;
    document.getElementById('editFirstName').value = firstName;
    document.getElementById('editLastName').value = lastName;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone || '';
    const newPwdEl = document.getElementById('editNewPassword');
    if (newPwdEl) newPwdEl.value = '';

    // Manage Privileges button in edit modal: toggle visibility based on authorization
    const editPrivBtn = document.getElementById('editUserPrivBtn');
    if (editPrivBtn) {
        const isTargetAdmin = (role || '').toLowerCase() === 'admin';
        const isTargetCurrentSa = (role || '').toLowerCase() === 'super_admin';
        const canManage = (userRole === 'super_admin') ? !isTargetCurrentSa : (userRole === 'admin' && userPrivileges.includes('edit_manage_accounts') && !isTargetAdmin && !isTargetCurrentSa);
        editPrivBtn.style.display = canManage ? 'inline-flex' : 'none';
    }

    const modal = document.getElementById('editUserModal');
    if (modal) modal.style.display = 'flex';
}

function openPrivFromEditModal() {
    const id = document.getElementById('editUserId').value;
    const fname = document.getElementById('editFirstName').value;
    const lname = document.getElementById('editLastName').value;
    const modal = document.getElementById('editUserModal');
    if (modal) modal.style.display = 'none';
    openManagePrivilegesModal(
        id, 
        currentEditUserData.username || '', 
        currentEditUserData.role || 'user', 
        `${fname} ${lname}`.trim() || currentEditUserData.username, 
        currentEditUserData.idno || ''
    );
}
window.openPrivFromEditModal = openPrivFromEditModal;

function closeEditModal() {
    const modal = document.getElementById('editUserModal');
    if (modal) modal.style.display = 'none';
}

async function submitEditUser() {
    const id = document.getElementById('editUserId').value;
    const firstName = document.getElementById('editFirstName').value.trim();
    const lastName = document.getElementById('editLastName').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const phone = document.getElementById('editPhone').value.trim();
    const newPwdEl = document.getElementById('editNewPassword');
    const newPassword = newPwdEl ? newPwdEl.value : '';
    
    if (!firstName || !lastName || !email) {
        await customAlert("First Name, Last Name, and Email are required and cannot be whitespace only.");
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        await customAlert("Please enter a valid email address.");
        return;
    }

    if (newPassword) {
        if (newPassword.length < 8) {
            await customAlert("Reset password must be at least 8 characters long.");
            return;
        }
        if (!/[A-Z]/.test(newPassword) || !/[a-z]/.test(newPassword) || !/[0-9]/.test(newPassword) || !/[^a-zA-Z0-9]/.test(newPassword)) {
            await customAlert("Password must contain at least 1 uppercase, 1 lowercase, 1 number, and 1 special character.");
            return;
        }
    }
    
    let pwd = '';
    if (userRole === 'super_admin' || userRole === 'admin') {
        try { pwd = await promptSaPassword(); } catch (e) { return; }
    }
    
    const saveBtn = document.querySelector('#editUserModal .cyber-btn');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
    }

    try {
        const res = await apiCall('update_user_info', { 
            target_id: id, 
            first_name: firstName, 
            last_name: lastName, 
            email: email, 
            phone_number: phone,
            new_password: newPassword,
            action_confirm_password: pwd
        });
        
        if (res.success) {
            document.getElementById('editUserModal').style.display = 'none';
            lastUsersPayloadHash = '';
            loadUsers(usersPage, false);
            showToast('User information updated successfully', 'success');
        } else {
            await customAlert(res.message);
        }
    } catch (e) {
        await customAlert('Network error occurred while updating user info.');
    } finally {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Changes';
        }
    }
}

async function updateStatus(id, status) {
    if (!await customConfirm(`Are you sure you want to mark this user as ${status}?`)) return;
    
    let pwd = '';
    if (userRole === 'super_admin' || userRole === 'admin') {
        try { pwd = await promptSaPassword(); } catch (e) { return; }
    }
    
    const res = await apiCall('update_user_status', { target_id: id, status, action_confirm_password: pwd });
    if (res.success) {
        lastUsersPayloadHash = '';
        loadUsers(usersPage, false);
    } else {
        await customAlert(res.message);
    }
}

async function updateRole(id, role, confirmTransfer = false) {
    if (!confirmTransfer && !await customConfirm(`Change privileges to ${role}?`)) {
        loadUsers(usersPage); // reset select
        return;
    }
    
    let pwd = '';
    if (userRole === 'super_admin' || userRole === 'admin') {
        try { pwd = await promptSaPassword(); } catch (e) { 
            loadUsers(usersPage); 
            return; 
        }
    }
    
    const params = { target_id: id, role, action_confirm_password: pwd };
    if (confirmTransfer) params.confirm_transfer = 'true';
    
    const res = await apiCall('update_user_role', params);
    
    if (res.success) {
        if (confirmTransfer || (res.data && res.data.transferred)) {
            await customAlert('Super Admin rights transferred. You will now be logged out.');
            window.location.href = '../php/logout.php?msg=rights_transferred';
            return;
        }
        lastUsersPayloadHash = '';
        loadUsers(usersPage, false);
    } else {
        if (res.data && res.data.requires_confirmation) {
            if (await customConfirm(res.message)) {
                updateRole(id, role, true);
                return;
            } else {
                loadUsers(usersPage);
            }
        } else {
            await customAlert(res.message);
            loadUsers(usersPage);
        }
    }
}

async function deleteUser(id, isRequest) {
    let reason = '';
    let pwd = '';
    if (isRequest) {
        reason = await customPrompt("Enter reason for deletion (goes to Super Admin):");
        if (!reason) return;
        if (userRole === 'super_admin' || userRole === 'admin') {
            try { pwd = await promptSaPassword(); } catch (e) { return; }
        }
    } else {
        if (!await customConfirm("Are you sure you want to permanently delete this user?")) return;
        if (userRole === 'super_admin' || userRole === 'admin') {
            try { pwd = await promptSaPassword(); } catch (e) { return; }
        }
    }
    
    const res = await apiCall('delete_user', { target_id: id, reason, action_confirm_password: pwd });
    if (res.success) {
        await customAlert(res.message);
        lastUsersPayloadHash = '';
        loadUsers(usersPage, false);
    } else {
        await customAlert(res.message);
    }
}

function getActionBadgeHtml(action) {
    const act = (action || '').toUpperCase();
    let cls = 'ca-action-badge';
    let label = action || 'UNKNOWN';
    switch (act) {
        case 'ACCOUNT_CREATED':
            cls += ' badge-account-created';
            label = 'Account Created';
            break;
        case 'LOGIN':
            cls += ' badge-login';
            label = 'Login';
            break;
        case 'LOGOUT':
            cls += ' badge-logout';
            label = 'Logout';
            break;
        case 'ROLE_UPDATED':
            cls += ' badge-role-updated';
            label = 'Role Updated';
            break;
        case 'ROLE_TRANSFER':
            cls += ' badge-role-transfer';
            label = 'Role Transfer';
            break;
        case 'STATUS_UPDATED':
            cls += ' badge-status-updated';
            label = 'Status Updated';
            break;
        case 'ACCOUNT_DELETED':
            cls += ' badge-account-deleted';
            label = 'Account Deleted';
            break;
        case 'PRIVILEGES_UPDATED':
            cls += ' badge-status-updated';
            label = 'Privileges Updated';
            break;
        case 'PROFILE_UPDATED':
            cls += ' badge-profile-updated';
            label = 'Profile Updated';
            break;
        case 'PASSWORD_CHANGED':
            cls += ' badge-password-changed';
            label = 'Password Changed';
            break;
        case 'SETUP_COMPLETED':
            cls += ' badge-setup-completed';
            label = 'Setup Completed';
            break;
        default:
            cls += ' badge-status-updated';
            label = (action || '').replace(/_/g, ' ');
            break;
    }
    return `<span class="${cls}">${escapeHtml(label)}</span>`;
}

async function loadLogs(page = null) {
    if (page === null) {
        page = parseInt(sessionStorage.getItem('dashboardLogsPage')) || 1;
    }
    sessionStorage.setItem('dashboardLogsPage', page);
    logsPage = page;
    const dateEl = document.getElementById('filterDate');
    const actionEl = document.getElementById('filterLogAction');
    const searchEl = document.getElementById('filterLogSearch');

    const date = dateEl ? dateEl.value : '';
    const actionFilter = actionEl ? actionEl.value : '';
    const search = searchEl ? searchEl.value.trim() : '';

    const topLimitSel = document.getElementById('logsLimitSelect') || document.getElementById('myLogsLimitSelect');
    if (topLimitSel) topLimitSel.value = logsPerPage;

    let url = `../php/admin_actions.php?action=get_logs&date=${encodeURIComponent(date)}&page=${page}&limit=${logsPerPage}`;
    if (actionFilter) url += `&action_filter=${encodeURIComponent(actionFilter)}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;

    const tbody = document.querySelector('#logsTable tbody');
    const colSpan = (userRole === 'super_admin' || userRole === 'admin') ? 6 : 3;
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="table-state-cell"><div class="table-loading-spinner"></div><div>Loading activity logs...</div></td></tr>`;
    }

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.success && data.data) {
            if (!tbody) return;
            while (tbody.firstChild) tbody.removeChild(tbody.firstChild);

            if (!data.data.logs || data.data.logs.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td colspan="${colSpan}" class="table-state-cell"><div style="font-size:1.5rem; margin-bottom:0.4rem;">📜</div><div>No activity logs found.</div></td>`;
                tbody.appendChild(tr);
            } else {
                data.data.logs.forEach(l => {
                    const tr = document.createElement('tr');

                    // Date & Time
                    const dtTd = document.createElement('td');
                    dtTd.style.fontFamily = "'JetBrains Mono', monospace";
                    dtTd.style.fontSize = "0.82rem";
                    dtTd.style.color = "var(--ca-cyan)";
                    dtTd.textContent = l.created_at || l.time_in || '—';
                    tr.appendChild(dtTd);

                    if (userRole === 'super_admin' || userRole === 'admin') {
                        // ID Number
                        const idTd = document.createElement('td');
                        idTd.style.fontFamily = "'JetBrains Mono', monospace";
                        idTd.style.fontSize = "0.82rem";
                        idTd.textContent = l.id_number || '—';
                        tr.appendChild(idTd);

                        // User Name / Username
                        const userTd = document.createElement('td');
                        const fullName = ((l.first_name || '') + ' ' + (l.last_name || '')).trim();
                        if (fullName) {
                            userTd.innerHTML = `<strong>${escapeHtml(fullName)}</strong> <span style="font-size:0.75rem; color:var(--text-muted);">@${escapeHtml(l.username || '')}</span>`;
                        } else if (l.username) {
                            userTd.textContent = '@' + l.username;
                        } else {
                            userTd.textContent = 'System';
                        }
                        tr.appendChild(userTd);

                        // Role
                        const roleTd = document.createElement('td');
                        roleTd.textContent = l.role ? (l.role.charAt(0).toUpperCase() + l.role.slice(1).replace('_', ' ')) : '—';
                        tr.appendChild(roleTd);
                    }

                    // Action badge
                    const actionTd = document.createElement('td');
                    actionTd.innerHTML = getActionBadgeHtml(l.action);
                    tr.appendChild(actionTd);

                    // Details
                    const detailsTd = document.createElement('td');
                    detailsTd.style.fontSize = "0.85rem";
                    detailsTd.style.color = "var(--text-secondary)";
                    detailsTd.textContent = l.details || (l.time_out ? 'Logged Out' : 'Active Session');
                    tr.appendChild(detailsTd);

                    tbody.appendChild(tr);
                });
            }

            renderPagination('logsPagination', data.data.pages, logsPage, 'loadLogs', data.data.total, logsPerPage);
        }
    } catch (e) {
        console.error('Error loading activity logs:', e);
    }
}

function resetLogFilters() {
    const dateEl = document.getElementById('filterDate');
    const actionEl = document.getElementById('filterLogAction');
    const searchEl = document.getElementById('filterLogSearch');
    if (dateEl) dateEl.value = '';
    if (actionEl) actionEl.value = '';
    if (searchEl) searchEl.value = '';
    loadLogs(1);
}

async function loadDeleteRequests() {
    const tbody = document.querySelector('#delReqTable tbody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="5" class="table-state-cell"><div class="table-loading-spinner"></div><div>Loading deletion requests...</div></td></tr>';
    }

    try {
        const res = await apiCall('get_delete_requests');
        if (res.success && res.data) {
            if (!tbody) return;
            while (tbody.firstChild) tbody.removeChild(tbody.firstChild);

            if (!res.data.requests || res.data.requests.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="5" class="table-state-cell"><div style="font-size:1.5rem; margin-bottom:0.4rem;">✓</div><div>No pending deletion requests found.</div></td>';
                tbody.appendChild(tr);
                return;
            }

        res.data.requests.forEach(r => {
            const tr = document.createElement('tr');

            function mkTd(val) {
                const td = document.createElement('td');
                td.textContent = val || '';
                return td;
            }

            tr.appendChild(mkTd((r.target_username || '') + ' (' + (r.target_id_number || '') + ')'));
            tr.appendChild(mkTd(r.admin_username));
            
            const reasonTd = document.createElement('td');
            const viewBtn = document.createElement('button');
            viewBtn.className = 'btn-small';
            viewBtn.textContent = 'View';
            viewBtn.addEventListener('click', () => {
                customAlert(r.reason || 'No reason provided.');
            });
            reasonTd.appendChild(viewBtn);
            tr.appendChild(reasonTd);
            
            tr.appendChild(mkTd(r.status));

            const actionsTd = document.createElement('td');
            const actionsWrapper = document.createElement('div');
            actionsWrapper.className = 'actions-cell-wrapper';

            const approveBtn = document.createElement('button');
            approveBtn.type = 'button';
            approveBtn.className = 'table-action-btn btn-action-approve';
            approveBtn.textContent = '✓ Approve';
            approveBtn.dataset.reqId = r.id;
            approveBtn.dataset.decision = 'approved';

            const rejectBtn = document.createElement('button');
            rejectBtn.type = 'button';
            rejectBtn.className = 'table-action-btn btn-action-delete';
            rejectBtn.textContent = '✕ Reject';
            rejectBtn.dataset.reqId = r.id;
            rejectBtn.dataset.decision = 'rejected';

            actionsWrapper.appendChild(approveBtn);
            actionsWrapper.appendChild(rejectBtn);
            actionsTd.appendChild(actionsWrapper);
            tr.appendChild(actionsTd);
            tbody.appendChild(tr);
        });

        // Delegate click handling safely without duplicate listener leaks
        const fresh = tbody.cloneNode(true);
        tbody.parentNode.replaceChild(fresh, tbody);
        fresh.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-decision]');
            if (!btn) return;
            await handleDeleteReq(btn.dataset.reqId, btn.dataset.decision);
        });
    } else {
        if (tbody) {
            tbody.innerHTML = `<td colspan="5" class="table-state-cell" style="color:var(--danger);">${escapeHtml(res.message || 'Failed to load requests.')}</td>`;
        }
    }
} catch (err) {
    console.error('Error loading delete requests:', err);
    const tbody = document.querySelector('#delReqTable tbody');
    if (tbody) {
        tbody.innerHTML = '<td colspan="5" class="table-state-cell" style="color:var(--danger);">Network error loading deletion requests.</td>';
    }
}
}

async function handleDeleteReq(id, decision) {
    if (!await customConfirm(`Are you sure you want to ${decision} this deletion request?`)) return;
    
    let pwd = '';
    if (userRole === 'super_admin' || userRole === 'admin') {
        try { pwd = await promptSaPassword(); } catch (e) { return; }
    }
    
    const res = await apiCall('handle_delete_request', { request_id: id, decision, action_confirm_password: pwd });
    if (res.success) {
        loadDeleteRequests();
        if (document.getElementById('users').classList.contains('active')) {
            loadUsers(usersPage);
        }
    } else {
        await customAlert(res.message);
    }
}

function renderPagination(elementId, totalPages, currentPage, funcName, totalRecords = 0, perPage = 10) {
    const el = document.getElementById(elementId);
    if (!el) return;
    while (el.firstChild) el.removeChild(el.firstChild);

    totalPages = Math.max(1, parseInt(totalPages) || 1);
    currentPage = Math.max(1, Math.min(totalPages, parseInt(currentPage) || 1));
    perPage = parseInt(perPage) || 10;
    totalRecords = parseInt(totalRecords) || 0;

    const wrapper = document.createElement('div');
    wrapper.className = 'cyber-pagination-container';

    // 1. Info / Summary: e.g. "Showing 1–10 of 42 entries"
    const summary = document.createElement('div');
    summary.className = 'cyber-pagination-summary';
    if (totalRecords > 0) {
        const start = ((currentPage - 1) * perPage) + 1;
        const end = Math.min(currentPage * perPage, totalRecords);
        summary.innerHTML = `Showing <span class="cyber-page-num">${start}</span>–<span class="cyber-page-num">${end}</span> of <span class="cyber-page-num">${totalRecords}</span> entries`;
    } else {
        summary.innerHTML = `Showing <span class="cyber-page-num">0</span> entries`;
    }
    wrapper.appendChild(summary);

    // 2. Navigation Group: [ Previous ]  Page [ 3 v ] of 9  [ Next ]
    const navGroup = document.createElement('div');
    navGroup.className = 'cyber-pagination-nav';

    // Previous Button
    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'cyber-page-nav-btn prev-btn' + (currentPage <= 1 ? ' disabled' : '');
    prevBtn.innerHTML = `&#8249; Previous`;
    prevBtn.disabled = (currentPage <= 1);
    prevBtn.title = currentPage <= 1 ? 'First page' : 'Go to previous page';
    if (currentPage > 1) {
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (funcName === 'loadUsers') loadUsers(currentPage - 1);
            else if (funcName === 'loadLogs') loadLogs(currentPage - 1);
        });
    }
    navGroup.appendChild(prevBtn);

    // Page Selector: "Page [ 3 v ] of 9"
    const pageSelectBox = document.createElement('div');
    pageSelectBox.className = 'cyber-page-select-box';

    const pageLabel = document.createElement('span');
    pageLabel.className = 'cyber-page-label';
    pageLabel.textContent = 'Page';
    pageSelectBox.appendChild(pageLabel);

    const pageSelect = document.createElement('select');
    pageSelect.className = 'cyber-page-select';
    pageSelect.setAttribute('aria-label', 'Select page number');
    for (let p = 1; p <= totalPages; p++) {
        const opt = document.createElement('option');
        opt.value = p;
        opt.textContent = p;
        if (p === currentPage) opt.selected = true;
        pageSelect.appendChild(opt);
    }
    pageSelect.addEventListener('change', (e) => {
        const targetPage = parseInt(e.target.value) || 1;
        if (funcName === 'loadUsers') loadUsers(targetPage);
        else if (funcName === 'loadLogs') loadLogs(targetPage);
    });
    pageSelectBox.appendChild(pageSelect);

    const ofTotal = document.createElement('span');
    ofTotal.className = 'cyber-page-total';
    ofTotal.textContent = `of ${totalPages}`;
    pageSelectBox.appendChild(ofTotal);

    navGroup.appendChild(pageSelectBox);

    // Next Button
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'cyber-page-nav-btn next-btn' + (currentPage >= totalPages ? ' disabled' : '');
    nextBtn.innerHTML = `Next &#8250;`;
    nextBtn.disabled = (currentPage >= totalPages);
    nextBtn.title = currentPage >= totalPages ? 'Last page' : 'Go to next page';
    if (currentPage < totalPages) {
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (funcName === 'loadUsers') loadUsers(currentPage + 1);
            else if (funcName === 'loadLogs') loadLogs(currentPage + 1);
        });
    }
    navGroup.appendChild(nextBtn);

    wrapper.appendChild(navGroup);

    // 3. Limit Selector: "Show [ 10 v ] entries"
    const limitBox = document.createElement('div');
    limitBox.className = 'cyber-pagination-limit';

    const showLabel = document.createElement('span');
    showLabel.className = 'cyber-limit-label';
    showLabel.textContent = 'Show';
    limitBox.appendChild(showLabel);

    const limitSelect = document.createElement('select');
    limitSelect.id = `${elementId}_per_page`;
    limitSelect.className = 'cyber-limit-select';
    limitSelect.setAttribute('aria-label', 'Entries per page');
    [10, 25, 50, 100].forEach(val => {
        const opt = document.createElement('option');
        opt.value = val;
        opt.textContent = val;
        if (val === perPage) opt.selected = true;
        limitSelect.appendChild(opt);
    });
    limitSelect.addEventListener('change', (e) => {
        const newLim = parseInt(e.target.value) || 10;
        if (funcName === 'loadUsers') changeUsersLimit(newLim);
        else if (funcName === 'loadLogs') changeLogsLimit(newLim);
    });
    limitBox.appendChild(limitSelect);

    const entriesLabel = document.createElement('span');
    entriesLabel.className = 'cyber-limit-label';
    entriesLabel.textContent = 'entries';
    limitBox.appendChild(entriesLabel);

    wrapper.appendChild(limitBox);

    el.appendChild(wrapper);
}

// ====================================================
// Create New Account Modal Dialog (Cyberpunk Theme)
// ====================================================
let caModalKeyHandler = null;
let caLastActiveElement = null;

function openCreateAccountModal() {
    caLastActiveElement = document.activeElement;
    const modal = document.getElementById('createAccountModal');
    if (!modal) return;

    // Reset to Step 1
    goToCreateAccountStep(1);

    // Reset Form
    const form = document.getElementById('createAccountForm');
    if (form) form.reset();

    // Auto-generate ID Number (format YYYY-NNNN, e.g. "2026-0007")
    const currentYear = new Date().getFullYear();
    const randomSeq = String(Math.floor(Math.random() * 9000) + 1000).padStart(4, '0');
    const idEl = document.getElementById('caIdNumber');
    if (idEl) idEl.value = `${currentYear}-${randomSeq}`;

    // Reset Role to User (default "User")
    const roleEl = document.getElementById('caRoleSelect');
    if (roleEl) roleEl.value = 'user';

    // Auto-generate 8-character random password (1 uppercase, 1 special char, 1 number, 5 lowercase)
    const initialPwd = generateRandomPassword();
    const pwdEl = document.getElementById('caPassword');
    if (pwdEl) {
        pwdEl.value = initialPwd;
        pwdEl.type = 'password'; // Masked by default
    }
    const eyeIcon = document.getElementById('caPwEyeIcon');
    if (eyeIcon) {
        eyeIcon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        `;
    }

    // Initialize role view, handover controls, and module privilege selection
    validateRoleField();

    // Clear all errors
    clearAllFieldErrors();

    // Update password strength for auto-generated password
    updatePasswordStrength(initialPwd);

    // Show modal
    modal.classList.add('active');

    // Focus first input (Role dropdown)
    setTimeout(() => {
        if (roleEl) roleEl.focus();
    }, 100);

    // Setup Esc key & Focus Trap
    if (caModalKeyHandler) document.removeEventListener('keydown', caModalKeyHandler);
    caModalKeyHandler = function(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            closeCreateAccountModal();
        } else if (e.key === 'Tab') {
            trapModalFocus(e, modal);
        }
    };
    document.addEventListener('keydown', caModalKeyHandler);
}

// Wizard navigation between Step 1 (Credentials) and Step 2 (Privileges)
async function goToCreateAccountStep(step) {
    const step1Page = document.getElementById('caStep1Page');
    const step2Page = document.getElementById('caStep2Page');
    const badge1 = document.getElementById('caStepBadge1');
    const badge2 = document.getElementById('caStepBadge2');
    const modalSubtitle = document.querySelector('.ca-modal-subtitle');
    const modalBody = document.querySelector('.ca-modal-body');

    if (step === 2) {
        // Validate Step 1 fields before proceeding to privilege configuration
        let isValid = true;
        if (!validateRoleField()) isValid = false;
        const usernameValid = await validateUsernameField();
        if (!usernameValid) isValid = false;
        const emailValid = await validateEmailField();
        if (!emailValid) isValid = false;
        if (!validatePasswordField()) isValid = false;

        if (!isValid) return false;

        // Populate Step 2 user summary banner
        const uEl = document.getElementById('caUsername');
        const eEl = document.getElementById('caEmail');
        const rEl = document.getElementById('caRoleSelect');
        const role = rEl ? rEl.value : 'user';
        const username = uEl ? uEl.value.trim() : '';
        const email = eEl ? eEl.value.trim() : '';

        const uDisplay = document.getElementById('caStep2UsernameDisplay');
        if (uDisplay) uDisplay.textContent = username ? `@${username}` : 'New Account';

        const eDisplay = document.getElementById('caStep2EmailDisplay');
        if (eDisplay) eDisplay.textContent = email || '—';

        const rBadge = document.getElementById('caStep2RoleBadge');
        if (rBadge) {
            rBadge.textContent = role.toUpperCase().replace('_', ' ');
            rBadge.className = 'badge ' + (role === 'super_admin' ? 'badge-super-admin' : (role === 'admin' ? 'badge-admin' : 'badge-user'));
        }

        const avatar = document.getElementById('caStep2Avatar');
        if (avatar) {
            avatar.textContent = username ? username.charAt(0).toUpperCase() : '👤';
        }

        // Show Step 2, hide Step 1
        if (step1Page) step1Page.style.display = 'none';
        if (step2Page) step2Page.style.display = 'block';

        // Update step indicator badges
        if (badge1) {
            badge1.classList.remove('active');
            badge1.classList.add('completed');
            const num1 = document.getElementById('caStepNum1');
            if (num1) num1.innerHTML = '✓';
        }
        if (badge2) {
            badge2.classList.add('active');
            badge2.classList.remove('completed');
        }

        if (modalSubtitle) {
            modalSubtitle.textContent = 'Step 2 of 2: Configure module privileges and feature access.';
        }

        if (modalBody) modalBody.scrollTop = 0;
        return true;
    } else {
        // Return to Step 1
        if (step2Page) step2Page.style.display = 'none';
        if (step1Page) step1Page.style.display = 'block';

        if (badge1) {
            badge1.classList.add('active');
            badge1.classList.remove('completed');
            const num1 = document.getElementById('caStepNum1');
            if (num1) num1.innerHTML = '1';
        }
        if (badge2) {
            badge2.classList.remove('active');
            badge2.classList.remove('completed');
        }

        if (modalSubtitle) {
            modalSubtitle.textContent = 'Step 1 of 2: Complete the account credentials and profile details.';
        }

        if (modalBody) modalBody.scrollTop = 0;
        return true;
    }
}

// Backward-compatible alias
function openCreateAdminModal() {
    openCreateAccountModal();
}

function closeCreateAccountModal() {
    const modal = document.getElementById('createAccountModal');
    if (modal) modal.classList.remove('active');

    if (caModalKeyHandler) {
        document.removeEventListener('keydown', caModalKeyHandler);
        caModalKeyHandler = null;
    }

    if (caLastActiveElement && typeof caLastActiveElement.focus === 'function') {
        caLastActiveElement.focus();
    }
}

function handleCreateAccountBackdropClick(event) {
    if (event.target === document.getElementById('createAccountModal')) {
        closeCreateAccountModal();
    }
}

function trapModalFocus(e, modal) {
    const focusable = modal.querySelectorAll('button:not([disabled]), [href], input:not([disabled]):not([readonly]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (e.shiftKey) {
        if (document.activeElement === first) {
            e.preventDefault();
            last.focus();
        }
    } else {
        if (document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }
}

// Generates an 8-character random password:
// 1 uppercase, 1 special character, 1 number, 5 lowercase
function generateRandomPassword() {
    const uppers = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const lowers = 'abcdefghjkmnpqrstuvwxyz';
    const digits = '23456789';
    const specials = '!@#$%&*?';

    const chars = [
        uppers[Math.floor(Math.random() * uppers.length)],
        specials[Math.floor(Math.random() * specials.length)],
        digits[Math.floor(Math.random() * digits.length)]
    ];

    for (let i = 0; i < 5; i++) {
        chars.push(lowers[Math.floor(Math.random() * lowers.length)]);
    }

    // Fisher-Yates shuffle
    for (let i = chars.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [chars[i], chars[j]] = [chars[j], chars[i]];
    }

    return chars.join('');
}

function generateAndSetNewPassword() {
    const pwdEl = document.getElementById('caPassword');
    if (pwdEl) {
        const newPwd = generateRandomPassword();
        pwdEl.value = newPwd;
        updatePasswordStrength(newPwd);
        clearFieldError('caPassword');
        showToast('Generated new random password!', 'success');
    }
}

function resetDefaultPassword() {
    generateAndSetNewPassword();
}

function copyPasswordToClipboard(btn) {
    const pwdEl = document.getElementById('caPassword');
    if (!pwdEl || !pwdEl.value) return;

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(pwdEl.value).then(() => {
            showToast('Password copied to clipboard!', 'success');
        }).catch(() => {
            fallbackCopyPassword(pwdEl);
        });
    } else {
        fallbackCopyPassword(pwdEl);
    }
}

function fallbackCopyPassword(input) {
    input.select();
    input.setSelectionRange(0, 99999);
    try {
        document.execCommand('copy');
        showToast('Password copied to clipboard!', 'success');
    } catch (e) {
        showToast('Unable to copy password', 'error');
    }
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    const svg = btn.querySelector('svg');
    if (svg) {
        if (isPassword) {
            svg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>`;
        } else {
            svg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
        }
    }
}

function updatePasswordStrength(pwd) {
    const seg1 = document.getElementById('caSegment1');
    const seg2 = document.getElementById('caSegment2');
    const seg3 = document.getElementById('caSegment3');
    const seg4 = document.getElementById('caSegment4');
    const badge = document.getElementById('caStrengthBadge');
    if (!seg1 || !seg2 || !seg3 || !seg4 || !badge) return;

    // Reset segments
    [seg1, seg2, seg3, seg4].forEach(s => {
        s.className = 'ca-strength-segment';
    });

    if (!pwd || pwd.length === 0) {
        badge.textContent = 'Weak';
        badge.style.color = 'var(--ca-red, #ef4444)';
        return;
    }

    let score = 0;
    if (pwd.length >= 8) score++;
    if (pwd.length >= 10) score++;
    if (/[a-z]/.test(pwd)) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^a-zA-Z0-9]/.test(pwd)) score++;

    // Classify score into Weak, Fair, Good, Strong
    if (score <= 2) {
        seg1.classList.add('active-weak');
        badge.textContent = 'Weak';
        badge.style.color = 'var(--ca-red, #ef4444)';
    } else if (score === 3) {
        seg1.classList.add('active-fair');
        seg2.classList.add('active-fair');
        badge.textContent = 'Fair';
        badge.style.color = 'var(--ca-yellow, #f59e0b)';
    } else if (score === 4 || score === 5) {
        seg1.classList.add('active-good');
        seg2.classList.add('active-good');
        seg3.classList.add('active-good');
        badge.textContent = 'Good';
        badge.style.color = 'var(--ca-cyan, #06b6d4)';
    } else {
        seg1.classList.add('active-strong');
        seg2.classList.add('active-strong');
        seg3.classList.add('active-strong');
        seg4.classList.add('active-strong');
        badge.textContent = 'Strong';
        badge.style.color = 'var(--ca-green, #047857)';
    }
}

// Field error handlers
function showFieldError(fieldId, errorId, message) {
    const field = document.getElementById(fieldId);
    const err = document.getElementById(errorId);
    if (field) field.classList.add('ca-invalid');
    if (err) {
        const span = document.getElementById(errorId + 'Text') || err.querySelector('span:last-child') || err;
        if (span) span.textContent = message;
        err.classList.add('visible');
    }
}

function clearFieldError(fieldId, errorId) {
    const field = document.getElementById(fieldId);
    if (field) field.classList.remove('ca-invalid');
    if (!errorId) {
        errorId = fieldId + 'Error';
    }
    const err = document.getElementById(errorId);
    if (err) err.classList.remove('visible');
}

function clearAllFieldErrors() {
    ['caRoleSelect', 'caUsername', 'caEmail', 'caPassword'].forEach(id => {
        clearFieldError(id, id + 'Error');
    });
}

function validateRoleField() {
    const roleEl = document.getElementById('caRoleSelect');
    if (!roleEl || !roleEl.value) {
        showFieldError('caRoleSelect', 'caRoleError', 'Please select a role');
        return false;
    }
    clearFieldError('caRoleSelect', 'caRoleError');

    const role = roleEl.value;
    const handoverGroup = document.getElementById('caSuperadminHandoverGroup');
    const deactCb = document.getElementById('caDeactivateOnLogout');
    const submitText = document.getElementById('caSubmitText');
    const privNotice = document.getElementById('caPrivNotice');
    const privGroupAdmin = document.getElementById('caPrivGroupAdmin');
    const privGroupAudit = document.getElementById('caPrivGroupAudit');

    if (role === 'super_admin') {
        if (handoverGroup) handoverGroup.style.display = 'block';
        if (submitText) submitText.textContent = 'Create Superadmin';
        if (privNotice) {
            privNotice.style.display = 'block';
            privNotice.innerHTML = '👑 <strong>Super Admin Role:</strong> Holds root authority across all modules.';
        }
        if (privGroupAdmin) privGroupAdmin.style.display = 'block';
        if (privGroupAudit) privGroupAudit.style.display = 'block';
        setCaPrivPreset('all');
    } else if (role === 'admin') {
        if (handoverGroup) handoverGroup.style.display = 'none';
        if (deactCb) deactCb.checked = false;
        if (submitText) submitText.textContent = 'Create Account';
        if (privNotice) {
            privNotice.style.display = 'block';
            privNotice.innerHTML = '🛡️ <strong>Administrator Role:</strong> Assign authorized administrative and operational module privileges.';
        }
        if (privGroupAdmin) privGroupAdmin.style.display = 'block';
        if (privGroupAudit) privGroupAudit.style.display = 'block';
        setCaPrivPreset('recommended');
    } else {
        if (handoverGroup) handoverGroup.style.display = 'none';
        if (deactCb) deactCb.checked = false;
        if (submitText) submitText.textContent = 'Create Account';
        if (privNotice) {
            privNotice.style.display = 'block';
            privNotice.innerHTML = '👤 <strong>Standard User Role:</strong> Configured for Personal &amp; Account features. Administrative privileges are disabled.';
        }
        if (privGroupAdmin) privGroupAdmin.style.display = 'none';
        if (privGroupAudit) privGroupAudit.style.display = 'none';
        setCaPrivPreset('user_defaults');
    }

    return true;
}

function setCaPrivPreset(preset) {
    const roleEl = document.getElementById('caRoleSelect');
    const role = roleEl ? roleEl.value : 'user';
    const cbs = document.querySelectorAll('.ca-privilege-cb');

    if (preset === 'clear') {
        cbs.forEach(cb => { cb.checked = false; });
        return;
    }

    if (preset === 'all') {
        cbs.forEach(cb => { cb.checked = true; });
        return;
    }

    // Recommended or default
    if (role === 'user' || preset === 'user_defaults') {
        cbs.forEach(cb => {
            cb.checked = (cb.value === 'profile_management' || cb.value === 'change_password');
        });
    } else {
        const adminDefaults = [
            'profile_management',
            'change_password',
            'view_accounts',
            'create_accounts',
            'edit_manage_accounts',
            'block_unblock_users',
            'registration_approval',
            'account_deletion_requests',
            'view_activity_logs'
        ];
        cbs.forEach(cb => {
            cb.checked = adminDefaults.includes(cb.value);
        });
    }
}

function toggleCaPasswordVisibility() {
    const pEl = document.getElementById('caPassword');
    const icon = document.getElementById('caPwEyeIcon');
    if (!pEl) return;

    if (pEl.type === 'password') {
        pEl.type = 'text';
        if (icon) {
            icon.innerHTML = `
                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                <line x1="2" y1="2" x2="22" y2="22"></line>
            `;
        }
    } else {
        pEl.type = 'password';
        if (icon) {
            icon.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;
        }
    }
}

async function validateUsernameField() {
    const uEl = document.getElementById('caUsername');
    if (!uEl) return true;
    const val = uEl.value.trim();

    if (!val) {
        showFieldError('caUsername', 'caUsernameError', 'Username is required');
        return false;
    }
    if (val.length < 3 || val.length > 50) {
        showFieldError('caUsername', 'caUsernameError', 'Username must be 3 to 50 characters');
        return false;
    }
    if (!/^[a-zA-Z0-9._-]+$/.test(val)) {
        showFieldError('caUsername', 'caUsernameError', 'Letters, numbers, dots, dashes, underscores only');
        return false;
    }

    // Check uniqueness via server
    try {
        const res = await apiCall('check_username_email', { username: val });
        if (res.success && res.data && res.data.username_taken) {
            showFieldError('caUsername', 'caUsernameError', 'Username is already taken');
            return false;
        }
    } catch (e) {
        // silent check pass
    }

    clearFieldError('caUsername', 'caUsernameError');
    return true;
}

async function validateEmailField() {
    const eEl = document.getElementById('caEmail');
    if (!eEl) return true;
    const val = eEl.value.trim();

    if (!val) {
        showFieldError('caEmail', 'caEmailError', 'Email address is required');
        return false;
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(val)) {
        showFieldError('caEmail', 'caEmailError', 'Please enter a valid email address');
        return false;
    }

    // Check uniqueness via server
    try {
        const res = await apiCall('check_username_email', { email: val });
        if (res.success && res.data && res.data.email_taken) {
            showFieldError('caEmail', 'caEmailError', 'Email address is already in use');
            return false;
        }
    } catch (e) {
        // silent check pass
    }

    clearFieldError('caEmail', 'caEmailError');
    return true;
}

function validatePasswordField() {
    const pEl = document.getElementById('caPassword');
    if (!pEl) return true;
    const val = pEl.value;
    if (!val || !val.trim()) {
        showFieldError('caPassword', 'caPasswordError', 'Password is required and cannot be whitespace only');
        return false;
    }
    if (val.length < 8) {
        showFieldError('caPassword', 'caPasswordError', 'Password must be at least 8 characters');
        return false;
    }
    if (!/[A-Z]/.test(val)) {
        showFieldError('caPassword', 'caPasswordError', 'Password must contain at least 1 uppercase letter');
        return false;
    }
    if (!/[a-z]/.test(val)) {
        showFieldError('caPassword', 'caPasswordError', 'Password must contain at least 1 lowercase letter');
        return false;
    }
    if (!/[0-9]/.test(val)) {
        showFieldError('caPassword', 'caPasswordError', 'Password must contain at least 1 number');
        return false;
    }
    if (!/[^a-zA-Z0-9]/.test(val)) {
        showFieldError('caPassword', 'caPasswordError', 'Password must contain at least 1 special character');
        return false;
    }
    clearFieldError('caPassword', 'caPasswordError');
    return true;
}

function openCreateSuperadminConfirmModal() {
    const modal = document.getElementById('createSuperadminConfirmModal');
    if (!modal) return;
    const deactCb = document.getElementById('caDeactivateOnLogout');
    const isDeact = deactCb && deactCb.checked;
    const statusBox = document.getElementById('caConfirmHandoverStatusBox');

    if (statusBox) {
        if (isDeact) {
            statusBox.style.border = '1px solid var(--danger-border, #fca5a5)';
            statusBox.style.background = 'var(--danger-light, rgba(239, 68, 68, 0.08))';
            statusBox.style.color = 'var(--text-primary)';
            statusBox.innerHTML = `
                <div style="display:flex; align-items:flex-start; gap:0.65rem;">
                    <span style="font-size:1.25rem; line-height:1;">⚠️</span>
                    <div>
                        <strong style="color:var(--danger, #dc2626); font-size:0.88rem; display:block; margin-bottom:0.25rem;">
                            CRITICAL CONSEQUENCE: &lsquo;Deactivate My Account After Logout&rsquo; IS ENABLED
                        </strong>
                        <span style="font-size:0.84rem; line-height:1.5; color:var(--text-primary); display:block;">
                            You have explicitly chosen to deactivate your account upon logout. When you log out after creating this new Superadmin, your account will immediately become <strong>Inactive</strong>. You will lose all access and will not be able to log in again until the new Superadmin reactivates your account.
                        </span>
                    </div>
                </div>
            `;
        } else {
            statusBox.style.border = '1px solid var(--primary-border, #c7d2fe)';
            statusBox.style.background = 'var(--primary-light, rgba(79, 70, 229, 0.06))';
            statusBox.style.color = 'var(--text-primary)';
            statusBox.innerHTML = `
                <div style="display:flex; align-items:flex-start; gap:0.65rem;">
                    <span style="font-size:1.25rem; line-height:1;">ℹ️</span>
                    <div>
                        <strong class="create-sa-notice-title" style="color:var(--primary, #4f46e5); font-size:0.88rem; display:block; margin-bottom:0.25rem;">
                            &lsquo;Deactivate My Account After Logout&rsquo; IS DISABLED
                        </strong>
                        <span style="font-size:0.84rem; line-height:1.5; color:var(--text-primary); display:block;">
                            Your current Superadmin account will remain <strong>Active</strong> after logout. Creating this new Superadmin will not automatically deactivate your account.
                        </span>
                    </div>
                </div>
            `;
        }
    }

    modal.style.display = 'flex';
}

function closeCreateSuperadminConfirmModal() {
    const modal = document.getElementById('createSuperadminConfirmModal');
    if (modal) modal.style.display = 'none';
}

async function proceedCreateSuperadminWithPassword() {
    closeCreateSuperadminConfirmModal();
    await executeAccountCreation(true);
}

async function submitCreateAccountForm(e) {
    if (e && typeof e.preventDefault === 'function') {
        e.preventDefault();
    }

    // If currently on Step 1, advance to Step 2 instead of executing final creation
    const step1Page = document.getElementById('caStep1Page');
    if (step1Page && step1Page.style.display !== 'none') {
        await goToCreateAccountStep(2);
        return;
    }

    // Run validations
    let isValid = true;
    if (!validateRoleField()) isValid = false;
    const usernameValid = await validateUsernameField();
    if (!usernameValid) isValid = false;
    const emailValid = await validateEmailField();
    if (!emailValid) isValid = false;
    if (!validatePasswordField()) isValid = false;

    if (!isValid) return;

    const roleEl = document.getElementById('caRoleSelect');
    const role = roleEl ? roleEl.value : 'user';

    if (role === 'super_admin') {
        // Mandatory confirmation warning modal before completing the operation
        openCreateSuperadminConfirmModal();
        return;
    }

    // For non-superadmin roles, proceed to execution
    await executeAccountCreation(false);
}

async function executeAccountCreation(isHandoverConfirmed = false) {
    const roleEl = document.getElementById('caRoleSelect');
    const idEl = document.getElementById('caIdNumber');
    const uEl = document.getElementById('caUsername');
    const eEl = document.getElementById('caEmail');
    const pEl = document.getElementById('caPassword');
    const deactEl = document.getElementById('caDeactivateOnLogout');
    const submitBtn = document.getElementById('caSubmitBtn');
    const spinner = document.getElementById('caSubmitSpinner');
    const btnText = document.getElementById('caSubmitText');

    const role = roleEl ? roleEl.value : 'user';
    const idNumber = idEl ? idEl.value : '';
    const username = uEl ? uEl.value.trim() : '';
    const email = eEl ? eEl.value.trim() : '';
    const password = pEl ? pEl.value : '';
    const isDeact = deactEl && deactEl.checked;

    let pwd = '';
    if (userRole === 'super_admin' || userRole === 'admin') {
        try {
            pwd = await promptSaPassword();
        } catch (err) {
            return;
        }
    }

    // UI Loading state
    if (submitBtn) submitBtn.disabled = true;
    if (spinner) spinner.style.display = 'inline-block';
    if (btnText) btnText.textContent = (role === 'super_admin') ? 'Creating Superadmin...' : 'Creating Account...';

    try {
        const selectedPrivs = Array.from(document.querySelectorAll('.ca-privilege-cb:checked')).map(cb => cb.value);

        const payload = {
            account_role: role,
            id_number: idNumber,
            username: username,
            email: email,
            password: password,
            confirm_password: password,
            privileges: JSON.stringify(selectedPrivs),
            deactivate_on_logout: isDeact ? '1' : '0',
            handover_confirmed: isHandoverConfirmed ? '1' : '0',
            action_confirm_password: pwd
        };

        const res = await apiCall('create_account', payload);

        if (res.success) {
            closeCreateAccountModal();
            const emailSent = res.data && res.data.email_sent;
            const emailError = res.data && res.data.email_error;
            const emailNote = emailSent ? ` Login credentials sent to ${email}.` : (emailError ? ` (Email issue: ${emailError})` : '');

            if (role === 'super_admin') {
                if (isDeact) {
                    showToast(`Superadmin @${username} created.${emailNote} Your account will become Inactive upon logout.`, 'warning');
                } else {
                    showToast(`Superadmin @${username} created.${emailNote} Your account remains Active after logout.`, 'success');
                }
            } else {
                if (emailSent) {
                    showToast(`Account created for @${username}! Login credentials sent to ${email}`, 'success');
                } else if (emailError) {
                    showToast(`Account created for @${username}, but email failed: ${emailError}`, 'warning');
                } else {
                    showToast(`Account created for @${username} (${idNumber})`, 'success');
                }
            }
            lastUsersPayloadHash = '';
            loadUsers(usersPage, false);
        } else {
            showToast(res.message || 'Failed to create account.', 'error');
            if (res.message && res.message.toLowerCase().includes('username')) {
                showFieldError('caUsername', 'caUsernameError', res.message);
            } else if (res.message && res.message.toLowerCase().includes('email')) {
                showFieldError('caEmail', 'caEmailError', res.message);
            }
        }
    } catch (err) {
        console.error('Account creation error:', err);
        showToast('A network error occurred while creating account.', 'error');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
        if (spinner) spinner.style.display = 'none';
        if (btnText) btnText.textContent = (role === 'super_admin') ? 'Create Superadmin' : 'Create Account';
    }
}

// Toast Notifications
function showToast(message, type = 'success') {
    let container = document.getElementById('caToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'caToastContainer';
        container.style.cssText = 'position:fixed; bottom:20px; right:20px; z-index:999999; display:flex; flex-direction:column; gap:10px; pointer-events:none;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `ca-toast ca-toast-${type}`;
    toast.style.cssText = 'pointer-events:auto; display:flex; align-items:center; gap:8px; padding:12px 18px; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,0.18); font-size:0.88rem; font-weight:600; font-family:"Inter",sans-serif; transition:all 0.3s ease; animation:toastIn 0.3s ease;';
    if (type === 'success') {
        toast.style.background = 'var(--bg-surface, #ffffff)';
        toast.style.color = 'var(--success, #10b981)';
        toast.style.border = '1px solid var(--success-border, rgba(16, 185, 129, 0.35))';
        toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.25), 0 0 16px rgba(16, 185, 129, 0.2)';
    } else if (type === 'warning') {
        toast.style.background = 'var(--bg-surface, #ffffff)';
        toast.style.color = 'var(--warning, #f59e0b)';
        toast.style.border = '1px solid var(--warning-border, rgba(245, 158, 11, 0.35))';
        toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.25), 0 0 16px rgba(245, 158, 11, 0.2)';
    } else if (type === 'info') {
        toast.style.background = 'var(--bg-surface, #ffffff)';
        toast.style.color = 'var(--primary, #3b82f6)';
        toast.style.border = '1px solid var(--primary-border, rgba(59, 130, 246, 0.35))';
        toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.25), 0 0 16px rgba(59, 130, 246, 0.2)';
    } else {
        toast.style.background = 'var(--bg-surface, #ffffff)';
        toast.style.color = 'var(--danger, #ef4444)';
        toast.style.border = '1px solid var(--danger-border, rgba(239, 68, 68, 0.35))';
        toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.25), 0 0 16px rgba(239, 68, 68, 0.2)';
    }

    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    const icon = icons[type] || 'ℹ';
    toast.innerHTML = `<span style="font-size:1.05rem; font-weight:bold; display:flex; align-items:center;">${icon}</span> <span>${escapeHtml(message)}</span>`;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        setTimeout(() => toast.remove(), 350);
    }, 4000);
}
window.showToast = showToast;

// ----------------------------------------------------
// Privilege Management: Modal Controls (Table Action)
// ----------------------------------------------------
const RECOMMENDED_PRIVILEGES = [
    'change_password',
    'profile_management',
    'view_accounts',
    'edit_manage_accounts',
    'block_unblock_users',
    'registration_approval',
    'account_deletion_requests'
];

async function openManagePrivilegesModal(userId, username, role, fullName, idNumber) {
    const modal = document.getElementById('managePrivilegesModal');
    if (!modal) return;

    const safeRole = (role || 'user').toLowerCase();
    modal.dataset.targetRole = safeRole;

    const idInput = document.getElementById('modalPrivUserId');
    if (idInput) idInput.value = userId;

    const displayName = (fullName && fullName.trim()) ? fullName.trim() : username;
    const nameEl = document.getElementById('modalPrivFullName');
    if (nameEl) nameEl.textContent = displayName;

    const handleEl = document.getElementById('modalPrivUsername');
    if (handleEl) handleEl.textContent = `@${username}`;

    const idnoEl = document.getElementById('modalPrivIdno');
    if (idnoEl) idnoEl.textContent = `ID: ${idNumber || '----'}`;

    const avatarEl = document.getElementById('modalPrivAvatar');
    if (avatarEl) avatarEl.textContent = (displayName || 'U').charAt(0).toUpperCase();

    const roleBadgeEl = document.getElementById('modalPrivRoleBadge');
    if (roleBadgeEl) {
        roleBadgeEl.textContent = safeRole.replace('_', ' ').toUpperCase();
        roleBadgeEl.className = `priv-role-badge ${safeRole}`;
    }

    const titleEl = document.getElementById('modalPrivTitle');
    if (titleEl) {
        titleEl.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> <span>Manage Privileges: ${escapeHtml(displayName)}</span>`;
    }

    const subEl = document.getElementById('modalPrivSubtitle');
    if (subEl) {
        subEl.textContent = `Role: ${safeRole.toUpperCase()} | Configure individual module permissions`;
    }

    // Role-specific layout rules
    const isUser = safeRole === 'user';
    const mCard2 = document.getElementById('modalMgmtCard2');
    const mCard3 = document.getElementById('modalMgmtCard3');
    if (mCard2) mCard2.style.display = isUser ? 'none' : 'block';
    if (mCard3) mCard3.style.display = isUser ? 'none' : 'block';

    const roleNotice = document.getElementById('modalPrivUserRoleNotice');
    if (roleNotice) roleNotice.style.display = isUser ? 'block' : 'none';

    // Clear alert banner
    const alertBox = document.getElementById('modalPrivAlert');
    if (alertBox) {
        alertBox.style.display = 'none';
        alertBox.textContent = '';
        alertBox.className = '';
    }

    // Reset all checkboxes first
    document.querySelectorAll('.modal-priv-cb').forEach(cb => cb.checked = false);
    updateModalPrivCount();

    // Show modal
    modal.style.display = 'flex';

    // Fetch user privileges from backend
    try {
        const res = await apiCall('get_user_privileges', { target_id: userId });
        if (res.success && res.data) {
            let userPrivs = res.data.privileges || [];
            if (typeof userPrivs === 'string') {
                try { userPrivs = JSON.parse(userPrivs); } catch(e) {}
            }
            if (typeof userPrivs === 'string') {
                try { userPrivs = JSON.parse(userPrivs); } catch(e) {}
            }
            if (!Array.isArray(userPrivs)) userPrivs = [];
            
            document.querySelectorAll('.modal-priv-cb').forEach(cb => {
                cb.checked = userPrivs.includes(cb.value);
            });
            updateModalPrivCount();

            // Refresh user details if backend returned more specific info
            if (res.data.first_name || res.data.last_name) {
                const refreshedName = `${res.data.first_name || ''} ${res.data.last_name || ''}`.trim();
                if (refreshedName && nameEl) nameEl.textContent = refreshedName;
            }
            if (res.data.id_number && idnoEl) {
                idnoEl.textContent = `ID: ${res.data.id_number}`;
            }
        } else {
            if (alertBox) {
                alertBox.textContent = res.message || 'Unable to retrieve current privileges.';
                alertBox.style.display = 'block';
                alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
                alertBox.style.color = 'var(--danger, #ef4444)';
                alertBox.style.border = '1px solid var(--danger, #ef4444)';
            }
        }
    } catch (e) {
        console.error('Error fetching privileges:', e);
        if (alertBox) {
            alertBox.textContent = 'Network error fetching privilege permissions.';
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
            alertBox.style.color = 'var(--danger, #ef4444)';
            alertBox.style.border = '1px solid var(--danger, #ef4444)';
        }
    }
}

function closeManagePrivilegesModal() {
    const modal = document.getElementById('managePrivilegesModal');
    if (modal) modal.style.display = 'none';
}

function updateModalPrivCount() {
    const checked = document.querySelectorAll('.modal-priv-cb:checked');
    const badge = document.getElementById('modalPrivCountBadge');
    if (badge) badge.textContent = `${checked.length} Permitted Function${checked.length === 1 ? '' : 's'}`;
}

function setModalPrivPresets(type) {
    const modal = document.getElementById('managePrivilegesModal');
    const targetRole = modal ? (modal.dataset.targetRole || 'user') : 'user';
    const isUser = targetRole === 'user';

    document.querySelectorAll('.modal-priv-cb').forEach(cb => {
        if (type === 'clear') {
            cb.checked = false;
        } else if (type === 'all') {
            if (isUser) {
                // Users only get Module 1
                cb.checked = ['profile_management', 'change_password'].includes(cb.value);
            } else {
                cb.checked = true;
            }
        } else if (type === 'recommended') {
            if (isUser) {
                cb.checked = ['profile_management', 'change_password'].includes(cb.value);
            } else {
                cb.checked = RECOMMENDED_PRIVILEGES.includes(cb.value);
            }
        }
    });
    updateModalPrivCount();
}

async function saveModalPrivileges() {
    const userId = document.getElementById('modalPrivUserId').value;
    const saveBtn = document.getElementById('saveModalPrivBtn');
    const alertBox = document.getElementById('modalPrivAlert');
    if (!userId) return;

    const selected = [];
    document.querySelectorAll('.modal-priv-cb:checked').forEach(cb => selected.push(cb.value));

    let pwd = '';
    if (userRole === 'super_admin' || userRole === 'admin') {
        try { 
            pwd = await promptSaPassword(); 
        } catch (e) { 
            return; 
        }
    }

    const origText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span>⏳</span> Saving Privileges...';

    if (alertBox) {
        alertBox.style.display = 'none';
        alertBox.textContent = '';
    }

    try {
        const res = await apiCall('update_user_privileges', {
            target_id: userId,
            privileges: JSON.stringify(selected),
            action_confirm_password: pwd
        });

        if (res.success) {
            closeManagePrivilegesModal();
            showToast(res.message || 'Privileges updated successfully!', 'success');
            loadUsers(usersPage);
        } else {
            if (alertBox) {
                alertBox.textContent = res.message || 'Failed to update privileges.';
                alertBox.style.display = 'block';
                alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
                alertBox.style.color = 'var(--danger, #ef4444)';
                alertBox.style.border = '1px solid var(--danger, #ef4444)';
            } else {
                await customAlert(res.message || 'Failed to update privileges.');
            }
        }
    } catch (e) {
        console.error('Error saving privileges:', e);
        if (alertBox) {
            alertBox.textContent = 'A network error occurred while saving privileges.';
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
            alertBox.style.color = 'var(--danger, #ef4444)';
            alertBox.style.border = '1px solid var(--danger, #ef4444)';
        } else {
            await customAlert('A network error occurred while saving privileges.');
        }
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = origText;
    }
}

// ----------------------------------------------------
// Compatibility Stubs for Obsolete Dedicated Tab
// ----------------------------------------------------
let privMgmtUsersCache = [];
async function initPrivilegeMgmtTab() {}
async function loadPrivMgmtUsers() {}
function renderPrivMgmtUserList() {}
function filterPrivMgmtUsers() {}
const debouncedFilterPrivMgmtUsers = () => {};
async function onPrivMgmtUserChange() {}
function updatePrivMgmtTabCount() {}
function setPrivMgmtPresets() {}
async function savePrivMgmtTabPrivileges() {}

// =============================================================================
// SUPERADMIN SUCCESSION & ACCOUNT STATUS MANAGEMENT SYSTEM LOGIC
// =============================================================================

function resetUserFilters() {
    const search = document.getElementById('filterEmpId');
    const role = document.getElementById('filterUserRole');
    const status = document.getElementById('filterUserStatus');
    if (search) search.value = '';
    userInteractedWithSearch = false;
    if (role) role.value = '';
    if (status) status.value = '';
    loadUsers(1);
}

// ── Superadmin Succession & Role Transfer ──────────────────────────────────
async function openSuperadminSuccessionModal(preselectedId = null) {
    const modal = document.getElementById('superadminTransferModal');
    if (!modal) return;

    const select = document.getElementById('succSuccessorSelect');
    const hint = document.getElementById('succSuccessorHint');
    const alertBox = document.getElementById('succModalAlert');
    const form = document.getElementById('superadminTransferForm');
    const submitBtn = document.getElementById('succSubmitBtn');
    const btnText = document.getElementById('succSubmitBtnText');

    if (alertBox) { alertBox.style.display = 'none'; alertBox.textContent = ''; }
    if (form) form.reset();
    const checkbox = document.getElementById('succConfirmCheckbox');
    if (checkbox) checkbox.checked = false;
    if (submitBtn) submitBtn.disabled = true;
    if (btnText) btnText.textContent = 'Execute Succession & Step Down';

    select.innerHTML = '<option value="">Loading eligible administrators...</option>';
    select.disabled = true;
    modal.style.display = 'flex';

    try {
        const res = await apiCall('get_eligible_successors');
        
        if (!res || !res.success) {
            const errMsg = (res && res.message) ? res.message : 'Failed to retrieve eligible administrators.';
            select.innerHTML = '<option value="">Error loading eligible administrators</option>';
            select.disabled = true;
            if (hint) hint.innerHTML = `<span style="color:var(--danger)">⚠️ ${escapeHtml(errMsg)}</span>`;
            if (alertBox) {
                alertBox.className = 'modal-alert-banner alert-error';
                alertBox.textContent = errMsg;
                alertBox.style.display = 'block';
            }
            return;
        }

        select.innerHTML = '<option value="">-- Select an eligible Active Administrator --</option>';
        
        let successors = [];
        if (res.data) {
            if (Array.isArray(res.data.successors)) {
                successors = res.data.successors;
            } else if (Array.isArray(res.data)) {
                successors = res.data;
            }
        }

        if (successors.length > 0) {
            successors.forEach(admin => {
                const opt = document.createElement('option');
                opt.value = admin.id;
                const name = ((admin.first_name || '') + ' ' + (admin.last_name || '')).trim() || admin.username;
                const identifier = admin.email || admin.id_number || 'Administrator';
                opt.textContent = `${name} (@${admin.username}) - ${identifier}`;
                opt.dataset.username = admin.username;
                opt.dataset.name = name;
                if (preselectedId && String(admin.id) === String(preselectedId)) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
            select.disabled = false;
            if (hint) hint.textContent = `${successors.length} eligible Active Administrator(s) available for succession.`;
            onSuccessorSelected();
        } else {
            select.innerHTML = '<option value="">No eligible Active Administrators found</option>';
            select.disabled = true;
            if (hint) hint.innerHTML = '<span style="color:var(--danger)">⚠️ No eligible Active Administrators found. You must promote or approve an active Administrator before stepping down.</span>';
        }
    } catch (err) {
        console.error('Error fetching eligible successors:', err);
        select.innerHTML = '<option value="">Error loading eligible administrators</option>';
        if (hint) hint.textContent = 'Network error fetching eligible administrators.';
        if (alertBox) {
            alertBox.className = 'modal-alert-banner alert-error';
            alertBox.textContent = 'Network or communication error occurred while fetching eligible administrators.';
            alertBox.style.display = 'block';
        }
    }
}

function closeSuperadminSuccessionModal() {
    const modal = document.getElementById('superadminTransferModal');
    if (modal) modal.style.display = 'none';
}

function onSuccessorSelected() {
    const select = document.getElementById('succSuccessorSelect');
    const hint = document.getElementById('succSuccessorHint');
    if (!select || !hint) return;
    const selectedOpt = select.options[select.selectedIndex];
    if (selectedOpt && selectedOpt.value) {
        hint.innerHTML = `Selected Successor: <strong>${escapeHtml(selectedOpt.dataset.name || selectedOpt.text)}</strong>. Master Superadmin privileges will be permanently transferred to this account.`;
    }
}

function onSuccConfirmCheckboxChange() {
    const checkbox = document.getElementById('succConfirmCheckbox');
    const submitBtn = document.getElementById('succSubmitBtn');
    if (checkbox && submitBtn) {
        submitBtn.disabled = !checkbox.checked;
    }
}

async function submitSuperadminSuccession(event) {
    if (event) event.preventDefault();
    const select = document.getElementById('succSuccessorSelect');
    const statusSelect = document.getElementById('succOutgoingStatus');
    const reasonInput = document.getElementById('succReasonInput');
    const passwordInput = document.getElementById('succPasswordInput');
    const checkbox = document.getElementById('succConfirmCheckbox');
    const alertBox = document.getElementById('succModalAlert');
    const submitBtn = document.getElementById('succSubmitBtn');
    const btnText = document.getElementById('succSubmitBtnText');

    if (!checkbox || !checkbox.checked) {
        if (submitBtn) submitBtn.disabled = true;
        alertBox.className = 'modal-alert-banner alert-error';
        alertBox.textContent = 'You must explicitly acknowledge the transfer by checking the confirmation box.';
        alertBox.style.display = 'block';
        return;
    }

    if (!select.value) {
        alertBox.className = 'modal-alert-banner alert-error';
        alertBox.textContent = 'Please select an eligible Administrator to succeed as Superadmin.';
        alertBox.style.display = 'block';
        return;
    }

    if (!reasonInput.value.trim() || reasonInput.value.trim().length < 5) {
        alertBox.className = 'modal-alert-banner alert-error';
        alertBox.textContent = 'Please provide a clear formal reason for stepping down / transferring rights (min 5 characters).';
        alertBox.style.display = 'block';
        return;
    }

    if (!passwordInput.value) {
        alertBox.className = 'modal-alert-banner alert-error';
        alertBox.textContent = 'Please confirm your current Superadmin password to authorize the succession.';
        alertBox.style.display = 'block';
        return;
    }

    // Double confirmation dialog
    const successorName = select.options[select.selectedIndex]?.text || 'selected administrator';
    const confirmed = await customConfirm(
        `FINAL CONFIRMATION:\n\nAre you absolutely certain you want to step down as Superadmin and transfer master authority to:\n${successorName}?\n\nThis action cannot be undone. Your active session will be terminated immediately.`
    );
    if (!confirmed) return;

    if (submitBtn) submitBtn.disabled = true;
    if (btnText) btnText.textContent = 'Executing Succession...';
    if (alertBox) { alertBox.style.display = 'none'; }

    try {
        const res = await apiCall('transfer_superadmin_role', {
            successor_id: select.value,
            outgoing_status: statusSelect.value,
            reason: reasonInput.value.trim(),
            action_confirm_password: passwordInput.value,
            confirm_transfer: 'true'
        });

        if (res.success) {
            alertBox.className = 'modal-alert-banner alert-success';
            alertBox.textContent = '✓ ' + (res.message || 'Superadmin succession executed successfully! Redirecting...');
            alertBox.style.display = 'block';

            setTimeout(() => {
                closeSuperadminSuccessionModal();
                window.location.href = '../php/logout.php?msg=succession_completed';
            }, 1800);
        } else {
            alertBox.className = 'modal-alert-banner alert-error';
            alertBox.textContent = '✗ ' + (res.message || 'Transfer failed. Check credentials and successor eligibility.');
            alertBox.style.display = 'block';
            if (submitBtn) submitBtn.disabled = !checkbox.checked;
            if (btnText) btnText.textContent = 'Execute Succession & Step Down';
        }
    } catch (err) {
        alertBox.className = 'modal-alert-banner alert-error';
        alertBox.textContent = '✗ Server or network error executing succession.';
        alertBox.style.display = 'block';
        if (submitBtn) submitBtn.disabled = !checkbox.checked;
        if (btnText) btnText.textContent = 'Execute Succession & Step Down';
    }
}

// ── Account Dossier & Details ───────────────────────────────────────────────
async function openUserDetailsModal(userId) {
    const modal = document.getElementById('viewUserDetailsModal');
    const content = document.getElementById('userDetailsContent');
    if (!modal || !content) return;

    content.innerHTML = '<div style="text-align:center; padding:3rem;"><div class="table-loading-spinner"></div><div style="margin-top:0.8rem; color:var(--text-muted);">Retrieving comprehensive account dossier...</div></div>';
    modal.style.display = 'flex';

    try {
        const res = await apiCall('get_user_details', { user_id: userId, target_id: userId });
        if (res && res.success && res.data) {
            const u = res.data.user || {};
            const succHistory = res.data.succession_history || [];
            const fullName = ((u.first_name || '') + ' ' + (u.last_name || '')).trim() || u.username || 'Unknown User';
            const rClean = (u.role || 'user').toLowerCase();

            const isCurrentSa = Boolean(u.is_current_superadmin ?? res.data.is_current_superadmin);
            const isFormerSa = Boolean(u.is_former_superadmin == 1 || res.data.is_former_superadmin);
            const isActiveSession = Boolean(u.is_active_session ?? res.data.is_active_session);
            const isEligibleSuccessor = Boolean(u.is_eligible_successor ?? res.data.is_eligible_successor);
            const displayStatus = u.display_status || res.data.display_status || (u.status ? u.status.charAt(0).toUpperCase() + u.status.slice(1) : 'Pending');

            let roleBadgeHtml = `<span class="priv-role-badge ${rClean}">${rClean.toUpperCase()}</span>`;
            if (isCurrentSa) {
                roleBadgeHtml = `<span class="priv-role-badge role-current-superadmin"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> CURRENT SUPERADMIN</span>`;
            } else if (isFormerSa) {
                roleBadgeHtml += ` <span class="priv-role-badge role-former-superadmin">FORMER SUPERADMIN</span>`;
            }

            let statusBadgeHtml = '';
            const dsLower = (displayStatus || '').toLowerCase();
            if (dsLower === 'active') {
                statusBadgeHtml = '<span class="user-status-badge status-active">Active</span>';
            } else if (dsLower === 'inactive' || dsLower === 'suspended' || dsLower === 'blocked') {
                statusBadgeHtml = '<span class="user-status-badge status-inactive">Inactive</span>';
            } else {
                statusBadgeHtml = `<span class="user-status-badge status-pending">${escapeHtml(displayStatus || 'Pending')}</span>`;
            }

            let succSectionHtml = '';
            if (succHistory.length > 0) {
                succSectionHtml = `
                    <div class="dossier-section">
                        <div class="dossier-section-title">👑 Superadmin Succession Records</div>
                        <div class="dossier-succ-list">
                            ${succHistory.map(s => {
                                const isOutgoing = (s.outgoing_superadmin_id == u.id || s.outgoing_user_id == u.id);
                                return `
                                <div class="dossier-succ-item">
                                    <div class="dossier-succ-header">
                                        <span class="dossier-succ-type">${isOutgoing ? 'Stepped Down As Superadmin' : 'Appointed As Superadmin'}</span>
                                        <span class="dossier-succ-date">${escapeHtml(s.transfer_date || s.created_at)}</span>
                                    </div>
                                    <div class="dossier-succ-body">
                                        <div><strong>Outgoing:</strong> @${escapeHtml(s.outgoing_username || 'Unknown')} ➔ <strong>Incoming:</strong> @${escapeHtml(s.incoming_username || 'Unknown')}</div>
                                        <div style="margin-top:0.3rem;"><strong>Reason:</strong> ${escapeHtml(s.reason || 'No statement recorded')}</div>
                                    </div>
                                </div>
                            `;
                            }).join('')}
                        </div>
                    </div>
                `;
            }

            const privList = res.data.privileges_list || [];
            const isTargetAdmin = rClean === 'admin';
            const isSuperAdminUser = (typeof userRole !== 'undefined' && userRole === 'super_admin');
            const isAdminUser = (typeof userRole !== 'undefined' && userRole === 'admin');
            const currentPrivs = (typeof userPrivileges !== 'undefined' && Array.isArray(userPrivileges)) ? userPrivileges : [];
            const canManageTargetPriv = isSuperAdminUser ? !isCurrentSa : (isAdminUser && currentPrivs.includes('edit_manage_accounts') && !isTargetAdmin && !isCurrentSa);

            let privSectionHtml = `
                <div class="dossier-section" style="margin-top:1.2rem; padding:1rem; border:1px solid var(--border-color); border-radius:12px; background:var(--bg-surface-subtle);">
                    <div class="dossier-section-title" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.6rem;">
                        <span style="font-weight:700; color:var(--text-primary);">🛡️ Assigned Module Privileges</span>
                        ${canManageTargetPriv ? `
                            <button type="button" class="btn-small btn-secondary" onclick="closeUserDetailsModal(); openManagePrivilegesModal(${u.id}, '${escapeHtml(u.username)}', '${escapeHtml(u.role)}', '${escapeHtml(fullName)}', '${escapeHtml(u.id_number || '')}');" style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.28rem 0.75rem; font-size:0.78rem;">
                                <span>✏️</span> Edit Privileges
                            </button>
                        ` : ''}
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:0.4rem;">
                        ${privList.length > 0 ? privList.map(p => `
                            <span class="user-status-badge status-active" style="font-size:0.75rem; text-transform:none; font-family:'Inter',sans-serif; padding:0.25rem 0.6rem;">
                                ✓ ${escapeHtml(p.replace(/_/g, ' '))}
                            </span>
                        `).join('') : '<span style="color:var(--text-muted); font-size:0.83rem;">No individual module permissions granted (Restricted standard access)</span>'}
                    </div>
                </div>
            `;

            content.innerHTML = `
                <div class="dossier-user-header">
                    <div class="dossier-avatar">${escapeHtml((fullName[0] || 'U').toUpperCase())}</div>
                    <div style="flex:1;">
                        <h4 class="dossier-name">${escapeHtml(fullName)}</h4>
                        <div class="dossier-handle">@${escapeHtml(u.username)} &bull; ${escapeHtml(u.email || 'No email provided')}</div>
                        <div class="dossier-badges" style="margin-top:0.4rem; display:flex; gap:0.4rem; flex-wrap:wrap; align-items:center;">
                            ${roleBadgeHtml}
                            ${statusBadgeHtml}
                            ${isActiveSession ? '<span class="dossier-session-badge live">● Active Session Live</span>' : '<span class="dossier-session-badge">No Active Session</span>'}
                        </div>
                    </div>
                </div>

                <div class="dossier-grid">
                    <div class="dossier-item">
                        <div class="dossier-label">Employee / ID Number</div>
                        <div class="dossier-value font-mono">${escapeHtml(u.id_number || '----')}</div>
                    </div>
                    <div class="dossier-item">
                        <div class="dossier-label">Phone Number</div>
                        <div class="dossier-value">${escapeHtml(u.phone_number || 'None')}</div>
                    </div>
                    <div class="dossier-item">
                        <div class="dossier-label">Account Created</div>
                        <div class="dossier-value">${escapeHtml(u.created_at || 'Unknown')}</div>
                    </div>
                    <div class="dossier-item">
                        <div class="dossier-label">Last Login</div>
                        <div class="dossier-value">${escapeHtml(u.last_login || 'Never')}</div>
                    </div>
                    <div class="dossier-item">
                        <div class="dossier-label">Account Status</div>
                        <div class="dossier-value">${escapeHtml(displayStatus)}</div>
                    </div>
                    <div class="dossier-item">
                        <div class="dossier-label">Successor Eligibility</div>
                        <div class="dossier-value">${isEligibleSuccessor ? '<span style="color:var(--success); font-weight:600;">✓ Eligible Active Admin</span>' : '<span style="color:var(--text-muted);">Not Eligible (Requires Active Admin role)</span>'}</div>
                    </div>
                </div>

                ${privSectionHtml}
                ${succSectionHtml}
            `;
        } else {
            content.innerHTML = `<div class="modal-alert-banner alert-error">${escapeHtml((res && res.message) ? res.message : 'Failed to load user details.')}</div>`;
        }
    } catch (err) {
        console.error('Error retrieving user dossier:', err);
        content.innerHTML = '<div class="modal-alert-banner alert-error">Network error retrieving user dossier.</div>';
    }
}

function closeUserDetailsModal() {
    const modal = document.getElementById('viewUserDetailsModal');
    if (modal) modal.style.display = 'none';
}

// ── Account Status Actions (Deactivate / Reactivate / Suspend / Restore) ───
function openAccountStatusModal(userId, actionType, userName, userRole, currentStatus, isFormerSuperadmin = false) {
    const modal = document.getElementById('accountStatusModal');
    if (!modal) return;

    document.getElementById('statusModalTargetId').value = userId;
    document.getElementById('statusModalActionType').value = actionType;
    document.getElementById('statusModalReasonInput').value = '';
    document.getElementById('statusModalPasswordInput').value = '';

    const avatar = document.getElementById('statusModalAvatar');
    if (avatar) avatar.textContent = (userName[0] || 'U').toUpperCase();
    document.getElementById('statusModalTargetName').textContent = userName;
    document.getElementById('statusModalTargetHandle').textContent = `@user_${userId}`;
    document.getElementById('statusModalTargetRole').textContent = (userRole || 'USER').toUpperCase();
    document.getElementById('statusModalTargetStatus').textContent = (currentStatus || 'STATUS').toUpperCase();

    const titleEl = document.getElementById('statusModalTitle');
    const subtitleEl = document.getElementById('statusModalSubtitle');
    const submitBtn = document.getElementById('statusModalSubmitBtn');
    const submitBtnText = document.getElementById('statusModalSubmitBtnText');
    const noticeBox = document.getElementById('statusModalNotice');
    const reasonLabel = document.getElementById('statusModalReasonLabel');

    submitBtn.disabled = false;
    submitBtn.className = 'btn-modal-submit';

    const isTargetSa = (userRole || '').toLowerCase() === 'super_admin';
    const pwLabel = document.getElementById('statusModalPasswordLabel');
    const pwInput = document.getElementById('statusModalPasswordInput');

    if (actionType === 'deactivate') {
        titleEl.textContent = isTargetSa ? 'Deactivate Superadmin Account' : 'Deactivate User Account';
        subtitleEl.textContent = isTargetSa ? 'Revoke Superadmin account login access' : 'Revoke account login access';
        reasonLabel.textContent = isTargetSa ? 'REASON FOR SUPERADMIN DEACTIVATION' : 'REASON FOR ACCOUNT DEACTIVATION';
        submitBtnText.textContent = 'Confirm Deactivation';
        submitBtn.classList.add('btn-danger');

        if (isTargetSa) {
            noticeBox.className = 'status-notice-box notice-critical';
            noticeBox.innerHTML = '🛡️ <strong>SUPERADMIN SECURITY VERIFICATION:</strong> You are deactivating another Superadmin account. You must enter your current active Superadmin password to authorize this action. The target Superadmin will be immediately marked Inactive and prevented from logging in.';
            if (pwLabel) pwLabel.textContent = 'CONFIRM YOUR SUPERADMIN PASSWORD';
            if (pwInput) pwInput.placeholder = 'Enter your active Superadmin password to authorize';
        } else {
            noticeBox.className = 'status-notice-box notice-warning';
            noticeBox.innerHTML = '⚠️ <strong>Deactivation Notice:</strong> This account will be marked Inactive and blocked from logging in. Any active sessions for this account will be invalidated immediately.';
            if (pwLabel) pwLabel.textContent = 'ADMINISTRATOR PASSWORD CONFIRMATION';
            if (pwInput) pwInput.placeholder = 'Confirm your password to authorize';
        }
        noticeBox.style.display = 'block';
    } else if (actionType === 'reactivate') {
        titleEl.textContent = isTargetSa ? 'Reactivate Superadmin Account' : 'Reactivate Account';
        subtitleEl.textContent = isTargetSa ? 'Restore login privileges to this Superadmin account' : 'Restore login privileges to this account';
        reasonLabel.textContent = isTargetSa ? 'REASON FOR SUPERADMIN REACTIVATION' : 'REASON FOR ACCOUNT REACTIVATION';
        submitBtnText.textContent = 'Confirm Reactivation';
        submitBtn.classList.add('btn-success');
        
        if (isTargetSa) {
            noticeBox.className = 'status-notice-box notice-critical';
            noticeBox.innerHTML = '🛡️ <strong>SUPERADMIN SECURITY VERIFICATION:</strong> You are reactivating a Superadmin account. You must enter your current active Superadmin password to authorize this action.';
            if (pwLabel) pwLabel.textContent = 'CONFIRM YOUR SUPERADMIN PASSWORD';
            if (pwInput) pwInput.placeholder = 'Enter your active Superadmin password to authorize';
        } else if (isFormerSuperadmin) {
            noticeBox.className = 'status-notice-box notice-critical';
            noticeBox.innerHTML = '🛡️ <strong>CRITICAL RULE APPLIED:</strong> This account belongs to a Former Superadmin. Reactivating will restore Active status with their non-Superadmin role (Admin/Staff). <strong>It will NOT restore Superadmin authority</strong>. Only a separate, explicit Superadmin Transfer workflow can reassign master rights.';
            if (pwLabel) pwLabel.textContent = 'ADMINISTRATOR PASSWORD CONFIRMATION';
            if (pwInput) pwInput.placeholder = 'Confirm your password to authorize';
        } else {
            noticeBox.className = 'status-notice-box notice-info';
            noticeBox.innerHTML = 'ℹ️ <strong>Reactivation Notice:</strong> The account will be restored to Active status and granted permission to authenticate and access assigned features.';
            if (pwLabel) pwLabel.textContent = 'ADMINISTRATOR PASSWORD CONFIRMATION';
            if (pwInput) pwInput.placeholder = 'Confirm your password to authorize';
        }
        noticeBox.style.display = 'block';
    }

    modal.style.display = 'flex';
}

function closeAccountStatusModal() {
    const modal = document.getElementById('accountStatusModal');
    if (modal) modal.style.display = 'none';
}

async function submitAccountStatusAction(event) {
    if (event) event.preventDefault();
    const userId = document.getElementById('statusModalTargetId').value;
    const actionType = document.getElementById('statusModalActionType').value;
    const reason = document.getElementById('statusModalReasonInput').value.trim();
    const password = document.getElementById('statusModalPasswordInput').value;
    const submitBtn = document.getElementById('statusModalSubmitBtn');
    const submitBtnText = document.getElementById('statusModalSubmitBtnText');

    if (!reason || reason.length < 3) {
        await customAlert('Please provide a reason for this account status change.');
        return;
    }
    if (!password) {
        await customAlert('Please confirm your administrator password.');
        return;
    }

    let actionEndpoint = '';
    if (actionType === 'deactivate') actionEndpoint = 'deactivate_account';
    else if (actionType === 'reactivate') actionEndpoint = 'reactivate_account';
    else if (actionType === 'suspend') actionEndpoint = 'suspend_account';
    else if (actionType === 'restore') actionEndpoint = 'restore_suspended_account';
    else {
        await customAlert('Invalid action type.');
        return;
    }

    submitBtn.disabled = true;
    const originalText = submitBtnText.textContent;
    submitBtnText.textContent = 'Processing...';

    try {
        const res = await apiCall(actionEndpoint, {
            target_id: userId,
            reason: reason,
            action_confirm_password: password
        });

        if (res.success) {
            closeAccountStatusModal();
            showToast(res.message || 'Account status updated successfully.', 'success');
            lastUsersPayloadHash = '';
            loadUsers(usersPage, false);
        } else {
            await customAlert(res.message || 'Failed to update account status.');
            submitBtn.disabled = false;
            submitBtnText.textContent = originalText;
        }
    } catch (err) {
        await customAlert('Network or server error updating account status.');
        submitBtn.disabled = false;
        submitBtnText.textContent = originalText;
    }
}

// ── User Audit History Modal ────────────────────────────────────────────────
async function openUserAuditModal(userId) {
    const modal = document.getElementById('userAuditModal');
    const content = document.getElementById('userAuditContent');
    const subtitle = document.getElementById('userAuditModalSubtitle');
    if (!modal || !content) return;

    content.innerHTML = '<div style="text-align:center; padding:3rem;"><div class="table-loading-spinner"></div><div style="margin-top:0.8rem; color:var(--text-muted);">Retrieving chronological audit history...</div></div>';
    modal.style.display = 'flex';

    try {
        const res = await apiCall('get_user_audit_history', { user_id: userId, target_id: userId });
        if (res.success && res.data) {
            const user = res.data.user || {};
            const logs = res.data.logs || [];
            if (subtitle) {
                subtitle.textContent = `Security events and privilege modifications for @${user.username || userId}`;
            }

            if (logs.length === 0) {
                content.innerHTML = '<div style="text-align:center; padding:2.5rem; color:var(--text-muted);">No recorded audit entries found for this account.</div>';
                return;
            }

            content.innerHTML = `
                <div class="table-responsive-wrapper">
                    <table class="user-audit-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Action</th>
                                <th>Actor</th>
                                <th>State Change</th>
                                <th>Reason / Details</th>
                                <th>Outcome</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${logs.map(l => {
                                const isSuccess = l.outcome === 'success' || !l.outcome;
                                const outcomeBadge = isSuccess 
                                    ? '<span class="audit-outcome-badge success">SUCCESS</span>'
                                    : '<span class="audit-outcome-badge failed">FAILED</span>';
                                
                                let stateChangeHtml = '—';
                                if (l.previous_state || l.new_state) {
                                    stateChangeHtml = `<span class="audit-state-old">${escapeHtml(l.previous_state || 'none')}</span> ➔ <span class="audit-state-new">${escapeHtml(l.new_state || 'none')}</span>`;
                                }

                                return `
                                    <tr>
                                        <td class="font-mono text-nowrap" style="font-size:0.78rem;">${escapeHtml(l.created_at || l.timestamp)}</td>
                                        <td><span class="audit-action-chip">${escapeHtml(l.action)}</span></td>
                                        <td>@${escapeHtml(l.actor_username || 'System')}</td>
                                        <td>${stateChangeHtml}</td>
                                        <td style="max-width:220px; font-size:0.82rem;">${escapeHtml(l.reason || l.details || '—')}</td>
                                        <td>${outcomeBadge}</td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            content.innerHTML = `<div class="modal-alert-banner alert-error">${escapeHtml(res.message || 'Failed to load audit history.')}</div>`;
        }
    } catch (err) {
        content.innerHTML = '<div class="modal-alert-banner alert-error">Network error retrieving audit history.</div>';
    }
}

function closeUserAuditModal() {
    const modal = document.getElementById('userAuditModal');
    if (modal) modal.style.display = 'none';
}

// ── Logout Confirmation Warning Dialog ──────────────────────────────────────
function openLogoutConfirmModal() {
    const modal = document.getElementById('logoutConfirmModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeLogoutConfirmModal() {
    const modal = document.getElementById('logoutConfirmModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function confirmLogout() {
    window.location.href = '../php/logout.php';
}

// Global key handler for modal dismissal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const logoutModal = document.getElementById('logoutConfirmModal');
        if (logoutModal && logoutModal.style.display === 'flex') {
            closeLogoutConfirmModal();
        }
    }
});

// Expose functions globally for inline HTML events
window.resetUserFilters = resetUserFilters;
window.openSuperadminSuccessionModal = openSuperadminSuccessionModal;
window.closeSuperadminSuccessionModal = closeSuperadminSuccessionModal;
window.onSuccessorSelected = onSuccessorSelected;
window.submitSuperadminSuccession = submitSuperadminSuccession;
window.openUserDetailsModal = openUserDetailsModal;
window.closeUserDetailsModal = closeUserDetailsModal;
window.openAccountStatusModal = openAccountStatusModal;
window.closeAccountStatusModal = closeAccountStatusModal;
window.submitAccountStatusAction = submitAccountStatusAction;
window.openUserAuditModal = openUserAuditModal;
window.closeUserAuditModal = closeUserAuditModal;
window.loadUsers = loadUsers;
window.startUsersRealtimePoll = startUsersRealtimePoll;
window.stopUsersRealtimePoll = stopUsersRealtimePoll;
window.manualRefreshUsers = manualRefreshUsers;
window.openCreateSuperadminConfirmModal = openCreateSuperadminConfirmModal;
window.closeCreateSuperadminConfirmModal = closeCreateSuperadminConfirmModal;
window.proceedCreateSuperadminWithPassword = proceedCreateSuperadminWithPassword;
window.openLogoutConfirmModal = openLogoutConfirmModal;
window.closeLogoutConfirmModal = closeLogoutConfirmModal;
window.confirmLogout = confirmLogout;
window.toggleCaPasswordVisibility = toggleCaPasswordVisibility;
window.setCaPrivPreset = setCaPrivPreset;
window.goToCreateAccountStep = goToCreateAccountStep;
window.onSuccConfirmCheckboxChange = onSuccConfirmCheckboxChange;
