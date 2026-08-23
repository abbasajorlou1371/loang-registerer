<?php

declare(strict_types=1);

$slug = (string) ($_GET['slug'] ?? '');
$product = get_product_by_slug($slug);
if ($product === null) {
    http_response_code(404);
    require APP_ROOT . '/pages/errors/404.php';
    return;
}

$pageTitle = $product['title_fa'] . ' | بانک ملی ایران';
$pageDescription = $product['description_fa'];
$dir = 'rtl';
$lang = 'fa';
$conditions = $product['conditions'];
$isDirect = $product['application_type'] === 'direct';
$officialUrl = (string) ($conditions['official_url'] ?? '');

$previewAmount = (int) $product['max_amount'] > 0
    ? (int) min(150000000, (int) $product['max_amount'])
    : 0;
$previewMonths = (int) ($product['term_options'][1] ?? $product['term_months_min'] ?? 12);
$previewCalc = ['installment' => 0.0, 'total_repayment' => 0.0];
if ($previewAmount > 0 && $product['slug'] === 'mehrabani-melli') {
    $previewCalc = mehrabani_installment($previewAmount, 2.0, $previewMonths);
} elseif ($previewAmount > 0 && $product['slug'] === 'etebar-melli') {
    $previewCalc = etebar_installment($previewAmount, 18.0, $previewMonths);
}

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
  <p class="text-sm text-muted" data-enter="1"><a class="hover:text-ink" href="/loans">وام‌ها</a> / <?= e($product['title_fa']) ?></p>

  <header class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between" data-enter="2">
    <div>
      <div class="flex flex-wrap items-center gap-2">
        <h1 class="page-title"><?= e($product['title_fa']) ?></h1>
        <span class="badge"><?= e(product_type_label($product['product_type'])) ?></span>
      </div>
      <p class="page-lead mt-3 max-w-2xl"><?= e($product['description_fa']) ?></p>
      <p class="mt-3 text-sm text-muted">
        آخرین به‌روزرسانی شرایط: <?= e(format_date_fa($product['last_updated_at'])) ?>
        <?php if ($product['source_url'] !== ''): ?>
          — منبع:
          <a class="font-medium text-cta hover:text-cta-hover" href="<?= e($product['source_url']) ?>" rel="noopener noreferrer" target="_blank">
            <?= e($product['source_label_fa'] !== '' ? $product['source_label_fa'] : 'منبع رسمی') ?>
          </a>
        <?php endif; ?>
      </p>
    </div>
    <div class="flex flex-wrap gap-2">
      <?php if ($isDirect): ?>
        <a class="btn-primary" href="/apply?product=<?= e($product['slug']) ?>">درخواست وام</a>
        <a class="btn-secondary" href="/calculator?tab=<?= e($product['slug']) ?>">محاسبه وام</a>
      <?php elseif ($officialUrl !== ''): ?>
        <a class="btn-primary" href="<?= e($officialUrl) ?>" rel="noopener noreferrer" target="_blank">شروع درخواست رسمی</a>
      <?php endif; ?>
    </div>
  </header>

  <p class="alert mt-8 <?= $isDirect ? '' : 'alert-warning' ?>">
    <?= e(application_type_label($product['application_type'])) ?>
    <?php if (!$isDirect): ?>
      — این سامانه درخواست ازدواج یا فرزندآوری را برای شما به بانک ملی ارسال نمی‌کند.
    <?php endif; ?>
  </p>

  <div class="mt-8 grid gap-6 lg:grid-cols-3" data-enter="3">
    <section class="card p-6 lg:col-span-2" aria-labelledby="finance-title">
      <h2 id="finance-title" class="text-xl font-bold text-navy">اطلاعات مالی</h2>
      <table class="data-table mt-4">
        <tbody>
          <tr>
            <th>سقف مبلغ</th>
            <td><?= (int) $product['max_amount'] > 0 ? e(format_toman((int) $product['max_amount'])) : 'طبق مصوبه جاری بانک مرکزی' ?></td>
          </tr>
          <tr>
            <th>حداقل مبلغ</th>
            <td><?= (int) $product['min_amount'] > 0 ? e(format_amount((int) $product['min_amount'])) : 'اعلام نشده' ?></td>
          </tr>
          <tr>
            <th>نرخ / کارمزد</th>
            <td><?= e(rate_or_fee_label($product)) ?></td>
          </tr>
          <tr>
            <th>مدت بازپرداخت</th>
            <td><?= e(term_label($product)) ?></td>
          </tr>
          <?php if ($previewAmount > 0): ?>
          <tr>
            <th>قسط تقریبی نمونه</th>
            <td><?= e(format_amount($previewCalc['installment'])) ?> برای <?= e(format_toman($previewAmount)) ?> در <?= e(fa_number((string) $previewMonths)) ?> ماه</td>
          </tr>
          <tr>
            <th>مجموع بازپرداخت نمونه</th>
            <td><?= e(format_amount($previewCalc['total_repayment'])) ?></td>
          </tr>
          <?php endif; ?>
          <tr>
            <th>حساب بانک ملی</th>
            <td><?= !empty($conditions['account_required']) ? 'معمولاً لازم است' : 'برای ثبت اولیه الزامی نیست' ?></td>
          </tr>
          <tr>
            <th>مسدودی حساب</th>
            <td><?= empty($conditions['account_blocking']) ? 'بدون مسدودی حساب (طبق اطلاع‌رسانی بانک)' : 'طبق ضوابط بانک' ?></td>
          </tr>
        </tbody>
      </table>
    </section>

    <aside class="card p-6">
      <h2 class="text-lg font-bold text-navy">روش درخواست</h2>
      <p class="mt-3 text-sm leading-relaxed text-muted">
        <?= $isDirect
            ? 'می‌توانید اطلاعات متقاضی را در این سامانه ثبت کنید و کد پیگیری بگیرید. تأیید نهایی با بانک ملی است.'
            : e((string) ($conditions['bank_selection_note_fa'] ?? 'ثبت‌نام از سامانه بانک مرکزی انجام می‌شود.')) ?>
      </p>
      <?php if (!empty($conditions['available_via'])): ?>
        <p class="mt-4 text-sm font-medium text-ink">کانال‌های اعلام‌شده بانک:</p>
        <ul class="mt-2 list-disc ps-5 text-sm text-muted">
          <?php foreach ($conditions['available_via'] as $via): ?>
            <li><?= e((string) $via) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </aside>
  </div>

  <section class="card mt-6 p-6" aria-labelledby="elig-title">
    <h2 id="elig-title" class="text-xl font-bold text-navy">شرایط دریافت</h2>
    <p class="mt-3 text-sm leading-relaxed text-muted"><?= e($product['eligibility_notes_fa']) ?></p>
    <ul class="mt-5 grid gap-3 sm:grid-cols-2">
      <?php foreach ($product['rules'] as $rule): ?>
        <li class="rounded-xl bg-page p-4">
          <p class="font-semibold text-navy"><?= e((string) $rule['label_fa']) ?></p>
          <p class="mt-1 text-sm text-muted"><?= e((string) $rule['description_fa']) ?></p>
        </li>
      <?php endforeach; ?>
      <li class="rounded-xl bg-page p-4">
        <p class="font-semibold text-navy">ضامن</p>
        <p class="mt-1 text-sm text-muted"><?= !empty($conditions['guarantor_required']) ? 'معرفی ضامن مطابق ضوابط شعبه معمولاً لازم است.' : 'طبق ضوابط محصول.' ?></p>
      </li>
    </ul>
  </section>

  <p class="alert mt-6"><?= e(calculator_disclaimer()) ?></p>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
