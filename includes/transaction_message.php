<?php

declare(strict_types=1);

const TRANSACTION_CODE_TTL_SECONDS = 120;

/**
 * Normalize a Unix timestamp that may be seconds or milliseconds.
 */
function transaction_received_at_seconds(int|float|string $receivedAt): int
{
    $value = (int) $receivedAt;
    if ($value <= 0) {
        return 0;
    }

    // Milliseconds are typically 13 digits (>= year 2001 in ms).
    if ($value >= 1_000_000_000_000) {
        return (int) intdiv($value, 1000);
    }

    return $value;
}

function transaction_code_age_seconds(int|float|string $receivedAt, ?int $now = null): int
{
    $now ??= time();
    $received = transaction_received_at_seconds($receivedAt);
    if ($received <= 0) {
        return TRANSACTION_CODE_TTL_SECONDS;
    }

    return max(0, $now - $received);
}

function transaction_code_is_expired(int|float|string $receivedAt, ?int $now = null): bool
{
    return transaction_code_age_seconds($receivedAt, $now) >= TRANSACTION_CODE_TTL_SECONDS;
}

function transaction_code_remaining_seconds(int|float|string $receivedAt, ?int $now = null): int
{
    return max(0, TRANSACTION_CODE_TTL_SECONDS - transaction_code_age_seconds($receivedAt, $now));
}

function transaction_message_to_array(array $row, ?int $now = null): array
{
    $receivedAt = (int) ($row['received_at'] ?? 0);
    $expired = transaction_code_is_expired($receivedAt, $now);

    return [
        'id' => (int) ($row['id'] ?? 0),
        'bank' => (string) ($row['bank'] ?? ''),
        'destination' => (string) ($row['destination'] ?? ''),
        'amount' => (int) ($row['amount'] ?? 0),
        'currency' => (string) ($row['currency'] ?? ''),
        'disposableCode' => (string) ($row['disposable_code'] ?? ''),
        'receivedAt' => $receivedAt,
        'receivedAtSeconds' => transaction_received_at_seconds($receivedAt),
        'isExpired' => $expired,
        'remainingSeconds' => transaction_code_remaining_seconds($receivedAt, $now),
        'createdAt' => (string) ($row['created_at'] ?? ''),
    ];
}

function transaction_message_read_payload(): array
{
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
    $raw = file_get_contents('php://input');
    $json = [];

    if (is_string($raw) && $raw !== '' && str_contains($contentType, 'application/json')) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $json = $decoded;
        }
    }

    $source = $json !== [] ? $json : $_POST;

    return [
        'bank' => trim((string) ($source['bank'] ?? '')),
        'destination' => trim((string) ($source['destination'] ?? '')),
        'amount' => $source['amount'] ?? null,
        'currency' => trim((string) ($source['currency'] ?? '')),
        'disposableCode' => trim((string) ($source['disposableCode'] ?? $source['disposable_code'] ?? '')),
        'receivedAt' => $source['receivedAt'] ?? $source['received_at'] ?? null,
    ];
}

/**
 * @return array{ok: bool, errors: array<string, string>, data?: array<string, mixed>}
 */
function transaction_message_validate(array $payload): array
{
    $errors = [];

    $bank = (string) ($payload['bank'] ?? '');
    $destination = (string) ($payload['destination'] ?? '');
    $currency = (string) ($payload['currency'] ?? '');
    $disposableCode = (string) ($payload['disposableCode'] ?? '');
    $amountRaw = $payload['amount'] ?? null;
    $receivedRaw = $payload['receivedAt'] ?? null;

    if ($bank === '' || mb_strlen($bank) > 120) {
        $errors['bank'] = 'bank is required (max 120 chars)';
    }
    if ($destination === '' || mb_strlen($destination) > 190) {
        $errors['destination'] = 'destination is required (max 190 chars)';
    }
    if ($currency === '' || mb_strlen($currency) > 16) {
        $errors['currency'] = 'currency is required (max 16 chars)';
    }
    if ($disposableCode === '' || mb_strlen($disposableCode) > 120) {
        $errors['disposableCode'] = 'disposableCode is required (max 120 chars)';
    }

    if (!is_numeric($amountRaw)) {
        $errors['amount'] = 'amount must be a number';
        $amount = 0;
    } else {
        $amount = (int) $amountRaw;
    }

    if (!is_numeric($receivedRaw)) {
        $errors['receivedAt'] = 'receivedAt must be a unix timestamp';
        $receivedAt = 0;
    } else {
        $receivedAt = (int) $receivedRaw;
        if ($receivedAt <= 0) {
            $errors['receivedAt'] = 'receivedAt must be a positive unix timestamp';
        }
    }

    if ($errors !== []) {
        return ['ok' => false, 'errors' => $errors];
    }

    return [
        'ok' => true,
        'errors' => [],
        'data' => [
            'bank' => $bank,
            'destination' => $destination,
            'amount' => $amount,
            'currency' => $currency,
            'disposable_code' => $disposableCode,
            'received_at' => $receivedAt,
        ],
    ];
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

function transaction_message_create(array $data): ?array
{
    $pdo = db();
    if (!$pdo instanceof PDO) {
        return null;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO transaction_messages
                (bank, destination, amount, currency, disposable_code, received_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['bank'],
            $data['destination'],
            $data['amount'],
            $data['currency'],
            $data['disposable_code'],
            $data['received_at'],
        ]);

        $id = (int) $pdo->lastInsertId();
        $row = transaction_message_find($id);

        return $row;
    } catch (PDOException $e) {
        app_log('error', 'Failed to store transaction message', ['error' => $e->getMessage()]);

        return null;
    }
}

function transaction_message_find(int $id): ?array
{
    if ($id < 1) {
        return null;
    }

    $pdo = db();
    if (!$pdo instanceof PDO) {
        return null;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT id, bank, destination, amount, currency, disposable_code, received_at, created_at
             FROM transaction_messages
             WHERE id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    } catch (PDOException $e) {
        app_log('error', 'Transaction message lookup failed', ['error' => $e->getMessage(), 'id' => $id]);

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
            "SELECT id, bank, destination, amount, currency, disposable_code, received_at, created_at
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

function transaction_message_notify_socket(array $payload): void
{
    $url = getenv('SOCKET_EMIT_URL');
    if ($url === false || $url === '') {
        $url = 'http://127.0.0.1:3001/emit';
    }

    $secret = getenv('SOCKET_EMIT_SECRET');
    $body = json_encode([
        'event' => 'transaction_message:created',
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

function format_received_at_fa(int|float|string $receivedAt): string
{
    $seconds = transaction_received_at_seconds($receivedAt);
    if ($seconds <= 0) {
        return '—';
    }

    return format_date_fa(date('Y-m-d H:i:s', $seconds)) . ' ' . fa_number(date('H:i:s', $seconds));
}
