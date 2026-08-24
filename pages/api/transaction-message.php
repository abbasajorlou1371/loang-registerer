<?php

declare(strict_types=1);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response([
        'ok' => false,
        'error' => 'Method not allowed. Use POST.',
    ], 405);
}

if (!transaction_message_api_authorized()) {
    json_response([
        'ok' => false,
        'error' => 'Unauthorized',
    ], 401);
}

if (rate_limited('transaction_message', 60, 60)) {
    json_response([
        'ok' => false,
        'error' => 'Too many requests',
    ], 429);
}

$row = transaction_message_create(transaction_message_read_payload());
if ($row === null) {
    json_response([
        'ok' => false,
        'error' => 'Unable to store transaction message',
    ], 503);
}

$message = transaction_message_to_array($row);
transaction_message_notify_socket($message);

json_response([
    'ok' => true,
    'message' => $message,
], 201);
