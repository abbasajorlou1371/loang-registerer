<?php

declare(strict_types=1);

$pageTitle = 'درخواست وام بانک ملی | ثبت‌وام';
$pageDescription = 'ثبت اطلاعات متقاضی برای مهربانی ملی یا اعتبار ملی بدون ساخت حساب کاربری.';
$dir = 'rtl';
$lang = 'fa';

$slug = (string) ($_POST['product'] ?? $_GET['product'] ?? '');
$product = get_product_by_slug($slug);
$directProducts = array_values(array_filter(get_catalog(), static fn (array $item): bool => $item['application_type'] === 'direct'));
$productRules = [];
foreach ($directProducts as $item) {
    $productRules[$item['slug']] = [
        'min_amount' => (int) $item['min_amount'],
        'max_amount' => (int) $item['max_amount'],
        'term_min' => (int) $item['term_months_min'],
        'term_max' => (int) $item['term_months_max'],
        'account_required' => !empty($item['conditions']['account_required']),
        'fee_options' => array_map('floatval', $item['fee_options'] ?? []),
        'interest_rates' => array_map('floatval', $item['interest_rates'] ?? []),
    ];
}
$errors = [];
$draft = $_SESSION['apply_draft'] ?? application_draft_from(['product' => $slug]);

if ($product && $product['application_type'] === 'external') {
    redirect('/loans/' . $product['slug']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'نشست امنیتی نامعتبر است. صفحه را تازه کنید.';
    } else {
        $draft = application_draft_from($_POST);
        $slug = $draft['product'];
        $product = get_product_by_slug($slug);
        if ($product === null || $product['application_type'] !== 'direct') {
            $errors[] = 'محصول انتخاب‌شده برای ثبت از این سامانه مجاز نیست.';
        } else {
            $draft = normalize_application_draft($draft, $product);
            $errors = validate_application_draft($draft, $product);
        }
        if ($errors === [] && $product !== null) {
            $_SESSION['apply_draft'] = $draft;
            redirect('/apply/review');
        }
    }
}

