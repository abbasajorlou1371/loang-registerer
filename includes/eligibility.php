<?php

declare(strict_types=1);

function evaluate_eligibility(array $inputs, ?string $productSlug = null): array
{
    $purpose = (string) ($inputs['purpose'] ?? 'general');
    $amount = (int) ($inputs['amount'] ?? 0);
    $hasAccount = (bool) ($inputs['has_account'] ?? false);
    $activityMonths = (int) ($inputs['activity_months'] ?? 0);
    $averageBalance = (int) ($inputs['average_balance'] ?? 0);
    $repaymentMonths = (int) ($inputs['repayment_months'] ?? 24);
    $hasGuarantor = (bool) ($inputs['has_guarantor'] ?? false);
    $applicantType = (string) ($inputs['applicant_type'] ?? 'individual');

    if ($purpose === 'marriage' || $productSlug === 'ezdevaj') {
        $product = get_product_by_slug('ezdevaj');

        return [
            'result' => 'external',
            'matched_products' => $product ? [$product] : [],
            'recommended_product' => $product,
            'explanation_fa' => 'وام ازدواج از تسهیلات دولتی است. ثبت‌نام اولیه باید در سامانه بانک مرکزی انجام شود و این سامانه درخواست را به بانک ملی ارسال نمی‌کند.',
            'reasons' => ['ثبت اولیه فقط از سامانه بانک مرکزی انجام می‌شود.'],
        ];
    }

    if ($purpose === 'childbirth' || $productSlug === 'farzand-avi') {
        $product = get_product_by_slug('farzand-avi');

        return [
            'result' => 'external',
            'matched_products' => $product ? [$product] : [],
            'recommended_product' => $product,
            'explanation_fa' => 'وام فرزندآوری از تسهیلات دولتی است. ابتدا در سامانه بانک مرکزی ثبت‌نام کنید؛ سپس در صورت معرفی، ادامه کار در بانک ملی انجام می‌شود.',
            'reasons' => ['ثبت اولیه فقط از سامانه بانک مرکزی انجام می‌شود.'],
        ];
    }

    $catalog = get_catalog();
    $targets = [];
    foreach ($catalog as $product) {
        if ($product['application_type'] !== 'direct') {
            continue;
        }
        if ($productSlug !== null && $product['slug'] !== $productSlug) {
            continue;
        }
        $targets[] = $product;
    }

    $evaluated = [];
    foreach ($targets as $product) {
        $evaluated[] = evaluate_product_eligibility($product, [
            'amount' => $amount,
            'has_account' => $hasAccount,
            'activity_months' => $activityMonths,
            'average_balance' => $averageBalance,
            'repayment_months' => $repaymentMonths,
            'has_guarantor' => $hasGuarantor,
            'applicant_type' => $applicantType,
        ]);
    }

    usort($evaluated, static function (array $a, array $b): int {
        $rank = ['eligible' => 0, 'potentially_eligible' => 1, 'not_eligible' => 2, 'external' => 3];

        return ($rank[$a['result']] ?? 9) <=> ($rank[$b['result']] ?? 9);
    });

    $best = $evaluated[0] ?? [
        'result' => 'not_eligible',
        'product' => null,
        'explanation_fa' => 'محصولی برای ارزیابی یافت نشد.',
        'reasons' => [],
    ];

    $matched = [];
    foreach ($evaluated as $item) {
        if (in_array($item['result'], ['eligible', 'potentially_eligible'], true) && $item['product']) {
            $matched[] = $item['product'];
        }
    }

    return [
        'result' => $best['result'],
        'matched_products' => $matched !== [] ? $matched : array_values(array_filter(array_map(
            static fn (array $item): ?array => $item['product'],
            $evaluated
        ))),
        'recommended_product' => $best['product'],
        'explanation_fa' => $best['explanation_fa'],
        'reasons' => $best['reasons'],
        'details' => $evaluated,
    ];
}

