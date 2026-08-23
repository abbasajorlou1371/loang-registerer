<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function fa_number(int|float|string $n): string
{
    return strtr((string) $n, [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ',' => '٬',
    ]);
}

function to_en_digits(string $value): string
{
    return strtr($value, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

function parse_int_input(mixed $value): int
{
    $raw = to_en_digits(trim((string) $value));
    $raw = str_replace([',', '٬', ' ', '_'], '', $raw);
    if ($raw === '' || !preg_match('/^-?\d+$/', $raw)) {
        return 0;
    }

    return (int) $raw;
}

function parse_bool_input(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value) || is_float($value)) {
        return (int) $value === 1;
    }

    $raw = strtolower(trim((string) $value));

    return in_array($raw, ['1', 'true', 'on', 'yes'], true);
}

function format_thousands(int|float|string|null $value): string
{
    $n = parse_int_input($value ?? 0);
    if ($n === 0) {
        return '';
    }

    return number_format($n, 0, '.', ',');
}

function is_jalali_leap(int $year): bool
{
    return in_array($year % 33, [1, 5, 9, 13, 17, 22, 26, 30], true);
}

function jalali_days_in_month(int $year, int $month): int
{
    if ($month >= 1 && $month <= 6) {
        return 31;
    }
    if ($month >= 7 && $month <= 11) {
        return 30;
    }
    if ($month === 12) {
        return is_jalali_leap($year) ? 30 : 29;
    }

    return 0;
}

function gregorian_to_jalali(int $gy, int $gm, int $gd): array
{
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = $gm > 2 ? $gy + 1 : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }

    return [$jy, $jm, $jd];
}

function jalali_to_gregorian(int $jy, int $jm, int $jd): array
{
    $jy += 1595;
    $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4) + $jd + ($jm < 7 ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
    $gy = 400 * intdiv($days, 146097);
    $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * intdiv(--$days, 36524);
        $days %= 36524;
        if ($days >= 365) {
            $days++;
        }
    }
    $gy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $gy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    $gd = $days + 1;
    $leapFeb = (($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0) ? 29 : 28;
    $sal_a = [0, 31, $leapFeb, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $gm = 1;
    while ($gm <= 12 && $gd > $sal_a[$gm]) {
        $gd -= $sal_a[$gm];
        $gm++;
    }

    return [$gy, $gm, $gd];
}

function normalize_birth_date(string $value): string
{
    $value = trim(str_replace(['.', '-', ' '], '/', to_en_digits($value)));
    $value = preg_replace('#/+#', '/', $value) ?? $value;
    if (!preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $value, $m)) {
        return '';
    }

    $y = (int) $m[1];
    $mo = (int) $m[2];
    $d = (int) $m[3];

    if ($y >= 1700) {
        $ts = strtotime(sprintf('%04d-%02d-%02d', $y, $mo, $d));

        return $ts === false ? '' : date('Y-m-d', $ts);
    }

    if ($mo < 1 || $mo > 12 || $d < 1 || $d > jalali_days_in_month($y, $mo)) {
        return '';
    }

    [$gy, $gm, $gd] = jalali_to_gregorian($y, $mo, $d);

    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
}

