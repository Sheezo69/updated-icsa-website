<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();

$conn = get_db_connection();
$admin = get_current_admin();
$csrf_token = generate_csrf_token();

$slug = $_GET['slug'] ?? '';
$is_edit = !empty($slug);

$course_file = __DIR__ . '/../courses/' . preg_replace('/[^a-z0-9-]/', '', $slug) . '.html';

// Template
$template = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{TITLE}} | ICSA Kuwait</title>
    <meta name="description" content="{{DESCRIPTION}}">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    {{HEADER}}
    
    <section class="course-detail-hero">
        <div class="container">
            <div class="course-back-row">
                <a href="../courses.html" class="course-back-link"><i class="fas fa-arrow-left"></i> Back to Courses</a>
            </div>
            <div class="course-detail-grid">
                <div class="course-detail-content">
                    <span class="hero-label">{{BADGE}}</span>
                    <h1>{{TITLE}}</h1>
                    <div class="course-detail-meta">
                        <span class="course-detail-meta-item"><i class="fas fa-clock"></i> {{DURATION}}</span>
                        <span class="course-detail-meta-item"><i class="fas fa-signal"></i> {{CERTIFICATION}}</span>
                        <span class="course-detail-meta-item"><i class="fas fa-certificate"></i> {{DIPLOMA_TYPE}}</span>
                    </div>
                    <p class="course-detail-description">{{DESCRIPTION}}</p>
                </div>
                <aside class="course-detail-card">
                    <img src="{{IMAGE}}" alt="{{TITLE}}" class="course-detail-image" loading="lazy">
                    <div class="course-detail-price">
                        <div class="price">{{PRICE}}</div>
                        <div class="price-note">{{PRICE_NOTE}}</div>
                    </div>
                    <div class="course-detail-features">
                        <h4>Program Highlights</h4>
                        <ul>
                            {{HIGHLIGHTS}}
                        </ul>
                    </div>
                    <a href="../contact.html?course={{SLUG}}" class="btn btn-primary">Enroll Now</a>
                </aside>
            </div>
        </div>
    </section>

    <section class="course-content-section">
        <div class="container">
            <div class="course-content-grid">
                <article class="tab-panel course-block">
                    <h3>Program Overview</h3>
                    <p>{{OVERVIEW}}</p>
                </article>
                <article class="tab-panel course-block">
                    <h3>What You Will Learn</h3>
                    <ul>
                        {{LEARNING_OUTCOMES}}
                    </ul>
                </article>
                <article class="tab-panel course-block">
                    <h3>Who Should Enroll</h3>
                    <ul>
                        {{TARGET_AUDIENCE}}
                    </ul>
                </article>
                <article class="tab-panel course-block">
                    <h3>Career Opportunities</h3>
                    <div class="career-list">
                        {{CAREERS}}
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{INQUIRY_SECTION}}
    {{FOOTER}}
</body>
</html>';

// Load header/footer from existing course
$sample_course = file_get_contents(__DIR__ . '/../courses/uk-diploma-business-management.html');
preg_match('/<div class=\'top-bar\'>.*?<\/header>/s', $sample_course, $header_match);
$header_html = $header_match[0] ?? '';
preg_match('/<section class=\'inquiry-section\'>.*?<\/section>/s', $sample_course, $inquiry_match);
$inquiry_html = $inquiry_match[0] ?? '';
preg_match('/<footer class=\'footer\'>.*?<\/footer>/s', $sample_course, $footer_match);
$footer_html = $footer_match[0] ?? '';

// Helper: Convert HTML list to plain text
function html_to_plain($html) {
    // Remove <li> tags and get text content
    $text = preg_replace('/<li[^>]*>\s*<i[^>]*><\/i>\s*/i', '', $html);
    $text = preg_replace('/<li[^>]*>/i', '', $text);
    $text = preg_replace('/<\/li>/i', "\n", $text);
    $text = preg_replace('/<span[^>]*class=["\']career-tag["\'][^>]*>/i', '', $text);
    $text = preg_replace('/<\/span>/i', "\n", $text);
    return trim($text);
}

// Helper: Convert plain text to HTML list
function plain_to_list($text) {
    $lines = array_filter(array_map('trim', explode("\n", $text)));
    $html = '';
    foreach ($lines as $line) {
        if (!empty($line)) {
            $html .= "<li><i class='fas fa-check'></i> " . htmlspecialchars($line) . "</li>\n";
        }
    }
    return $html;
}

// Helper: Convert plain text to career tags
function plain_to_careers($text) {
    $lines = array_filter(array_map('trim', explode("\n", $text)));
    $html = '';
    foreach ($lines as $line) {
        if (!empty($line)) {
            $html .= "<span class='career-tag'>" . htmlspecialchars($line) . "</span>\n";
        }
    }
    return $html;
}

