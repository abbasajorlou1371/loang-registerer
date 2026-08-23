<?php

declare(strict_types=1);

$pageTitle = 'صفحه پیدا نشد | ثبت‌وام';
$pageDescription = 'صفحه مورد نظر وجود ندارد.';
$dir = 'rtl';
$lang = 'fa';

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
  <p class="text-sm font-semibold text-cta" data-enter="1"><?= e(fa_number('404')) ?></p>
  <h1 class="page-title mt-3" data-enter="2">این صفحه پیدا نشد</h1>
  <p class="mt-3 text-muted" data-enter="3">نشانی واردشده در سامانه ثبت‌وام وجود ندارد.</p>
  <a class="btn-primary mt-8 inline-flex" href="/" data-enter="4">بازگشت به خانه</a>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