function jalali_date_string(?string $gregorianIso): string
{
    if ($gregorianIso === null || $gregorianIso === '') {
        return '';
    }

    $normalized = normalize_birth_date($gregorianIso);
    if ($normalized === '') {
        return '';
    }

    $ts = strtotime($normalized);
    if ($ts === false) {
        return '';
    }

    [$jy, $jm, $jd] = gregorian_to_jalali((int) date('Y', $ts), (int) date('n', $ts), (int) date('j', $ts));

    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

function jalali_today_string(): string
{
    [$jy, $jm, $jd] = gregorian_to_jalali((int) date('Y'), (int) date('n'), (int) date('j'));

    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

function format_amount(int|float $amount): string
{
    return fa_number(number_format((float) $amount, 0, '.', ',')) . ' تومان';
}

function format_toman(int|float $amount): string
{
    $amount = (int) round($amount);
    if ($amount >= 1_000_000) {
        $millions = $amount / 1_000_000;
        $label = fmod($millions, 1.0) === 0.0
            ? fa_number((string) (int) $millions)
            : fa_number(number_format($millions, 1, '.', ','));

        return $label . ' میلیون تومان';
    }

    return format_amount($amount);
}

function format_date_fa(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    $ts = strtotime($date);
    if ($ts === false) {
        return e($date);
    }

    [$jy, $jm, $jd] = gregorian_to_jalali(
        (int) date('Y', $ts),
        (int) date('n', $ts),
        (int) date('j', $ts)
    );

    return fa_number(sprintf('%04d/%02d/%02d', $jy, $jm, $jd));
}

function generate_tracking_number(): string
{
    return 'MEL-' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function app_log(string $level, string $message, array $context = []): void
{
    $payload = $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    error_log(date('c') . " [{$level}] {$message}{$payload}");
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    $session = $_SESSION['csrf_token'] ?? '';
    if (!is_string($session) || $session === '' || !is_string($token) || $token === '') {
        return false;
    }

    return hash_equals($session, $token);
}

function current_path(): string
{
    return $_SERVER['ROUTE_PATH'] ?? '/';
}

function nav_active(string $href): bool
{
    $current = current_path();
    if ($href === '/') {
        return $current === '/';
    }

    return $current === $href || str_starts_with($current, $href . '/');
}

function json_field(mixed $value): mixed
{
    if (is_string($value) && $value !== '') {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }

    return $value;
}

function product_type_label(string $type): string
{
    return match ($type) {
        'qard_hasanah' => 'قرض‌الحسنه',
        'financing' => 'تسهیلات بانکی',
        'external_government' => 'تسهیلات دولتی',
        default => $type,
    };
}

function application_type_label(string $type): string
{
    return match ($type) {
        'direct' => 'درخواست از طریق این سامانه',
        'external' => 'درخواست از سامانه رسمی بانک ملی / بانک مرکزی',
        default => $type,
    };
}

function status_label(string $status): string
{
    return match ($status) {
        'submitted' => 'ثبت شده',
        'under_review' => 'در حال بررسی',
        'needs_info' => 'نیاز به اطلاعات بیشتر',
        'approved' => 'تأیید شده',
        'rejected' => 'رد شده',
        'external' => 'درخواست از سامانه رسمی',
        default => $status,
    };
}

function eligibility_label(string $result): string
{
    return match ($result) {
        'eligible' => 'احتمالاً واجد شرایط',
        'potentially_eligible' => 'احتمالاً نیازمند بررسی بیشتر',
        'not_eligible' => 'با شرایط فعلی منطبق نیست',
        'external' => 'ثبت از سامانه رسمی',
        default => $result,
    };
}

function validate_national_id(string $nid): bool
{
    $nid = preg_replace('/\D/', '', to_en_digits($nid)) ?? '';
    if (strlen($nid) !== 10) {
        return false;
    }
    if (preg_match('/^(\d)\1{9}$/', $nid) === 1) {
        return false;
    }

    $check = (int) $nid[9];
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (int) $nid[$i] * (10 - $i);
    }
    $mod = $sum % 11;

    return $mod < 2 ? $check === $mod : $check === 11 - $mod;
}

function validate_mobile(string $mobile): bool
{
    $mobile = preg_replace('/\D/', '', to_en_digits($mobile)) ?? '';

    return (bool) preg_match('/^09\d{9}$/', $mobile);
}

function normalize_mobile(string $mobile): string
{
    return preg_replace('/\D/', '', to_en_digits($mobile)) ?? '';
}

function normalize_national_id(string $nid): string
{
    return preg_replace('/\D/', '', to_en_digits($nid)) ?? '';
}

function rate_limited(string $action, int $max, int $windowSeconds): bool
{
    $pdo = function_exists('db') ? db() : null;
    if (!$pdo instanceof PDO) {
        return false;
    }

    $ip = client_ip();
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM rate_limits
             WHERE ip_address = ? AND action = ? AND created_at >= (NOW() - INTERVAL ? SECOND)'
        );
        $stmt->execute([$ip, $action, $windowSeconds]);
        $count = (int) $stmt->fetchColumn();
        if ($count >= $max) {
            return true;
        }

        $insert = $pdo->prepare(
            'INSERT INTO rate_limits (ip_address, action) VALUES (?, ?)'
        );
        $insert->execute([$ip, $action]);
    } catch (PDOException $e) {
        app_log('warning', 'Rate limit check failed', ['error' => $e->getMessage()]);
    }

    return false;
}

function decode_conditions(mixed $json): array
{
    $data = json_field($json);

    return is_array($data) ? $data : [];
}

