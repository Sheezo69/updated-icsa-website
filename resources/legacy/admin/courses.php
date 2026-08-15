<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();

$conn = get_db_connection();
$admin = get_current_admin();
$role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'staff';
$csrf_token = generate_csrf_token();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $slug = preg_replace('/[^a-z0-9-]/', '', $_POST['slug'] ?? '');
    $file = __DIR__ . '/../courses/' . $slug . '.html';
    if ($slug && file_exists($file)) {
        unlink($file);
        header('Location: courses.php?deleted=1');
        exit;
    }
}

// Scan course files
$course_dir = __DIR__ . '/../courses/';
$course_files = glob($course_dir . '*.html');

$courses = [];
foreach ($course_files as $file) {
    $slug = basename($file, '.html');
    $content = file_get_contents($file);
    
    preg_match('/<title>(.*?)\s*\|/', $content, $title_match);
    preg_match('/<h1>(.*?)<\/h1>/s', $content, $heading_match);
    preg_match('/<span class=[\'"]hero-label[\'"]>(.*?)<\/span>/', $content, $category_match);
    preg_match('/<i class=[\'"]fas fa-clock[\'"][^>]*><\/i>\s*([^<]+)/', $content, $duration_match);
    
    $courses[] = [
        'slug' => $slug,
        'title' => $title_match[1] ?? ($heading_match[1] ?? 'Unknown'),
        'category' => $category_match[1] ?? 'Uncategorized',
        'duration' => trim($duration_match[1] ?? 'N/A'),
        'file' => $file,
        'modified' => filemtime($file)
    ];
}

usort($courses, fn($a, $b) => strcmp($a['title'], $b['title']));

// Filter by search
$search = $_GET['search'] ?? '';
if ($search) {
    $search_lower = strtolower($search);
    $courses = array_filter($courses, fn($c) => 
        str_contains(strtolower($c['title']), $search_lower) || 
        str_contains(strtolower($c['category']), $search_lower) ||
        str_contains(strtolower($c['slug']), $search_lower)
    );
}

$message = '';
if (isset($_GET['saved'])) $message = 'Course saved successfully';
if (isset($_GET['deleted'])) $message = 'Course deleted';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses | ICSA Admin</title>
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
            --danger: #ef4444;
            --success: #22c55e;
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
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sidebar-logo img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        
        .sidebar-logo h2 {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
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
        }
        
        .website-link i {
            color: var(--accent);
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        
        .sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .sidebar-header p {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
        
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
        
        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
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
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }
        
        .btn-outline:hover { background: var(--bg-hover); }
        
        .btn-danger {
            background: transparent;
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        .btn-danger:hover { background: var(--danger); color: white; }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: var(--success);
        }
        
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.25rem;
        }
        
        .course-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s;
        }
        
        .course-card:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }
        
        .course-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
        }
        
        .course-category {
            font-size: 0.75rem;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }
        
        .course-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .course-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .course-body {
            padding: 1.25rem;
        }
        
        .course-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 1rem; }
            .course-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('active')">
        <i class="fas fa-bars"></i>
    </button>
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
                    <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="inquiries.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'inquiries.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Inquiries</a>
                    <a href="courses.php" class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['courses.php', 'course-edit.php']) ? 'active' : '' ?>"><i class="fas fa-graduation-cap"></i> Courses</a>
                    <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin'): ?>
                    <a href="users.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Users</a>
                    <?php endif; ?>
                    <a href="settings.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a>
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
                <h1>Courses (<?= count($courses) ?>)</h1>
                <a href="course-edit.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Course
                </a>
            </div>
            
            <?php if ($message): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <form method="GET" style="margin-bottom: 1.5rem; display: flex; gap: 1rem;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search courses..." style="flex: 1; padding: 0.625rem 1rem; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: 0.9rem;">
                <button type="submit" class="btn btn-outline">Search</button>
                <?php if ($search): ?>
                <a href="courses.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>
            
            <?php if (count($courses) > 0): ?>
            <div class="course-grid">
                <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <div class="course-header">
                        <div class="course-category"><?= htmlspecialchars($course['category']) ?></div>
                        <div class="course-title"><?= htmlspecialchars($course['title']) ?></div>
                        <div class="course-meta">
                            <span><i class="fas fa-clock"></i> <?= htmlspecialchars($course['duration']) ?></span>
                            <span><i class="fas fa-file"></i> <?= htmlspecialchars($course['slug']) ?>.html</span>
                        </div>
                    </div>
                    <div class="course-body">
                        <div class="course-actions">
                            <a href="../courses/<?= htmlspecialchars($course['slug']) ?>.html" target="_blank" class="btn btn-outline btn-sm">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="course-edit.php?slug=<?= htmlspecialchars($course['slug']) ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this course?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="slug" value="<?= htmlspecialchars($course['slug']) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <p>No courses found</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
