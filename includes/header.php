<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'ثبت‌وام | بانک ملی ایران';
$pageDescription = $pageDescription ?? 'کشف، محاسبه و ثبت درخواست تسهیلات بانک ملی ایران.';
$lang = $lang ?? 'fa';
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($pageDescription) ?>">
  <title><?= e($pageTitle) ?></title>
  <script src="/assets/js/theme-boot.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="stylesheet" href="/assets/vendor/jalalidatepicker.min.css">
</head>
<body class="has-atmosphere min-h-screen antialiased text-ink">
  <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:inset-inline-start-4 focus:top-4 focus:z-50 focus:rounded-xl focus:bg-surface focus:px-4 focus:py-2 focus:shadow">
    رفتن به محتوا
  </a>

  <header data-site-header class="site-header sticky top-0 z-40">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-2 px-4 py-3 sm:gap-4 sm:px-6 sm:py-4 lg:px-8">
      <a href="/" class="brand-logo-link shrink-0" aria-label="ثبت‌وام — بانک ملی ایران">
        <?php require APP_ROOT . '/includes/brand-logo.svg.php'; ?>
      </a>

      <nav class="hidden items-center gap-6 text-sm font-medium md:flex" aria-label="منوی اصلی">
        <a href="/loans" class="nav-link transition <?= nav_active('/loans') ? 'is-active text-ink' : 'text-muted hover:text-ink' ?>">وام‌ها</a>
        <a href="/calculator" class="nav-link transition <?= nav_active('/calculator') ? 'is-active text-ink' : 'text-muted hover:text-ink' ?>">محاسبه وام</a>
        <a href="/eligibility" class="nav-link transition <?= nav_active('/eligibility') ? 'is-active text-ink' : 'text-muted hover:text-ink' ?>">بررسی شرایط</a>
        <a href="/track" class="nav-link transition <?= nav_active('/track') ? 'is-active text-ink' : 'text-muted hover:text-ink' ?>">پیگیری درخواست</a>
        <a href="/faq" class="nav-link transition <?= nav_active('/faq') ? 'is-active text-ink' : 'text-muted hover:text-ink' ?>">پرسش‌ها</a>
      </nav>

      <div class="flex items-center gap-2">
        <button
          type="button"
          data-theme-toggle
          aria-pressed="false"
          aria-label="فعال‌سازی حالت تاریک"
          title="تغییر تم"
          class="icon-btn theme-toggle"
        >
          <svg data-theme-icon="dark" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg data-theme-icon="light" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke-linecap="round"/>
          </svg>
        </button>
        <button
          type="button"
          data-nav-toggle
          class="icon-btn md:hidden"
          aria-expanded="false"
          aria-controls="mobile-nav"
          aria-label="باز و بسته کردن منو"
        >
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
          </svg>
        </button>
        <a href="/apply" class="btn-primary">
          <span class="sm:hidden">درخواست وام</span>
          <span class="hidden sm:inline">شروع درخواست وام</span>
        </a>
      </div>
    </div>

    <div id="mobile-nav" data-mobile-nav class="hidden border-t border-line bg-surface px-4 py-4 md:hidden">
      <nav class="flex flex-col gap-3 text-sm font-medium text-ink" aria-label="منوی موبایل">
        <a href="/loans">وام‌ها</a>
        <a href="/calculator">محاسبه وام</a>
        <a href="/eligibility">بررسی شرایط</a>
        <a href="/track">پیگیری درخواست</a>
        <a href="/faq">پرسش‌ها</a>
        <a href="/apply" class="btn-primary mt-2 w-full">شروع درخواست وام</a>
      </nav>
    </div>
  </header>
