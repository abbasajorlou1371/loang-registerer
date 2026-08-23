  <footer class="site-footer">
    <div class="site-footer-glow" aria-hidden="true"></div>

    <div class="site-footer-cta">
      <div class="relative mx-auto flex max-w-6xl flex-col gap-5 px-4 py-8 sm:px-6 sm:py-10 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <div class="max-w-xl">
          <p class="site-footer-title">وام مناسب را همین حالا پیدا کنید</p>
          <p class="site-footer-copy mt-2">
            شرایط را ببینید، قسط تقریبی را حساب کنید و درخواست مهربانی ملی یا اعتبار ملی را ثبت کنید.
          </p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
          <a href="/apply" class="btn-primary w-full sm:w-auto">شروع درخواست وام</a>
          <a href="/calculator" class="btn-footer-ghost w-full sm:w-auto">محاسبه وام</a>
        </div>
      </div>
    </div>

    <div class="relative mx-auto grid max-w-6xl gap-10 px-4 py-10 sm:px-6 sm:py-12 lg:grid-cols-12 lg:gap-12 lg:px-8">
      <div class="lg:col-span-5">
        <a href="/" class="brand-logo-link" aria-label="ثبت‌وام — بانک ملی ایران">
          <?php require APP_ROOT . '/includes/brand-logo.svg.php'; ?>
        </a>
        <p class="site-footer-copy mt-4 max-w-md">
          سامانه کشف و ثبت درخواست تسهیلات بانک ملی ایران. تأیید نهایی وام فقط توسط بانک ملی انجام می‌شود.
        </p>
        <ul class="mt-6 flex flex-wrap gap-2" aria-label="نشانه‌های اعتماد">
          <li class="site-footer-chip">
            <svg class="h-3.5 w-3.5 text-cta" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4z" stroke-linejoin="round"/></svg>
            امنیت اطلاعات
          </li>
          <li class="site-footer-chip">
            <svg class="h-3.5 w-3.5 text-cta" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 12h16M12 4v16" stroke-linecap="round"/></svg>
            شرایط شفاف
          </li>
          <li class="site-footer-chip">
            <svg class="h-3.5 w-3.5 text-cta" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2L4 14h7l-1 8 10-14h-7l0-6z" stroke-linejoin="round"/></svg>
            بررسی سریع
          </li>
        </ul>
      </div>

      <div class="grid gap-8 sm:grid-cols-3 lg:col-span-7">
        <nav aria-label="وام‌ها">
          <p class="site-footer-heading">وام‌ها</p>
          <ul class="mt-4 grid gap-2.5 text-sm">
            <li><a class="site-footer-link" href="/loans">فهرست همه وام‌ها</a></li>
            <li><a class="site-footer-link" href="/loans/mehrabani-melli">مهربانی ملی</a></li>
            <li><a class="site-footer-link" href="/loans/etebar-melli">اعتبار ملی</a></li>
            <li><a class="site-footer-link" href="/loans/ezdevaj">ازدواج</a></li>
            <li><a class="site-footer-link" href="/loans/farzand-avi">فرزندآوری</a></li>
          </ul>
        </nav>
        <nav aria-label="ابزارها">
          <p class="site-footer-heading">ابزارها</p>
          <ul class="mt-4 grid gap-2.5 text-sm">
            <li><a class="site-footer-link" href="/calculator">محاسبه اقساط</a></li>
            <li><a class="site-footer-link" href="/eligibility">بررسی شرایط</a></li>
            <li><a class="site-footer-link" href="/compare">مقایسه وام‌ها</a></li>
            <li><a class="site-footer-link" href="/apply">ثبت درخواست</a></li>
          </ul>
        </nav>
        <nav aria-label="راهنما">
          <p class="site-footer-heading">راهنما</p>
          <ul class="mt-4 grid gap-2.5 text-sm">
            <li><a class="site-footer-link" href="/track">پیگیری درخواست</a></li>
            <li><a class="site-footer-link" href="/faq">پرسش‌های متداول</a></li>
            <li><a class="site-footer-link" href="/apply">شروع درخواست وام</a></li>
          </ul>
        </nav>
      </div>
    </div>

    <div class="site-footer-bar">
      <div class="relative mx-auto flex max-w-6xl flex-col gap-3 px-4 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <p class="site-footer-meta">© <span data-year></span> ثبت‌وام — این سامانه جایگزین شعب یا سامانه بام نیست.</p>
        <p class="site-footer-meta">حریم خصوصی و امنیت اطلاعات رعایت می‌شود. تأیید نهایی فقط با بانک ملی ایران است.</p>
      </div>
    </div>
  </footer>
  <script src="/assets/vendor/jquery.min.js"></script>
  <script src="/assets/vendor/jquery.validate.min.js"></script>
  <script src="/assets/vendor/jquery.mask.min.js"></script>
  <script src="/assets/vendor/jalalidatepicker.min.js"></script>
  <script src="/assets/vendor/anime.min.js" defer></script>
  <script src="/assets/js/app.js" defer></script>
  <script src="/assets/js/apply.js" defer></script>
  <script src="/assets/js/motion.js" defer></script>
</body>
</html>
