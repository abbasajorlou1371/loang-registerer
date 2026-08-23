<?php

declare(strict_types=1);

$pageTitle = 'داشبورد مدیریت | ثبت‌وام';
$pageDescription = 'نمای کلی درخواست‌ها و پرداخت‌های ثبت‌شده.';

$stats = admin_dashboard_stats();

require APP_ROOT . '/pages/admin/_layout_start.php';
?>

<main id="main">
  <h1 class="page-title">داشبورد مدیریت</h1>
  <p class="page-lead mt-3">خلاصه وضعیت درخواست‌های ثبت‌شده در سامانه.</p>

  <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="آمار">
    <article class="card p-5">
      <p class="text-sm text-muted">کل درخواست‌ها</p>
      <p class="mt-2 text-2xl font-bold text-navy"><?= e(fa_number((string) $stats['applications'])) ?></p>
    </article>
    <article class="card p-5">
      <p class="text-sm text-muted">پرداخت‌شده</p>
      <p class="mt-2 text-2xl font-bold text-navy"><?= e(fa_number((string) $stats['paid'])) ?></p>
    </article>
    <article class="card p-5">
      <p class="text-sm text-muted">بدون پرداخت</p>
      <p class="mt-2 text-2xl font-bold text-navy"><?= e(fa_number((string) $stats['unpaid'])) ?></p>
    </article>
    <article class="card p-5">
      <p class="text-sm text-muted">در حال بررسی</p>
      <p class="mt-2 text-2xl font-bold text-navy"><?= e(fa_number((string) $stats['under_review'])) ?></p>
    </article>
  </section>

  <section class="card mt-8 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h2 class="section-title">آخرین درخواست‌ها</h2>
      <a href="/admin/requests" class="btn-secondary w-full sm:w-auto">مشاهده همه درخواست‌ها</a>
    </div>

    <?php if ($stats['recent'] === []): ?>
      <p class="mt-6 text-sm text-muted">هنوز درخواستی ثبت نشده است.</p>
    <?php else: ?>
      <div class="mt-6 overflow-x-auto">
        <table class="data-table data-table-wide">
          <thead>
            <tr>
              <th>کد پیگیری</th>
              <th>متقاضی</th>
              <th>وام</th>
              <th>وضعیت</th>
              <th>پرداخت</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($stats['recent'] as $row): ?>
              <tr>
                <td class="font-latin"><?= e((string) $row['tracking_number']) ?></td>
                <td><?= e((string) $row['full_name']) ?></td>
                <td><?= e((string) $row['product_title']) ?></td>
                <td><?= e(status_label((string) $row['status'])) ?></td>
                <td>
                  <span class="badge <?= ($row['payment_status'] ?? '') === 'paid' ? '' : 'badge-warn' ?>">
                    <?= e(payment_status_label($row['payment_status'] ?? null)) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php require APP_ROOT . '/pages/admin/_layout_end.php'; ?>
<?php require APP_ROOT . '/pages/admin/_layout_scripts.php'; ?>
