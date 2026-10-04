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

window.customAlert = function(msg) {
    return new Promise(resolve => {
        document.getElementById('customAlertMessage').innerText = msg;
        const modal = document.getElementById('customAlertModal');
        modal.style.display = 'flex';
        
        const okBtn = modal.querySelector('button');
        if (okBtn) setTimeout(() => okBtn.focus(), 10);

        if (alertEnterHandler) document.removeEventListener('keydown', alertEnterHandler);
        alertEnterHandler = function(e) {
            if (e.key === 'Enter') {
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
    document.getElementById('customAlertModal').style.display = 'none';
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
let logsPage = 1;

document.addEventListener('DOMContentLoaded', () => {
    // Show default tab or saved tab
    const savedTab = sessionStorage.getItem('activeDashboardTab');
    
    if (savedTab && document.getElementById(savedTab)) {
        showTab(savedTab);
    } else if (userRole === 'super_admin' || userRole === 'admin') {
        showTab('users');
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
});

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
    const response = await fetch('../php/admin_actions.php', {
        method: 'POST',
        body: formData
    });
    return await response.json();
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

async function loadUsers(page = null) {
    if (page === null) {
        page = parseInt(sessionStorage.getItem('dashboardUsersPage')) || 1;
    }
    sessionStorage.setItem('dashboardUsersPage', page);
    usersPage = page;
    const empId = document.getElementById('filterEmpId').value;
    
    const response = await fetch(`../php/admin_actions.php?action=get_users&emp_id=${empId}&page=${page}`);
    const data = await response.json();
    
    if (data.success && data.data) {
        const tbody = document.querySelector('#usersTable tbody');
        // Clear using safe method instead of innerHTML assignment
        while (tbody.firstChild) tbody.removeChild(tbody.firstChild);

        data.data.users.forEach(u => {
            // Build action buttons using createElement + data-* attributes (no inline onclick)
            const actionsCell = document.createElement('td');

            const canBlock = userRole === 'super_admin' || (userRole === 'admin' && userPrivileges.includes('block_unblock_users') && u.role !== 'super_admin');
            const canApprove = userRole === 'super_admin' || (userRole === 'admin' && userPrivileges.includes('registration_approval'));

            function makeBtn(label, cls, action, extraData = {}) {
                const btn = document.createElement('button');
                btn.className = cls;
                btn.textContent = label;
                btn.dataset.action = action;
                btn.dataset.userId = u.id;
                Object.assign(btn.dataset, extraData);
                return btn;
            }

            if (u.status === 'pending') {
                if (canApprove) actionsCell.appendChild(makeBtn('Approve', 'btn-small', 'approve'));
                if (canBlock)   actionsCell.appendChild(makeBtn('Block',   'btn-small btn-danger', 'block'));
            } else if (u.status === 'approved') {
                if (canBlock)   actionsCell.appendChild(makeBtn('Block',   'btn-small btn-danger', 'block'));
            } else if (u.status === 'blocked') {
                if (canBlock)   actionsCell.appendChild(makeBtn('Unblock', 'btn-small', 'unblock'));
            }

            if (userRole === 'super_admin') {
                const sel = document.createElement('select');
                sel.className = 'btn-small';
                sel.style.cssText = 'color:#000; background: var(--cyber-primary);';
                sel.dataset.action = 'change_role';
                sel.dataset.userId = u.id;
                const safeRole = (u.role || '').trim().toLowerCase();
                ['user', 'admin', 'super_admin'].forEach(rv => {
                    const opt = document.createElement('option');
                    opt.value = rv;
                    opt.textContent = rv.replace('_', ' ');
                    if (safeRole === rv) {
                        opt.selected = true;
                        opt.defaultSelected = true;
                        opt.setAttribute('selected', 'selected');
                    }
                    sel.appendChild(opt);
                });
                sel.value = safeRole;
                actionsCell.appendChild(sel);
            }

            if (userRole === 'super_admin' || (userRole === 'admin' && userPrivileges.includes('edit_manage_accounts') && u.role !== 'super_admin')) {
                const editBtn = document.createElement('button');
                editBtn.className = 'btn-small';
                editBtn.textContent = 'Edit';
                editBtn.dataset.action = 'edit';
                editBtn.dataset.userId   = u.id;
                editBtn.dataset.firstName  = u.first_name || '';
                editBtn.dataset.lastName   = u.last_name  || '';
                editBtn.dataset.email      = u.email      || '';
                editBtn.dataset.phone      = u.phone_number || '';
                actionsCell.appendChild(editBtn);
            }

            if (userRole === 'super_admin') {
                actionsCell.appendChild(makeBtn('Delete', 'btn-small btn-danger', 'delete'));
            } else if (userRole === 'admin' && userPrivileges.includes('account_deletion_requests') && u.role !== 'super_admin') {
                actionsCell.appendChild(makeBtn('Req Delete', 'btn-small btn-danger', 'req_delete'));
            }

            // Compute display status
            let displayStatus = u.status || '';
            let statusColor = '#fff';
            if (u.status === 'approved')      { displayStatus = 'Active';        statusColor = '#00ff88'; }
            else if (u.status === 'pending_setup') { displayStatus = 'Pending Setup'; statusColor = '#ff8c00'; }
            else if (u.status === 'blocked')  { displayStatus = 'Inactive';      statusColor = '#ff3366'; }
            else if (u.status === 'pending')  { displayStatus = 'Pending';       statusColor = '#ffcc00'; }

            // Build the row entirely with DOM APIs — no user data goes into innerHTML
            const tr = document.createElement('tr');

            function makeTextCell(val) {
                const td = document.createElement('td');
                td.textContent = val || '';
                return td;
            }

            tr.appendChild(makeTextCell(u.id_number));

            const nameTd = document.createElement('td');
            nameTd.textContent = ((u.first_name || '') + ' ' + (u.last_name || '')).trim();
            tr.appendChild(nameTd);

            tr.appendChild(makeTextCell(u.username));
            tr.appendChild(makeTextCell(u.role));

            const statusTd = document.createElement('td');
            statusTd.textContent = displayStatus;
            statusTd.style.color = statusColor;
            statusTd.style.fontWeight = 'bold';
            if (u.status === 'pending_setup') {
                statusTd.style.cursor = 'help';
                statusTd.title = 'Account created — needs to login first to complete setup';
            }
            tr.appendChild(statusTd);

            tr.appendChild(actionsCell);
            tbody.appendChild(tr);
        });

        // Attach action delegator once per load
        attachUserTableListeners();

        renderPagination('usersPagination', data.data.pages, usersPage, 'loadUsers');
    }
}

// Delegate user-table action clicks to avoid inline onclick XSS vectors
function attachUserTableListeners() {
    const tbody = document.querySelector('#usersTable tbody');
    if (!tbody) return;
    // Remove previous listener by cloning (simple approach for single-threaded JS)
    const fresh = tbody.cloneNode(true);
    tbody.parentNode.replaceChild(fresh, tbody);
    fresh.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;
        const uid    = btn.dataset.userId;
        if (action === 'approve') await updateStatus(uid, 'approved');
        else if (action === 'block')   await updateStatus(uid, 'blocked');
        else if (action === 'unblock') await updateStatus(uid, 'approved');
        else if (action === 'delete')  await deleteUser(uid, false);
        else if (action === 'req_delete') await deleteUser(uid, true);
        else if (action === 'edit') {
            openEditModal(uid, btn.dataset.firstName, btn.dataset.lastName, btn.dataset.email, btn.dataset.phone);
        }
    });
    fresh.addEventListener('change', async (e) => {
        const sel = e.target.closest('select[data-action="change_role"]');
        if (!sel) return;
        await updateRole(sel.dataset.userId, sel.value);
    });
}


function openEditModal(id, firstName, lastName, email, phone) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editFirstName').value = firstName;
    document.getElementById('editLastName').value = lastName;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone || '';
    const newPwdEl = document.getElementById('editNewPassword');
    if (newPwdEl) newPwdEl.value = '';
    document.getElementById('editUserModal').style.display = 'grid';
}

async function submitEditUser() {
    const id = document.getElementById('editUserId').value;
    const firstName = document.getElementById('editFirstName').value;
    const lastName = document.getElementById('editLastName').value;
    const email = document.getElementById('editEmail').value;
    const phone = document.getElementById('editPhone').value;
    const newPwdEl = document.getElementById('editNewPassword');
    const newPassword = newPwdEl ? newPwdEl.value : '';
    
    if (!firstName || !lastName || !email) {
        await customAlert("First Name, Last Name, and Email are required.");
        return;
    }
    
    let pwd = '';
    if (userRole === 'super_admin' || userRole === 'admin') {
        try { pwd = await promptSaPassword(); } catch (e) { return; }
    }
    
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
        loadUsers(usersPage);
    } else {
        await customAlert(res.message);
    }
}

async function updateStatus(id, status) {
    if (!await customConfirm(`Are you sure you want to mark this user as ${status}?`)) return;
    
    let pwd = '';
    if (userRole === 'super_admin' || userRole === 'admin') {
        try { pwd = await promptSaPassword(); } catch (e) { return; }
    }
    
    const res = await apiCall('update_user_status', { target_id: id, status, action_confirm_password: pwd });
    if (res.success) loadUsers(usersPage);
    else await customAlert(res.message);
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
        loadUsers(usersPage);
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
        loadUsers(usersPage);
    } else {
        await customAlert(res.message);
    }
}

async function loadLogs(page = null) {
    if (page === null) {
        page = parseInt(sessionStorage.getItem('dashboardLogsPage')) || 1;
    }
    sessionStorage.setItem('dashboardLogsPage', page);
    logsPage = page;
    const month = document.getElementById('filterMonth').value;
    const date = document.getElementById('filterDate').value;
    
    const response = await fetch(`../php/admin_actions.php?action=get_logs&month=${encodeURIComponent(month)}&date=${encodeURIComponent(date)}&page=${page}`);
    const data = await response.json();
    
    if (data.success && data.data) {
        const tbody = document.querySelector('#logsTable tbody');
        while (tbody.firstChild) tbody.removeChild(tbody.firstChild);

        data.data.logs.forEach(l => {
            const tr = document.createElement('tr');

            function mkTd(val) {
                const td = document.createElement('td');
                td.textContent = val || '';
                return td;
            }

            if (userRole === 'super_admin' || userRole === 'admin') {
                tr.appendChild(mkTd(l.id_number));
                const nameTd = document.createElement('td');
                nameTd.textContent = ((l.first_name || '') + ' ' + (l.last_name || '')).trim();
                tr.appendChild(nameTd);
                tr.appendChild(mkTd(l.role));
                tr.appendChild(mkTd(l.time_in));
                tr.appendChild(mkTd(l.time_out || 'Active'));
            } else {
                tr.appendChild(mkTd(l.time_in));
                tr.appendChild(mkTd(l.time_out || 'Active'));
            }
            tbody.appendChild(tr);
        });
        
        renderPagination('logsPagination', data.data.pages, logsPage, 'loadLogs');
    }
}

async function loadDeleteRequests() {
    const res = await apiCall('get_delete_requests');
    if (res.success && res.data) {
        const tbody = document.querySelector('#delReqTable tbody');
        while (tbody.firstChild) tbody.removeChild(tbody.firstChild);

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

            const approveBtn = document.createElement('button');
            approveBtn.className = 'btn-small';
            approveBtn.textContent = 'Approve';
            approveBtn.dataset.reqId = r.id;
            approveBtn.dataset.decision = 'approved';

            const rejectBtn = document.createElement('button');
            rejectBtn.className = 'btn-small btn-danger';
            rejectBtn.textContent = 'Reject';
            rejectBtn.dataset.reqId = r.id;
            rejectBtn.dataset.decision = 'rejected';

            actionsTd.appendChild(approveBtn);
            actionsTd.appendChild(rejectBtn);
            tr.appendChild(actionsTd);
            tbody.appendChild(tr);
        });

        // Delegate click handling — no inline onclick
        const tbody2 = document.querySelector('#delReqTable tbody');
        tbody2.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-decision]');
            if (!btn) return;
            await handleDeleteReq(btn.dataset.reqId, btn.dataset.decision);
        }, { once: true });
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

