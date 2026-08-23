<?php

declare(strict_types=1);

$pageTitle = 'محاسبه وام بانک ملی | ثبت‌وام';
$pageDescription = 'محاسبه برآورد مهربانی ملی، اعتبار ملی و قسط عمومی.';
$dir = 'rtl';
$lang = 'fa';

$tab = (string) ($_GET['tab'] ?? 'mehrabani-melli');
$allowedTabs = ['mehrabani-melli', 'etebar-melli', 'general'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'mehrabani-melli';
}

$mehrabani = get_product_by_slug('mehrabani-melli');
$etebar = get_product_by_slug('etebar-melli');

$errors = [];
$postedTab = (string) ($_POST['tab'] ?? $tab);
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'نشست امنیتی نامعتبر است. صفحه را تازه کنید و دوباره تلاش کنید.';
    } else {
        $tab = in_array($postedTab, $allowedTabs, true) ? $postedTab : $tab;
        $amount = parse_int_input($_POST['amount'] ?? 0);
        $months = parse_int_input($_POST['months'] ?? 0);
        $balance = parse_int_input($_POST['average_balance'] ?? 0);
        $activity = parse_int_input($_POST['activity_months'] ?? 0);
        $hasAccount = ($_POST['has_account'] ?? '1') === '1';

        if ($amount <= 0) {
            $errors[] = 'مبلغ درخواستی را وارد کنید.';
        }
        if ($months <= 0) {
            $errors[] = 'مدت بازپرداخت را انتخاب کنید.';
        }

        if ($errors === []) {
            if ($tab === 'mehrabani-melli' && $mehrabani) {
                $fee = (float) ($_POST['fee'] ?? 2);
                $installment = mehrabani_installment($amount, $fee, $months);
                $eligible = estimate_eligible_amount($mehrabani, [
                    'average_balance' => $balance,
                    'activity_months' => $activity,
                    'repayment_months' => $months,
                    'has_account' => $hasAccount,
                ]);
                $result = [
                    'eligible' => $eligible,
                    'installment' => $installment['installment'],
                    'total' => $installment['total_repayment'],
                    'interest' => $installment['total_interest'],
                    'activity_note' => 'قسط نمایش‌داده‌شده مربوط به ماه‌های بازپرداخت اصل است. کارمزد قرض‌الحسنه در ابتدای هر سال روی مانده بدهی محاسبه می‌شود.',
                ];
            } elseif ($tab === 'etebar-melli' && $etebar) {
                $rate = (float) ($_POST['rate'] ?? 18);
                $installment = etebar_installment($amount, $rate, $months);
                $eligible = estimate_eligible_amount($etebar, [
                    'average_balance' => $balance,
                    'activity_months' => $activity,
                    'repayment_months' => $months,
                    'has_account' => $hasAccount,
                ]);
                $result = [
                    'eligible' => $eligible,
                    'installment' => $installment['installment'],
                    'total' => $installment['total_repayment'],
                    'interest' => $installment['total_interest'],
                    'activity_note' => 'سقف برآوردی به گردش حساب و مدت بازپرداخت وابسته است.',
                ];
            } else {
                $rate = (float) ($_POST['rate'] ?? 18);
                $installment = general_installment($amount, $rate, $months);
                $result = [
                    'eligible' => $amount,
                    'installment' => $installment['installment'],
                    'total' => $installment['total_repayment'],
                    'interest' => $installment['total_interest'],
                    'activity_note' => 'این محاسبه عمومی است و به محصول خاصی وابسته نیست.',
                ];
            }
        }
    }
}

