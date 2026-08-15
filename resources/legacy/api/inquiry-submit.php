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

if (!enforce_rate_limit('inquiry_form')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again in a few minutes.']);
    exit;
}

if (is_honeypot_triggered('website')) {
    echo json_encode(['success' => true, 'message' => 'Inquiry saved']);
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
$course = clean_value('course', 120);
$message = clean_value('message', 4000);

if ($name === '' || $email === '' || $phone === '' || $course === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Name, email, phone, and course are required']);
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

try {
    $connection = get_db_connection();

    $stmt = $connection->prepare(
        'INSERT INTO contact_messages (name, email, phone, course_interest, subject, message)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $subject = null;
    $message = $message !== '' ? $message : null;

    $stmt->bind_param(
        'ssssss',
        $name,
        $email,
        $phone,
        $course,
        $subject,
        $message
    );
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Inquiry saved']);
} catch (Throwable $exception) {
    error_log('inquiry-submit failure: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to save inquiry right now']);
}
