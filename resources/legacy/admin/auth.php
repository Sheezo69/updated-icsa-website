<?php
declare(strict_types=1);

session_start();

/**
 * Admin Authentication Helper
 * Provides secure session management and authentication checks
 */

require_once __DIR__ . '/../api/db.php';

const SESSION_LIFETIME = 3600; // 1 hour
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_DURATION = 900; // 15 minutes

/**
 * Check if admin is logged in and session is valid
 */
function is_admin_logged_in(): bool {
    if (empty($_SESSION['admin_id']) || empty($_SESSION['session_token'])) {
        return false;
    }
    
    // Verify session in database
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT s.id, a.role FROM admin_sessions s JOIN admins a ON s.admin_id = a.id WHERE s.id = ? AND s.admin_id = ? AND s.ip_address = ?");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->bind_param('sis', $_SESSION['session_token'], $_SESSION['admin_id'], $ip);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $session = $result->fetch_assoc();
        $_SESSION['admin_role'] = $session['role'] ?? 'staff';
        
        // Update last activity
        $stmt = $conn->prepare("UPDATE admin_sessions SET last_activity = NOW() WHERE id = ?");
        $stmt->bind_param('s', $_SESSION['session_token']);
        $stmt->execute();
        
        return true;
    } catch (Throwable $e) {
        error_log('Session verification failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Require admin login - redirect to login if not authenticated
 */
function require_admin(): void {
    if (!is_admin_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Verify login credentials
 */
function verify_admin_login(string $username, string $password): ?array {
    try {
        $conn = get_db_connection();
        
        // Get admin record
        $stmt = $conn->prepare("SELECT id, username, password_hash, login_attempts, locked_until FROM admins WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $admin = $result->fetch_assoc();
        
        // Check if account is locked
        if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
            return ['error' => 'Account locked. Try again in ' . ceil((strtotime($admin['locked_until']) - time()) / 60) . ' minutes.'];
        }
        
        // Verify password
        if (!password_verify($password, $admin['password_hash'])) {
            // Increment failed attempts
            $attempts = $admin['login_attempts'] + 1;
            $locked_until = $attempts >= MAX_LOGIN_ATTEMPTS ? date('Y-m-d H:i:s', time() + LOCKOUT_DURATION) : null;
            
            $stmt = $conn->prepare("UPDATE admins SET login_attempts = ?, locked_until = ? WHERE id = ?");
            $stmt->bind_param('isi', $attempts, $locked_until, $admin['id']);
            $stmt->execute();
            
            return null;
        }
        
        // Reset login attempts and update last login
        $stmt = $conn->prepare("UPDATE admins SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $stmt->bind_param('i', $admin['id']);
        $stmt->execute();
        
        return $admin;
        
    } catch (Throwable $e) {
        error_log('Login verification failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * Create secure session after successful login
 */
function create_admin_session(array $admin): void {
    // Generate secure session token
    $session_token = bin2hex(random_bytes(32));
    
    // Store session in database
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("INSERT INTO admin_sessions (id, admin_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $stmt->bind_param('siss', $session_token, $admin['id'], $ip, $ua);
        $stmt->execute();
    } catch (Throwable $e) {
        error_log('Session creation failed: ' . $e->getMessage());
    }
    
    // Set session variables
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_role'] = $admin['role'] ?? 'staff';
    $_SESSION['session_token'] = $session_token;
    $_SESSION['last_regeneration'] = time();
    $_SESSION['login_time'] = time();
}

/**
 * Logout admin and destroy session
 */
function admin_logout(): void {
    // Remove session from database
    if (!empty($_SESSION['session_token'])) {
        try {
            $conn = get_db_connection();
            $stmt = $conn->prepare("DELETE FROM admin_sessions WHERE id = ?");
            $stmt->bind_param('s', $_SESSION['session_token']);
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('Session cleanup failed: ' . $e->getMessage());
        }
    }
    
    // Clear session
    $_SESSION = [];
    session_destroy();
}

/**
 * Get current admin info
 */
function get_current_admin(): ?array {
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, username, email, role, last_login FROM admins WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Generate CSRF token
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if current user is admin (owner), not just staff
 */
function is_admin_owner(): bool {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}

/**
 * Require admin owner role - redirect if not admin
 */
function require_admin_owner(): void {
    require_admin();
    if (!is_admin_owner()) {
        header('Location: dashboard.php?error=unauthorized');
        exit;
    }
}

/**
 * Clean up old sessions (call periodically)
 */
function cleanup_old_sessions(): void {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("DELETE FROM admin_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->bind_param('i', SESSION_LIFETIME);
        $stmt->execute();
    } catch (Throwable $e) {
        error_log('Session cleanup failed: ' . $e->getMessage());
    }
}
