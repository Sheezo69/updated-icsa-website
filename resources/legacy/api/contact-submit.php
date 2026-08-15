<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/request-guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!enforce_rate_limit('contact_form')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again in a few minutes.']);
    exit;
}

if (is_honeypot_triggered('website')) {
    echo json_encode(['success' => true, 'message' => 'Contact message saved']);
    exit;
}

if (
    !validate_csrf_token(isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : null)
    && !is_same_origin_request()
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token mismatch. Refresh and try again.']);
    exit;
}

function clean_value(string $key, int $maxLength = 255): string
{
    $value = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
    if ($maxLength > 0) {
        $value = substr($value, 0, $maxLength);
    }
    return $value;
}

$name = clean_value('name', 120);
$email = clean_value('email', 190);
$phone = clean_value('phone', 40);
$courseInterest = clean_value('course', 120);
$subject = clean_value('subject', 60);
$message = clean_value('message', 4000);

if ($name === '' || $email === '' || $phone === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Name, email, and phone are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

if (!preg_match('/^[0-9+()\\-\\s]{7,40}$/', $phone)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number']);
    exit;
}

$allowedSubjects = ['general', 'enrollment', 'pricing', 'schedule', 'other', ''];
if (!in_array($subject, $allowedSubjects, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid subject selected']);
    exit;
}

$allowedCourseInterests = [
    '',
    'computer-secretarial',
    'office-management',
    'graphics-designing',
    'multimedia-motion-graphics',
    'web-designing',
    'programming-web-development',
    'pc-networking',
    'autocad-2d-3d',
    '3d-studio-max',
    'revit',
    'sketchup',
    'uk-diploma-strategic-management',
    'uk-diploma-health-social-care',
    'uk-diploma-information-technology',
    'uk-diploma-accounting-finance',
    'uk-diploma-hospitality-tourism',
    'uk-diploma-business-management',
    'uk-diploma-entrepreneurship',
    'ielts-preparation',
    'english-enhancement',
    'arabic-learning',
    'airline-ticketing',
];

if (!in_array($courseInterest, $allowedCourseInterests, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid course selected']);
    exit;
}

try {
    $connection = get_db_connection();

    $stmt = $connection->prepare(
        'INSERT INTO contact_messages (name, email, phone, course_interest, subject, message)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $courseInterest = $courseInterest !== '' ? $courseInterest : null;
    $subject = $subject !== '' ? $subject : null;
    $message = $message !== '' ? $message : null;

    $stmt->bind_param(
        'ssssss',
        $name,
        $email,
        $phone,
        $courseInterest,
        $subject,
        $message
    );
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Contact message saved']);
} catch (Throwable $exception) {
    error_log('contact-submit failure: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to save message right now']);
}