function evaluate_product_eligibility(array $product, array $inputs): array
{
    $reasons = [];
    $blockers = [];
    $warnings = [];

    $amount = (int) $inputs['amount'];
    $hasAccount = (bool) $inputs['has_account'];
    $activityMonths = (int) $inputs['activity_months'];
    $averageBalance = (int) $inputs['average_balance'];
    $repaymentMonths = (int) $inputs['repayment_months'];
    $hasGuarantor = (bool) $inputs['has_guarantor'];
    $applicantType = (string) $inputs['applicant_type'];
    $conditions = $product['conditions'] ?? [];

    if (!empty($conditions['account_required']) && !$hasAccount) {
        $warnings[] = 'برای ' . $product['title_fa'] . ' داشتن حساب بانک ملی معمولاً لازم است.';
    }

    if ($amount > 0 && (int) $product['max_amount'] > 0 && $amount > (int) $product['max_amount']) {
        $blockers[] = 'مبلغ درخواستی از سقف اعلام‌شده ' . format_toman((int) $product['max_amount']) . ' بیشتر است.';
    }

    if ($amount > 0 && (int) $product['min_amount'] > 0 && $amount < (int) $product['min_amount']) {
        $warnings[] = 'مبلغ درخواستی کمتر از حداقل معمول این محصول است.';
    }

    $termMin = (int) $product['term_months_min'];
    $termMax = (int) $product['term_months_max'];
    if ($repaymentMonths > 0 && ($repaymentMonths < $termMin || $repaymentMonths > $termMax)) {
        $warnings[] = 'مدت بازپرداخت انتخابی خارج از بازه ' . term_label($product) . ' است.';
    }

    if (!empty($conditions['guarantor_required']) && !$hasGuarantor) {
        $warnings[] = 'معرفی ضامن برای این تسهیلات معمولاً لازم است.';
    }

    $allowedTypes = $conditions['applicant_types'] ?? ['individual'];
    if ($product['slug'] === 'etebar-melli' && $allowedTypes !== [] && !in_array($applicantType, $allowedTypes, true)) {
        $blockers[] = 'نوع متقاضی با شرایط اعلام‌شده این محصول هم‌خوان نیست.';
    }

    $eligibleAmount = estimate_eligible_amount($product, $inputs);
    if ($hasAccount && $averageBalance > 0) {
        $reasons[] = 'برآورد سقف بر اساس معدل حساب حدود ' . format_toman($eligibleAmount) . ' است.';
        if ($amount > 0 && $eligibleAmount > 0 && $amount > $eligibleAmount) {
            $warnings[] = 'مبلغ درخواستی بالاتر از برآورد اولیه بر اساس معدل حساب است.';
        }
        if ($activityMonths < 6) {
            $warnings[] = 'دوره گردش حساب کوتاه است و ممکن است مبلغ قابل دریافت محدود شود.';
        }
    } elseif ($hasAccount) {
        $warnings[] = 'بدون معدل حساب نمی‌توان سقف تقریبی را برآورد کرد.';
    }

    if ($blockers !== []) {
        $result = 'not_eligible';
        $explanation = 'با اطلاعات فعلی، این محصول با شرایط اعلام‌شده منطبق نیست. ' . implode(' ', $blockers);
    } elseif ($warnings !== [] || !$hasAccount) {
        $result = 'potentially_eligible';
        $explanation = 'ممکن است واجد شرایط باشید، اما بانک ملی باید معدل حساب، گردش و تضامین را بررسی کند.';
    } else {
        $result = 'eligible';
        $explanation = 'بر اساس اطلاعات واردشده، احتمالاً می‌توانید برای ' . $product['title_fa'] . ' اقدام کنید. این به معنای تأیید قطعی وام نیست.';
    }

    return [
        'result' => $result,
        'product' => $product,
        'eligible_amount' => $eligibleAmount,
        'explanation_fa' => $explanation,
        'reasons' => array_merge($reasons, $warnings, $blockers),
    ];
}