function renderPagination(elementId, totalPages, currentPage, funcName) {
    const el = document.getElementById(elementId);
    while (el.firstChild) el.removeChild(el.firstChild);
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
        btn.textContent = i;
        // Use data attribute instead of inline onclick to prevent injection
        btn.dataset.page = i;
        btn.dataset.fn   = funcName;
        btn.addEventListener('click', () => {
            if (funcName === 'loadUsers') loadUsers(i);
            else if (funcName === 'loadLogs') loadLogs(i);
        });
        el.appendChild(btn);
    }
}

function openCreateAdminModal() {
    showTab('create_admin_portal');
}

// Super Admin Portal Logic
function initCreateAdminPortal() {
    updatePrivilegeCount();
}

function selectAccountRole(role) {
    const roleInput = document.getElementById('selectedAccountRole');
    if (roleInput) roleInput.value = role;

    const cardAdmin = document.getElementById('roleCardAdmin');
    const cardSuper = document.getElementById('roleCardSuperAdmin');

    if (role === 'super_admin') {
        if (cardSuper) cardSuper.classList.add('active');
        if (cardAdmin) cardAdmin.classList.remove('active');
        // Super Admin gets all privileges
        selectAllPrivileges();
    } else {
        if (cardAdmin) cardAdmin.classList.add('active');
        if (cardSuper) cardSuper.classList.remove('active');
    }
}

