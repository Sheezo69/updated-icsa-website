<?php
declare(strict_types=1);

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/auth.php';
require_admin();

$conn = get_db_connection();
$admin = get_current_admin();
$csrf_token = generate_csrf_token();

// Stats
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages");
$stats['total'] = (int)($result->fetch_assoc()['count'] ?? 0);

$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'");
$stats['new'] = (int)($result->fetch_assoc()['count'] ?? 0);

$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'in_progress'");
$stats['in_progress'] = (int)($result->fetch_assoc()['count'] ?? 0);

$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'resolved'");
$stats['resolved'] = (int)($result->fetch_assoc()['count'] ?? 0);

$course_files = glob(__DIR__ . '/../courses/*.html');
$stats['courses'] = count($course_files);

// Get inquiries by date for chart (last 7 days)
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contact_messages WHERE DATE(created_at) = ?");
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $chart_data[] = [
        'date' => date('M j', strtotime($date)),
        'count' => $count
    ];
}

// Recent inquiries
$recent = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");

// Top courses by interest
$top_courses = $conn->query("SELECT course_interest, COUNT(*) as count FROM contact_messages WHERE course_interest IS NOT NULL AND course_interest != '' GROUP BY course_interest ORDER BY count DESC LIMIT 5");

// Get role from session
$role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ICSA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --purple: #a855f7;
            --cyan: #06b6d4;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            overflow-x: hidden;
            max-width: 100vw;
        }
        
        .layout { 
            display: flex; 
            min-height: 100vh;
            max-width: 100vw;
            overflow-x: hidden;
        }
        
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
        .sidebar-header p { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; }
        
        .nav { padding: 1rem 0; }
        
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
            max-width: calc(100vw - 260px);
            overflow-x: hidden;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .header h1 { font-size: 1.75rem; font-weight: 700; }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem 0.5rem 0.5rem;
            background: var(--bg-card);
            border-radius: 24px;
            border: 1px solid var(--border);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .user-name { font-weight: 500; font-size: 0.9rem; }
        .user-role { font-size: 0.75rem; color: var(--text-muted); }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
            width: 100%;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-hover) 100%);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--cyan));
        }
        
        .stat-card.warning::before { background: linear-gradient(90deg, var(--warning), #f97316); }
        .stat-card.success::before { background: linear-gradient(90deg, var(--success), #10b981); }
        .stat-card.purple::before { background: linear-gradient(90deg, var(--purple), #c084fc); }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.blue { background: rgba(59, 130, 246, 0.15); color: var(--accent); }
        .stat-icon.orange { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
        .stat-icon.green { background: rgba(34, 197, 94, 0.15); color: var(--success); }
        .stat-icon.purple { background: rgba(168, 85, 247, 0.15); color: var(--purple); }
        
        .stat-trend {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            background: rgba(34, 197, 94, 0.15);
            color: var(--success);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
            width: 100%;
        }
        
        .chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .chart-container {
            position: relative;
            height: 250px;
        }
        
        .pie-legend {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            width: 100%;
        }
        
        .section-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        
        .section-header h3 {
            font-size: 1rem;
            font-weight: 600;
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
        
        .btn-sm { padding: 0.5rem 0.875rem; font-size: 0.8125rem; }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }
        
        .btn-outline:hover { background: var(--bg-hover); }
        
        .activity-list { display: flex; flex-direction: column; }
        
        .activity-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s;
        }
        
        .activity-item:last-child { border-bottom: none; }
        
        .activity-item:hover { background: var(--bg-hover); }
        
        .activity-info h4 {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .activity-info p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .activity-meta { text-align: right; }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
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
        
        .activity-time {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
        
        .course-list {
            display: flex;
            flex-direction: column;
        }
        
        .course-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        
        .course-item:last-child { border-bottom: none; }
        
        .course-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .course-rank {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            background: var(--bg);
            color: var(--text-muted);
        }
        
        .course-rank.top { background: var(--accent); color: white; }
        
        .course-name {
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .course-count {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }
        
        @media (max-width: 1400px) {
            .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 1200px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
            .bottom-grid { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: flex;
                position: fixed;
                top: 1rem;
                right: 1rem;
                z-index: 1001;
                background: var(--accent);
                border: none;
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 8px;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 1.2rem;
            }
            .sidebar { 
                display: none; 
                position: fixed;
                z-index: 1000;
                width: 280px;
            }
            .sidebar.active { display: flex; }
            .main { margin-left: 0; padding: 1rem; padding-top: 4rem; }
            th, td { padding: 0.75rem; font-size: 0.85rem; }
            .filters { flex-direction: column; }
            .filters input, .filters select { width: 100%; }
        }
        
        @media (min-width: 769px) {
            .mobile-menu-btn { display: none; }
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
                        <h2>Admin</h2>
                    </div>
                </div>
                <nav class="nav">
                    <a href="dashboard.php" class="nav-item active"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="inquiries.php" class="nav-item"><i class="fas fa-envelope"></i> Inquiries</a>
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
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button type="submit" class="website-link" style="width: 100%; border: none; cursor: pointer; background: var(--bg-card);">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </aside>
        
        <main class="main">
            <div class="header">
                <h1>Dashboard</h1>
                <div class="user-menu">
                    <div class="user-avatar"><?php echo strtoupper(substr($admin['username'], 0, 1)); ?></div>
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($admin['username']); ?></div>
                        <div class="user-role"><?php echo $role === 'admin' ? 'Admin' : 'User'; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon blue"><i class="fas fa-envelope"></i></div>
                        <span class="stat-trend">Total</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Inquiries</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                        <span class="stat-trend">New</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['new']; ?></div>
                    <div class="stat-label">New Inquiries</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                        <span class="stat-trend"><?php echo $stats['total'] > 0 ? round(($stats['resolved'] / $stats['total']) * 100) : 0; ?>%</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['resolved']; ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-header">
                        <div class="stat-icon purple"><i class="fas fa-book"></i></div>
                    </div>
                    <div class="stat-value"><?php echo $stats['courses']; ?></div>
                    <div class="stat-label">Courses</div>
                </div>
            </div>
            
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Inquiries Overview</h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Last 7 Days</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="inquiryChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Status Breakdown</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="pie-legend">
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #3b82f6;"></span>
                            <span class="legend-name">New: <?php echo $stats['new']; ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #f59e0b;"></span>
                            <span class="legend-name">In Progress: <?php echo $stats['in_progress']; ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #22c55e;"></span>
                            <span class="legend-name">Resolved: <?php echo $stats['resolved']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bottom-grid">
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-clock" style="margin-right: 0.5rem; color: var(--accent);"></i>Recent Inquiries</h3>
                        <a href="inquiries.php" class="btn btn-outline btn-sm">View All</a>
                    </div>
                    <div class="activity-list">
                        <?php if ($recent->num_rows > 0): ?>
                            <?php while ($row = $recent->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div class="activity-info">
                                    <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($row['course_interest'] ?? 'General Inquiry'); ?></p>
                                </div>
                                <div class="activity-meta">
                                    <span class="status-badge <?php echo $row['status'] ?? 'new'; ?>"><?php echo ucfirst(str_replace('_', ' ', $row['status'] ?? 'new')); ?></span>
                                    <div class="activity-time"><?php echo date('M j, g:i A', strtotime($row['created_at'])); ?></div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">No recent inquiries</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-fire" style="margin-right: 0.5rem; color: var(--warning);"></i>Top Courses by Interest</h3>
                    </div>
                    <div class="course-list">
                        <?php if ($top_courses && $top_courses->num_rows > 0): ?>
                            <?php $rank = 1; while ($course = $top_courses->fetch_assoc()): ?>
                            <div class="course-item">
                                <div class="course-info">
                                    <div class="course-rank <?php echo $rank <= 3 ? 'top' : ''; ?>"><?php echo $rank; ?></div>
                                    <span class="course-name"><?php echo htmlspecialchars($course['course_interest']); ?></span>
                                </div>
                                <span class="course-count"><?php echo $course['count']; ?> inquiries</span>
                            </div>
                            <?php $rank++; endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">No course data yet</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        const ctx = document.getElementById('inquiryChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($chart_data, 'date')); ?>,
                datasets: [{
                    label: 'Inquiries',
                    data: <?php echo json_encode(array_column($chart_data, 'count')); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { 
                            color: '#888', 
                            font: { size: 11 },
                            stepSize: 1,
                            precision: 0
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#888', font: { size: 11 } }
                    }
                }
            }
        });
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['New', 'In Progress', 'Resolved'],
                datasets: [{
                    data: [<?php echo $stats['new']; ?>, <?php echo $stats['in_progress']; ?>, <?php echo $stats['resolved']; ?>],
                    backgroundColor: ['#3b82f6', '#f59e0b', '#22c55e'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '65%'
            }
        });
    </script>
</body>
</html>
