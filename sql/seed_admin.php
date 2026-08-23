<?php

declare(strict_types=1);

/**
 * Seeds the default admin user (username: admin, password: admin).
 * Usage from project root: php sql/seed_admin.php
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/includes/db.php';

$pdo = db();
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$username = 'admin';
$password = 'admin';
$name = 'مدیر سامانه';
$role = 'admin';
$hash = password_hash($password, PASSWORD_DEFAULT);

if ($hash === false) {
    fwrite(STDERR, "Could not hash password.\n");
    exit(1);
}

try {
    $existing = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $existing->execute([$username]);
    $id = $existing->fetchColumn();

    if ($id) {
        $update = $pdo->prepare(
            'UPDATE users SET name = ?, password = ?, role = ?, mobile = NULL WHERE id = ?'
        );
        $update->execute([$name, $hash, $role, $id]);
        fwrite(STDOUT, "Updated existing admin user (id={$id}).\n");
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO users (name, username, password, role, mobile) VALUES (?, ?, ?, ?, NULL)'
        );
        $insert->execute([$name, $username, $hash, $role]);
        fwrite(STDOUT, "Created admin user (id=" . $pdo->lastInsertId() . ").\n");
    }
} catch (PDOException $e) {
    fwrite(STDERR, 'Seeder failed: ' . $e->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "Login: username={$username} password={$password}\n");