function catalog_fallback(): array
{
    static $products = null;
    if (is_array($products)) {
        return $products;
    }

    $products = [
        normalize_product_row([
            'id' => 1,
            'slug' => 'mehrabani-melli',
            'title_fa' => 'مهربانی ملی',
            'description_fa' => 'تسهیلات قرض‌الحسنه مهربانی ملی بانک ملی ایران. سقف اعلام‌شده تا ۳۰۰ میلیون تومان، با کارمزد قابل انتخاب و بازپرداخت ۶ تا ۶۰ ماه.',
            'product_type' => 'qard_hasanah',
            'application_type' => 'direct',
            'sort_order' => 1,
            'version_id' => 1,
            'version' => 1,
            'min_amount' => 10000000,
            'max_amount' => 300000000,
            'fee_options' => [0, 2, 4],
            'interest_rates' => [],
            'term_months_min' => 6,
            'term_months_max' => 60,
            'term_options' => [6, 12, 18, 24, 36, 48, 60],
            'eligibility_notes_fa' => 'واجد شرایط بودن نهایی بر اساس معدل حساب و محاسبه بانک ملی تعیین می‌شود.',
            'conditions_json' => [
                'account_required' => true,
                'account_blocking' => false,
                'guarantor_required' => true,
                'transferable' => true,
                'available_via' => ['سامانه بام', 'شعب بانک ملی'],
                'activity_ratios' => [
                    ['months' => 6, 'ratio' => 0.40],
                    ['months' => 12, 'ratio' => 0.70],
                    ['months' => 18, 'ratio' => 0.90],
                    ['months' => 24, 'ratio' => 1.00],
                ],
            ],
            'source_url' => 'https://t.me/s/bankmelli_ir/7709',
            'source_label_fa' => 'کانال رسمی بانک ملی ایران',
            'last_updated_at' => '2026-01-20',
            'effective_from' => '2026-01-01',
            'rules' => [
                ['rule_key' => 'requires_account', 'label_fa' => 'حساب بانک ملی', 'description_fa' => 'داشتن حساب فعال در بانک ملی ایران لازم است.'],
                ['rule_key' => 'requires_guarantor', 'label_fa' => 'ضامن', 'description_fa' => 'معرفی ضامن مطابق ضوابط شعبه معمولاً ضروری است.'],
                ['rule_key' => 'activity_based', 'label_fa' => 'معدل حساب', 'description_fa' => 'مبلغ قابل دریافت بر اساس معدل و دوره گردش حساب محاسبه می‌شود.'],
            ],
        ]),
        normalize_product_row([
            'id' => 2,
            'slug' => 'etebar-melli',
            'title_fa' => 'اعتبار ملی',
            'description_fa' => 'تسهیلات اعتبار ملی بانک ملی ایران. سقف اعلام‌شده تا ۷۰۰ میلیون تومان، با نرخ‌های ۱۴، ۱۸ یا ۲۳ درصد و بازپرداخت ۱ تا ۵ سال.',
            'product_type' => 'financing',
            'application_type' => 'direct',
            'sort_order' => 2,
            'version_id' => 2,
            'version' => 1,
            'min_amount' => 20000000,
            'max_amount' => 700000000,
            'fee_options' => [],
            'interest_rates' => [14, 18, 23],
            'term_months_min' => 12,
            'term_months_max' => 60,
            'term_options' => [12, 24, 36, 48, 60],
            'eligibility_notes_fa' => 'سقف تسهیلات به گردش حساب، معدل موجودی و مدت بازپرداخت بستگی دارد.',
            'conditions_json' => [
                'account_required' => true,
                'account_blocking' => false,
                'guarantor_required' => true,
                'applicant_types' => ['individual', 'legal'],
                'available_via' => ['سامانه بام', 'شعب بانک ملی'],
                'activity_ratios' => [
                    ['months' => 6, 'ratio' => 0.35],
                    ['months' => 12, 'ratio' => 0.55],
                    ['months' => 18, 'ratio' => 0.75],
                    ['months' => 24, 'ratio' => 1.00],
                ],
                'term_ratios' => [
                    ['months' => 12, 'ratio' => 0.55],
                    ['months' => 24, 'ratio' => 0.70],
                    ['months' => 36, 'ratio' => 0.85],
                    ['months' => 48, 'ratio' => 0.95],
                    ['months' => 60, 'ratio' => 1.00],
                ],
            ],
            'source_url' => 'https://t.me/s/bankmelli_ir/8301',
            'source_label_fa' => 'کانال رسمی بانک ملی ایران',
            'last_updated_at' => '2026-03-01',
            'effective_from' => '2026-03-01',
            'rules' => [
                ['rule_key' => 'requires_account', 'label_fa' => 'حساب بانک ملی', 'description_fa' => 'ایجاد رابطه حساب مرتبط در بانک ملی لازم است.'],
                ['rule_key' => 'applicant_types', 'label_fa' => 'نوع متقاضی', 'description_fa' => 'اشخاص حقیقی و حقوقی مطابق شرایط بانک.'],
                ['rule_key' => 'activity_based', 'label_fa' => 'گردش حساب', 'description_fa' => 'مبلغ قابل دریافت به گردش حساب و مدت بازپرداخت بستگی دارد.'],
            ],
        ]),
        normalize_product_row([
            'id' => 3,
            'slug' => 'ezdevaj',
            'title_fa' => 'وام ازدواج',
            'description_fa' => 'وام قرض‌الحسنه ازدواج با مشارکت بانک ملی ایران. ثبت‌نام اولیه در سامانه بانک مرکزی انجام می‌شود.',
            'product_type' => 'external_government',
            'application_type' => 'external',
            'sort_order' => 3,
            'version_id' => 3,
            'version' => 1,
            'min_amount' => 0,
            'max_amount' => 0,
            'fee_options' => [4],
            'interest_rates' => [],
            'term_months_min' => 36,
            'term_months_max' => 120,
            'term_options' => [60, 84, 96, 120],
            'eligibility_notes_fa' => 'ثبت‌نام اولیه فقط از سامانه بانک مرکزی انجام می‌شود.',
            'conditions_json' => [
                'account_required' => false,
                'account_blocking' => false,
                'guarantor_required' => true,
                'official_url' => 'https://ve.cbi.ir',
                'official_label_fa' => 'سامانه وام ازدواج بانک مرکزی',
                'bank_selection_note_fa' => 'پس از ثبت‌نام در سامانه بانک مرکزی می‌توانید بانک ملی ایران را انتخاب کنید، در صورت تخصیص سهمیه.',
            ],
            'source_url' => 'https://t.me/s/bankmelli_ir',
            'source_label_fa' => 'کانال رسمی بانک ملی ایران',
            'last_updated_at' => '2026-01-10',
            'effective_from' => '2025-01-01',
            'rules' => [
                ['rule_key' => 'external_registration', 'label_fa' => 'ثبت‌نام بانک مرکزی', 'description_fa' => 'ثبت درخواست اولیه فقط از سامانه بانک مرکزی انجام می‌شود.'],
            ],
        ]),
        normalize_product_row([
            'id' => 4,
            'slug' => 'farzand-avi',
            'title_fa' => 'وام فرزندآوری',
            'description_fa' => 'وام قرض‌الحسنه فرزندآوری با مشارکت بانک ملی ایران. ثبت‌نام اولیه از سامانه بانک مرکزی است.',
            'product_type' => 'external_government',
            'application_type' => 'external',
            'sort_order' => 4,
            'version_id' => 4,
            'version' => 1,
            'min_amount' => 0,
            'max_amount' => 0,
            'fee_options' => [4],
            'interest_rates' => [],
            'term_months_min' => 36,
            'term_months_max' => 84,
            'term_options' => [36, 48, 60, 84],
            'eligibility_notes_fa' => 'ثبت‌نام اولیه از سامانه اختصاصی بانک مرکزی انجام می‌شود.',
            'conditions_json' => [
                'account_required' => false,
                'account_blocking' => false,
                'guarantor_required' => true,
                'official_url' => 'https://ve.cbi.ir',
                'official_label_fa' => 'سامانه تسهیلات فرزندآوری بانک مرکزی',
                'bank_selection_note_fa' => 'پس از ثبت در سامانه بانک مرکزی، در صورت معرفی به بانک ملی، ادامه فرآیند در شعبه انجام می‌شود.',
            ],
            'source_url' => 'https://t.me/s/bankmelli_ir',
            'source_label_fa' => 'کانال رسمی بانک ملی ایران',
            'last_updated_at' => '2026-01-10',
            'effective_from' => '2025-01-01',
            'rules' => [
                ['rule_key' => 'external_registration', 'label_fa' => 'ثبت‌نام بانک مرکزی', 'description_fa' => 'ثبت درخواست اولیه از سامانه اختصاصی بانک مرکزی است.'],
            ],
        ]),
    ];

    return $products;
}

