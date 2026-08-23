<?php

declare(strict_types=1);

$pageTitle = 'خطای سرور | ثبت‌وام';
$pageDescription = 'خطای داخلی سامانه.';
$dir = 'rtl';
$lang = 'fa';

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
  <p class="text-sm font-semibold text-cta" data-enter="1"><?= e(fa_number('500')) ?></p>
  <h1 class="page-title mt-3" data-enter="2">مشکلی در سامانه رخ داد</h1>
  <p class="mt-3 text-muted" data-enter="3">لطفاً کمی بعد دوباره تلاش کنید. اطلاعات شما ذخیره نشده یا ناقص مانده است.</p>
  <a class="btn-primary mt-8 inline-flex" href="/" data-enter="4">بازگشت به خانه</a>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
