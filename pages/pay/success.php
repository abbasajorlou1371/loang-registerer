<?php

declare(strict_types=1);

$success = $_SESSION['ipg_success'] ?? null;
if (!is_array($success) || empty($success['tracking_number'])) {
    redirect('/apply');
}

$pageTitle = 'پرداخت موفق | درگاه پرداخت آزمایشی';
$tracking = (string) $success['tracking_number'];
$ipgScript = false;

require APP_ROOT . '/pages/pay/_layout_start.php';
?>

<section class="ipg-result ok">
  <h1>پرداخت با موفقیت انجام شد</h1>
  <p>هزینه ثبت درخواست به‌صورت آزمایشی تأیید شد.</p>
  <div class="ipg-receipt">
    <div><span>مبلغ</span><b><?= e(format_rial_fa((int) ($success['amount_rial'] ?? 0))) ?> ریال</b></div>
    <div><span>شماره سفارش</span><b><?= e(fa_number((string) ($success['order_number'] ?? ''))) ?></b></div>
    <div><span>شماره مرجع</span><b><?= e(fa_number((string) ($success['reference'] ?? ''))) ?></b></div>
    <div><span>زمان</span><b><?= e(format_date_fa((string) ($success['paid_at'] ?? ''))) ?></b></div>
  </div>
  <p>کد پیگیری درخواست وام</p>
  <p class="ipg-tracking"><?= e($tracking) ?></p>
  <a class="ipg-btn ipg-btn-pay" href="/apply/confirm?t=<?= e(urlencode($tracking)) ?>">بازگشت به سامانه</a>
</section>

<?php require APP_ROOT . '/pages/pay/_layout_end.php'; ?>
