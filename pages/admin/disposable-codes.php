<?php

declare(strict_types=1);

require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (isset($_POST['clear_data']) && verify_csrf($_POST['csrf_token'] ?? null)) {
        transaction_message_truncate();
    }
    redirect('/admin/disposable-codes');
}

$pageTitle = 'کدهای یک‌بارمصرف | ثبت‌وام';
$pageDescription = 'فهرست کدهای بانکی یک‌بارمصرف دریافتی.';

$rows = transaction_message_list(200);
$messages = array_map(
    static fn (array $row): array => transaction_message_to_array($row),
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
>
  <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
      <h1 class="page-title">کدهای یک‌بارمصرف</h1>
      <p class="page-lead mt-3">پیام‌های تراکنش دریافتی، جدیدترین در بالا.</p>
    </div>
    <form
      method="post"
      class="shrink-0"
      onsubmit="return confirm('همه پیام‌های تراکنش حذف می‌شوند. ادامه می‌دهید؟');"
    >
      <?= csrf_field() ?>
      <button class="btn-secondary" type="submit" name="clear_data" value="1">پاک کردن داده‌ها</button>
    </form>
  </div>

  <p class="mt-3 text-sm text-muted" data-socket-status>
    در حال اتصال به به‌روزرسانی زنده…
  </p>

  <section class="mt-8 flex flex-col gap-3" data-codes-list>
    <p class="text-sm text-muted" data-codes-empty <?= $messages !== [] ? 'hidden' : '' ?>>هنوز پیامی ثبت نشده است.</p>

    <?php foreach ($messages as $message): ?>
      <div class="alert alert-info" data-code-row data-id="<?= e((string) $message['id']) ?>">
        <pre><?= e(transaction_message_display_text($message['data'])) ?></pre>
      </div>
    <?php endforeach; ?>
  </section>
</main>

<?php require APP_ROOT . '/pages/admin/_layout_end.php'; ?>
<script src="<?= e(rtrim($socketUrl, '/')) ?>/socket.io/socket.io.js" defer></script>
<script src="/assets/js/disposable-codes.js" defer></script>
<?php require APP_ROOT . '/pages/admin/_layout_scripts.php'; ?>
