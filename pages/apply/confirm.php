<?php

declare(strict_types=1);

$tracking = strtoupper(trim((string) ($_GET['t'] ?? $_SESSION['apply_confirmation'] ?? '')));
if ($tracking === '') {
    redirect('/track');
}

$pageTitle = 'ثبت موفق درخواست | ثبت‌وام';
$pageDescription = 'کد پیگیری درخواست وام بانک ملی.';
$dir = 'rtl';
$lang = 'fa';

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
  <section class="card p-8 text-center" data-enter="1">
    <div class="success-seal mx-auto" data-success-seal aria-hidden="true">
      <svg viewBox="0 0 64 64" fill="none">
        <circle class="success-seal-ring" cx="32" cy="32" r="26" stroke="currentColor" stroke-width="3"/>
        <path class="success-seal-check" d="M20 33.5 28.5 42 44 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <p class="badge mt-4">ثبت شد</p>
    <h1 class="page-title mt-4">درخواست شما با موفقیت ثبت شد.</h1>
    <p class="mt-4 text-muted">کد پیگیری:</p>
    <p class="success-tracking font-latin mt-2 text-3xl font-bold tracking-wide text-cta" data-success-tracking><?= e($tracking) ?></p>
    <p class="mt-6 text-sm text-muted">هزینه ثبت نمایشی پرداخت شد. این کد را همراه شماره موبایل برای پیگیری نگه دارید. تأیید نهایی با بانک ملی است.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
      <a class="btn-primary" href="/track?t=<?= e(urlencode($tracking)) ?>">پیگیری درخواست</a>
      <a class="btn-secondary" href="/">بازگشت به خانه</a>
    </div>
  </section>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
