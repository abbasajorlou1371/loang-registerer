<?php

declare(strict_types=1);

$pageTitle = 'بررسی شرایط دریافت وام | ثبت‌وام';
$pageDescription = 'پرسش‌های کوتاه برای برآورد اولیه واجد شرایط بودن تسهیلات بانک ملی.';
$dir = 'rtl';
$lang = 'fa';

$step = (int) ($_POST['step'] ?? 0);
$errors = [];
$result = null;

$draft = [
    'purpose' => (string) ($_POST['purpose'] ?? $_GET['purpose'] ?? 'general'),
    'amount' => parse_int_input($_POST['amount'] ?? $_GET['amount'] ?? 0),
    'has_account' => (string) ($_POST['has_account'] ?? '1'),
    'activity_months' => (int) ($_POST['activity_months'] ?? 12),
    'average_balance' => parse_int_input($_POST['average_balance'] ?? 0),
    'repayment_months' => (int) ($_POST['repayment_months'] ?? 24),
    'has_guarantor' => (string) ($_POST['has_guarantor'] ?? '1'),
    'applicant_type' => (string) ($_POST['applicant_type'] ?? 'individual'),
];

if (in_array($draft['purpose'], ['marriage', 'childbirth'], true) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $result = evaluate_eligibility([
        'purpose' => $draft['purpose'],
        'amount' => $draft['amount'],
        'has_account' => $draft['has_account'] === '1',
        'activity_months' => $draft['activity_months'],
        'average_balance' => $draft['average_balance'],
        'repayment_months' => $draft['repayment_months'],
        'has_guarantor' => $draft['has_guarantor'] === '1',
        'applicant_type' => $draft['applicant_type'],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'نشست امنیتی نامعتبر است. صفحه را تازه کنید.';
        $step = max(0, $step - 1);
    } elseif (in_array($draft['purpose'], ['marriage', 'childbirth'], true)) {
        $result = evaluate_eligibility([
            'purpose' => $draft['purpose'],
            'amount' => $draft['amount'],
            'has_account' => $draft['has_account'] === '1',
            'activity_months' => $draft['activity_months'],
            'average_balance' => $draft['average_balance'],
            'repayment_months' => $draft['repayment_months'],
            'has_guarantor' => $draft['has_guarantor'] === '1',
            'applicant_type' => $draft['applicant_type'],
        ]);
    } elseif (isset($_POST['back'])) {
        $step = max(0, $step - 2);
    } elseif ($step >= 4) {
        $result = evaluate_eligibility([
            'purpose' => $draft['purpose'],
            'amount' => $draft['amount'],
            'has_account' => $draft['has_account'] === '1',
            'activity_months' => $draft['activity_months'],
            'average_balance' => $draft['average_balance'],
            'repayment_months' => $draft['repayment_months'],
            'has_guarantor' => $draft['has_guarantor'] === '1',
            'applicant_type' => $draft['applicant_type'],
        ]);
        $_SESSION['eligibility_result'] = [
            'result' => $result['result'],
            'slug' => $result['recommended_product']['slug'] ?? null,
        ];
    }
}

$step = max(0, min(4, $step));
$showResult = $result !== null;

require APP_ROOT . '/includes/header.php';
?>

<main id="main" class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
  <h1 class="page-title" data-enter="1">بررسی شرایط دریافت وام</h1>
  <p class="mt-3 text-muted" data-enter="2">پاسخ‌ها فقط برای برآورد اولیه است و به معنای «حتماً واجد شرایط هستید» نیست.</p>

  <?php if (!$showResult): ?>
    <div class="mt-6 flex items-center gap-2" aria-hidden="true" data-enter="3">
      <?php for ($i = 0; $i < 4; $i++): ?>
        <span class="wizard-dot <?= $i <= $step ? 'is-active' : '' ?>"></span>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <?php if ($errors !== []): ?>
    <div class="alert alert-warning mt-6">
      <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($showResult && is_array($result)): ?>
    <?php
    $tone = match ($result['result']) {
        'eligible' => 'احتمالاً واجد شرایط هستید',
        'potentially_eligible' => 'ممکن است واجد شرایط باشید',
        'not_eligible' => 'با اطلاعات فعلی منطبق نیست',
        'external' => 'باید از سامانه رسمی اقدام کنید',
        default => eligibility_label($result['result']),
    };
    $product = $result['recommended_product'];
    ?>
    <section class="card card-featured mt-8 p-6 sm:p-8" data-enter="3">
      <p class="badge"><?= e(eligibility_label($result['result'])) ?></p>
      <h2 class="mt-4 text-2xl font-bold text-navy"><?= e($tone) ?></h2>
      <p class="mt-3 text-sm leading-relaxed text-muted"><?= e($result['explanation_fa']) ?></p>
      <?php if ($result['reasons'] !== []): ?>
        <ul class="mt-4 list-disc ps-5 text-sm text-muted">
          <?php foreach ($result['reasons'] as $reason): ?>
            <li><?= e($reason) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if (is_array($product)): ?>
        <article class="mt-6 rounded-xl bg-page p-5">
          <h3 class="text-lg font-bold text-navy"><?= e($product['title_fa']) ?></h3>
          <p class="mt-2 text-sm text-muted"><?= e(product_headline_amount($product)) ?></p>
          <div class="mt-4 flex flex-wrap gap-2">
            <a class="btn-secondary" href="/loans/<?= e($product['slug']) ?>">جزئیات وام</a>
            <?php if ($product['application_type'] === 'direct'): ?>
              <a class="btn-primary" href="/apply?product=<?= e($product['slug']) ?>">درخواست وام</a>
            <?php elseif (!empty($product['conditions']['official_url'])): ?>
              <a class="btn-primary" href="<?= e((string) $product['conditions']['official_url']) ?>" rel="noopener noreferrer" target="_blank">شروع درخواست رسمی</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endif; ?>

      <?php if (!empty($result['details']) && count($result['details']) > 1): ?>
        <div class="mt-6 grid gap-3">
          <?php foreach ($result['details'] as $detail): ?>
            <?php if (!$detail['product']) { continue; } ?>
            <p class="text-sm text-muted">
              <span class="font-semibold text-ink"><?= e($detail['product']['title_fa']) ?>:</span>
              <?= e(eligibility_label($detail['result'])) ?>
            </p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
    <a class="btn-secondary mt-6 inline-flex" href="/eligibility">شروع دوباره</a>
  <?php else: ?>
    <form class="card mt-8 grid gap-5 p-6" method="post" action="/eligibility" data-enter="4">
      <?= csrf_field() ?>
      <input type="hidden" name="step" value="<?= $step + 1 ?>">
      <?php foreach ($draft as $key => $value): ?>
        <?php if ($step === 0 && in_array($key, ['purpose', 'amount'], true)) { continue; } ?>
        <?php if ($step === 1 && in_array($key, ['has_account', 'activity_months', 'average_balance'], true)) { continue; } ?>
        <?php if ($step === 2 && in_array($key, ['repayment_months', 'has_guarantor'], true)) { continue; } ?>
        <?php if ($step === 3 && $key === 'applicant_type') { continue; } ?>
        <input type="hidden" name="<?= e($key) ?>" value="<?= e((string) $value) ?>">
      <?php endforeach; ?>

      <?php if ($step === 0): ?>
        <h2 class="font-bold text-navy">گام ۱ از ۴ — نیاز شما</h2>
        <label class="block">
          <span class="mb-2 block text-sm font-medium">چقدر پول لازم دارید؟ (تومان)</span>
          <input class="field font-latin js-thousands" type="text" name="amount" inputmode="numeric" data-thousands value="<?= $draft['amount'] > 0 ? e(format_thousands($draft['amount'])) : '' ?>" required>
        </label>
        <label class="block">
          <span class="mb-2 block text-sm font-medium">هدف دریافت وام چیست؟</span>
          <select class="field" name="purpose">
            <option value="general" <?= $draft['purpose'] === 'general' ? 'selected' : '' ?>>عمومی / سایر</option>
            <option value="marriage" <?= $draft['purpose'] === 'marriage' ? 'selected' : '' ?>>ازدواج</option>
            <option value="childbirth" <?= $draft['purpose'] === 'childbirth' ? 'selected' : '' ?>>فرزندآوری</option>
          </select>
        </label>
      <?php elseif ($step === 1): ?>
        <h2 class="font-bold text-navy">گام ۲ از ۴ — حساب بانک ملی</h2>
        <fieldset class="grid gap-2 text-sm">
          <legend class="mb-2 font-medium">آیا حساب بانک ملی دارید؟</legend>
          <label class="flex items-center gap-2"><input type="radio" name="has_account" value="1" <?= $draft['has_account'] === '1' ? 'checked' : '' ?>> بله</label>
          <label class="flex items-center gap-2"><input type="radio" name="has_account" value="0" <?= $draft['has_account'] !== '1' ? 'checked' : '' ?>> خیر</label>
        </fieldset>
        <label class="block">
          <span class="mb-2 block text-sm font-medium">حساب چند ماه فعال بوده است؟</span>
          <select class="field" name="activity_months">
            <?php foreach ([0, 6, 12, 18, 24] as $m): ?>
              <option value="<?= $m ?>" <?= $draft['activity_months'] === $m ? 'selected' : '' ?>><?= $m === 0 ? 'کمتر از ۶ ماه' : e(fa_number((string) $m) . ' ماه') ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="block">
          <span class="mb-2 block text-sm font-medium">معدل تقریبی حساب (تومان)</span>
          <input class="field font-latin js-thousands" type="text" name="average_balance" inputmode="numeric" data-thousands value="<?= $draft['average_balance'] > 0 ? e(format_thousands($draft['average_balance'])) : '' ?>">
        </label>
      <?php elseif ($step === 2): ?>
        <h2 class="font-bold text-navy">گام ۳ از ۴ — بازپرداخت و ضامن</h2>
        <label class="block">
          <span class="mb-2 block text-sm font-medium">مدت بازپرداخت مورد نظر</span>
          <select class="field" name="repayment_months">
            <?php foreach ([12, 24, 36, 48, 60] as $m): ?>
              <option value="<?= $m ?>" <?= $draft['repayment_months'] === $m ? 'selected' : '' ?>><?= e(fa_number((string) $m)) ?> ماه</option>
            <?php endforeach; ?>
          </select>
        </label>
        <fieldset class="grid gap-2 text-sm">
          <legend class="mb-2 font-medium">آیا ضامن دارید؟</legend>
          <label class="flex items-center gap-2"><input type="radio" name="has_guarantor" value="1" <?= $draft['has_guarantor'] === '1' ? 'checked' : '' ?>> بله</label>
          <label class="flex items-center gap-2"><input type="radio" name="has_guarantor" value="0" <?= $draft['has_guarantor'] !== '1' ? 'checked' : '' ?>> خیر / هنوز مشخص نیست</label>
        </fieldset>
      <?php else: ?>
        <h2 class="font-bold text-navy">گام ۴ از ۴ — نوع متقاضی</h2>
        <p class="text-sm text-muted">برای اعتبار ملی، بانک ملی اشخاص حقیقی و حقوقی را می‌پذیرد.</p>
        <fieldset class="grid gap-2 text-sm">
          <legend class="mb-2 font-medium">نوع متقاضی</legend>
          <label class="flex items-center gap-2"><input type="radio" name="applicant_type" value="individual" <?= $draft['applicant_type'] === 'individual' ? 'checked' : '' ?>> حقیقی</label>
          <label class="flex items-center gap-2"><input type="radio" name="applicant_type" value="legal" <?= $draft['applicant_type'] === 'legal' ? 'checked' : '' ?>> حقوقی</label>
        </fieldset>
      <?php endif; ?>

      <div class="flex flex-wrap gap-2">
        <?php if ($step > 0): ?>
          <button class="btn-secondary" type="submit" name="back" value="1">بازگشت</button>
        <?php endif; ?>
        <button class="btn-primary" type="submit"><?= $step >= 3 ? 'مشاهده نتیجه' : 'ادامه' ?></button>
      </div>
    </form>
  <?php endif; ?>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
