<?php

declare(strict_types=1);

$pageTitle = 'مقایسه وام‌های بانک ملی | ثبت‌وام';
$pageDescription = 'مقایسه شرایط مهربانی ملی، اعتبار ملی، ازدواج و فرزندآوری.';
$dir = 'rtl';
$lang = 'fa';

$slugs = $_GET['products'] ?? [];
if (is_string($slugs)) {
    $slugs = array_filter(explode(',', $slugs));
}
if (!is_array($slugs) || $slugs === []) {
    $slugs = ['mehrabani-melli', 'etebar-melli'];
}
$slugs = array_values(array_unique(array_map('strval', $slugs)));
$selected = [];
foreach ($slugs as $slug) {
    $product = get_product_by_slug($slug);
    if ($product !== null) {
        $selected[] = $product;
    }
}

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
  <h1 class="page-title" data-enter="1">مقایسه وام‌ها</h1>
  <p class="page-lead mt-3" data-enter="2">شرایط نسخه جاری هر محصول. اعداد قطعی نیستند.</p>

  <?php if ($selected === []): ?>
    <p class="card mt-8 p-6">محصولی برای مقایسه انتخاب نشده است. از صفحه <a class="text-cta" href="/loans">وام‌ها</a> انتخاب کنید.</p>
  <?php else: ?>
    <div class="compare-cards mt-8">
      <?php foreach ($selected as $product): ?>
        <article class="card card-lift p-5 reveal">
          <h2 class="text-base font-bold text-navy"><?= e($product['title_fa']) ?></h2>
          <dl class="mt-4 grid gap-3 text-sm">
            <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
              <dt class="text-muted">سقف مبلغ</dt>
              <dd class="text-end font-semibold text-ink"><?= e(product_headline_amount($product)) ?></dd>
            </div>
            <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
              <dt class="text-muted">نرخ / کارمزد</dt>
              <dd class="text-end font-semibold text-ink"><?= e(rate_or_fee_label($product)) ?></dd>
            </div>
            <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
              <dt class="text-muted">بازپرداخت</dt>
              <dd class="text-end font-semibold text-ink"><?= e(term_label($product)) ?></dd>
            </div>
            <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
              <dt class="text-muted">نوع درخواست</dt>
              <dd class="text-end font-semibold text-ink"><?= e(application_type_label($product['application_type'])) ?></dd>
            </div>
            <div class="flex items-start justify-between gap-3">
              <dt class="text-muted">آخرین به‌روزرسانی</dt>
              <dd class="text-end font-semibold text-ink"><?= e(format_date_fa($product['last_updated_at'])) ?></dd>
            </div>
          </dl>
          <a class="btn-secondary mt-4 w-full" href="/loans/<?= e($product['slug']) ?>">جزئیات</a>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="compare-table-wrap card mt-8 overflow-x-auto p-4 sm:p-6" data-enter="3">
      <table class="data-table min-w-[36rem]">
        <thead>
          <tr>
            <th>شاخص</th>
            <?php foreach ($selected as $product): ?>
              <th><?= e($product['title_fa']) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th>سقف مبلغ</th>
            <?php foreach ($selected as $product): ?>
              <td><?= e(product_headline_amount($product)) ?></td>
            <?php endforeach; ?>
          </tr>
          <tr>
            <th>نرخ / کارمزد</th>
            <?php foreach ($selected as $product): ?>
              <td><?= e(rate_or_fee_label($product)) ?></td>
            <?php endforeach; ?>
          </tr>
          <tr>
            <th>بازپرداخت</th>
            <?php foreach ($selected as $product): ?>
              <td><?= e(term_label($product)) ?></td>
            <?php endforeach; ?>
          </tr>
          <tr>
            <th>نوع درخواست</th>
            <?php foreach ($selected as $product): ?>
              <td><?= e(application_type_label($product['application_type'])) ?></td>
            <?php endforeach; ?>
          </tr>
          <tr>
            <th>آخرین به‌روزرسانی</th>
            <?php foreach ($selected as $product): ?>
              <td><?= e(format_date_fa($product['last_updated_at'])) ?></td>
            <?php endforeach; ?>
          </tr>
          <tr>
            <th></th>
            <?php foreach ($selected as $product): ?>
              <td><a class="text-cta font-semibold" href="/loans/<?= e($product['slug']) ?>">جزئیات</a></td>
            <?php endforeach; ?>
          </tr>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
