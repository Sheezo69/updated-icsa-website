<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();

$conn = get_db_connection();
$admin = get_current_admin();
$csrf_token = generate_csrf_token();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    $id = (int)($_POST['id'] ?? 0);
    
    if ($_POST['action'] === 'update_status' && $id) {
        $status = in_array($_POST['status'], ['new', 'in_progress', 'resolved']) ? $_POST['status'] : 'new';
        $stmt = $conn->prepare("UPDATE contact_messages SET status = ?, updated_by = ? WHERE id = ?");
        $stmt->bind_param('sii', $status, $admin['id'], $id);
        $stmt->execute();
        header('Location: inquiries.php?updated=1');
        exit;
    }
    
    if ($_POST['action'] === 'delete' && $id) {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        header('Location: inquiries.php?deleted=1');
        exit;
    }
    
    if ($_POST['action'] === 'bulk_delete' && !empty($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        header('Location: inquiries.php?bulk_deleted=1');
        exit;
    }
    
    if ($_POST['action'] === 'bulk_status' && !empty($_POST['ids']) && !empty($_POST['bulk_status'])) {
        $ids = array_map('intval', $_POST['ids']);
        $bulk_status = in_array($_POST['bulk_status'], ['new', 'in_progress', 'resolved']) ? $_POST['bulk_status'] : 'new';
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("UPDATE contact_messages SET status = ? WHERE id IN ($placeholders)");
        $types = 's' . str_repeat('i', count($ids));
        $stmt->bind_param($types, $bulk_status, ...$ids);
        $stmt->execute();
        header('Location: inquiries.php?bulk_updated=1');
        exit;
    }
}

// Filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = [];
$params = [];
$types = '';

if ($status_filter && in_array($status_filter, ['new', 'in_progress', 'resolved'])) {
    $where[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ? OR message LIKE ?)";
    $search_like = "%$search%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= 'sss';
}

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

if ($date_from) {
    $where[] = "created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
    $types .= 's';
}

if ($date_to) {
    $where[] = "created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM contact_messages $where_clause";
$stmt = $conn->prepare($count_sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total / $per_page);

// Get inquiries
$sql = "SELECT * FROM contact_messages $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$per_page, $offset]));
} else {
    $stmt->bind_param('ii', $per_page, $offset);
}
$stmt->execute();
$inquiries = $stmt->get_result();

// Get stats
$stats = [];
foreach (['new', 'in_progress', 'resolved'] as $s) {
    $result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = '$s'");
    $stats[$s] = $result->fetch_assoc()['count'] ?? 0;
}

