<?php include 'session_protect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WorkConnect Add Account</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #f4f7fb 60%, #e3eaff 100%);
            min-height: 100vh;
        }
        .header {
            background: #233a8b;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            height: 64px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            max-width: 100vw;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(35,58,139,0.10);
            box-sizing: border-box;
        }
        .header img {
            height: 48px;
            margin-right: 16px;
            border-radius: 50%;
            background: none;
            border: none;
        }
        .header-title {
            font-size: 1.7rem;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .layout {
            display: flex;
            min-height: 100vh;
            padding-top: 64px; /* offset for fixed header */
        }
        .sidebar {
            background: #e3eaff;
            width: 240px;
            height: calc(100vh - 64px);
            position: fixed;
            top: 64px;
            left: 0;
            z-index: 999;
            display: flex;
            flex-direction: column;
            padding: 32px 0 0 24px;
            box-sizing: border-box;
            overflow-y: auto;
        }
        .sidebar a {
            font-weight: bold;
            color: #222;
            text-decoration: none;
            margin-bottom: 16px;
            font-size: 1rem;
            letter-spacing: 0.3px;
            transition: all 0.2s;
            padding: 12px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10%;
        }
        .sidebar a:hover {
            color: #233a8b;
            background: #d1dbfa;
        }
        .sidebar .logout {
            margin-top: auto;
            margin-bottom: 32px;
            color: #222;
            font-weight: bold;
            display: block;
            width: 90%;
            text-align: left;
        }
        .sidebar a:hover {
            color: #233a8b;
            background: #d1dbfa;
            border-radius: 8px;
            padding-left: 10px;
        }
        .sidebar a.active {
            color: #fff;
            background: #233a8b;
            box-shadow: 0 2px 8px rgba(35,58,139,0.15);
        }
        .main-content {
            flex: 1;
            padding: 40px 0 32px 0;
            background: transparent;
            margin-left: 240px;
            height: calc(100vh - 64px);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            box-sizing: border-box;
        }
        .admin-form-container, .admin-table-container {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(35,58,139,0.10);
            width: 100%;
            max-width: 480px;
            margin-bottom: 32px;
            padding: 36px 32px 28px 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .admin-form-container h2 {
            text-align: center;
            color: #233a8b;
            margin-bottom: 18px;
        }
        .form-group label {
            font-weight: 600;
            color: #233a8b;
        }
        .form-group input {
            width: 100%;
            padding: 0.7rem;
            border-radius: 8px;
            border: 1px solid #b3c6e0;
            font-size: 1rem;
            margin-top: 0.3rem;
            margin-bottom: 1.2rem;
            background: #f4f7fb;
        }
        .login-btn {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(90deg, #233a8b 60%, #4f7cf7 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 1rem;
            transition: background 0.2s;
        }
        .login-btn:hover {
            background: linear-gradient(90deg, #4f7cf7 60%, #233a8b 100%);
        }
        .admin-table-container {
            max-width: 700px;
            padding: 36px 24px 28px 24px;
        }
        .admin-table-container h3 {
            color: #233a8b;
            margin-bottom: 18px;
            text-align: center;
        }
        table.admin-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        table.admin-table th, table.admin-table td {
            border: 1px solid #b3c6e0;
            padding: 10px 8px;
            text-align: center;
        }
        table.admin-table th {
            background: #e3eaff;
            color: #233a8b;
            font-weight: bold;
        }
        table.admin-table td {
            background: #f8fafc;
        }
        .admin-action-btn {
            padding: 6px 16px;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            margin: 0 4px;
            cursor: pointer;
            transition: background 0.18s;
        }
        .admin-action-btn.edit {
            background: #1976d2;
            color: #fff;
        }
        .admin-action-btn.edit:hover {
            background: #1251a3;
        }
        .admin-action-btn.delete {
            background: #d32f2f;
            color: #fff;
        }
        .admin-action-btn.delete:hover {
            background: #a31515;
        }
        .edit-row input {
            width: 90%;
            padding: 4px 6px;
            border-radius: 4px;
            border: 1px solid #b3c6e0;
            font-size: 1rem;
        }
        #adminCreateMsg {
            text-align: center;
        }
        @media (max-width: 768px) {
            .header {
                padding: 8px 16px;
                height: 56px;
            }
            
            .header img {
                height: 36px;
                margin-right: 12px;
            }
            
            .header-title {
                font-size: 1.4rem;
            }
            
            .header div {
                margin-left: auto !important;
                flex-direction: column;
                gap: 8px;
            }
            
            .layout {
                padding-top: 56px;
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                top: 0;
                left: 0;
                padding: 16px;
                flex-direction: row;
                overflow-x: auto;
                gap: 8px;
            }
            
            .sidebar a {
                white-space: nowrap;
                margin-bottom: 0;
                margin-top: 0;
                padding: 8px 12px;
                font-size: 0.9rem;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
                height: auto;
            }
            
            .admin-form-container,
            .admin-table-container {
                padding: 24px 20px 20px 20px;
                margin-bottom: 24px;
            }
            
            .admin-form-container h2 {
                font-size: 1.3rem;
            }
            
            .form-group input {
                padding: 0.6rem;
                font-size: 0.95rem;
            }
            
            .login-btn {
                padding: 0.8rem;
                font-size: 1rem;
            }
            
            .admin-table-container h3 {
                font-size: 1.2rem;
            }
            
            table.admin-table th,
            table.admin-table td {
                padding: 8px 6px;
                font-size: 0.9rem;
            }
            
            .admin-action-btn {
                padding: 4px 12px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 6px 12px;
                height: 48px;
            }
            
            .header img {
                height: 28px;
                margin-right: 8px;
            }
            
            .header-title {
                font-size: 1.2rem;
            }
            
            .header div {
                font-size: 0.8rem;
            }
            
            .layout {
                padding-top: 48px;
            }
            
            .sidebar {
                padding: 12px;
                gap: 6px;
            }
            
            .sidebar a {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            
            .main-content {
                padding: 16px;
            }
            
            .admin-form-container,
            .admin-table-container {
                padding: 20px 16px 16px 16px;
                margin-bottom: 20px;
            }
            
            .admin-form-container h2 {
                font-size: 1.2rem;
            }
            
            .form-group input {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
            
            .login-btn {
                padding: 0.7rem;
                font-size: 0.95rem;
            }
            
            .admin-table-container h3 {
                font-size: 1.1rem;
            }
            
            table.admin-table th,
            table.admin-table td {
                padding: 6px 4px;
                font-size: 0.8rem;
            }
            
            .admin-action-btn {
                padding: 3px 8px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 900px) {
            .main-content, .admin-form-container, .admin-table-container {
                margin-left: 0;
                padding: 12px 2vw 12px 2vw;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                flex-direction: row;
                padding: 16px 0 0 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; align-items: center;">
            <img src="../assets/image/PESO Logo circle.png" alt="Logo">
            <span class="header-title">WorkConnect</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-right: 20px;">
            <div style="width: 28px; height: 28px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #233a8b; font-weight: bold;">
                👤
            </div>
            <span id="adminUsername" style="font-size: 1rem; font-weight: 500;">Welcome, Admin</span>
        </div>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="Dashboard.php">📊 DASHBOARD</a>
            <a href="job.php">👥 JOB APPLICANTS</a>
            <a href="skill.php">🛠️ SKILL REGISTRY</a>
            <a href="btec.php">📈 BTEC MONTHLY REPORT</a>
            <a href="#" class="active">➕ ADD ACCOUNT</a>
            <a href="analytics.php">📊 Analytics</a>
            <a href="logout.php" class="logout">🚪 Logout</a>
        </div>
        <div class="main-content">
            <div class="admin-form-container">
                <h2>Create New Admin Account</h2>
                <form id="createAdminForm" autocomplete="off">
                    <div class="form-group">
                        <label for="newUsername">Username</label>
                        <input id="newUsername" name="username" type="text" required>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">Password</label>
                        <input id="newPassword" name="password" type="text" required>
                    </div>
                    <button type="submit" class="login-btn">Create Admin</button>
                    <div id="adminCreateMsg" style="margin-top:1rem;font-weight:bold;"></div>
                </form>
            </div>
            <div class="admin-table-container">
                <h3>Admin Accounts</h3>
                <table class="admin-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Admin rows will be inserted here -->
                    </tbody>
                </table>
            </div>
            <script>
            // Session check and update UI
            fetch('session_check.php')
                .then(r => r.json())
                .then(d => {
                    // Update username display
                    document.getElementById('adminUsername').textContent = 'Welcome, ' + d.username;
                })
                .catch(() => {
                    console.error('Session check failed');
                });
            // Create admin
            document.getElementById('createAdminForm').addEventListener('submit', function(e) {
                e.preventDefault();
                var msg = document.getElementById('adminCreateMsg');
                msg.textContent = '';
                fetch('add_admin.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({
                        username: document.getElementById('newUsername').value.trim(),
                        password: document.getElementById('newPassword').value.trim()
                    })
                })
                .then(r => r.json())
                .then(d => {
                    msg.textContent = d.message;
                    msg.style.color = d.success ? '#388e3c' : '#d32f2f';
                    if (d.success) {
                        document.getElementById('createAdminForm').reset();
                        loadAdmins();
                    }
                })
                .catch(() => {
                    msg.textContent = 'Server error.';
                    msg.style.color = '#d32f2f';
                });
            });
            // Load admin accounts
            function loadAdmins() {
                fetch('admin_accounts.php')
                    .then(r => r.json())
                    .then(admins => {
                        var tbody = document.querySelector('#adminTable tbody');
                        tbody.innerHTML = '';
                        admins.forEach(function(admin) {
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + admin.id + '</td>' +
                                '<td>' + (admin.username === 'Admin' ? '<b>' + admin.username + '</b>' : '<span class="admin-username">' + admin.username + '</span>') + '</td>' +
                                '<td>' +
                                (admin.username === 'Admin' ? '' :
                                    '<button class="admin-action-btn edit" data-id="' + admin.id + '" data-username="' + admin.username + '">Edit</button>' +
                                    '<button class="admin-action-btn delete" data-id="' + admin.id + '">Delete</button>'
                                ) +
                                '</td>';
                            tbody.appendChild(tr);
                        });
                    });
            }
            loadAdmins();
            // Delete admin
            document.getElementById('adminTable').addEventListener('click', function(e) {
                // Handle delete button (only if it has 'delete' class but NOT 'cancel')
                if (e.target.classList.contains('delete') && !e.target.classList.contains('cancel')) {
                    var id = e.target.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this admin?')) {
                        fetch('delete_admin.php', {
                            method: 'POST',
                            headers: {'Content-Type':'application/json'},
                            body: JSON.stringify({id: id})
                        })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) loadAdmins();
                            else alert(d.message || 'Delete failed.');
                        });
                    }
                }
                // Handle edit button (only if it has 'edit' class but NOT 'save')
                else if (e.target.classList.contains('edit') && !e.target.classList.contains('save')) {
                    var tr = e.target.closest('tr');
                    var id = e.target.getAttribute('data-id');
                    var username = e.target.getAttribute('data-username');
                    tr.classList.add('edit-row');
                    tr.innerHTML =
                        '<td>' + id + '</td>' +
                        '<td><input type="text" value="' + username + '" class="edit-username"></td>' +
                        '<td>' +
                        '<input type="text" placeholder="New Password" class="edit-password"> ' +
                        '<button class="admin-action-btn edit save" data-id="' + id + '">Save</button>' +
                        '<button class="admin-action-btn delete cancel">Cancel</button>' +
                        '</td>';
                }
                // Handle save button
                else if (e.target.classList.contains('save')) {
                    var tr = e.target.closest('tr');
                    var id = e.target.getAttribute('data-id');
                    var username = tr.querySelector('.edit-username').value.trim();
                    var password = tr.querySelector('.edit-password').value.trim();
                    if (!username || !password) {
                        alert('Username and password required.');
                        return;
                    }
                    fetch('edit_admin.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({id: id, username: username, password: password})
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) loadAdmins();
                        else alert(d.message || 'Update failed.');
                    });
                }
                // Handle cancel button
                else if (e.target.classList.contains('cancel')) {
                    loadAdmins();
                }
            });
            document.querySelectorAll('.logout').forEach(function(btn) {
              btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('logoutModal').style.display = 'flex';
              });
            });

            // Logout modal functionality - wait for DOM to be ready
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('confirmLogoutBtn').onclick = function() {
                    // Show loading state
                    const confirmBtn = document.getElementById('confirmLogoutBtn');
                    const cancelBtn = document.getElementById('cancelLogoutBtn');
                    const originalText = confirmBtn.textContent;
                    
                    // Disable buttons and show loading
                    confirmBtn.disabled = true;
                    cancelBtn.disabled = true;
                    confirmBtn.innerHTML = '<div style="display: inline-block; width: 16px; height: 16px; border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 8px;"></div>Logging out...';
                    
                    // Add spinner animation
                    const style = document.createElement('style');
                    style.textContent = `
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                    `;
                    document.head.appendChild(style);
                    
                    // Small delay to show loading state, then redirect
                    setTimeout(() => {
                        window.location.href = 'logout.php';
                    }, 1000);
                };

                document.getElementById('cancelLogoutBtn').onclick = function() {
                    document.getElementById('logoutModal').style.display = 'none';
                };

                // Close modal on outside click
                window.onclick = function(e) {
                    if (e.target === document.getElementById('logoutModal')) {
                        document.getElementById('logoutModal').style.display = 'none';
                    }
                };
            });
            </script>

            <!-- Logout Modal -->
            <div id="logoutModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(30,40,60,0.18);justify-content:center;align-items:center;">
                <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(25,118,210,0.18);padding:32px 28px 24px 28px;max-width:400px;width:100%;margin:0 auto;text-align:center;">
                    <div style="font-size:3rem;margin-bottom:16px;">🚪</div>
                    <h3 style="margin-top:0;color:#233a8b;font-size:1.3rem;font-weight:bold;margin-bottom:12px;">Confirm Logout</h3>
                    <p style="color:#666;margin-bottom:24px;font-size:1rem;">Are you sure you want to logout from your account?</p>
                    <div style="display:flex;gap:12px;justify-content:center;">
                        <button id="confirmLogoutBtn" style="background:#f44336;color:#fff;border:none;border-radius:8px;padding:12px 24px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.2s ease;">Yes, Logout</button>
                        <button id="cancelLogoutBtn" style="background:#bdbdbd;color:#1a3876;border:none;border-radius:8px;padding:12px 24px;font-weight:600;font-size:1rem;cursor:pointer;transition:all 0.2s ease;">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