function normalize_product_row(array $row): array
{
    $fee = json_field($row['fee_options'] ?? []);
    $rates = json_field($row['interest_rates'] ?? []);
    $terms = json_field($row['term_options'] ?? []);
    $conditions = decode_conditions($row['conditions_json'] ?? []);

    return [
        'id' => (int) ($row['id'] ?? 0),
        'slug' => (string) ($row['slug'] ?? ''),
        'title_fa' => (string) ($row['title_fa'] ?? ''),
        'description_fa' => (string) ($row['description_fa'] ?? ''),
        'product_type' => (string) ($row['product_type'] ?? ''),
        'application_type' => (string) ($row['application_type'] ?? 'direct'),
        'sort_order' => (int) ($row['sort_order'] ?? 0),
        'version_id' => (int) ($row['version_id'] ?? $row['id'] ?? 0),
        'version' => (int) ($row['version'] ?? 1),
        'min_amount' => (int) ($row['min_amount'] ?? 0),
        'max_amount' => (int) ($row['max_amount'] ?? 0),
        'fee_options' => is_array($fee) ? array_map('floatval', $fee) : [],
        'interest_rates' => is_array($rates) ? array_map('floatval', $rates) : [],
        'term_months_min' => (int) ($row['term_months_min'] ?? 0),
        'term_months_max' => (int) ($row['term_months_max'] ?? 0),
        'term_options' => is_array($terms) ? array_map('intval', $terms) : [],
        'eligibility_notes_fa' => (string) ($row['eligibility_notes_fa'] ?? ''),
        'conditions' => $conditions,
        'source_url' => (string) ($row['source_url'] ?? ''),
        'source_label_fa' => (string) ($row['source_label_fa'] ?? ''),
        'last_updated_at' => (string) ($row['last_updated_at'] ?? ''),
        'effective_from' => (string) ($row['effective_from'] ?? ''),
        'rules' => $row['rules'] ?? [],
    ];
}