$selectedProduct = ($product && ($product['application_type'] ?? '') === 'direct') ? $product : null;
$termMin = (int) ($selectedProduct['term_months_min'] ?? 6);
$termMax = (int) ($selectedProduct['term_months_max'] ?? 60);
$currentSlug = (string) ($draft['product'] ?? $slug);
$isEtebar = $currentSlug === 'etebar-melli';
$isMehrabani = $currentSlug === 'mehrabani-melli';

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
  <h1 class="page-title" data-enter="1">ثبت درخواست وام</h1>
  <p class="page-lead mt-3" data-enter="2">بدون ورود یا ساخت حساب. فقط اطلاعات لازم برای مهربانی ملی و اعتبار ملی را وارد کنید.</p>

  <?php if ($errors !== []): ?>
    <div class="alert alert-warning mt-6">
      <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form id="apply-form" class="card mt-8 grid gap-5 p-6" method="post" action="/apply" novalidate data-enter="3">
    <?= csrf_field() ?>
    <script type="application/json" id="apply-product-rules"><?= json_encode($productRules, JSON_UNESCAPED_UNICODE) ?></script>

    <label class="block">
      <span class="mb-2 block text-sm font-medium">نوع وام</span>
      <select class="field" name="product" required>
        <option value="">انتخاب کنید</option>
        <?php foreach ($directProducts as $item): ?>
          <option value="<?= e($item['slug']) ?>" <?= ($draft['product'] ?? '') === $item['slug'] ? 'selected' : '' ?>>
            <?= e($item['title_fa']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <div class="grid gap-5 sm:grid-cols-2">
      <label class="block sm:col-span-2">
        <span class="mb-2 block text-sm font-medium">نام و نام خانوادگی</span>
        <input class="field" name="full_name" maxlength="150" autocomplete="name" value="<?= e((string) ($draft['full_name'] ?? '')) ?>" required>
      </label>
      <label class="block">
        <span class="mb-2 block text-sm font-medium">کد ملی</span>
        <input class="field font-latin" name="national_id" inputmode="numeric" maxlength="10" autocomplete="off" value="<?= e((string) ($draft['national_id'] ?? '')) ?>" required>
      </label>
      <label class="block">
        <span class="mb-2 block text-sm font-medium">شماره موبایل</span>
        <input class="field font-latin" name="mobile" data-mask="mobile" inputmode="numeric" autocomplete="tel" value="<?= e((string) ($draft['mobile'] ?? '')) ?>" required>
      </label>
      <label class="block">
        <span class="mb-2 block text-sm font-medium">تاریخ تولد</span>
        <input
          class="field font-latin"
          type="text"
          name="birth_date"
          data-jdp
          data-jdp-only-date
          data-jdp-max-date="today"
          data-jdp-min-date="1300/01/01"
          placeholder="۱۳۷۰/۰۱/۱۵"
          autocomplete="off"
          value="<?= e(jalali_date_string((string) ($draft['birth_date'] ?? ''))) ?>"
          required
        >
      </label>
      <label class="block">
        <span class="mb-2 block text-sm font-medium">وضعیت شغلی</span>
        <select class="field" name="employment_type" required>
          <?php
          $jobs = ['' => 'انتخاب کنید', 'employed' => 'شاغل', 'self_employed' => 'شغل آزاد', 'retired' => 'بازنشسته', 'other' => 'سایر'];
          $currentJob = (string) ($draft['employment_type'] ?? '');
          foreach ($jobs as $value => $label):
          ?>
            <option value="<?= e($value) ?>" <?= $currentJob === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="block">
        <span class="mb-2 block text-sm font-medium">درآمد ماهانه (تومان)</span>
        <input class="field font-latin js-thousands" type="text" name="monthly_income" inputmode="numeric" data-thousands value="<?= e(format_thousands(parse_int_input($draft['monthly_income'] ?? 0))) ?>">
      </label>
    </div>

    <fieldset class="grid gap-3 rounded-xl bg-page p-4">
      <legend class="text-sm font-medium">حساب بانک ملی</legend>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" id="has-account" name="has_account" value="1" <?= !empty($draft['has_account']) ? 'checked' : '' ?>>
        حساب بانک ملی دارم
      </label>
      <label class="block">
        <span class="mb-2 block text-sm font-medium">شماره حساب / کارت (اختیاری)</span>
        <input class="field font-latin" name="bank_melli_account" maxlength="40" value="<?= e((string) ($draft['bank_melli_account'] ?? '')) ?>">
      </label>
      <label class="block">
        <span class="mb-2 block text-sm font-medium">مدت گردش حساب (ماه)</span>
        <input class="field font-latin" type="number" name="account_activity_months" min="0" max="120" value="<?= e((string) ($draft['account_activity_months'] ?? '12')) ?>">
      </label>
      <label class="block">
        <span class="mb-2 block text-sm font-medium">معدل حساب (تومان)</span>
        <input class="field font-latin js-thousands" type="text" name="average_balance" inputmode="numeric" data-thousands value="<?= e(format_thousands(parse_int_input($draft['average_balance'] ?? 0))) ?>">
      </label>
    </fieldset>

    <div class="grid gap-5 sm:grid-cols-2">
      <label class="block">
        <span class="mb-2 block text-sm font-medium">مبلغ درخواستی (تومان)</span>
        <input class="field font-latin js-thousands" type="text" name="requested_amount" inputmode="numeric" data-thousands value="<?= e(format_thousands(parse_int_input($draft['requested_amount'] ?? 0))) ?>" required>
      </label>
      <label class="block">
        <span class="mb-2 block text-sm font-medium">مدت بازپرداخت (ماه)</span>
        <input class="field font-latin" type="number" name="repayment_months" min="<?= e((string) $termMin) ?>" max="<?= e((string) $termMax) ?>" value="<?= e((string) ($draft['repayment_months'] ?? '24')) ?>" required>
      </label>
    </div>

    <fieldset class="grid gap-3">
      <legend class="text-sm font-medium">اطلاعات تکمیلی اعتبار ملی / مهربانی ملی</legend>
      <label class="block <?= $isEtebar ? '' : 'hidden' ?>" data-apply-for="etebar-melli">
        <span class="mb-2 block text-sm font-medium">نوع متقاضی</span>
        <select class="field" name="applicant_type">
          <option value="individual" <?= ($draft['applicant_type'] ?? '') === 'individual' ? 'selected' : '' ?>>حقیقی</option>
          <option value="legal" <?= ($draft['applicant_type'] ?? '') === 'legal' ? 'selected' : '' ?>>حقوقی</option>
        </select>
      </label>
      <label class="block <?= $isEtebar ? '' : 'hidden' ?>" data-apply-for="etebar-melli">
        <span class="mb-2 block text-sm font-medium">نرخ سود مورد نظر (اعتبار ملی)</span>
        <select class="field" name="interest_rate">
          <option value="">انتخاب کنید</option>
          <?php foreach ([14, 18, 23] as $rate): ?>
            <option value="<?= $rate ?>" <?= (int) ($draft['interest_rate'] ?? 0) === $rate ? 'selected' : '' ?>><?= e(fa_number((string) $rate)) ?>٪</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="block <?= $isMehrabani ? '' : 'hidden' ?>" data-apply-for="mehrabani-melli">
        <span class="mb-2 block text-sm font-medium">کارمزد (مهربانی ملی)</span>
        <select class="field" name="fee_option">
          <?php foreach ([0, 2, 4] as $fee): ?>
            <option value="<?= $fee ?>" <?= (int) ($draft['fee_option'] ?? 0) === $fee ? 'selected' : '' ?>><?= e(fa_number((string) $fee)) ?>٪</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="has_guarantor" value="1" <?= !empty($draft['has_guarantor']) ? 'checked' : '' ?>>
        امکان معرفی ضامن دارم
      </label>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="existing_obligations" value="1" <?= !empty($draft['existing_obligations']) ? 'checked' : '' ?>>
        تسهیلات معوق یا تعهد جاری دارم
      </label>
    </fieldset>

    <p class="text-sm text-muted">با ثبت، تأیید می‌کنید که اطلاعات صحیح است و می‌دانید تأیید نهایی با بانک ملی است.</p>
    <button class="btn-primary justify-self-start" type="submit">بررسی اطلاعات</button>
  </form>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
