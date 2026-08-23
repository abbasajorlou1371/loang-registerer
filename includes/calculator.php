<?php

declare(strict_types=1);

function ratio_for_months(array $rows, int $months): float
{
    if ($rows === []) {
        return 1.0;
    }

    usort($rows, static fn (array $a, array $b): int => ((int) $a['months']) <=> ((int) $b['months']));

    $selected = (float) $rows[0]['ratio'];
    foreach ($rows as $row) {
        if ($months >= (int) $row['months']) {
            $selected = (float) $row['ratio'];
        }
    }

    return $selected;
}

function mehrabani_installment(float $amount, float $feePct, int $months): array
{
    if ($months < 1 || $amount <= 0) {
        return [
            'installment' => 0.0,
            'total_repayment' => 0.0,
            'total_fee' => 0.0,
            'total_interest' => 0.0,
        ];
    }

    $rate = $feePct / 100;
    if ($rate <= 0) {
        $installment = $amount / $months;

        return [
            'installment' => $installment,
            'total_repayment' => $amount,
            'total_fee' => 0.0,
            'total_interest' => 0.0,
        ];
    }

    // قرض‌الحسنه بانک مرکزی: یک ماه کارمزد در ابتدای هر دوره ۱۲ماهه،
    // کارمزد روی مانده بدهی، اصل در ماه‌های باقی‌مانده به‌صورت مساوی.
    $feeMonthCount = (int) ceil($months / 12);
    $principalMonths = $months - $feeMonthCount;
    if ($principalMonths < 1) {
        $fee = $amount * $rate * ($months / 12);
        $total = $amount + $fee;

        return [
            'installment' => $total / $months,
            'total_repayment' => $total,
            'total_fee' => $fee,
            'total_interest' => $fee,
        ];
    }

    $monthlyPrincipal = $amount / $principalMonths;
    $principalLeft = $principalMonths;
    $remaining = $amount;
    $totalFee = 0.0;
    $cursor = 0;

    while ($cursor < $months) {
        $block = min(12, $months - $cursor);
        $totalFee += $remaining * $rate * ($block / 12);
        $principalInBlock = min(max($block - 1, 0), $principalLeft);
        $principalLeft -= $principalInBlock;
        $remaining = $principalLeft > 0
            ? $amount * ($principalLeft / $principalMonths)
            : 0.0;
        $cursor += $block;
    }

    return [
        'installment' => $monthlyPrincipal,
        'total_repayment' => $amount + $totalFee,
        'total_fee' => $totalFee,
        'total_interest' => $totalFee,
    ];
}

function etebar_installment(float $amount, float $annualRatePct, int $months): array
{
    if ($months < 1 || $amount <= 0) {
        return [
            'installment' => 0.0,
            'total_repayment' => 0.0,
            'total_fee' => 0.0,
            'total_interest' => 0.0,
        ];
    }

    $monthlyRate = ($annualRatePct / 100) / 12;
    if ($monthlyRate <= 0) {
        $installment = $amount / $months;

        return [
            'installment' => $installment,
            'total_repayment' => $amount,
            'total_fee' => 0.0,
            'total_interest' => 0.0,
        ];
    }

    $pow = (1 + $monthlyRate) ** $months;
    $installment = $amount * $monthlyRate * $pow / ($pow - 1);
    $total = $installment * $months;

    return [
        'installment' => $installment,
        'total_repayment' => $total,
        'total_fee' => 0.0,
        'total_interest' => $total - $amount,
    ];
}

function general_installment(float $amount, float $annualRatePct, int $months): array
{
    return etebar_installment($amount, $annualRatePct, $months);
}

function estimate_eligible_amount(array $product, array $inputs): int
{
    $max = (int) ($product['max_amount'] ?? 0);
    $min = (int) ($product['min_amount'] ?? 0);
    $balance = (int) ($inputs['average_balance'] ?? 0);
    $activityMonths = (int) ($inputs['activity_months'] ?? 0);
    $termMonths = (int) ($inputs['repayment_months'] ?? $product['term_months_max'] ?? 12);
    $hasAccount = (bool) ($inputs['has_account'] ?? false);

    if ($max <= 0) {
        return 0;
    }
    if (!$hasAccount || $balance <= 0) {
        return 0;
    }

    $conditions = $product['conditions'] ?? [];
    $activityRatio = ratio_for_months($conditions['activity_ratios'] ?? [], max($activityMonths, 1));
    $termRatio = 1.0;
    if (!empty($conditions['term_ratios'])) {
        $termRatio = ratio_for_months($conditions['term_ratios'], max($termMonths, 1));
    }

    $estimated = (int) round($balance * $activityRatio * $termRatio);
    $estimated = min($estimated, $max);
    if ($estimated > 0 && $estimated < $min) {
        return 0;
    }

    return max(0, $estimated);
}

function calculator_disclaimer(): string
{
    return 'این محاسبه صرفاً برآورد اولیه بر اساس شرایط اعلام‌شده است. مبلغ نهایی و شرایط قطعی توسط بانک ملی ایران تعیین می‌شود.';
}
