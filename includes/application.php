<?php

declare(strict_types=1);

function application_draft_from(array $src): array
{
    return [
        'product' => trim((string) ($src['product'] ?? '')),
        'full_name' => trim((string) ($src['full_name'] ?? '')),
        'national_id' => normalize_national_id((string) ($src['national_id'] ?? '')),
        'mobile' => normalize_mobile((string) ($src['mobile'] ?? '')),
        'birth_date' => normalize_birth_date((string) ($src['birth_date'] ?? '')),
        'employment_type' => trim((string) ($src['employment_type'] ?? '')),
        'monthly_income' => parse_int_input($src['monthly_income'] ?? 0),
        'bank_melli_account' => trim((string) ($src['bank_melli_account'] ?? '')),
        'has_account' => parse_bool_input($src['has_account'] ?? 0),
        'account_activity_months' => parse_int_input($src['account_activity_months'] ?? 0),
        'average_balance' => parse_int_input($src['average_balance'] ?? 0),
        'requested_amount' => parse_int_input($src['requested_amount'] ?? 0),
        'repayment_months' => parse_int_input($src['repayment_months'] ?? 0),
        'applicant_type' => (string) ($src['applicant_type'] ?? 'individual'),
        'interest_rate' => (float) ($src['interest_rate'] ?? 0),
        'fee_option' => (float) ($src['fee_option'] ?? 0),
        'has_guarantor' => parse_bool_input($src['has_guarantor'] ?? 0),
        'existing_obligations' => parse_bool_input($src['existing_obligations'] ?? 0),
    ];
}

function normalize_application_draft(array $draft, array $product): array
{
    if (($product['interest_rates'] ?? []) === []) {
        $draft['interest_rate'] = 0.0;
    }
    if (($product['fee_options'] ?? []) === []) {
        $draft['fee_option'] = 0.0;
    }
    if (($product['slug'] ?? '') !== 'etebar-melli') {
        $draft['applicant_type'] = 'individual';
    } elseif (!in_array($draft['applicant_type'], ['individual', 'legal'], true)) {
        $draft['applicant_type'] = 'individual';
    }

    return $draft;
}

function option_in_list(float $value, array $options): bool
{
    foreach ($options as $option) {
        if (abs((float) $option - $value) < 0.001) {
            return true;
        }
    }

    return false;
}

function validate_application_draft(array $draft, array $product): array
{
    $errors = [];
    $nameLength = function_exists('mb_strlen') ? mb_strlen($draft['full_name']) : strlen($draft['full_name']);
    if ($draft['full_name'] === '' || $nameLength < 3) {
        $errors[] = 'نام و نام خانوادگی را کامل وارد کنید.';
    }
    if (!validate_national_id($draft['national_id'])) {
        $errors[] = 'کد ملی معتبر نیست.';
    }
    if (!validate_mobile($draft['mobile'])) {
        $errors[] = 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.';
    }
    if ($draft['birth_date'] === '' || strtotime($draft['birth_date']) === false) {
        $errors[] = 'تاریخ تولد را وارد کنید.';
    } elseif (strtotime($draft['birth_date']) > strtotime(date('Y-m-d'))) {
        $errors[] = 'تاریخ تولد نمی‌تواند در آینده باشد.';
    }
    if ($draft['employment_type'] === '') {
        $errors[] = 'وضعیت شغلی را انتخاب کنید.';
    }
    if ($draft['monthly_income'] < 0) {
        $errors[] = 'درآمد ماهانه نامعتبر است.';
    }
    if ($draft['requested_amount'] <= 0) {
        $errors[] = 'مبلغ درخواستی را وارد کنید.';
    }
    if ((int) $product['min_amount'] > 0 && $draft['requested_amount'] > 0 && $draft['requested_amount'] < (int) $product['min_amount']) {
        $errors[] = 'مبلغ درخواستی از حداقل این وام کمتر است.';
    }
    if ((int) $product['max_amount'] > 0 && $draft['requested_amount'] > (int) $product['max_amount']) {
        $errors[] = 'مبلغ درخواستی از سقف اعلام‌شده بیشتر است.';
    }
    if ($draft['repayment_months'] < (int) $product['term_months_min'] || $draft['repayment_months'] > (int) $product['term_months_max']) {
        $errors[] = 'مدت بازپرداخت خارج از بازه مجاز این وام است.';
    }
    if (!empty($product['conditions']['account_required']) && !$draft['has_account']) {
        $errors[] = 'برای این محصول داشتن حساب بانک ملی لازم است.';
    }
    if ($product['interest_rates'] !== [] && !option_in_list((float) $draft['interest_rate'], $product['interest_rates'])) {
        $errors[] = 'نرخ سود را از گزینه‌های این محصول انتخاب کنید.';
    }
    if ($product['fee_options'] !== [] && !option_in_list((float) $draft['fee_option'], $product['fee_options'])) {
        $errors[] = 'کارمزد را از گزینه‌های این محصول انتخاب کنید.';
    }
    if (($product['slug'] ?? '') === 'etebar-melli' && !in_array($draft['applicant_type'], ['individual', 'legal'], true)) {
        $errors[] = 'نوع متقاضی را انتخاب کنید.';
    }

    return $errors;
}

