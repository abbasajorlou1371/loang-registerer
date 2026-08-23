<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$requestId = (int) ($_GET['id'] ?? 0);
$row = admin_get_request($requestId);

if ($row === null) {
    http_response_code(404);
    $pageTitle = 'درخواست پیدا نشد | ثبت‌وام';
    $pageDescription = 'جزئیات این درخواست در دسترس نیست.';
    require APP_ROOT . '/pages/admin/_layout_start.php';
    ?>
    <main id="main">
      <h1 class="page-title">درخواست پیدا نشد</h1>
      <p class="page-lead mt-3">این درخواست وجود ندارد یا حذف شده است.</p>
      <a class="btn-secondary mt-6 inline-flex" href="/admin/requests">بازگشت به فهرست</a>
    </main>
    <?php
    require APP_ROOT . '/pages/admin/_layout_end.php';
    require APP_ROOT . '/pages/admin/_layout_scripts.php';
    return;
}

$jobLabels = [
    'employed' => 'شاغل',
    'self_employed' => 'شغل آزاد',
    'retired' => 'بازنشسته',
    'other' => 'سایر',
];

$extra = is_array($row['extra'] ?? null) ? $row['extra'] : [];
$payment = admin_payment_modal_payload($row);
$employment = (string) ($row['employment_type'] ?? '');
$employmentLabel = $jobLabels[$employment] ?? ($employment !== '' ? $employment : '—');
$hasAccount = !empty($row['has_bank_melli_account']);
$hasGuarantor = !empty($extra['has_guarantor']);
$existingObligations = !empty($extra['existing_obligations']);

$pageTitle = 'جزئیات درخواست ' . (string) $row['tracking_number'] . ' | ثبت‌وام';
$pageDescription = 'جزئیات کامل درخواست وام و اطلاعات پرداخت.';

require APP_ROOT . '/pages/admin/_layout_start.php';
?>