function updatePrivilegeCount() {
    const checkboxes = document.querySelectorAll('#create_admin_portal .privilege-checkbox');
    const checked = document.querySelectorAll('#create_admin_portal .privilege-checkbox:checked');
    const badge = document.getElementById('privilegeCountBadge');
    if (badge) {
        badge.textContent = `${checked.length} Selected`;
    }
}

function selectAllPrivileges() {
    document.querySelectorAll('#create_admin_portal .privilege-checkbox').forEach(cb => {
        cb.checked = true;
    });
    updatePrivilegeCount();
}

function clearAllPrivileges() {
    document.querySelectorAll('#create_admin_portal .privilege-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updatePrivilegeCount();
}

function selectRecommendedPrivileges() {
    const recommendedPrivileges = [
        'change_password',
        'profile_management',
        'view_accounts',
        'edit_manage_accounts',
        'block_unblock_users',
        'registration_approval',
        'account_deletion_requests'
    ];
    document.querySelectorAll('#create_admin_portal .privilege-checkbox').forEach(cb => {
        cb.checked = recommendedPrivileges.includes(cb.value);
    });
    updatePrivilegeCount();
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>`;
    } else {
        input.type = 'password';
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>`;
    }
}

async function submitCreatePrivilegedAccount(confirmTransfer = false) {
    const usernameInput = document.getElementById('portalUsername');
    const passwordInput = document.getElementById('portalPassword');
    const confirmPasswordInput = document.getElementById('portalConfirmPassword');
    const roleInput = document.getElementById('selectedAccountRole');
    const submitBtn = document.getElementById('submitCreateAccountBtn');

    const username = usernameInput ? usernameInput.value.trim() : '';
    const password = passwordInput ? passwordInput.value : '';
    const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';
    const role = roleInput ? roleInput.value : 'admin';

    // Client-side validations
    if (!username) {
        await customAlert('Please enter a username.');
        if (usernameInput) usernameInput.focus();
        return;
    }
    if (username.length < 3 || username.length > 50) {
        await customAlert('Username must be between 3 and 50 characters.');
        if (usernameInput) usernameInput.focus();
        return;
    }
    if (!/^[a-zA-Z0-9._-]+$/.test(username)) {
        await customAlert('Username can only contain letters, numbers, dots, underscores, and dashes.');
        if (usernameInput) usernameInput.focus();
        return;
    }
    if (!password || password.length < 8) {
        await customAlert('Default password must be at least 8 characters long.');
        if (passwordInput) passwordInput.focus();
        return;
    }
    if (password !== confirmPassword) {
        await customAlert('Password and Confirm Password do not match.');
        if (confirmPasswordInput) confirmPasswordInput.focus();
        return;
    }

    const selectedPrivileges = [];
    document.querySelectorAll('#create_admin_portal .privilege-checkbox:checked').forEach(cb => {
        selectedPrivileges.push(cb.value);
    });

    const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
    
    let pwd = '';
    if (userRole === 'super_admin' || userRole === 'admin') {
        try { pwd = await promptSaPassword(); } catch (e) { return; }
    }
    
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>⏳</span> Creating Account & Saving Privileges...';
    }
    
    try {
        const payload = {
            account_role: role,
            username: username,
            password: password,
            confirm_password: confirmPassword,
            privileges: JSON.stringify(selectedPrivileges),
            confirm_transfer: confirmTransfer ? 'true' : 'false',
            action_confirm_password: pwd
        };

        const res = await apiCall('create_privileged_account', payload);

        if (res.success) {
            await customAlert(`SUCCESS: Account created for "${res.data.username}" with ID "${res.data.id_number}".\nStatus: Pending Setup (Profile completion on first login).`);
            
            // Reset fields
            if (usernameInput) usernameInput.value = '';
            if (passwordInput) passwordInput.value = '';
            if (confirmPasswordInput) confirmPasswordInput.value = '';
            selectAccountRole('admin');
            selectAllPrivileges();

            // Transition to User Management tab to show the new account
            showTab('users');
        } else {
            if (res.data && res.data.requires_confirmation) {
                const transferAccepted = await customConfirm(res.message);
                if (transferAccepted) {
                    submitCreatePrivilegedAccount(true);
                    return;
                }
            } else {
                await customAlert(res.message || 'Failed to create account.');
            }
        }
    } catch (err) {
        console.error('Error creating privileged account:', err);
        await customAlert('An unexpected network or server error occurred.');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
        }
    }
}

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