$message = '';
if (isset($_GET['updated'])) $message = 'Inquiry updated';
if (isset($_GET['deleted'])) $message = 'Inquiry deleted';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=inquiries_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers - simple format
    fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Course', 'Subject', 'Message', 'Status', 'Date']);
    
    // Get all inquiries (no pagination for export)
    $export_sql = "SELECT id, name, email, phone, course_interest, subject, message, status, created_at FROM contact_messages ORDER BY created_at DESC";
    $export_result = $conn->query($export_sql);
    
    while ($row = $export_result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['email'],
            $row['phone'] ?? '',
            $row['course_interest'] ?? '',
            $row['subject'] ?? '',
            $row['message'] ?? '',
            $row['status'],
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit;
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiries | ICSA Admin</title>
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
            --warning: #f59e0b;
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
        
        .sidebar-header h2 { font-size: 1.25rem; font-weight: 700; }
        .sidebar-header p { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; }
        
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
        
        /* Stats */
        .stats-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-pill {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .stat-pill.new { border-color: var(--accent); }
        .stat-pill.in_progress { border-color: var(--warning); }
        .stat-pill.resolved { border-color: var(--success); }
        
        .stat-pill .count {
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .stat-pill.new .count { background: var(--accent); }
        .stat-pill.in_progress .count { background: var(--warning); color: #000; }
        .stat-pill.resolved .count { background: var(--success); }
        
        /* Filters */
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filters input, .filters select {
            padding: 0.625rem 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.9rem;
        }
        
        .filters input:focus, .filters select:focus {
            outline: none;
            border-color: var(--accent);
        }
        
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
        
        .btn-danger {
            background: transparent;
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        .btn-danger:hover { background: var(--danger); color: white; }
        
        .btn-sm { padding: 0.5rem 0.75rem; font-size: 0.8rem; }
        
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
            letter-spacing: 0.5px;
        }
        
        tr:hover { background: rgba(255,255,255,0.02); }
        
        .inquiry-name {
            font-weight: 600;
            color: var(--text);
        }
        
        .inquiry-email {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-badge.new {
            background: rgba(59, 130, 246, 0.15);
            color: var(--accent);
        }
        
        .status-badge.in_progress {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
        }
        
        .status-badge.resolved {
            background: rgba(34, 197, 94, 0.15);
            color: var(--success);
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
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
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 { font-size: 1.125rem; font-weight: 600; }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.25rem;
            cursor: pointer;
        }
        
        .modal-body { padding: 1.25rem; }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.375rem;
            color: var(--text-muted);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.625rem 0.875rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.9rem;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .modal-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
            justify-content: center;
        }
        
        .page-link {
            padding: 0.5rem 0.875rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .page-link:hover { background: var(--bg-hover); }
        .page-link.active { background: var(--accent); border-color: var(--accent); }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: var(--success);
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 1rem; }
            .stats-row { flex-wrap: wrap; }
            th, td { padding: 0.75rem; font-size: 0.85rem; }
            .filters { flex-direction: column; }
            .filters input, .filters select { width: 100%; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('active')">
        <i class="fas fa-bars"></i>
    </button>
    <div class="layout">
        <?php 
        $role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'staff';
        ?>
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
                    <a href="inquiries.php" class="nav-item active"><i class="fas fa-envelope"></i> Inquiries</a>
                    <a href="courses.php" class="nav-item"><i class="fas fa-graduation-cap"></i> Courses</a>
                    <?php if ($role === 'admin'): ?>
                    <a href="users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
                    <?php endif; ?>
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
                <h1>Inquiries (<?= $total ?>)</h1>
            </div>
            
            <?php if ($message): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <div class="stats-row">
                <div class="stat-pill new">
                    <span>New</span>
                    <span class="count"><?= $stats['new'] ?></span>
                </div>
                <div class="stat-pill in_progress">
                    <span>In Progress</span>
                    <span class="count"><?= $stats['in_progress'] ?></span>
                </div>
                <div class="stat-pill resolved">
                    <span>Resolved</span>
                    <span class="count"><?= $stats['resolved'] ?></span>
                </div>
            </div>
            
            <form class="filters" method="GET">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email...">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>
                <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" placeholder="From">
                <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" placeholder="To">
                <button type="submit" class="btn btn-outline">Filter</button>
                <?php if ($search || $status_filter || ($_GET['date_from'] ?? '') || ($_GET['date_to'] ?? '')): ?>
                <a href="inquiries.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
                <a href="inquiries.php?export=csv" class="btn btn-primary"><i class="fas fa-download"></i> Export Excel</a>
            </form>
            
            <form method="POST" id="bulkForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="bulk-actions" style="margin-bottom: 1rem; display: flex; gap: 0.5rem; align-items: center;">
                    <input type="checkbox" id="selectAll" style="margin-right: 0.5rem;">
                    <label for="selectAll" style="margin-right: 1rem; cursor: pointer;">Select All</label>
                    <select name="bulk_status" class="btn btn-outline" style="padding: 0.5rem;">
                        <option value="">Bulk Actions...</option>
                        <option value="new">Mark as New</option>
                        <option value="in_progress">Mark as In Progress</option>
                        <option value="resolved">Mark as Resolved</option>
                    </select>
                    <button type="submit" name="action" value="bulk_status" class="btn btn-primary" onclick="return confirmBulkAction()">Apply</button>
                    <button type="submit" name="action" value="bulk_delete" class="btn btn-danger" onclick="return confirmBulkDelete()">Delete Selected</button>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="selectAllHeader"></th>
                                <th>Name</th>
                                <th>Course Interest</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($inquiries->num_rows > 0): ?>
                                <?php while ($row = $inquiries->fetch_assoc()): ?>
                                <tr data-inquiry='<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
                                    <td><input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="row-checkbox"></td>
                                    <td>
                                        <div class="inquiry-name"><?= htmlspecialchars($row['name']) ?></div>
                                        <div class="inquiry-email"><?= htmlspecialchars($row['email']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($row['course_interest'] ?? 'General') ?></td>
                                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                    <td><span class="status-badge <?= $row['status'] ?? 'new' ?>"><?= ucfirst(str_replace('_', ' ', $row['status'] ?? 'new')) ?></span></td>
                                    <td>
                                        <button type="button" class="btn btn-outline btn-sm view-btn"><i class="fas fa-eye"></i> View</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">No inquiries found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Modal -->
    <div class="modal-overlay" id="modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Inquiry Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="modalForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="inquiryId">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" id="modalName" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" id="modalEmail" readonly>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" id="modalPhone" readonly>
                    </div>
                    <div class="form-group">
                        <label>Course Interest</label>
                        <input type="text" id="modalCourse" readonly>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea id="modalMessage" readonly></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="modalStatus">
                            <option value="new">New</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="deleteInquiry()">Delete</button>
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-outline" style="background: var(--accent); border-color: var(--accent);">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let currentInquiry = null;
        
        function openModal(data) {
            currentInquiry = data;
            document.getElementById('inquiryId').value = data.id;
            document.getElementById('modalName').value = data.name;
            document.getElementById('modalEmail').value = data.email;
            document.getElementById('modalPhone').value = data.phone || '';
            document.getElementById('modalCourse').value = data.course_interest || 'General';
            document.getElementById('modalMessage').value = data.message || '';
            document.getElementById('modalStatus').value = data.status || 'new';
            document.getElementById('modal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
            currentInquiry = null;
        }
        
        function deleteInquiry() {
            if (!currentInquiry) return;
            if (confirm('Delete this inquiry?')) {
                const form = document.getElementById('modalForm');
                form.action.value = 'delete';
                form.submit();
            }
        }
        
        document.getElementById('modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        // Bulk actions
        const selectAll = document.getElementById('selectAll');
        const selectAllHeader = document.getElementById('selectAllHeader');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        
        function toggleAll(checked) {
            rowCheckboxes.forEach(cb => cb.checked = checked);
            selectAll.checked = checked;
            selectAllHeader.checked = checked;
        }
        
        selectAll.addEventListener('change', () => toggleAll(selectAll.checked));
        selectAllHeader.addEventListener('change', () => toggleAll(selectAllHeader.checked));
        
        function confirmBulkAction() {
            const checked = document.querySelectorAll('.row-checkbox:checked').length;
            if (checked === 0) {
                alert('Please select at least one inquiry');
                return false;
            }
            const status = document.querySelector('[name="bulk_status"]').value;
            if (!status) {
                alert('Please select a status');
                return false;
            }
            return confirm(`Change status for ${checked} inquiries?`);
        }
        
        function confirmBulkDelete() {
            const checked = document.querySelectorAll('.row-checkbox:checked').length;
            if (checked === 0) {
                alert('Please select at least one inquiry');
                return false;
            }
            return confirm(`Delete ${checked} inquiries? This cannot be undone.`);
        }
        // View button click handlers
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const data = JSON.parse(row.dataset.inquiry);
                openModal(data);
            });
        });
    </script>
</body>
</html>