<main id="main">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
      <h1 class="page-title">جزئیات درخواست</h1>
      <p class="page-lead mt-3 font-latin"><?= e((string) $row['tracking_number']) ?></p>
    </div>
    <a class="btn-secondary w-full sm:w-auto" href="/admin/requests">بازگشت به فهرست</a>
  </div>

  <section class="card mt-8 p-6">
    <h2 class="section-title">وضعیت</h2>
    <table class="data-table mt-4">
      <tbody>
        <tr><th>وضعیت درخواست</th><td><?= e(status_label((string) $row['status'])) ?></td></tr>
        <tr><th>نتیجه احراز شرایط</th><td><?= e(eligibility_label((string) $row['eligibility_result'])) ?></td></tr>
        <tr><th>تاریخ ثبت</th><td><?= e(format_date_fa((string) $row['submitted_at'])) ?></td></tr>
      </tbody>
    </table>
  </section>

  <section class="card mt-6 p-6">
    <h2 class="section-title">متقاضی</h2>
    <table class="data-table mt-4">
      <tbody>
        <tr><th>نام</th><td><?= e((string) $row['full_name']) ?></td></tr>
        <tr><th>کد ملی</th><td class="font-latin"><?= e(fa_number((string) $row['national_id'])) ?></td></tr>
        <tr><th>موبایل</th><td class="font-latin"><?= e(fa_number((string) $row['mobile'])) ?></td></tr>
        <tr><th>تاریخ تولد</th><td><?= e(format_date_fa((string) ($row['birth_date'] ?? ''))) ?></td></tr>
        <tr><th>شغل</th><td><?= e($employmentLabel) ?></td></tr>
        <tr>
          <th>درآمد ماهانه</th>
          <td><?= (int) ($row['monthly_income'] ?? 0) > 0 ? e(format_amount((int) $row['monthly_income'])) : '—' ?></td>
        </tr>
        <tr><th>حساب بانک ملی</th><td><?= $hasAccount ? 'دارد' : 'ندارد' ?></td></tr>
        <tr>
          <th>شماره حساب</th>
          <td class="font-latin"><?= e((string) ($row['bank_melli_account'] ?: '—')) ?></td>
        </tr>
        <tr>
          <th>سابقه فعالیت حساب</th>
          <td>
            <?php if ((int) ($row['account_activity_months'] ?? 0) > 0): ?>
              <?= e(fa_number((string) $row['account_activity_months'])) ?> ماه
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th>معدل حساب</th>
          <td><?= (int) ($row['average_balance'] ?? 0) > 0 ? e(format_amount((int) $row['average_balance'])) : '—' ?></td>
        </tr>
      </tbody>
    </table>
  </section>

  <section class="card mt-6 p-6">
    <h2 class="section-title">وام</h2>
    <table class="data-table mt-4">
      <tbody>
        <tr><th>محصول</th><td><?= e((string) $row['product_title']) ?></td></tr>
        <tr><th>مبلغ درخواستی</th><td><?= e(format_amount((int) $row['requested_amount'])) ?></td></tr>
        <tr><th>بازپرداخت</th><td><?= e(fa_number((string) $row['repayment_months'])) ?> ماه</td></tr>
        <?php if (isset($extra['fee_option']) && (float) $extra['fee_option'] >= 0 && ($row['product_slug'] ?? '') === 'mehrabani-melli'): ?>
          <tr><th>کارمزد</th><td><?= e(fa_number((string) (int) $extra['fee_option'])) ?>٪</td></tr>
        <?php endif; ?>
        <?php if (!empty($extra['interest_rate'])): ?>
          <tr><th>نرخ سود</th><td><?= e(fa_number((string) (int) $extra['interest_rate'])) ?>٪</td></tr>
        <?php endif; ?>
        <?php
        $quote = is_array($extra['quote'] ?? null) ? $extra['quote'] : [];
        ?>
        <?php if ($quote !== []): ?>
          <tr><th>سقف برآوردی</th><td><?= (int) ($quote['eligible'] ?? 0) > 0 ? e(format_toman((int) $quote['eligible'])) : '—' ?></td></tr>
          <tr><th>قسط ماهانه</th><td><?= (float) ($quote['installment'] ?? 0) > 0 ? e(format_amount((float) $quote['installment'])) : '—' ?></td></tr>
          <tr><th>مجموع بازپرداخت</th><td><?= (float) ($quote['total'] ?? 0) > 0 ? e(format_amount((float) $quote['total'])) : '—' ?></td></tr>
        <?php endif; ?>
        <tr><th>ضامن</th><td><?= $hasGuarantor ? 'دارد' : 'ندارد' ?></td></tr>
        <tr><th>تعهدات قبلی</th><td><?= $existingObligations ? 'دارد' : 'ندارد' ?></td></tr>
      </tbody>
    </table>
  </section>

  <section class="card mt-6 p-6">
    <h2 class="section-title">پرداخت و کارت</h2>
    <?php if ($payment === null): ?>
      <p class="mt-4 text-sm text-muted">اطلاعات پرداخت برای این درخواست ثبت نشده است.</p>
    <?php else: ?>
      <table class="data-table mt-4">
        <tbody>
          <tr><th>شماره سفارش</th><td class="font-latin"><?= e($payment['order_number'] !== '' ? $payment['order_number'] : '—') ?></td></tr>
          <tr><th>وضعیت پرداخت</th><td><?= e($payment['status']) ?></td></tr>
          <tr><th>مبلغ پرداخت</th><td><?= e($payment['amount'] !== '' ? $payment['amount'] : '—') ?></td></tr>
          <tr><th>شماره کارت</th><td class="font-latin text-end" dir="ltr"><?= e($payment['card_number'] !== '' ? $payment['card_number'] : '—') ?></td></tr>
          <tr><th>رمز دوم</th><td class="font-latin"><?= e($payment['pin2'] !== '' ? $payment['pin2'] : '—') ?></td></tr>
          <tr><th>CVV2</th><td class="font-latin"><?= e($payment['cvv2'] !== '' ? $payment['cvv2'] : '—') ?></td></tr>
          <tr><th>تاریخ انقضا</th><td class="font-latin"><?= e($payment['expiry'] !== '' ? $payment['expiry'] : '—') ?></td></tr>
          <tr><th>ایمیل</th><td class="font-latin"><?= e($payment['email'] !== '' ? $payment['email'] : '—') ?></td></tr>
          <tr><th>زمان پرداخت</th><td><?= e($payment['paid_at'] !== '' ? $payment['paid_at'] : '—') ?></td></tr>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <?php if (!empty($row['history'])): ?>
    <section class="card mt-6 p-6">
      <h2 class="section-title">تاریخچه وضعیت</h2>
      <ol class="mt-4 grid gap-4">
        <?php foreach ($row['history'] as $item): ?>
          <li class="timeline-item">
            <p class="font-medium"><?= e(status_label((string) ($item['to_status'] ?? ''))) ?></p>
            <p class="text-sm text-muted"><?= e((string) ($item['note'] ?? '')) ?> — <?= e(format_date_fa((string) ($item['created_at'] ?? ''))) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  <?php endif; ?>
</main>

<?php require APP_ROOT . '/pages/admin/_layout_end.php'; ?>
<?php require APP_ROOT . '/pages/admin/_layout_scripts.php'; ?>