async function openManagePrivilegesModal(userId, username, role) {
    const modal = document.getElementById('managePrivilegesModal');
    if (!modal) return;

    document.getElementById('modalPrivUserId').value = userId;
    document.getElementById('modalPrivTitle').textContent = `🛡️ Manage Privileges: ${username}`;
    document.getElementById('modalPrivSubtitle').textContent = `Role: ${role.toUpperCase()} | Configure individual module permissions`;

    // Reset all checkboxes first
    document.querySelectorAll('.modal-priv-cb').forEach(cb => cb.checked = false);
    updateModalPrivCount();

    // Hide Module 2 and 3 if role is 'user'
    const isUser = role.toLowerCase() === 'user';
    const mCard2 = document.getElementById('modalMgmtCard2');
    const mCard3 = document.getElementById('modalMgmtCard3');
    if (mCard2) mCard2.style.display = isUser ? 'none' : 'block';
    if (mCard3) mCard3.style.display = isUser ? 'none' : 'block';

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
        } else {
            console.warn("Failed to fetch privileges:", res);
        }
    } catch (e) {
        console.error('Error fetching privileges:', e);
    }

    modal.style.display = 'grid';
}

function closeManagePrivilegesModal() {
    const modal = document.getElementById('managePrivilegesModal');
    if (modal) modal.style.display = 'none';
}

