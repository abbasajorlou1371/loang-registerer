<?php

declare(strict_types=1);

/**
 * Returns a shared PDO connection, or null when the database is unavailable.
 * Showcase pages keep working without MySQL.
 */
function db(): ?PDO
{
    static $pdo = null;
    static $attempted = false;

    if ($attempted) {
        return $pdo;
    }

    $attempted = true;
    $config = require dirname(__DIR__) . '/config/database.php';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $dsns = [];
    $socket = trim((string) ($config['socket'] ?? ''));
    if ($socket !== '') {
        $dsns[] = sprintf(
            'mysql:unix_socket=%s;dbname=%s;charset=%s',
            $socket,
            $config['name'],
            $config['charset']
        );
    }
    $dsns[] = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['name'],
        $config['charset']
    );

    $lastError = null;
    foreach ($dsns as $dsn) {
        try {
            $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
            $lastError = null;
            break;
        } catch (PDOException $e) {
            $lastError = $e;
            $pdo = null;
        }
    }

    if ($lastError instanceof PDOException) {
        error_log('Database connection failed: ' . $lastError->getMessage());
    }

    return $pdo;
}