function get_catalog(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $pdo = function_exists('db') ? db() : null;
    if (!$pdo instanceof PDO) {
        $cached = catalog_fallback();

        return $cached;
    }

    try {
        $stmt = $pdo->query(
            'SELECT p.id, p.slug, p.title_fa, p.description_fa, p.product_type, p.application_type, p.sort_order,
                    v.id AS version_id, v.version, v.min_amount, v.max_amount, v.fee_options, v.interest_rates,
                    v.term_months_min, v.term_months_max, v.term_options, v.eligibility_notes_fa,
                    v.conditions_json, v.source_url, v.source_label_fa, v.last_updated_at, v.effective_from
             FROM loan_products p
             INNER JOIN loan_product_versions v ON v.loan_product_id = p.id AND v.is_current = 1
             WHERE p.is_active = 1
             ORDER BY p.sort_order ASC, p.id ASC'
        );
        $rows = $stmt->fetchAll();
        if ($rows === []) {
            $cached = catalog_fallback();

            return $cached;
        }

        $ruleStmt = $pdo->query(
            'SELECT loan_product_id, rule_key, rule_value, label_fa, description_fa
             FROM loan_eligibility_rules'
        );
        $rulesByProduct = [];
        foreach ($ruleStmt->fetchAll() as $rule) {
            $rulesByProduct[(int) $rule['loan_product_id']][] = $rule;
        }

        $products = [];
        foreach ($rows as $row) {
            $row['rules'] = $rulesByProduct[(int) $row['id']] ?? [];
            $products[] = normalize_product_row($row);
        }
        $cached = $products;
    } catch (PDOException $e) {
        app_log('error', 'Failed to load loan catalog', ['error' => $e->getMessage()]);
        $cached = catalog_fallback();
    }

    return $cached;
}

function get_product_by_slug(string $slug): ?array
{
    foreach (get_catalog() as $product) {
        if ($product['slug'] === $slug) {
            return $product;
        }
    }

    return null;
}

function get_current_version(string $slug): ?array
{
    return get_product_by_slug($slug);
}

function product_headline_amount(array $product): string
{
    if ((int) $product['max_amount'] > 0) {
        return 'تا ' . format_toman((int) $product['max_amount']);
    }
    if ($product['slug'] === 'ezdevaj') {
        return 'ثبت درخواست از سامانه بانک مرکزی';
    }
    if ($product['slug'] === 'farzand-avi') {
        return 'ثبت درخواست از سامانه بانک مرکزی';
    }

    return 'شرایط طبق مقررات جاری';
}

function rate_or_fee_label(array $product): string
{
    if ($product['fee_options'] !== []) {
        $opts = array_map(static fn ($n) => fa_number((string) (int) $n) . '٪', $product['fee_options']);

        return 'کارمزد ' . implode('، ', $opts);
    }
    if ($product['interest_rates'] !== []) {
        $opts = array_map(static fn ($n) => fa_number((string) (int) $n) . '٪', $product['interest_rates']);

        return 'نرخ ' . implode('، ', $opts);
    }

    return 'طبق مقررات جاری';
}

function term_label(array $product): string
{
    $min = (int) $product['term_months_min'];
    $max = (int) $product['term_months_max'];
    if ($min > 0 && $max > 0) {
        return fa_number((string) $min) . ' تا ' . fa_number((string) $max) . ' ماه';
    }

    return 'طبق مقررات جاری';
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}