function updateModalPrivCount() {
    const checked = document.querySelectorAll('.modal-priv-cb:checked');
    const badge = document.getElementById('modalPrivCountBadge');
    if (badge) badge.textContent = `${checked.length} Selected`;
}

function setModalPrivPresets(type) {
    document.querySelectorAll('.modal-priv-cb').forEach(cb => {
        if (type === 'all') cb.checked = true;
        else if (type === 'clear') cb.checked = false;
        else if (type === 'recommended') cb.checked = RECOMMENDED_PRIVILEGES.includes(cb.value);
    });
    updateModalPrivCount();
}

async function saveModalPrivileges() {
    const userId = document.getElementById('modalPrivUserId').value;
    const saveBtn = document.getElementById('saveModalPrivBtn');
    if (!userId) return;

    const selected = [];
    document.querySelectorAll('.modal-priv-cb:checked').forEach(cb => selected.push(cb.value));

    let pwd = '';
    if (userRole === 'super_admin') {
        try { pwd = await promptSaPassword(); } catch (e) { return; }
    }

    const origText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span>⏳</span> Saving Privileges...';

    try {
        const res = await apiCall('update_user_privileges', {
            target_id: userId,
            privileges: JSON.stringify(selected),
            action_confirm_password: pwd
        });

        if (res.success) {
            await customAlert(res.message || 'Privileges updated successfully!');
            closeManagePrivilegesModal();
            loadUsers(usersPage);
        } else {
            await customAlert(res.message || 'Failed to update privileges.');
        }
    } catch (e) {
        console.error('Error saving privileges:', e);
        await customAlert('A network error occurred while saving privileges.');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = origText;
    }
}

// ----------------------------------------------------
// Privilege Management: Dedicated Tab Controls
// ----------------------------------------------------
let privMgmtUsersCache = [];

