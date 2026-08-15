<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();

$conn = get_db_connection();
$admin = get_current_admin();
$role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'staff';
$csrf_token = generate_csrf_token();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All fields are required';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password_hash FROM admins WHERE id = ?");
            $stmt->bind_param('i', $admin['id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!password_verify($current_password, $result['password_hash'])) {
                $error = 'Current password is incorrect';
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                $stmt->bind_param('si', $new_hash, $admin['id']);
                
                if ($stmt->execute()) {
                    $message = 'Password updated successfully';
                } else {
                    $error = 'Failed to update password';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | ICSA Admin</title>
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
            margin-bottom: 2rem;
        }
        
        .header h1 { font-size: 1.75rem; font-weight: 700; }
        
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            max-width: 500px;
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
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
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.95rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        
        .btn-primary:hover { background: var(--accent-hover); }
        
        .info-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-box p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        
        .info-box p:last-child { margin-bottom: 0; }
        
        .info-box strong { color: var(--text); }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 1rem; }
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
                        <h2><?php echo $role === 'admin' ? 'Admin' : 'User'; ?></h2>
                    </div>
                </div>
                <nav class="nav">
                    <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="inquiries.php" class="nav-item"><i class="fas fa-envelope"></i> Inquiries</a>
                    <a href="courses.php" class="nav-item"><i class="fas fa-graduation-cap"></i> Courses</a>
                    <?php if ($role === 'admin'): ?>
                    <a href="users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
                    <?php endif; ?>
                    <a href="settings.php" class="nav-item active"><i class="fas fa-cog"></i> Settings</a>
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
                <h1>Settings</h1>
            </div>
            
            <div class="card">
                <div class="card-title"><i class="fas fa-user-shield"></i> Account Security</div>
                
                <div class="info-box">
                    <p><strong>Username:</strong> <?= htmlspecialchars($admin['username']) ?></p>
                    <p><strong>Role:</strong> Administrator</p>
                    <p style="font-size: 0.85rem; margin-top: 0.75rem;">Change your password below. Use a strong password with at least 8 characters.</p>
                </div>
                
                <?php if ($message): ?>
                <div class="alert success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required placeholder="Enter current password">
                    </div>
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required placeholder="Enter new password (min 8 chars)">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required placeholder="Confirm new password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Password</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
