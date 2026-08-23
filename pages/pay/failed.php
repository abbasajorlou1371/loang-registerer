<?php

declare(strict_types=1);

$reason = (string) ($_GET['reason'] ?? 'session');
if ($reason === 'timeout' || $reason === 'session' || $reason === 'cancel') {
    clear_demo_payment();
}

$pageTitle = 'پرداخت ناموفق | درگاه پرداخت';
$ipgScript = false;

$message = match ($reason) {
    'timeout' => 'زمان نشست پرداخت به پایان رسید. درخواست وام هنوز ثبت نشده است.',
    'cancel' => 'پرداخت لغو شد. درخواست وام هنوز ثبت نشده است.',
    default => 'نشست پرداخت نامعتبر است. دوباره از صفحه بازبینی اقدام کنید.',
};

require APP_ROOT . '/pages/pay/_layout_start.php';
?>

<section class="ipg-result fail">
  <h1>پرداخت انجام نشد</h1>
  <p><?= e($message) ?></p>
  <div class="ipg-actions" style="justify-content:center">
    <a class="ipg-btn ipg-btn-pay" href="/apply/review">بازگشت به بازبینی</a>
    <a class="ipg-btn ipg-btn-cancel" href="/apply">ویرایش درخواست</a>
  </div>
</section>

<?php require APP_ROOT . '/pages/pay/_layout_end.php'; ?>