async function initPrivilegeMgmtTab() {
    await loadPrivMgmtUsers();
}

async function loadPrivMgmtUsers() {
    const listContainer = document.getElementById('privMgmtUserList');
    if (!listContainer) return;

    listContainer.innerHTML = '<div style="color:var(--cyber-text-dim);">Loading accounts...</div>';

    try {
        const response = await fetch('../php/admin_actions.php?action=get_users&page=1&limit=1000');
        const data = await response.json();

        if (data.success && data.data && data.data.users) {
            privMgmtUsersCache = data.data.users;
            renderPrivMgmtUserList(privMgmtUsersCache);
        } else {
            listContainer.innerHTML = '<div style="color:var(--cyber-error);">No accounts found</div>';
        }
    } catch (e) {
        console.error('Failed to load accounts for privilege tab:', e);
        listContainer.innerHTML = '<div style="color:var(--cyber-error);">Error loading accounts</div>';
    }
}

function renderPrivMgmtUserList(users) {
    const listContainer = document.getElementById('privMgmtUserList');
    if (!listContainer) return;
    listContainer.innerHTML = '';

    if (users.length === 0) {
        listContainer.innerHTML = '<div style="color:var(--cyber-text-dim); padding: 0.5rem;">No accounts match your search.</div>';
        return;
    }

    const selectedId = document.getElementById('privMgmtUserSelect').value;

    users.forEach(u => {
        const item = document.createElement('div');
        item.className = 'priv-user-item';
        
        // Base styling
        const isActive = (selectedId == u.id);
        const baseBg = isActive ? 'rgba(0,255,255,0.15)' : 'rgba(0,0,0,0.5)';
        const baseBorder = isActive ? 'var(--cyber-primary)' : 'var(--cyber-border)';
        
        item.style.cssText = `padding: 0.6rem 1rem; border: 1px solid ${baseBorder}; border-radius: 4px; background: ${baseBg}; cursor: pointer; transition: all 0.3s; color: var(--cyber-text); display: flex; justify-content: space-between; align-items: center;`;
        
        item.innerHTML = `
            <div>
                <strong style="color: #fff; font-size: 1.05rem;">${u.username}</strong> 
                <span style="color: var(--cyber-text-dim); font-size: 0.85rem; margin-left: 0.5rem;">${u.first_name} ${u.last_name}</span>
            </div>
            <div style="font-size: 0.75rem; color: var(--cyber-accent); text-transform: uppercase; border: 1px solid var(--cyber-accent); padding: 0.2rem 0.5rem; border-radius: 3px; font-weight: bold;">
                ${u.role}
            </div>
        `;
        
        item.onmouseover = () => {
            if (document.getElementById('privMgmtUserSelect').value != u.id) {
                item.style.borderColor = 'var(--cyber-primary)';
                item.style.background = 'rgba(0,255,255,0.05)';
            }
        };
        item.onmouseout = () => {
            if (document.getElementById('privMgmtUserSelect').value != u.id) {
                item.style.borderColor = 'var(--cyber-border)';
                item.style.background = 'rgba(0,0,0,0.5)';
            }
        };

        item.onclick = () => {
            // Update active state across all items
            document.querySelectorAll('.priv-user-item').forEach(el => {
                el.style.borderColor = 'var(--cyber-border)';
                el.style.background = 'rgba(0,0,0,0.5)';
            });
            item.style.borderColor = 'var(--cyber-primary)';
            item.style.background = 'rgba(0,255,255,0.15)';
            
            document.getElementById('privMgmtUserSelect').value = u.id;
            onPrivMgmtUserChange(u.id);
        };
        
        listContainer.appendChild(item);
    });
}

