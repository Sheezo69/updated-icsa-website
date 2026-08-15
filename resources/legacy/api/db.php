<?php
declare(strict_types=1);

/*
 * Set these env vars in production:
 * DB_HOST, DB_NAME, DB_USER, DB_PASS
 */
const DB_HOST = '127.0.0.1';
const DB_NAME = 'icsa_website';
const DB_USER = 'root';
const DB_PASS = '';

function env_or_default(string $name, string $default): string
{
    $value = getenv($name);
    if ($value === false || trim($value) === '') {
        return $default;
    }

    return trim($value);
}

function get_db_connection(): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $connection = new mysqli(
        env_or_default('DB_HOST', DB_HOST),
        env_or_default('DB_USER', DB_USER),
        env_or_default('DB_PASS', DB_PASS),
        env_or_default('DB_NAME', DB_NAME)
    );
    $connection->set_charset('utf8mb4');

    return $connection;
}
