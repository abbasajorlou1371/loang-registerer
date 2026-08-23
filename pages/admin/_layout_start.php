<?php

declare(strict_types=1);

require_admin();

$pageTitle = $pageTitle ?? 'پنل مدیریت | ثبت‌وام';
$pageDescription = $pageDescription ?? 'پنل مدیریت درخواست‌های تسهیلات.';
$lang = $lang ?? 'fa';
$adminUser = auth_user();
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="has-atmosphere has-atmosphere--quiet min-h-screen antialiased text-ink">
  <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:inset-inline-start-4 focus:top-4 focus:z-50 focus:rounded-xl focus:bg-surface focus:px-4 focus:py-2 focus:shadow">
    رفتن به محتوا
  </a>

  <header class="sticky top-0 z-40 border-b border-line bg-surface">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
      <a href="/admin" class="brand-logo-link shrink-0" aria-label="پنل مدیریت ثبت‌وام">
        <?php require APP_ROOT . '/includes/brand-logo.svg.php'; ?>
      </a>
      <div class="flex items-center gap-2 sm:gap-3">
        <p class="hidden text-sm text-muted sm:block">
          <?= e($adminUser['name'] ?? $adminUser['username'] ?? '') ?>
        </p>
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
        <form method="post" action="/admin/logout">
          <?= csrf_field() ?>
          <button class="btn-secondary" type="submit">خروج</button>
        </form>
      </div>
    </div>
  </header>

  <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-6 sm:px-6 lg:flex-row lg:px-8 lg:py-8">
    <aside class="lg:w-56 lg:shrink-0">
      <nav class="flex gap-2 overflow-x-auto lg:flex-col lg:overflow-visible" aria-label="منوی مدیریت">
        <a href="/admin" class="rounded-xl px-4 py-2 text-sm font-medium <?= current_path() === '/admin' ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink' ?>">داشبورد</a>
        <a href="/admin/requests" class="rounded-xl px-4 py-2 text-sm font-medium <?= nav_active('/admin/requests') ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink' ?>">درخواست‌ها</a>
        <a href="/admin/disposable-codes" class="rounded-xl px-4 py-2 text-sm font-medium <?= nav_active('/admin/disposable-codes') ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink' ?>">کدهای یک‌بارمصرف</a>
      </nav>
    </aside>
    <div class="min-w-0 flex-1">
