<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin_owner();

$conn = get_db_connection();
$admin = get_current_admin();
$csrf_token = generate_csrf_token();

$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = in_array($_POST['role'], ['admin', 'staff']) ? $_POST['role'] : 'staff';
            
            if (empty($username) || empty($password)) {
                $error = 'Username and password are required';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $error = 'Username can only contain letters, numbers, and underscores';
            } else {
                // Check if username exists
                $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
                $stmt->bind_param('s', $username);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'Username already exists';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO admins (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param('ssss', $username, $hash, $email, $role);
                    if ($stmt->execute()) {
                        $message = 'User created successfully';
                    } else {
                        $error = 'Failed to create user';
                    }
                }
            }
        }
        
        if ($action === 'delete' && !empty($_POST['user_id'])) {
            $user_id = (int)$_POST['user_id'];
            // Prevent deleting yourself
            if ($user_id === $admin['id']) {
                $error = 'You cannot delete your own account';
            } else {
                $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
                $stmt->bind_param('i', $user_id);
                if ($stmt->execute()) {
                    $message = 'User deleted successfully';
                } else {
                    $error = 'Failed to delete user';
                }
            }
        }
        
        if ($action === 'reset_password' && !empty($_POST['user_id'])) {
            $user_id = (int)$_POST['user_id'];
            $new_password = $_POST['new_password'] ?? '';
            
            if (strlen($new_password) < 6) {
                $error = 'Password must be at least 6 characters';
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                $stmt->bind_param('si', $hash, $user_id);
                if ($stmt->execute()) {
                    $message = 'Password reset successfully';
                } else {
                    $error = 'Failed to reset password';
                }
            }
        }
    }
}

// Get all users
$users = $conn->query("SELECT id, username, email, role, last_login, created_at FROM admins ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | ICSA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #0a0a0a;
            --bg-card: #141414;
            --bg-hover: #1a1a1a;
            --border: #2a2a2a;
            --text: #ffffff;
            --text-muted: #888888;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        
        .layout { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 260px;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        
        .sidebar-header h2 { font-size: 1.25rem; font-weight: 700; }
        .sidebar-header p { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-logo img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        
        .sidebar-logo h2 { font-size: 1.5rem; font-weight: 700; }
        
        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            margin-top: auto;
        }
        
        .website-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .website-link:hover {
            border-color: var(--accent);
            background: var(--bg-hover);
        }
        
        .website-link i { color: var(--accent); }
        
        .nav { padding: 1rem 0; }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover, .nav-item.active {
            background: var(--bg-hover);
            color: var(--text);
            border-left-color: var(--accent);
        }
        
        .nav-item i { width: 24px; margin-right: 0.75rem; }
        
        .main {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .header h1 { font-size: 1.75rem; font-weight: 700; }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }
        
        .btn-outline:hover { background: var(--bg-hover); }
        
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        
        .btn-primary:hover { background: var(--accent-hover); }
        
        .btn-danger {
            background: transparent;
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        .btn-danger:hover { background: var(--danger); color: white; }
        
        .btn-sm { padding: 0.5rem 0.75rem; font-size: 0.8rem; }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert.success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: var(--success);
        }
        
        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
        }
        
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            color: var(--accent);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.375rem;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.625rem 0.875rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.9rem;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        .hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
        
        /* Table */
        .table-container {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        th {
            background: var(--bg-hover);
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        
        tr:hover { background: rgba(255,255,255,0.02); }
        
        .user-name {
            font-weight: 600;
        }
        
        .user-email {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-badge.admin {
            background: rgba(59, 130, 246, 0.15);
            color: var(--accent);
        }
        
        .role-badge.staff {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.active { display: flex; }
        
        .modal {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            padding: 1.5rem;
        }
        
        .modal h3 {
            margin-bottom: 1rem;
        }
        
        .modal-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            justify-content: flex-end;
        }
        
        .you-badge {
            font-size: 0.7rem;
            background: var(--accent);
            color: white;
            padding: 0.125rem 0.5rem;
            border-radius: 4px;
            margin-left: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 1rem; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div>
                <div class="sidebar-header">
                    <div class="sidebar-logo">
                        <img src="../images/ICSA-LOGO.png" alt="ICSA Logo">
                        <h2>Admin</h2>
                    </div>
                </div>
                <nav class="nav">
                    <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="inquiries.php" class="nav-item"><i class="fas fa-envelope"></i> Inquiries</a>
                    <a href="courses.php" class="nav-item"><i class="fas fa-graduation-cap"></i> Courses</a>
                    <a href="users.php" class="nav-item active"><i class="fas fa-users"></i> Users</a>
                    <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
                </nav>
            </div>
            <div class="sidebar-footer">
                <a href="../index.html" class="website-link" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Website
                </a>
                <form method="POST" action="logout.php" style="margin-top: 0.75rem;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button type="submit" class="website-link" style="width: 100%; border: none; cursor: pointer; background: var(--bg-card);">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </aside>
        
        <main class="main">
            <div class="header">
                <h1>Users</h1>
            </div>
            
            <?php if ($message): ?>
            <div class="alert success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <!-- Create User Form -->
            <div class="card">
                <div class="card-title"><i class="fas fa-user-plus"></i> Create New User</div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" required placeholder="e.g., john_doe">
                            <div class="hint">Letters, numbers, underscores only</div>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="optional@email.com">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role">
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required placeholder="Min 6 characters">
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create User</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Users List -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users->num_rows > 0): ?>
                            <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="user-name">
                                        <?= htmlspecialchars($user['username']) ?>
                                        <?php if ($user['id'] === $admin['id']): ?>
                                        <span class="you-badge">YOU</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($user['email']): ?>
                                    <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="role-badge <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                                <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td><?= $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never' ?></td>
                                <td class="actions">
                                    <?php if ($user['id'] !== $admin['id']): ?>
                                    <button class="btn btn-outline btn-sm" onclick="openResetModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                        <i class="fas fa-key"></i> Reset Password
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="openDeleteModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <h3><i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i> Delete User</h3>
            <p>Are you sure you want to delete <strong id="deleteUsername"></strong>?</p>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModals()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reset Password Modal -->
    <div class="modal-overlay" id="resetModal">
        <div class="modal">
            <h3><i class="fas fa-key" style="color: var(--accent);"></i> Reset Password</h3>
            <p>Enter new password for <strong id="resetUsername"></strong>:</p>
            <form method="POST" id="resetForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="resetUserId">
                <div class="form-group" style="margin-top: 1rem;">
                    <input type="password" name="new_password" required placeholder="New password (min 6 chars)">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModals()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openDeleteModal(id, username) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUsername').textContent = username;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function openResetModal(id, username) {
            document.getElementById('resetUserId').value = id;
            document.getElementById('resetUsername').textContent = username;
            document.getElementById('resetModal').classList.add('active');
        }
        
        function closeModals() {
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
        }
        
        document.querySelectorAll('.modal-overlay').forEach(m => {
            m.addEventListener('click', e => { if (e.target === m) closeModals(); });
        });
    </script>
</body>
</html>
