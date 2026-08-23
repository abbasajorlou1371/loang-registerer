<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$pageTitle = 'درخواست‌های ثبت‌شده | ثبت‌وام';
$pageDescription = 'فهرست درخواست‌های وام به‌همراه اطلاعات پرداخت.';

$requests = admin_list_requests(200);

require APP_ROOT . '/pages/admin/_layout_start.php';
?>

<main id="main">
  <h1 class="page-title">درخواست‌های ثبت‌شده</h1>
  <p class="page-lead mt-3">هر درخواست همراه با وضعیت پرداخت نمایش داده می‌شود.</p>

  <section class="card mt-8 overflow-hidden p-0 sm:p-0">
    <?php if ($requests === []): ?>
      <p class="p-6 text-sm text-muted">درخواستی برای نمایش وجود ندارد.</p>
    <?php else: ?>
      <div class="overflow-x-auto p-4 sm:p-6">
        <table class="data-table data-table-wide">
          <thead>
            <tr>
              <th>کد پیگیری</th>
              <th>متقاضی</th>
              <th>موبایل</th>
              <th>وام</th>
              <th>وضعیت درخواست</th>
              <th>مبلغ پرداخت</th>
              <th>تاریخ ثبت</th>
              <th>جزئیات</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $row): ?>
              <tr>
                <td class="font-latin"><?= e((string) $row['tracking_number']) ?></td>
                <td><?= e((string) $row['full_name']) ?></td>
                <td class="font-latin"><?= e(fa_number((string) $row['mobile'])) ?></td>
                <td><?= e((string) $row['product_title']) ?></td>
                <td><?= e(status_label((string) $row['status'])) ?></td>
                <td>
                  <?php if (!empty($row['amount_rial'])): ?>
                    <?= e(fa_number(number_format((int) $row['amount_rial'], 0, '.', ','))) ?> ریال
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td><?= e(format_date_fa((string) $row['submitted_at'])) ?></td>
                <td>
                  <a class="btn-inline" href="/admin/requests/<?= e((string) (int) $row['id']) ?>">مشاهده</a>
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
