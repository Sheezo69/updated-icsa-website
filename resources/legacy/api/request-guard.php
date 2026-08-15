<?php
declare(strict_types=1);

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function get_csrf_token(): string
{
    start_secure_session();

    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool
{
    if ($token === null || $token === '') {
        return false;
    }

    $sessionToken = get_csrf_token();
    return hash_equals($sessionToken, $token);
}

function is_honeypot_triggered(string $fieldName = 'website'): bool
{
    if (!isset($_POST[$fieldName])) {
        return false;
    }

    return trim((string)$_POST[$fieldName]) !== '';
}

function get_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return substr((string)$ip, 0, 45);
}

function is_same_origin_request(): bool
{
    $host = isset($_SERVER['HTTP_HOST']) ? trim((string)$_SERVER['HTTP_HOST']) : '';
    if ($host === '') {
        return false;
    }

    $candidates = [];
    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        $candidates[] = (string)$_SERVER['HTTP_ORIGIN'];
    }
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $candidates[] = (string)$_SERVER['HTTP_REFERER'];
    }

    foreach ($candidates as $candidate) {
        $parts = parse_url($candidate);
        if (!is_array($parts)) {
            continue;
        }

        $candidateHost = isset($parts['host']) ? trim((string)$parts['host']) : '';
        $candidatePort = isset($parts['port']) ? ':' . (string)$parts['port'] : '';
        $normalizedHost = $candidateHost !== '' ? $candidateHost . $candidatePort : '';

        if ($normalizedHost !== '' && strcasecmp($normalizedHost, $host) === 0) {
            return true;
        }
    }

    return false;
}

function enforce_rate_limit(string $scope, int $maxAttempts = 6, int $windowSeconds = 300): bool
{
    $projectTempDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp';
    if (!is_dir($projectTempDir)) {
        @mkdir($projectTempDir, 0775, true);
    }

    $baseDir = is_dir($projectTempDir) && is_writable($projectTempDir)
        ? $projectTempDir
        : rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);

    $filePath = $baseDir . DIRECTORY_SEPARATOR . 'icsa_form_rate_limit.json';
    $fileHandle = fopen($filePath, 'c+');
    if ($fileHandle === false) {
        return true;
    }

    $now = time();
    $clientKey = hash('sha256', $scope . '|' . get_client_ip());

    try {
        if (!flock($fileHandle, LOCK_EX)) {
            fclose($fileHandle);
            return true;
        }

        $raw = stream_get_contents($fileHandle);
        $all = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $all = $decoded;
            }
        }

        $all = array_filter($all, static function ($record) use ($now, $windowSeconds): bool {
            if (!is_array($record) || !isset($record['timestamps']) || !is_array($record['timestamps'])) {
                return false;
            }

            $fresh = array_filter(
                $record['timestamps'],
                static fn($ts): bool => is_int($ts) && ($now - $ts) <= $windowSeconds
            );

            return !empty($fresh);
        });

        $timestamps = [];
        if (isset($all[$clientKey]['timestamps']) && is_array($all[$clientKey]['timestamps'])) {
            $timestamps = array_values(
                array_filter(
                    $all[$clientKey]['timestamps'],
                    static fn($ts): bool => is_int($ts) && ($now - $ts) <= $windowSeconds
                )
            );
        }

        if (count($timestamps) >= $maxAttempts) {
            ftruncate($fileHandle, 0);
            rewind($fileHandle);
            fwrite($fileHandle, (string)json_encode($all, JSON_UNESCAPED_SLASHES));
            fflush($fileHandle);
            flock($fileHandle, LOCK_UN);
            fclose($fileHandle);
            return false;
        }

        $timestamps[] = $now;
        $all[$clientKey] = ['timestamps' => $timestamps];

        ftruncate($fileHandle, 0);
        rewind($fileHandle);
        fwrite($fileHandle, (string)json_encode($all, JSON_UNESCAPED_SLASHES));
        fflush($fileHandle);

        flock($fileHandle, LOCK_UN);
        fclose($fileHandle);
        return true;
    } catch (Throwable $exception) {
        flock($fileHandle, LOCK_UN);
        fclose($fileHandle);
        return true;
    }
}
