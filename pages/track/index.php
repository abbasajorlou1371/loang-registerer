<?php

declare(strict_types=1);

$pageTitle = 'پیگیری درخواست | ثبت‌وام';
$pageDescription = 'پیگیری وضعیت درخواست وام با کد پیگیری و شماره موبایل.';
$dir = 'rtl';
$lang = 'fa';

$errors = [];
$application = null;
$tracking = strtoupper(trim((string) ($_POST['tracking'] ?? $_GET['t'] ?? '')));
$mobile = (string) ($_POST['mobile'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'نشست امنیتی نامعتبر است. صفحه را تازه کنید.';
    } elseif (rate_limited('track_lookup', 30, 3600)) {
        $errors[] = 'تعداد پیگیری بیش از حد مجاز است. بعداً دوباره تلاش کنید.';
    } else {
        $tracking = strtoupper(trim((string) ($_POST['tracking'] ?? '')));
        $mobile = (string) ($_POST['mobile'] ?? '');
        if ($tracking === '' || $mobile === '') {
            $errors[] = 'کد پیگیری و شماره موبایل را وارد کنید.';
        } else {
            $application = find_application($tracking, $mobile);
            if ($application === null) {
                $errors[] = 'درخواستی با این مشخصات پیدا نشد.';
            }
        }
    }
}

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
  <h1 class="page-title" data-enter="1">پیگیری درخواست</h1>
  <p class="mt-3 text-muted" data-enter="2">کد پیگیری و شماره موبایلی که هنگام ثبت وارد کرده‌اید را بنویسید.</p>

  <?php if ($errors !== []): ?>
    <div class="alert alert-warning mt-6">
      <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form class="card mt-8 grid gap-4 p-6" method="post" action="/track" data-enter="3">
    <?= csrf_field() ?>
    <label class="block">
      <span class="mb-2 block text-sm font-medium">کد پیگیری</span>
      <input class="field font-latin" name="tracking" value="<?= e($tracking) ?>" placeholder="MEL-839421" required>
    </label>
    <label class="block">
      <span class="mb-2 block text-sm font-medium">شماره موبایل</span>
      <input class="field font-latin" name="mobile" data-mask="mobile" inputmode="numeric" value="<?= e($mobile) ?>" required>
    </label>
    <button class="btn-primary justify-self-start" type="submit">مشاهده وضعیت</button>
  </form>

  <?php if (is_array($application)): ?>
    <section class="card mt-8 p-6" data-enter="4">
      <p class="text-sm text-muted">درخواست</p>
      <p class="font-latin text-2xl font-bold text-navy"><?= e((string) $application['tracking_number']) ?></p>
      <table class="data-table mt-4">
        <tbody>
          <tr><th>وام</th><td><?= e((string) ($application['product']['title_fa'] ?? '')) ?></td></tr>
          <tr><th>مبلغ</th><td><?= e(format_amount((int) ($application['draft']['requested_amount'] ?? 0))) ?></td></tr>
          <tr><th>وضعیت</th><td><?= e(status_label((string) $application['status'])) ?></td></tr>
        </tbody>
      </table>

      <?php if (($application['status'] ?? '') === 'external'): ?>
        <p class="alert alert-warning mt-6">وضعیت داخلی بانک ملی برای این نوع درخواست در دسترس نیست. ادامه کار را از سامانه رسمی پیگیری کنید.</p>
      <?php endif; ?>

      <?php if (!empty($application['history'])): ?>
        <h2 class="mt-6 font-bold text-navy">تاریخچه وضعیت</h2>
        <ol class="mt-4 grid gap-4">
          <?php foreach ($application['history'] as $item): ?>
            <li class="timeline-item">
              <p class="font-medium"><?= e(status_label((string) ($item['to_status'] ?? ''))) ?></p>
              <p class="text-sm text-muted"><?= e((string) ($item['note'] ?? '')) ?> — <?= e(format_date_fa((string) ($item['created_at'] ?? ''))) ?></p>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
