<?php

declare(strict_types=1);

$pageTitle = 'ورود مدیر | ثبت‌وام';
$pageDescription = 'ورود به پنل مدیریت سامانه ثبت‌وام.';
$lang = 'fa';

if (auth_is_admin()) {
    redirect('/admin');
}

$errors = [];
$username = trim((string) ($_POST['username'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'نشست امنیتی نامعتبر است. صفحه را تازه کنید.';
    } elseif (rate_limited('admin_login', 10, 3600)) {
        $errors[] = 'تعداد تلاش ورود بیش از حد مجاز است. بعداً دوباره تلاش کنید.';
    } else {
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            $errors[] = 'نام کاربری و رمز عبور را وارد کنید.';
        } elseif (!attempt_admin_login($username, $password)) {
            $errors[] = 'نام کاربری یا رمز عبور نادرست است.';
        } else {
            redirect(admin_intended_path());
        }
    }
}

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-md px-4 py-12 sm:px-6 lg:px-8">
  <h1 class="page-title">ورود مدیر</h1>
  <p class="page-lead mt-3">برای مشاهده درخواست‌ها و وضعیت پرداخت وارد شوید.</p>

  <?php if ($errors !== []): ?>
    <div class="alert alert-warning mt-6">
      <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form class="card mt-8 grid gap-5 p-6" method="post" action="/admin/login" autocomplete="username">
    <?= csrf_field() ?>
    <label class="block">
      <span class="mb-2 block text-sm font-medium">نام کاربری</span>
      <input class="field font-latin" name="username" value="<?= e($username) ?>" required autofocus>
    </label>
    <label class="block">
      <span class="mb-2 block text-sm font-medium">رمز عبور</span>
      <input class="field font-latin" type="password" name="password" required autocomplete="current-password">
    </label>
    <button class="btn-primary" type="submit">ورود به پنل</button>
  </form>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
