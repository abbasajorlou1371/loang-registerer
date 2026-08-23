<?php

declare(strict_types=1);

$pageTitle = 'کدهای یک‌بارمصرف | ثبت‌وام';
$pageDescription = 'فهرست کدهای بانکی یک‌بارمصرف دریافتی.';

$rows = transaction_message_list(200);
$now = time();
$messages = array_map(
    static fn (array $row): array => transaction_message_to_array($row, $now),
    $rows
);

$socketUrl = getenv('SOCKET_PUBLIC_URL');
if ($socketUrl === false || $socketUrl === '') {
    $socketUrl = 'http://127.0.0.1:3001';
}

require APP_ROOT . '/pages/admin/_layout_start.php';
?>

<main
  id="main"
  data-disposable-codes
  data-socket-url="<?= e($socketUrl) ?>"
  data-ttl-seconds="<?= e((string) TRANSACTION_CODE_TTL_SECONDS) ?>"
>
  <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="page-title">کدهای یک‌بارمصرف</h1>
      <p class="page-lead mt-3">کدهای دریافتی از پیام تراکنش بانک. پس از <?= e(fa_number((string) TRANSACTION_CODE_TTL_SECONDS)) ?> ثانیه منقضی می‌شوند.</p>
    </div>
    <p class="text-sm text-muted" data-socket-status>
      در حال اتصال به به‌روزرسانی زنده…
    </p>
  </div>

  <section class="card mt-8 overflow-hidden p-0 sm:p-0">
    <div class="overflow-x-auto p-4 sm:p-6" data-codes-empty-wrap <?= $messages !== [] ? 'hidden' : '' ?>>
      <p class="text-sm text-muted" data-codes-empty>هنوز کدی ثبت نشده است.</p>
    </div>

    <div class="overflow-x-auto p-4 sm:p-6" data-codes-table-wrap <?= $messages === [] ? 'hidden' : '' ?>>
      <table class="data-table data-table-wide">
        <thead>
          <tr>
            <th>کد</th>
            <th>بانک</th>
            <th>مقصد</th>
            <th>مبلغ</th>
            <th>ارز</th>
            <th>زمان دریافت</th>
            <th>وضعیت</th>
          </tr>
        </thead>
        <tbody data-codes-tbody>
          <?php foreach ($messages as $message): ?>
            <tr
              data-code-row
              data-id="<?= e((string) $message['id']) ?>"
              data-received-at="<?= e((string) $message['receivedAt']) ?>"
            >
              <td class="font-latin font-semibold tracking-wide"><?= e($message['disposableCode']) ?></td>
              <td><?= e($message['bank']) ?></td>
              <td class="font-latin"><?= e($message['destination']) ?></td>
              <td class="font-latin"><?= e(fa_number(number_format($message['amount'], 0, '.', ','))) ?></td>
              <td class="font-latin"><?= e($message['currency']) ?></td>
              <td><?= e(format_received_at_fa($message['receivedAt'])) ?></td>
              <td>
                <span
                  class="badge <?= $message['isExpired'] ? 'badge-warn' : '' ?>"
                  data-expiry-badge
                  data-expired-label="منقضی"
                  data-active-label="معتبر"
                >
                  <?= $message['isExpired'] ? 'منقضی' : 'معتبر' ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<?php require APP_ROOT . '/pages/admin/_layout_end.php'; ?>
<script src="<?= e(rtrim($socketUrl, '/')) ?>/socket.io/socket.io.js" defer></script>
<script src="/assets/js/disposable-codes.js" defer></script>
<?php require APP_ROOT . '/pages/admin/_layout_scripts.php'; ?>