function application_quote(array $draft, array $product): array
{
    $amount = (int) $draft['requested_amount'];
    $months = (int) $draft['repayment_months'];
    $eligible = estimate_eligible_amount($product, [
        'average_balance' => (int) $draft['average_balance'],
        'activity_months' => (int) $draft['account_activity_months'],
        'repayment_months' => $months,
        'has_account' => (bool) $draft['has_account'],
    ]);

    if (($product['slug'] ?? '') === 'mehrabani-melli') {
        $calc = mehrabani_installment($amount, (float) $draft['fee_option'], $months);
    } elseif (($product['slug'] ?? '') === 'etebar-melli') {
        $calc = etebar_installment($amount, (float) $draft['interest_rate'], $months);
    } else {
        $calc = general_installment($amount, (float) $draft['interest_rate'], $months);
    }

    return [
        'eligible' => $eligible,
        'installment' => $calc['installment'],
        'total' => $calc['total_repayment'],
        'interest' => $calc['total_interest'],
    ];
}

function persist_application(array $draft, array $product, array $paymentMeta = [], ?array $demoCard = null): array
{
    $draft = normalize_application_draft($draft, $product);
    $errors = validate_application_draft($draft, $product);
    if ($errors !== []) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $tracking = generate_tracking_number();
    $quote = application_quote($draft, $product);
    $eligibility = evaluate_eligibility([
        'purpose' => 'general',
        'amount' => $draft['requested_amount'],
        'has_account' => $draft['has_account'],
        'activity_months' => $draft['account_activity_months'],
        'average_balance' => $draft['average_balance'],
        'repayment_months' => $draft['repayment_months'],
        'has_guarantor' => $draft['has_guarantor'],
        'applicant_type' => $draft['applicant_type'],
    ], $product['slug']);

    $historyNote = $paymentMeta !== [] ? 'ثبت درخواست پس از پرداخت آزمایشی' : 'ثبت درخواست از سامانه';
    $payload = [
        'tracking_number' => $tracking,
        'application_id' => null,
        'product' => $product,
        'draft' => $draft,
        'quote' => $quote,
        'eligibility_result' => $eligibility['result'],
        'status' => 'submitted',
        'submitted_at' => date('Y-m-d H:i:s'),
        'payment' => $paymentMeta,
        'history' => [
            [
                'from_status' => null,
                'to_status' => 'submitted',
                'note' => $historyNote,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ],
    ];

    $pdo = db();
    if (!$pdo instanceof PDO) {
        $_SESSION['offline_applications'][$tracking] = $payload;
        if ($demoCard !== null && function_exists('persist_demo_payment')) {
            persist_demo_payment(null, null, $demoCard, [
                'order_number' => (string) ($paymentMeta['order_number'] ?? ''),
                'amount_rial' => (int) ($paymentMeta['amount_rial'] ?? 300000),
            ]);
        }
        app_log('info', 'Stored application offline', ['tracking' => $tracking]);

        return $payload;
    }

    try {
        $pdo->beginTransaction();

        $existing = $pdo->prepare('SELECT id FROM applicants WHERE national_id = ? LIMIT 1');
        $existing->execute([$draft['national_id']]);
        $applicantId = $existing->fetchColumn();

        if ($applicantId) {
            $update = $pdo->prepare(
                'UPDATE applicants SET full_name = ?, mobile = ?, birth_date = ?, employment_type = ?,
                 monthly_income = ?, bank_melli_account = ?, has_bank_melli_account = ?,
                 account_activity_months = ?, average_balance = ?
                 WHERE id = ?'
            );
            $update->execute([
                $draft['full_name'],
                $draft['mobile'],
                $draft['birth_date'] !== '' ? $draft['birth_date'] : null,
                $draft['employment_type'],
                $draft['monthly_income'] ?: null,
                $draft['bank_melli_account'] !== '' ? $draft['bank_melli_account'] : null,
                $draft['has_account'] ? 1 : 0,
                $draft['account_activity_months'] ?: null,
                $draft['average_balance'] ?: null,
                $applicantId,
            ]);
            $applicantId = (int) $applicantId;
        } else {
            $insertApplicant = $pdo->prepare(
                'INSERT INTO applicants
                 (full_name, national_id, mobile, birth_date, employment_type, monthly_income,
                  bank_melli_account, has_bank_melli_account, account_activity_months, average_balance)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insertApplicant->execute([
                $draft['full_name'],
                $draft['national_id'],
                $draft['mobile'],
                $draft['birth_date'] !== '' ? $draft['birth_date'] : null,
                $draft['employment_type'],
                $draft['monthly_income'] ?: null,
                $draft['bank_melli_account'] !== '' ? $draft['bank_melli_account'] : null,
                $draft['has_account'] ? 1 : 0,
                $draft['account_activity_months'] ?: null,
                $draft['average_balance'] ?: null,
            ]);
            $applicantId = (int) $pdo->lastInsertId();
        }

        $extra = [
            'applicant_type' => $draft['applicant_type'],
            'interest_rate' => $draft['interest_rate'],
            'fee_option' => $draft['fee_option'],
            'has_guarantor' => $draft['has_guarantor'],
            'existing_obligations' => $draft['existing_obligations'],
            'account_activity_months' => $draft['account_activity_months'],
            'average_balance' => $draft['average_balance'],
            'quote' => [
                'eligible' => (int) $quote['eligible'],
                'installment' => (float) $quote['installment'],
                'total' => (float) $quote['total'],
                'interest' => (float) $quote['interest'],
            ],
        ];
        if ($paymentMeta !== []) {
            $extra['payment'] = [
                'amount_rial' => (int) ($paymentMeta['amount_rial'] ?? 0),
                'order_number' => (string) ($paymentMeta['order_number'] ?? ''),
                'paid_at' => (string) ($paymentMeta['paid_at'] ?? date('Y-m-d H:i:s')),
                'gateway' => (string) ($paymentMeta['gateway'] ?? 'demo_sadad'),
            ];
        }

        $insertApp = $pdo->prepare(
            'INSERT INTO loan_applications
             (tracking_number, applicant_id, loan_product_id, loan_product_version_id,
              requested_amount, repayment_months, eligibility_result, status, extra_data, ip_address, submitted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );

        $inserted = false;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $insertApp->execute([
                    $tracking,
                    $applicantId,
                    $product['id'],
                    $product['version_id'],
                    $draft['requested_amount'],
                    $draft['repayment_months'],
                    $eligibility['result'],
                    'submitted',
                    json_encode($extra, JSON_UNESCAPED_UNICODE),
                    client_ip(),
                ]);
                $inserted = true;
                break;
            } catch (PDOException $e) {
                if ((int) $e->getCode() === 23000) {
                    $tracking = generate_tracking_number();
                    continue;
                }
                throw $e;
            }
        }
        if (!$inserted) {
            throw new RuntimeException('Could not allocate a unique tracking number.');
        }

        $applicationId = (int) $pdo->lastInsertId();
        $history = $pdo->prepare(
            'INSERT INTO application_status_history (loan_application_id, from_status, to_status, note)
             VALUES (?, NULL, ?, ?)'
        );
        $history->execute([$applicationId, 'submitted', $historyNote]);
        if ($demoCard !== null && function_exists('persist_demo_payment')) {
            persist_demo_payment($pdo, $applicationId, $demoCard, [
                'order_number' => (string) ($paymentMeta['order_number'] ?? ''),
                'amount_rial' => (int) ($paymentMeta['amount_rial'] ?? 300000),
            ]);
        }
        $pdo->commit();
        $payload['tracking_number'] = $tracking;
        $payload['application_id'] = $applicationId;
        app_log('info', 'Application submitted', ['tracking' => $tracking, 'product' => $product['slug']]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        app_log('error', 'Failed to persist application', ['error' => $e->getMessage()]);
        throw $e;
    }

    return $payload;
}

function find_application(string $tracking, string $mobile): ?array
{
    $tracking = strtoupper(trim($tracking));
    $mobile = normalize_mobile($mobile);

    $offline = $_SESSION['offline_applications'][$tracking] ?? null;
    if (is_array($offline) && normalize_mobile((string) ($offline['draft']['mobile'] ?? '')) === $mobile) {
        return $offline;
    }

    $pdo = db();
    if (!$pdo instanceof PDO) {
        return null;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT a.tracking_number, a.requested_amount, a.repayment_months, a.status,
                    a.eligibility_result, a.submitted_at, a.extra_data,
                    p.title_fa, p.slug, ap.mobile, ap.full_name
             FROM loan_applications a
             INNER JOIN loan_products p ON p.id = a.loan_product_id
             INNER JOIN applicants ap ON ap.id = a.applicant_id
             WHERE a.tracking_number = ? AND ap.mobile = ?
             LIMIT 1'
        );
        $stmt->execute([$tracking, $mobile]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $historyStmt = $pdo->prepare(
            'SELECT from_status, to_status, note, created_at
             FROM application_status_history
             WHERE loan_application_id = (
               SELECT id FROM loan_applications WHERE tracking_number = ? LIMIT 1
             )
             ORDER BY created_at ASC'
        );
        $historyStmt->execute([$tracking]);

        return [
            'tracking_number' => $row['tracking_number'],
            'product' => [
                'title_fa' => $row['title_fa'],
                'slug' => $row['slug'],
            ],
            'draft' => [
                'full_name' => $row['full_name'],
                'mobile' => $row['mobile'],
                'requested_amount' => (int) $row['requested_amount'],
                'repayment_months' => (int) $row['repayment_months'],
            ],
            'eligibility_result' => $row['eligibility_result'],
            'status' => $row['status'],
            'submitted_at' => $row['submitted_at'],
            'history' => $historyStmt->fetchAll() ?: [],
        ];
    } catch (PDOException $e) {
        app_log('error', 'Application lookup failed', ['error' => $e->getMessage()]);

        return null;
    }
}
