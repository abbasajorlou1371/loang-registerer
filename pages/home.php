<?php

declare(strict_types=1);

$pageTitle = 'وام مناسب بانک ملی را پیدا کنید | ثبت‌وام';
$pageDescription = 'کشف تسهیلات بانک ملی ایران، محاسبه اقساط تقریبی و ثبت درخواست مهربانی ملی و اعتبار ملی.';
$dir = 'rtl';
$lang = 'fa';

$products = get_catalog();
$mehrabani = get_product_by_slug('mehrabani-melli');
$sampleAmount = 150000000;
$sampleMonths = 24;
$sampleFee = 2.0;
$sampleCalc = $mehrabani
    ? mehrabani_installment($sampleAmount, $sampleFee, $sampleMonths)
    : ['installment' => 0];

require APP_ROOT . '/includes/header.php';
?>

<main id="main">
  <section class="hero-stage">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-2 lg:items-center lg:gap-14 lg:px-8 lg:py-20">
      <div>
        <p class="text-sm font-semibold text-cta" data-enter="1">تسهیلات بانک ملی ایران</p>
        <h1 class="page-title page-title-hero mt-3 text-balance" data-enter="2">
          وام مناسب بانک ملی را پیدا کنید
        </h1>
        <p class="page-lead mt-4 max-w-xl" data-enter="3">
          شرایط مهربانی ملی، اعتبار ملی، ازدواج و فرزندآوری را ببینید، قسط تقریبی را حساب کنید و در صورت امکان درخواست خود را ثبت کنید.
        </p>

        <div class="mt-6 flex flex-col gap-3 sm:mt-8 sm:flex-row sm:flex-wrap sm:items-center" data-enter="4">
          <a href="/apply" class="btn-primary w-full sm:w-auto">شروع درخواست وام</a>
          <div class="flex flex-wrap gap-2">
          <a href="/loans" class="btn-secondary">
            مشاهده وام‌ها
            <svg class="icon-rtl h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </a>
          <a href="/calculator" class="btn-secondary">محاسبه وام</a>
          <a href="/eligibility" class="btn-secondary hidden sm:inline-flex">بررسی شرایط</a>
          </div>
        </div>

        <ul class="mt-6 grid gap-3 sm:mt-8 sm:grid-cols-2 lg:grid-cols-3" aria-label="نشانه‌های اعتماد" data-enter="5">
          <li class="flex items-center gap-2 text-sm text-muted">
            <span class="trust-icon inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cta/10 text-cta" aria-hidden="true">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4z" stroke-linejoin="round"/></svg>
            </span>
            امنیت اطلاعات
          </li>
          <li class="flex items-center gap-2 text-sm text-muted">
            <span class="trust-icon inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cta/10 text-cta" aria-hidden="true">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16M12 4v16" stroke-linecap="round"/></svg>
            </span>
            شرایط شفاف
          </li>
          <li class="flex items-center gap-2 text-sm text-muted">
            <span class="trust-icon inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cta/10 text-cta" aria-hidden="true">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L4 14h7l-1 8 10-14h-7l0-6z" stroke-linejoin="round"/></svg>
            </span>
            بررسی سریع
          </li>
        </ul>
      </div>

      <aside class="card card-featured p-6 sm:p-8" aria-labelledby="loan-overview-title" data-enter="4">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 id="loan-overview-title" class="text-lg font-bold text-navy">برآورد نمونه مهربانی ملی</h2>
            <p class="mt-1 text-sm text-muted">مبلغ، مدت و قسط تقریبی — بدون تضمین تأیید</p>
          </div>
          <span class="badge">قرض‌الحسنه</span>
        </div>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div class="rounded-xl bg-page p-4">
            <dt class="text-xs font-medium text-muted">مبلغ وام</dt>
            <dd class="stat-value mt-2"><?= e(format_toman($sampleAmount)) ?></dd>
          </div>
          <div class="rounded-xl bg-page p-4">
            <dt class="text-xs font-medium text-muted">مدت بازپرداخت</dt>
            <dd class="stat-value mt-2"><?= e(fa_number((string) $sampleMonths)) ?> ماه</dd>
            <dd class="text-xs text-muted">کارمزد <?= e(fa_number('2')) ?>٪</dd>
          </div>
          <div class="stat-tile-cta rounded-xl p-4 sm:col-span-2 lg:col-span-1">
            <dt class="text-xs font-medium text-muted">قسط تقریبی</dt>
            <dd class="stat-value mt-2 text-cta"><?= e(format_amount($sampleCalc['installment'])) ?></dd>
          </div>
        </dl>
        <p class="alert mt-6"><?= e(calculator_disclaimer()) ?></p>
      </aside>
    </div>
  </section>

  <section class="border-t border-line bg-surface" aria-labelledby="products-title">
    <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h2 id="products-title" class="section-title">وام‌های بانک ملی</h2>
          <p class="mt-2 text-muted">شرایط نمایش‌داده‌شده نسخه جاری است و ممکن است تغییر کند.</p>
        </div>
        <a href="/loans" class="text-sm font-semibold text-cta hover:text-cta-hover">مشاهده همه</a>
      </div>

      <div class="mt-8 grid gap-5 sm:grid-cols-2">
        <?php foreach ($products as $product): ?>
          <article class="card card-lift flex flex-col p-6 reveal">
            <div class="flex items-start justify-between gap-3">
              <h3 class="text-lg font-bold text-navy"><?= e($product['title_fa']) ?></h3>
              <span class="badge <?= $product['application_type'] === 'external' ? 'badge-navy' : '' ?>">
                <?= e(product_type_label($product['product_type'])) ?>
              </span>
            </div>
            <p class="stat-value mt-3"><?= e(product_headline_amount($product)) ?></p>
            <p class="mt-2 text-sm leading-relaxed text-muted"><?= e($product['description_fa']) ?></p>
            <p class="mt-4 text-xs text-muted">
              آخرین به‌روزرسانی شرایط: <?= e(format_date_fa($product['last_updated_at'])) ?>
            </p>
            <div class="mt-6 flex flex-wrap gap-2">
              <a class="btn-primary" href="/loans/<?= e($product['slug']) ?>">جزئیات وام</a>
              <?php if ($product['application_type'] === 'direct'): ?>
                <a class="btn-secondary" href="/apply?product=<?= e($product['slug']) ?>">درخواست وام</a>
              <?php else: ?>
                <a class="btn-secondary" href="/loans/<?= e($product['slug']) ?>">شروع درخواست رسمی</a>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="border-t border-line sm:hidden" aria-labelledby="app-download-title">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
      <div class="card p-6">
        <h2 id="app-download-title" class="text-lg font-bold text-navy">اپلیکیشن اندروید</h2>
        <p class="mt-2 text-sm leading-relaxed text-muted">
          فایل نصب را دانلود کنید تا درخواست و پیگیری وام را روی موبایل انجام دهید.
        </p>
        <a
          href="/assets/apk/loan.apk"
          class="btn-primary mt-5 w-full"
          download="loan.apk"
          type="application/vnd.android.package-archive"
        >
          <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M12 3v12M7 10l5 5 5-5M5 19h14" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          دانلود موبایل بانک بام
        </a>
      </div>
    </div>
  </section>

  <section aria-labelledby="how-title">
    <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
      <h2 id="how-title" class="section-title">نحوه کار</h2>
      <ol class="mt-8 grid gap-5 md:grid-cols-3">
        <li class="card card-lift p-6 reveal">
          <span class="step-badge">۱</span>
          <h3 class="mt-4 font-bold text-navy">وام را پیدا کنید</h3>
          <p class="mt-2 text-sm leading-relaxed text-muted">از میان مهربانی ملی، اعتبار ملی، ازدواج و فرزندآوری انتخاب کنید.</p>
        </li>
        <li class="card card-lift p-6 reveal">
          <span class="step-badge">۲</span>
          <h3 class="mt-4 font-bold text-navy">شرایط را بسنجید</h3>
          <p class="mt-2 text-sm leading-relaxed text-muted">قسط تقریبی را حساب کنید و با معدل حساب، برآورد اولیه بگیرید.</p>
        </li>
        <li class="card card-lift p-6 reveal">
          <span class="step-badge">۳</span>
          <h3 class="mt-4 font-bold text-navy">درخواست را ثبت کنید</h3>
          <p class="mt-2 text-sm leading-relaxed text-muted">بدون ساخت حساب، اطلاعات را بفرستید و کد پیگیری بگیرید.</p>
        </li>
      </ol>
    </div>
  </section>

  <section class="border-t border-line bg-surface" aria-labelledby="finder-title">
    <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
      <div class="card p-6 sm:p-8 reveal">
        <h2 id="finder-title" class="section-title">کدام وام به شما می‌خورد؟</h2>
        <p class="mt-2 max-w-2xl text-sm text-muted">چند سؤال کوتاه بپرسید تا محصول مناسب‌تر بانک ملی را پیشنهاد کنیم. نتیجه قطعی نیست.</p>
        <form class="mt-6 grid gap-4 sm:grid-cols-2" action="/eligibility" method="get">
          <label class="block">
            <span class="mb-2 block text-sm font-medium">مبلغ مورد نیاز (تومان)</span>
            <input class="field font-latin js-thousands" type="text" name="amount" inputmode="numeric" data-thousands placeholder="150,000,000">
          </label>
          <label class="block">
            <span class="mb-2 block text-sm font-medium">هدف از دریافت وام</span>
            <select class="field" name="purpose">
              <option value="general">عمومی / سایر</option>
              <option value="marriage">ازدواج</option>
              <option value="childbirth">فرزندآوری</option>
            </select>
          </label>
          <div class="sm:col-span-2">
            <button type="submit" class="btn-primary">بررسی شرایط دریافت وام</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
