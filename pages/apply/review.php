<?php

declare(strict_types=1);

$draft = $_SESSION['apply_draft'] ?? null;
if (!is_array($draft)) {
    redirect('/apply');
}

$product = get_product_by_slug((string) ($draft['product'] ?? ''));
if ($product === null || $product['application_type'] !== 'direct') {
    redirect('/apply');
}

$pageTitle = 'بازبینی درخواست | ثبت‌وام';
$pageDescription = 'بازبینی اطلاعات و پرداخت هزینه ثبت درخواست وام بانک ملی.';
$dir = 'rtl';
$lang = 'fa';

$draft = normalize_application_draft($draft, $product);
$_SESSION['apply_draft'] = $draft;
$quote = application_quote($draft, $product);
$errors = validate_application_draft($draft, $product);

$jobLabels = [
    'employed' => 'شاغل',
    'self_employed' => 'شغل آزاد',
    'retired' => 'بازنشسته',
    'other' => 'سایر',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'نشست امنیتی نامعتبر است. صفحه را تازه کنید.';
    } elseif (rate_limited('apply_submit', 10, 3600)) {
        $errors[] = 'تعداد درخواست‌های شما بیش از حد مجاز است. بعداً دوباره تلاش کنید.';
        app_log('warning', 'Application rate limit hit', ['ip' => client_ip()]);
    } elseif ($errors === []) {
        create_demo_payment($draft, $product);
        redirect('/pay');
    }
}

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
  <h1 class="page-title" data-enter="1">بازبینی اطلاعات</h1>
  <p class="mt-3 text-muted" data-enter="2">قبل از پرداخت هزینه ثبت و ارسال درخواست، موارد زیر را بررسی کنید.</p>

  <?php if ($errors !== []): ?>
    <div class="alert alert-warning mt-6">
      <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <section class="card mt-8 p-6" data-enter="3">
    <table class="data-table">
      <tbody>
        <tr><th>وام</th><td><?= e($product['title_fa']) ?> (نسخه <?= e(fa_number((string) $product['version'])) ?>)</td></tr>
        <tr><th>نام</th><td><?= e((string) $draft['full_name']) ?></td></tr>
        <tr><th>کد ملی</th><td class="font-latin"><?= e((string) $draft['national_id']) ?></td></tr>
        <tr><th>موبایل</th><td class="font-latin"><?= e((string) $draft['mobile']) ?></td></tr>
        <tr><th>تاریخ تولد</th><td><?= e(format_date_fa((string) $draft['birth_date'])) ?></td></tr>
        <tr><th>شغل</th><td><?= e($jobLabels[$draft['employment_type']] ?? (string) $draft['employment_type']) ?></td></tr>
        <tr><th>درآمد ماهانه</th><td><?= (int) $draft['monthly_income'] > 0 ? e(format_amount((int) $draft['monthly_income'])) : '—' ?></td></tr>
        <tr><th>حساب بانک ملی</th><td><?= !empty($draft['has_account']) ? 'دارد' : 'ندارد' ?></td></tr>
        <tr><th>معدل حساب</th><td><?= (int) $draft['average_balance'] > 0 ? e(format_amount((int) $draft['average_balance'])) : '—' ?></td></tr>
        <tr><th>گردش حساب</th><td><?= (int) $draft['account_activity_months'] > 0 ? e(fa_number((string) $draft['account_activity_months']) . ' ماه') : '—' ?></td></tr>
        <tr><th>مبلغ درخواستی</th><td><?= e(format_amount((int) $draft['requested_amount'])) ?></td></tr>
        <tr><th>بازپرداخت</th><td><?= e(fa_number((string) $draft['repayment_months'])) ?> ماه</td></tr>
        <?php if ($product['slug'] === 'mehrabani-melli'): ?>
          <tr><th>کارمزد</th><td><?= e(fa_number((string) (int) $draft['fee_option'])) ?>٪</td></tr>
        <?php elseif ($product['slug'] === 'etebar-melli'): ?>
          <tr><th>نوع متقاضی</th><td><?= ($draft['applicant_type'] ?? '') === 'legal' ? 'حقوقی' : 'حقیقی' ?></td></tr>
          <tr><th>نرخ سود</th><td><?= e(fa_number((string) (int) $draft['interest_rate'])) ?>٪</td></tr>
        <?php endif; ?>
        <tr><th>سقف برآوردی</th><td><?= (int) $quote['eligible'] > 0 ? e(format_toman((int) $quote['eligible'])) : '—' ?></td></tr>
        <tr><th>قسط ماهانه</th><td><?= (float) $quote['installment'] > 0 ? e(format_amount((float) $quote['installment'])) : '—' ?></td></tr>
        <tr><th>مجموع بازپرداخت</th><td><?= (float) $quote['total'] > 0 ? e(format_amount((float) $quote['total'])) : '—' ?></td></tr>
        <tr><th>کارمزد / سود کل</th><td><?= (float) $quote['interest'] > 0 ? e(format_amount((float) $quote['interest'])) : '—' ?></td></tr>
        <tr><th>ضامن</th><td><?= !empty($draft['has_guarantor']) ? 'دارد' : 'ندارد' ?></td></tr>
      </tbody>
    </table>
  </section>

  <aside class="alert mt-6">
    <p>برای ثبت درخواست، هزینه <?= e(format_rial_fa(demo_payment_amount_rial())) ?> ریال از درگاه پرداخت می‌شود.</p>
  </aside>

  <form class="mt-6 flex flex-wrap gap-3" method="post" action="/apply/review">
    <?= csrf_field() ?>
    <a class="btn-secondary" href="/apply">ویرایش</a>
    <button class="btn-primary" type="submit" <?= $errors !== [] ? 'disabled' : '' ?>>پرداخت و ثبت درخواست</button>
  </form>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