// Default course data
$course = [
    'slug' => '',
    'title' => '',
    'badge' => '',
    'duration' => '',
    'certification' => 'Certified',
    'diploma_type' => '',
    'description' => '',
    'image' => '',
    'price' => 'Contact for Price',
    'price_note' => 'Flexible payment options available',
    'highlights' => "Practical classroom approach\nCertificate on completion\nCareer-focused learning\nInstructor-led guidance",
    'overview' => '',
    'learning_outcomes' => '',
    'target_audience' => '',
    'careers' => ''
];

// Load existing course
if ($is_edit && file_exists($course_file)) {
    $content = file_get_contents($course_file);
    
    preg_match('/<title>(.*?)\s*\|/i', $content, $m);
    $course['title'] = trim($m[1] ?? '');
    
    preg_match('/<span class=["\']hero-label["\']>(.*?)<\/span>/i', $content, $m);
    $course['badge'] = trim($m[1] ?? '');
    
    preg_match('/<h1>(.*?)<\/h1>/s', $content, $m);
    $course['title'] = trim($m[1] ?? $course['title']);
    
    preg_match_all('/<span class=["\']course-detail-meta-item["\']><i class=["\']fas fa-.*?["\']><\/i>\s*([^<]+)/', $content, $meta_matches);
    $course['duration'] = trim($meta_matches[1][0] ?? '');
    $course['certification'] = trim($meta_matches[1][1] ?? 'Certified');
    $course['diploma_type'] = trim($meta_matches[1][2] ?? '');
    
    preg_match('/<p class=["\']course-detail-description["\']>(.*?)<\/p>/s', $content, $m);
    $course['description'] = trim($m[1] ?? '');
    
    preg_match('/<img src=["\']([^"\']+)["\'].*?class=["\']course-detail-image["\']/', $content, $m);
    $course['image'] = trim($m[1] ?? '');
    
    preg_match('/<div class=["\']price["\']>(.*?)<\/div>/', $content, $m);
    $course['price'] = trim($m[1] ?? 'Contact for Price');
    
    preg_match('/<div class=["\']price-note["\']>(.*?)<\/div>/', $content, $m);
    $course['price_note'] = trim($m[1] ?? 'Flexible payment options available');
    
    preg_match('/<div class=["\']course-detail-features["\']>.*?<ul>(.*?)<\/ul>/s', $content, $m);
    $course['highlights'] = html_to_plain($m[1] ?? '');
    
    preg_match('/<h3>Program Overview<\/h3>\s*<p>(.*?)<\/p>/s', $content, $m);
    $course['overview'] = trim($m[1] ?? '');
    
    preg_match('/<h3>What You Will Learn<\/h3>\s*<ul>(.*?)<\/ul>/s', $content, $m);
    $course['learning_outcomes'] = html_to_plain($m[1] ?? '');
    
    preg_match('/<h3>Who Should Enroll<\/h3>\s*<ul>(.*?)<\/ul>/s', $content, $m);
    $course['target_audience'] = html_to_plain($m[1] ?? '');
    
    preg_match('/<h3>Career Opportunities<\/h3>\s*<div class=["\']career-list["\']>(.*?)<\/div>/s', $content, $m);
    $course['careers'] = html_to_plain($m[1] ?? '');
    
    $course['slug'] = $slug;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace([' ', '_'], '-', $_POST['title'] ?? '')));
        if (empty($slug)) $slug = 'untitled-course';
        
        $course_file = __DIR__ . '/../courses/' . $slug . '.html';
        
        $data = [
            'TITLE' => htmlspecialchars($_POST['title'] ?? ''),
            'BADGE' => htmlspecialchars($_POST['badge'] ?? ''),
            'DURATION' => htmlspecialchars($_POST['duration'] ?? ''),
            'CERTIFICATION' => htmlspecialchars($_POST['certification'] ?? 'Certified'),
            'DIPLOMA_TYPE' => htmlspecialchars($_POST['diploma_type'] ?? ''),
            'DESCRIPTION' => htmlspecialchars($_POST['description'] ?? ''),
            'IMAGE' => htmlspecialchars($_POST['image'] ?? ''),
            'PRICE' => htmlspecialchars($_POST['price'] ?? 'Contact for Price'),
            'PRICE_NOTE' => htmlspecialchars($_POST['price_note'] ?? 'Flexible payment options available'),
            'HIGHLIGHTS' => plain_to_list($_POST['highlights'] ?? ''),
            'OVERVIEW' => htmlspecialchars($_POST['overview'] ?? ''),
            'LEARNING_OUTCOMES' => plain_to_list($_POST['learning_outcomes'] ?? ''),
            'TARGET_AUDIENCE' => plain_to_list($_POST['target_audience'] ?? ''),
            'CAREERS' => plain_to_careers($_POST['careers'] ?? ''),
            'SLUG' => $slug,
            'HEADER' => $header_html,
            'INQUIRY_SECTION' => $inquiry_html,
            'FOOTER' => $footer_html
        ];
        
        $html = $template;
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }
        
        if (file_put_contents($course_file, $html)) {
            header('Location: courses.php?saved=1');
            exit;
        } else {
            $errors[] = 'Failed to save course file';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Add' ?> Course | ICSA Admin</title>
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
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .form {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
            color: var(--accent);
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
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.95rem;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
            font-size: 0.95rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        
        .form-row-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.375rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            position: sticky;
            bottom: 0;
            background: var(--bg-card);
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 1rem; }
            .form-row, .form-row-2 { grid-template-columns: 1fr; }
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
                    <a href="courses.php" class="nav-item active"><i class="fas fa-graduation-cap"></i> Courses</a>
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
                <h1><?= $is_edit ? 'Edit Course' : 'Add New Course' ?></h1>
                <a href="courses.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            
            <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <!-- Hero Section -->
                <div class="form">
                    <div class="form-title"><i class="fas fa-heading"></i> Hero Section</div>
                    
                    <div class="form-group">
                        <label>Course Title</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($course['title']) ?>" required placeholder="e.g., UK Diploma in Business Management">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Badge/Label</label>
                            <input type="text" name="badge" value="<?= htmlspecialchars($course['badge']) ?>" placeholder="e.g., UK Diploma">
                        </div>
                        <div class="form-group">
                            <label>Duration</label>
                            <input type="text" name="duration" value="<?= htmlspecialchars($course['duration']) ?>" placeholder="e.g., 12 Months">
                        </div>
                        <div class="form-group">
                            <label>Certification Status</label>
                            <input type="text" name="certification" value="<?= htmlspecialchars($course['certification']) ?>" placeholder="e.g., Certified">
                        </div>
                    </div>
                    
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Diploma Type</label>
                            <input type="text" name="diploma_type" value="<?= htmlspecialchars($course['diploma_type']) ?>" placeholder="e.g., International UK Diploma">
                        </div>
                        <div class="form-group">
                            <label>Course Image Path</label>
                            <input type="text" name="image" value="<?= htmlspecialchars($course['image']) ?>" placeholder="e.g., ../images/course-name.jpg">
                            <div class="hint">Relative path from courses folder</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Short Description</label>
                        <textarea name="description" rows="3" placeholder="Brief description shown in hero"><?= htmlspecialchars($course['description']) ?></textarea>
                    </div>
                </div>
                
                <!-- Pricing & Highlights -->
                <div class="form">
                    <div class="form-title"><i class="fas fa-tag"></i> Pricing & Highlights</div>
                    
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Price Display</label>
                            <input type="text" name="price" value="<?= htmlspecialchars($course['price']) ?>" placeholder="e.g., Contact for Price or $500">
                        </div>
                        <div class="form-group">
                            <label>Price Note</label>
                            <input type="text" name="price_note" value="<?= htmlspecialchars($course['price_note']) ?>" placeholder="e.g., Flexible payment options available">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Program Highlights</label>
                        <textarea name="highlights" rows="5" placeholder="Practical classroom approach&#10;Certificate on completion&#10;Career-focused learning"><?= htmlspecialchars($course['highlights']) ?></textarea>
                        <div class="hint">One item per line - no HTML needed</div>
                    </div>
                </div>
                
                <!-- Content Sections -->
                <div class="form">
                    <div class="form-title"><i class="fas fa-book"></i> Content Sections</div>
                    
                    <div class="form-group">
                        <label>Program Overview</label>
                        <textarea name="overview" rows="4" placeholder="Detailed course description"><?= htmlspecialchars($course['overview']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>What You Will Learn</label>
                        <textarea name="learning_outcomes" rows="6" placeholder="Business management and organizational operations&#10;Strategic planning and execution&#10;Financial awareness for managers"><?= htmlspecialchars($course['learning_outcomes']) ?></textarea>
                        <div class="hint">One item per line - system will format automatically</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Who Should Enroll</label>
                        <textarea name="target_audience" rows="5" placeholder="Future managers and supervisors&#10;Professionals seeking leadership progression"><?= htmlspecialchars($course['target_audience']) ?></textarea>
                        <div class="hint">One item per line - system will format automatically</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Career Opportunities</label>
                        <textarea name="careers" rows="4" placeholder="Business Manager&#10;Operations Supervisor&#10;Administrative Manager"><?= htmlspecialchars($course['careers']) ?></textarea>
                        <div class="hint">One job title per line - system will format as tags</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Course</button>
                    <a href="courses.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
