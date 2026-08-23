<?php

declare(strict_types=1);

$pageTitle = 'وام‌های بانک ملی ایران | ثبت‌وام';
$pageDescription = 'فهرست تسهیلات بانک ملی: مهربانی ملی، اعتبار ملی، ازدواج و فرزندآوری.';
$dir = 'rtl';
$lang = 'fa';

$products = get_catalog();
$typeFilter = (string) ($_GET['type'] ?? 'all');
$appFilter = (string) ($_GET['application'] ?? 'all');
$maxFilter = (int) ($_GET['max'] ?? 0);
$termFilter = (int) ($_GET['term'] ?? 0);
$activityFilter = (string) ($_GET['activity'] ?? 'all');
$guarantorFilter = (string) ($_GET['guarantor'] ?? 'all');

$filtered = array_values(array_filter($products, static function (array $product) use ($typeFilter, $appFilter, $maxFilter, $termFilter, $activityFilter, $guarantorFilter): bool {
    if ($typeFilter !== 'all' && $product['slug'] !== $typeFilter && $product['product_type'] !== $typeFilter) {
        return false;
    }
    if ($appFilter !== 'all' && $product['application_type'] !== $appFilter) {
        return false;
    }
    if ($maxFilter > 0 && (int) $product['max_amount'] > 0 && (int) $product['max_amount'] < $maxFilter) {
        return false;
    }
    if ($termFilter > 0 && ($termFilter < (int) $product['term_months_min'] || $termFilter > (int) $product['term_months_max'])) {
        return false;
    }
    $needsActivity = !empty($product['conditions']['activity_ratios']);
    if ($activityFilter === 'yes' && !$needsActivity) {
        return false;
    }
    if ($activityFilter === 'no' && $needsActivity) {
        return false;
    }
    $needsGuarantor = !empty($product['conditions']['guarantor_required']);
    if ($guarantorFilter === 'yes' && !$needsGuarantor) {
        return false;
    }
    if ($guarantorFilter === 'no' && $needsGuarantor) {
        return false;
    }

    return true;
}));

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
  <header class="max-w-2xl">
    <h1 class="page-title" data-enter="1">وام‌های بانک ملی</h1>
    <p class="page-lead mt-3" data-enter="2">به‌جای بازار چندبانکی، فقط تسهیلات جاری بانک ملی ایران را می‌بینید.</p>
  </header>

  <form class="card mt-8 grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3" method="get" action="/loans" data-enter="3">
    <label class="block text-sm">
      <span class="mb-2 block font-medium">نوع وام</span>
      <select class="field" name="type">
        <option value="all" <?= $typeFilter === 'all' ? 'selected' : '' ?>>همه</option>
        <option value="mehrabani-melli" <?= $typeFilter === 'mehrabani-melli' ? 'selected' : '' ?>>مهربانی ملی</option>
        <option value="etebar-melli" <?= $typeFilter === 'etebar-melli' ? 'selected' : '' ?>>اعتبار ملی</option>
        <option value="ezdevaj" <?= $typeFilter === 'ezdevaj' ? 'selected' : '' ?>>ازدواج</option>
        <option value="farzand-avi" <?= $typeFilter === 'farzand-avi' ? 'selected' : '' ?>>فرزندآوری</option>
      </select>
    </label>
    <label class="block text-sm">
      <span class="mb-2 block font-medium">سقف حداقل (تومان)</span>
      <select class="field" name="max">
        <option value="0" <?= $maxFilter === 0 ? 'selected' : '' ?>>مهم نیست</option>
        <option value="100000000" <?= $maxFilter === 100000000 ? 'selected' : '' ?>>تا ۱۰۰ میلیون و بیشتر</option>
        <option value="300000000" <?= $maxFilter === 300000000 ? 'selected' : '' ?>>تا ۳۰۰ میلیون و بیشتر</option>
        <option value="700000000" <?= $maxFilter === 700000000 ? 'selected' : '' ?>>تا ۷۰۰ میلیون</option>
      </select>
    </label>
    <label class="block text-sm">
      <span class="mb-2 block font-medium">مدت بازپرداخت</span>
      <select class="field" name="term">
        <option value="0" <?= $termFilter === 0 ? 'selected' : '' ?>>مهم نیست</option>
        <option value="12" <?= $termFilter === 12 ? 'selected' : '' ?>>۱۲ ماه</option>
        <option value="24" <?= $termFilter === 24 ? 'selected' : '' ?>>۲۴ ماه</option>
        <option value="36" <?= $termFilter === 36 ? 'selected' : '' ?>>۳۶ ماه</option>
        <option value="60" <?= $termFilter === 60 ? 'selected' : '' ?>>۶۰ ماه</option>
      </select>
    </label>
    <label class="block text-sm">
      <span class="mb-2 block font-medium">نیاز به گردش حساب</span>
      <select class="field" name="activity">
        <option value="all" <?= $activityFilter === 'all' ? 'selected' : '' ?>>همه</option>
        <option value="yes" <?= $activityFilter === 'yes' ? 'selected' : '' ?>>بله</option>
        <option value="no" <?= $activityFilter === 'no' ? 'selected' : '' ?>>خیر / دولتی</option>
      </select>
    </label>
    <label class="block text-sm">
      <span class="mb-2 block font-medium">نیاز به ضامن</span>
      <select class="field" name="guarantor">
        <option value="all" <?= $guarantorFilter === 'all' ? 'selected' : '' ?>>همه</option>
        <option value="yes" <?= $guarantorFilter === 'yes' ? 'selected' : '' ?>>معمولاً لازم است</option>
        <option value="no" <?= $guarantorFilter === 'no' ? 'selected' : '' ?>>اعلام نشده</option>
      </select>
    </label>
    <label class="block text-sm">
      <span class="mb-2 block font-medium">روش درخواست</span>
      <select class="field" name="application">
        <option value="all" <?= $appFilter === 'all' ? 'selected' : '' ?>>همه</option>
        <option value="direct" <?= $appFilter === 'direct' ? 'selected' : '' ?>>از این سامانه</option>
        <option value="external" <?= $appFilter === 'external' ? 'selected' : '' ?>>سامانه رسمی</option>
      </select>
    </label>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:col-span-2 lg:col-span-3">
      <button class="btn-primary w-full sm:w-auto" type="submit">اعمال فیلتر</button>
      <a class="btn-secondary w-full sm:w-auto" href="/loans">حذف فیلتر</a>
    </div>
  </form>

  <form class="mt-8" method="get" action="/compare" id="compare-form">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <p class="text-sm text-muted"><?= e(fa_number((string) count($filtered))) ?> مورد</p>
      <button class="btn-secondary" type="submit">مقایسه انتخاب‌شده‌ها</button>
    </div>
    <div class="grid gap-5 md:grid-cols-2">
      <?php if ($filtered === []): ?>
        <p class="card p-6 text-muted sm:col-span-2">وامی با این فیلترها پیدا نشد.</p>
      <?php endif; ?>
      <?php foreach ($filtered as $product): ?>
        <article class="card card-lift flex flex-col p-6 reveal">
          <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" name="products[]" value="<?= e($product['slug']) ?>">
            افزودن به مقایسه
          </label>
          <div class="mt-3 flex items-start justify-between gap-3">
            <h2 class="text-xl font-bold text-navy"><?= e($product['title_fa']) ?></h2>
            <span class="badge"><?= e(product_type_label($product['product_type'])) ?></span>
          </div>
          <p class="stat-value mt-3"><?= e(product_headline_amount($product)) ?></p>
          <ul class="mt-4 grid gap-2 text-sm text-muted">
            <li><?= e(rate_or_fee_label($product)) ?></li>
            <li>بازپرداخت: <?= e(term_label($product)) ?></li>
            <li><?= e(application_type_label($product['application_type'])) ?></li>
          </ul>
          <p class="mt-4 text-xs text-muted">آخرین به‌روزرسانی شرایط: <?= e(format_date_fa($product['last_updated_at'])) ?></p>
          <div class="mt-6 flex flex-wrap gap-2">
            <a class="btn-primary" href="/loans/<?= e($product['slug']) ?>">جزئیات</a>
            <?php if ($product['application_type'] === 'direct'): ?>
              <a class="btn-secondary" href="/calculator?tab=<?= e($product['slug']) ?>">محاسبه</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </form>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