$calcConfig = [
    'mehrabani' => $mehrabani ? [
        'max' => $mehrabani['max_amount'],
        'min' => $mehrabani['min_amount'],
        'activity_ratios' => $mehrabani['conditions']['activity_ratios'] ?? [],
        'term_ratios' => [],
    ] : null,
    'etebar' => $etebar ? [
        'max' => $etebar['max_amount'],
        'min' => $etebar['min_amount'],
        'activity_ratios' => $etebar['conditions']['activity_ratios'] ?? [],
        'term_ratios' => $etebar['conditions']['term_ratios'] ?? [],
    ] : null,
];

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8" data-calculator>
  <script type="application/json" id="calc-config"><?= json_encode($calcConfig, JSON_UNESCAPED_UNICODE) ?></script>
  <h1 class="page-title" data-enter="1">محاسبه وام</h1>
  <p class="page-lead mt-3 max-w-2xl" data-enter="2">برآورد اولیه برای مهربانی ملی و اعتبار ملی. نتیجه نهایی را بانک ملی تعیین می‌کند.</p>

  <div class="tab-group mt-8" role="tablist" data-enter="3">
    <a class="<?= $tab === 'mehrabani-melli' ? 'btn-primary' : 'btn-secondary' ?>" href="/calculator?tab=mehrabani-melli">مهربانی ملی</a>
    <a class="<?= $tab === 'etebar-melli' ? 'btn-primary' : 'btn-secondary' ?>" href="/calculator?tab=etebar-melli">اعتبار ملی</a>
    <a class="<?= $tab === 'general' ? 'btn-primary' : 'btn-secondary' ?>" href="/calculator?tab=general">قسط عمومی</a>
  </div>

  <?php if ($errors !== []): ?>
    <div class="alert alert-warning mt-6">
      <?php foreach ($errors as $error): ?>
        <p><?= e($error) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="mt-8 grid gap-6 lg:grid-cols-5">
    <form class="card grid gap-5 p-6 lg:col-span-3" method="post" action="/calculator?tab=<?= e($tab) ?>" data-calc-form data-calc-tab="<?= e($tab) ?>" data-enter="4">
      <?= csrf_field() ?>
      <input type="hidden" name="tab" value="<?= e($tab) ?>">

      <?php if ($tab !== 'general'): ?>
        <label class="block">
          <span class="mb-2 block text-sm font-medium">معدل حساب (تومان)</span>
          <input class="field font-latin js-thousands" data-calc-input="average_balance" data-thousands type="text" name="average_balance" inputmode="numeric" value="<?= e(format_thousands(parse_int_input($_POST['average_balance'] ?? 100000000))) ?>">
        </label>
        <label class="block">
          <span class="mb-2 block text-sm font-medium">دوره گردش حساب</span>
          <select class="field" name="activity_months" data-calc-input="activity_months">
            <?php foreach ([6, 12, 18, 24] as $m): ?>
              <option value="<?= $m ?>" <?= ((int) ($_POST['activity_months'] ?? 12)) === $m ? 'selected' : '' ?>><?= e(fa_number((string) $m)) ?> ماه<?= $m === 24 ? ' و بیشتر' : '' ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="flex items-center gap-2 text-sm">
          <input type="hidden" name="has_account" value="0">
          <input type="checkbox" name="has_account" value="1" data-calc-input="has_account" <?= ($_POST['has_account'] ?? '1') === '1' ? 'checked' : '' ?>>
          حساب بانک ملی دارم
        </label>
      <?php endif; ?>

      <label class="block">
        <span class="mb-2 block text-sm font-medium">مبلغ درخواستی (تومان)</span>
        <input class="field font-latin js-thousands" data-calc-input="amount" data-thousands type="text" name="amount" inputmode="numeric" value="<?= e(format_thousands(parse_int_input($_POST['amount'] ?? 150000000))) ?>" required>
      </label>

      <?php if ($tab === 'mehrabani-melli'): ?>
        <label class="block">
          <span class="mb-2 block text-sm font-medium">مدت بازپرداخت (ماه)</span>
          <input class="field" data-calc-input="months" type="number" name="months" min="6" max="60" value="<?= e((string) ($_POST['months'] ?? '24')) ?>">
        </label>
        <fieldset>
          <legend class="mb-2 text-sm font-medium">گزینه کارمزد</legend>
          <div class="flex flex-wrap gap-3">
            <?php foreach ([0, 2, 4] as $fee): ?>
              <label class="inline-flex items-center gap-2 text-sm">
                <input type="radio" name="fee" data-calc-input="fee" value="<?= $fee ?>" <?= ((int) ($_POST['fee'] ?? 2)) === $fee ? 'checked' : '' ?>>
                <?= e(fa_number((string) $fee)) ?>٪
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
      <?php elseif ($tab === 'etebar-melli'): ?>
        <fieldset>
          <legend class="mb-2 text-sm font-medium">نرخ سود</legend>
          <div class="flex flex-wrap gap-3">
            <?php foreach ([14, 18, 23] as $rate): ?>
              <label class="inline-flex items-center gap-2 text-sm">
                <input type="radio" name="rate" data-calc-input="rate" value="<?= $rate ?>" <?= ((int) ($_POST['rate'] ?? 18)) === $rate ? 'checked' : '' ?>>
                <?= e(fa_number((string) $rate)) ?>٪
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <fieldset>
          <legend class="mb-2 text-sm font-medium">مدت بازپرداخت</legend>
          <div class="flex flex-wrap gap-3">
            <?php foreach ([12, 24, 36, 48, 60] as $m): ?>
              <label class="inline-flex items-center gap-2 text-sm">
                <input type="radio" name="months" data-calc-input="months" value="<?= $m ?>" <?= ((int) ($_POST['months'] ?? 24)) === $m ? 'checked' : '' ?>>
                <?= e(fa_number((string) $m)) ?> ماه
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
      <?php else: ?>
        <label class="block">
          <span class="mb-2 block text-sm font-medium">نرخ سالانه (٪)</span>
          <input class="field" data-calc-input="rate" type="number" name="rate" min="0" max="40" step="0.1" value="<?= e((string) ($_POST['rate'] ?? '18')) ?>">
        </label>
        <label class="block">
          <span class="mb-2 block text-sm font-medium">مدت (ماه)</span>
          <input class="field" data-calc-input="months" type="number" name="months" min="1" max="120" value="<?= e((string) ($_POST['months'] ?? '24')) ?>">
        </label>
      <?php endif; ?>

      <button class="btn-primary w-full" type="submit">محاسبه</button>
 
    </form>

    <aside class="card card-featured p-6 lg:col-span-2" data-calc-output data-enter="4">
      <h2 class="text-lg font-bold text-navy">نتیجه برآورد</h2>
      <dl class="mt-4 grid gap-4">
        <div class="rounded-xl bg-page p-4">
          <dt class="text-xs text-muted">سقف برآوردی</dt>
          <dd class="stat-value mt-1" data-out="eligible"><?= $result ? e(format_toman((int) $result['eligible'])) : '—' ?></dd>
        </div>
        <div class="stat-tile-cta rounded-xl p-4">
          <dt class="text-xs text-muted">قسط ماهانه</dt>
          <dd class="stat-value mt-1 text-cta" data-out="installment"><?= $result ? e(format_amount((float) $result['installment'])) : '—' ?></dd>
        </div>
        <div class="rounded-xl bg-page p-4">
          <dt class="text-xs text-muted">مجموع بازپرداخت</dt>
          <dd class="mt-1 font-bold text-navy" data-out="total"><?= $result ? e(format_amount((float) $result['total'])) : '—' ?></dd>
        </div>
        <div class="rounded-xl bg-page p-4">
          <dt class="text-xs text-muted">کارمزد / سود کل</dt>
          <dd class="mt-1 font-bold text-navy" data-out="interest"><?= $result ? e(format_amount((float) $result['interest'])) : '—' ?></dd>
        </div>
      </dl>
      <p class="mt-4 text-sm text-muted" data-out="note"><?= $result ? e((string) $result['activity_note']) : 'مقادیر را وارد کنید تا برآورد به‌روز شود.' ?></p>
      <p class="alert mt-4"><?= e(calculator_disclaimer()) ?></p>
      <?php if ($tab === 'mehrabani-melli'): ?>
        <a class="btn-primary mt-4 w-full" href="/apply?product=mehrabani-melli">ادامه به درخواست</a>
      <?php elseif ($tab === 'etebar-melli'): ?>
        <a class="btn-primary mt-4 w-full" href="/apply?product=etebar-melli">ادامه به درخواست</a>
      <?php endif; ?>
    </aside>
  </div>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
