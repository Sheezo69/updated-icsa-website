<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$debug = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Debug: Check if user exists
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM admins WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) {
            $debug = "User '$username' not found in database";
        } else {
            $debug = "User found: " . $user['username'] . " (Role: " . $user['role'] . ")";
            $debug .= " | Password verify: " . (password_verify($password, $user['password_hash']) ? 'YES' : 'NO');
        }
    } catch (Throwable $e) {
        $debug = "DB Error: " . $e->getMessage();
    }
    
    // Rate limiting check
    sleep(1);
    
    $result = verify_admin_login($username, $password);
    
    if ($result && isset($result['error'])) {
        $error = $result['error'];
    } elseif ($result) {
        create_admin_session($result);
        
        // Redirect to intended page or dashboard
        $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ICSA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0d2137 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header img {
            width: 80px;
            height: auto;
            margin-bottom: 1rem;
        }
        
        .login-header h1 {
            color: #1e3a5f;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #1e3a5f;
        }
        
        .form-input::placeholder {
            color: #9ca3af;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: #1e3a5f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-login:hover {
            background: #0d2137;
        }
        
        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .error-message::before {
            content: '⚠';
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.75rem;
            color: #9ca3af;
        }
        
        .input-icon-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
        }
        
        .form-input.with-icon {
            padding-left: 2.75rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Portal</h1>
            <p>ICSA Website Management</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($debug): ?>
        <div style="background: #1a1a1a; border: 1px solid #2a2a2a; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.85rem; color: #888;">
            <strong>Debug:</strong> <?= htmlspecialchars($debug) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div class="input-icon-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-input with-icon" 
                           placeholder="Enter your username"
                           required 
                           autocomplete="username"
                           autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-icon-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-input with-icon" 
                           placeholder="Enter your password"
                           required 
                           autocomplete="current-password">
                </div>
            </div>
            
            <button type="submit" class="btn-login">Sign In</button>
        </form>
        
        <div class="login-footer">
            <p>Use the current admin credentials configured for this environment.</p>
            <p>Manage password changes from the Laravel admin settings page.</p>
        </div>
    </div>
</body>
</html>