function filterPrivMgmtUsers() {
    const term = document.getElementById('privMgmtSearchInput').value.toLowerCase();
    const filtered = privMgmtUsersCache.filter(u => 
        (u.username && u.username.toLowerCase().includes(term)) ||
        (u.first_name && u.first_name.toLowerCase().includes(term)) ||
        (u.last_name && u.last_name.toLowerCase().includes(term)) ||
        (u.role && u.role.toLowerCase().includes(term))
    );
    renderPrivMgmtUserList(filtered);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
const debouncedFilterPrivMgmtUsers = debounce(filterPrivMgmtUsers, 250);

async function onPrivMgmtUserChange(userId) {
    const configArea = document.getElementById('privMgmtConfigArea');
    const infoArea = document.getElementById('privMgmtUserInfo');
    if (!userId) {
        if (configArea) configArea.style.display = 'none';
        if (infoArea) infoArea.style.display = 'none';
        return;
    }

    const user = privMgmtUsersCache.find(u => u.id == userId);
    if (user && infoArea) {
        const nameEl = document.getElementById('privUserInfoName');
        if (nameEl) nameEl.textContent = `${user.username} (${user.first_name} ${user.last_name})`;
        document.getElementById('privUserInfoId').textContent = user.id_number || user.id;
        document.getElementById('privUserInfoRole').textContent = user.role;
        
        let dispStatus = user.status;
        let statColor = '#fff';
        if (dispStatus === 'approved') { dispStatus = 'Active'; statColor = '#00ff88'; }
        else if (dispStatus === 'pending_setup') { dispStatus = 'Pending Setup'; statColor = '#ff8c00'; }
        else if (dispStatus === 'blocked') { dispStatus = 'Inactive'; statColor = '#ff3366'; }
        else if (dispStatus === 'pending') { dispStatus = 'Pending'; statColor = '#ffcc00'; }

        const statusEl = document.getElementById('privUserInfoStatus');
        if (statusEl) {
            statusEl.textContent = dispStatus;
            statusEl.style.color = statColor;
        }

        infoArea.style.display = 'flex';
    }

    // Reset tab checkboxes
    document.querySelectorAll('.priv-mgmt-tab-cb').forEach(cb => cb.checked = false);

    // Hide Module 2 and 3 if role is 'user'
    const isUser = user.role.toLowerCase() === 'user';
    const pCard2 = document.getElementById('privMgmtCard2');
    const pCard3 = document.getElementById('privMgmtCard3');
    if (pCard2) pCard2.style.display = isUser ? 'none' : 'block';
    if (pCard3) pCard3.style.display = isUser ? 'none' : 'block';

    // Fetch user's current privileges
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
            
            document.querySelectorAll('.priv-mgmt-tab-cb').forEach(cb => {
                cb.checked = userPrivs.includes(cb.value);
            });
        }
    } catch (e) {
        console.error('Error fetching user privileges:', e);
    }

    updatePrivMgmtTabCount();
    if (configArea) configArea.style.display = 'block';
}

function updatePrivMgmtTabCount() {
    const checked = document.querySelectorAll('.priv-mgmt-tab-cb:checked');
    const badge = document.getElementById('privMgmtTabCountBadge');
    if (badge) badge.textContent = `${checked.length} Selected`;
}

function setPrivMgmtPresets(type) {
    document.querySelectorAll('.priv-mgmt-tab-cb').forEach(cb => {
        if (type === 'all') cb.checked = true;
        else if (type === 'clear') cb.checked = false;
        else if (type === 'recommended') cb.checked = RECOMMENDED_PRIVILEGES.includes(cb.value);
    });
    updatePrivMgmtTabCount();
}

async function savePrivMgmtTabPrivileges() {
    const select = document.getElementById('privMgmtUserSelect');
    const saveBtn = document.getElementById('savePrivMgmtTabBtn');
    const userId = select ? select.value : '';

    if (!userId) {
        await customAlert('Please choose an account first.');
        return;
    }

    const selected = [];
    document.querySelectorAll('.priv-mgmt-tab-cb:checked').forEach(cb => selected.push(cb.value));

    let pwd = '';
    if (userRole === 'super_admin') {
        try { pwd = await promptSaPassword(); } catch (e) { return; }
    }

    const origText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span>⏳</span> Saving Privileges...';

    try {
        const res = await apiCall('update_user_privileges', {
            target_id: userId,
            privileges: JSON.stringify(selected),
            action_confirm_password: pwd
        });

        if (res.success) {
            await customAlert(res.message || 'Privileges updated successfully!');
            // Refresh cached data
            onPrivMgmtUserChange(userId);
        } else {
            await customAlert(res.message || 'Failed to update privileges.');
        }
    } catch (e) {
        console.error('Error saving tab privileges:', e);
        await customAlert('A network error occurred while saving privileges.');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = origText;
    }
}

