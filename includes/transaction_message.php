<?php

declare(strict_types=1);

function transaction_message_to_array(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'data' => (string) ($row['data'] ?? ''),
    ];
}

function transaction_message_display_text(string $data): string
{
    if ($data === '') {
        return '';
    }

    $decoded = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $data;
    }

    $pretty = json_encode(
        $decoded,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );

    return $pretty !== false ? $pretty : $data;
}

function transaction_message_read_payload(): string
{
    $raw = file_get_contents('php://input');
    if (is_string($raw) && $raw !== '') {
        return $raw;
    }

    if ($_POST !== []) {
        $encoded = json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded !== false ? $encoded : '';
    }

    return '';
}

function transaction_message_api_authorized(): bool
{
    $expected = getenv('TRANSACTION_API_KEY');
    if ($expected === false || $expected === '') {
        return true;
    }

    $header = (string) ($_SERVER['HTTP_X_API_KEY'] ?? '');
    if ($header !== '' && hash_equals($expected, $header)) {
        return true;
    }

    $auth = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m) === 1) {
        return hash_equals($expected, trim($m[1]));
    }

    return false;
}

function transaction_message_create(string $data): ?array
{
    $pdo = db();
    if (!$pdo instanceof PDO) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO transaction_messages (data) VALUES (?)');
        $stmt->execute([$data]);

        return [
            'id' => (int) $pdo->lastInsertId(),
            'data' => $data,
        ];
    } catch (PDOException $e) {
        app_log('error', 'Failed to store transaction message', ['error' => $e->getMessage()]);

        return null;
    }
}

function transaction_message_list(int $limit = 100): array
{
    $pdo = db();
    if (!$pdo instanceof PDO) {
        return [];
    }

    $limit = max(1, min(500, $limit));

    try {
        $stmt = $pdo->query(
            "SELECT id, data
             FROM transaction_messages
             ORDER BY id DESC
             LIMIT {$limit}"
        );

        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        app_log('error', 'Transaction message list failed', ['error' => $e->getMessage()]);

        return [];
    }
}

function transaction_message_truncate(): bool
{
    $pdo = db();
    if (!$pdo instanceof PDO) {
        return false;
    }

    try {
        $pdo->exec('TRUNCATE TABLE transaction_messages');
        transaction_message_notify_socket([], 'transaction_message:cleared');

        return true;
    } catch (PDOException $e) {
        app_log('error', 'Failed to truncate transaction messages', ['error' => $e->getMessage()]);

        return false;
    }
}

function transaction_message_notify_socket(array $payload, string $event = 'transaction_message:created'): void
{
    $url = getenv('SOCKET_EMIT_URL');
    if ($url === false || $url === '') {
        $url = 'http://127.0.0.1:3001/emit';
    }

    $secret = getenv('SOCKET_EMIT_SECRET');
    $body = json_encode([
        'event' => $event,
        'data' => $payload,
    ], JSON_UNESCAPED_UNICODE);

    if ($body === false) {
        return;
    }

    $headers = [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($body),
    ];
    if (is_string($secret) && $secret !== '') {
        $headers[] = 'X-Emit-Secret: ' . $secret;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_CONNECTTIMEOUT => 1,
        ]);
        curl_exec($ch);
        curl_close($ch);

        return;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => 2,
            'ignore_errors' => true,
        ],
    ]);
    @file_get_contents($url, false, $context);
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
