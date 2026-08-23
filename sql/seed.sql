-- Bank Melli loan catalog seed (versioned, date-sensitive conditions)
USE loan_registrarer;

INSERT INTO loan_products
  (id, slug, title_fa, description_fa, product_type, application_type, sort_order, is_active)
VALUES
  (1, 'mehrabani-melli', 'مهربانی ملی',
   'تسهیلات قرض‌الحسنه مهربانی ملی بانک ملی ایران. سقف اعلام‌شده تا ۳۰۰ میلیون تومان، با کارمزد قابل انتخاب و بازپرداخت ۶ تا ۶۰ ماه. مبلغ قابل دریافت بر اساس معدل حساب و گردش حساب تعیین می‌شود و بدون مسدودی حساب تبلیغ شده است.',
   'qard_hasanah', 'direct', 1, 1),
  (2, 'etebar-melli', 'اعتبار ملی',
   'تسهیلات اعتبار ملی بانک ملی ایران. سقف اعلام‌شده تا ۷۰۰ میلیون تومان، با نرخ‌های ۱۴، ۱۸ یا ۲۳ درصد و بازپرداخت ۱ تا ۵ سال. بدون مسدودی حساب و وابسته به گردش حساب و مدت بازپرداخت.',
   'financing', 'direct', 2, 1),
  (3, 'ezdevaj', 'وام ازدواج',
   'وام قرض‌الحسنه ازدواج با مشارکت بانک ملی ایران. ثبت‌نام اولیه در سامانه بانک مرکزی انجام می‌شود و سپس متقاضی به بانک معرفی می‌گردد. این سامانه درخواست را مستقیماً به بانک ملی ارسال نمی‌کند.',
   'external_government', 'external', 3, 1),
  (4, 'farzand-avi', 'وام فرزندآوری',
   'وام قرض‌الحسنه فرزندآوری با مشارکت بانک ملی ایران. ثبت‌نام اولیه از سامانه اختصاصی بانک مرکزی انجام می‌شود. این سامانه درخواست را مستقیماً به بانک ملی ارسال نمی‌کند.',
   'external_government', 'external', 4, 1)
ON DUPLICATE KEY UPDATE
  title_fa = VALUES(title_fa),
  description_fa = VALUES(description_fa),
  product_type = VALUES(product_type),
  application_type = VALUES(application_type);

INSERT INTO loan_product_versions (
  loan_product_id, version, min_amount, max_amount, fee_options, interest_rates,
  term_months_min, term_months_max, term_options, eligibility_notes_fa, conditions_json,
  source_url, source_label_fa, last_updated_at, effective_from, is_current
) VALUES
(
  1, 1, 10000000, 300000000,
  '[0, 2, 4]', '[]',
  6, 60, '[6, 12, 18, 24, 36, 48, 60]',
  'واجد شرایط بودن نهایی بر اساس معدل حساب و محاسبه بانک ملی تعیین می‌شود. داشتن حساب بانک ملی، گردش حساب و معرفی ضامن معمولاً لازم است. انتقال امتیاز تسهیلات در شرایط اعلام‌شده بانک ممکن است.',
  JSON_OBJECT(
    'account_required', true,
    'account_blocking', false,
    'guarantor_required', true,
    'transferable', true,
    'available_via', JSON_ARRAY('سامانه بام', 'شعب بانک ملی'),
    'activity_ratios', JSON_ARRAY(
      JSON_OBJECT('months', 6, 'ratio', 0.40),
      JSON_OBJECT('months', 12, 'ratio', 0.70),
      JSON_OBJECT('months', 18, 'ratio', 0.90),
      JSON_OBJECT('months', 24, 'ratio', 1.00)
    ),
    'official_url', NULL
  ),
  'https://t.me/s/bankmelli_ir/7709',
  'کانال رسمی بانک ملی ایران',
  '2026-01-20', '2026-01-01', 1
),
(
  2, 1, 20000000, 700000000,
  '[]', '[14, 18, 23]',
  12, 60, '[12, 24, 36, 48, 60]',
  'سقف تسهیلات به گردش حساب، معدل موجودی و مدت بازپرداخت بستگی دارد. افراد حقیقی و حقوقی مطابق شرایط جاری بانک می‌توانند درخواست دهند. افتتاح و فعال بودن حساب مرتبط در بانک ملی لازم است. بدون مسدودی حساب تبلیغ شده است.',
  JSON_OBJECT(
    'account_required', true,
    'account_blocking', false,
    'guarantor_required', true,
    'applicant_types', JSON_ARRAY('individual', 'legal'),
    'available_via', JSON_ARRAY('سامانه بام', 'شعب بانک ملی'),
    'activity_ratios', JSON_ARRAY(
      JSON_OBJECT('months', 6, 'ratio', 0.35),
      JSON_OBJECT('months', 12, 'ratio', 0.55),
      JSON_OBJECT('months', 18, 'ratio', 0.75),
      JSON_OBJECT('months', 24, 'ratio', 1.00)
    ),
    'term_ratios', JSON_ARRAY(
      JSON_OBJECT('months', 12, 'ratio', 0.55),
      JSON_OBJECT('months', 24, 'ratio', 0.70),
      JSON_OBJECT('months', 36, 'ratio', 0.85),
      JSON_OBJECT('months', 48, 'ratio', 0.95),
      JSON_OBJECT('months', 60, 'ratio', 1.00)
    ),
    'official_url', NULL
  ),
  'https://t.me/s/bankmelli_ir/8301',
  'کانال رسمی بانک ملی ایران',
  '2026-03-01', '2026-03-01', 1
),
(
  3, 1, 0, 0,
  '[4]', '[]',
  36, 120, '[60, 84, 96, 120]',
  'ثبت‌نام اولیه فقط از سامانه بانک مرکزی انجام می‌شود. پس از تکمیل فرآیند، متقاضی به یکی از بانک‌ها از جمله بانک ملی معرفی می‌گردد. شرایط سنی، تأهل و مدارک طبق مقررات جاری بانک مرکزی است.',
  JSON_OBJECT(
    'account_required', false,
    'account_blocking', false,
    'guarantor_required', true,
    'official_url', 'https://ve.cbi.ir',
    'official_label_fa', 'سامانه وام ازدواج بانک مرکزی',
    'bank_selection_note_fa', 'پس از ثبت‌نام در سامانه بانک مرکزی می‌توانید بانک ملی ایران را انتخاب کنید، در صورت تخصیص سهمیه.'
  ),
  'https://t.me/s/bankmelli_ir',
  'کانال رسمی بانک ملی ایران',
  '2026-01-10', '2025-01-01', 1
),
(
  4, 1, 0, 0,
  '[4]', '[]',
  36, 84, '[36, 48, 60, 84]',
  'ثبت‌نام اولیه از سامانه اختصاصی بانک مرکزی انجام می‌شود و سپس متقاضی به بانک معرفی می‌گردد. مبلغ تسهیلات معمولاً بر اساس رتبه تولد فرزند طبق مصوبات جاری تعیین می‌شود.',
  JSON_OBJECT(
    'account_required', false,
    'account_blocking', false,
    'guarantor_required', true,
    'official_url', 'https://ve.cbi.ir',
    'official_label_fa', 'سامانه تسهیلات فرزندآوری بانک مرکزی',
    'bank_selection_note_fa', 'پس از ثبت در سامانه بانک مرکزی، در صورت معرفی به بانک ملی، ادامه فرآیند در شعبه انجام می‌شود.'
  ),
  'https://t.me/s/bankmelli_ir',
  'کانال رسمی بانک ملی ایران',
  '2026-01-10', '2025-01-01', 1
);

INSERT INTO loan_eligibility_rules (loan_product_id, rule_key, rule_value, label_fa, description_fa) VALUES
(1, 'requires_account', 'true', 'حساب بانک ملی', 'داشتن حساب فعال در بانک ملی ایران برای محاسبه معدل و گردش حساب لازم است.'),
(1, 'requires_guarantor', 'true', 'ضامن', 'معرفی ضامن مطابق ضوابط شعبه معمولاً ضروری است.'),
(1, 'max_amount', '300000000', 'سقف مبلغ', 'سقف اعلام‌شده فعلی ۳۰۰ میلیون تومان است.'),
(1, 'term_range', '{"min":6,"max":60}', 'مدت بازپرداخت', 'بازپرداخت از ۶ تا ۶۰ ماه قابل انتخاب است.'),
(1, 'fee_options', '[0,2,4]', 'کارمزد', 'گزینه‌های کارمزد اعلام‌شده: ۰، ۲ یا ۴ درصد.'),
(1, 'activity_based', 'true', 'معدل حساب', 'مبلغ قابل دریافت بر اساس معدل و دوره گردش حساب محاسبه می‌شود.'),

(2, 'requires_account', 'true', 'حساب بانک ملی', 'ایجاد رابطه حساب مرتبط در بانک ملی برای این تسهیلات لازم است.'),
(2, 'requires_guarantor', 'true', 'تضامین', 'شرایط ضمانت مطابق ضوابط جاری بانک ملی است.'),
(2, 'max_amount', '700000000', 'سقف مبلغ', 'سقف اعلام‌شده فعلی ۷۰۰ میلیون تومان است.'),
(2, 'term_range', '{"min":12,"max":60}', 'مدت بازپرداخت', 'بازپرداخت از ۱ تا ۵ سال (۱۲ تا ۶۰ ماه).'),
(2, 'interest_rates', '[14,18,23]', 'نرخ سود', 'نرخ‌های قابل انتخاب: ۱۴، ۱۸ یا ۲۳ درصد.'),
(2, 'applicant_types', '["individual","legal"]', 'نوع متقاضی', 'اشخاص حقیقی و حقوقی مطابق شرایط بانک.'),
(2, 'activity_based', 'true', 'گردش حساب', 'مبلغ قابل دریافت به گردش حساب و مدت بازپرداخت بستگی دارد.'),

(3, 'external_registration', 'true', 'ثبت‌نام بانک مرکزی', 'ثبت درخواست اولیه فقط از سامانه بانک مرکزی انجام می‌شود.'),
(3, 'requires_guarantor', 'true', 'ضامن', 'پس از معرفی به بانک، ارائه ضامن طبق ضوابط لازم است.'),
(3, 'purpose', '"marriage"', 'هدف', 'مخصوص متقاضیان وام ازدواج.'),

(4, 'external_registration', 'true', 'ثبت‌نام بانک مرکزی', 'ثبت درخواست اولیه از سامانه اختصاصی بانک مرکزی است.'),
(4, 'requires_guarantor', 'true', 'ضامن', 'پس از معرفی به بانک، ارائه ضامن طبق ضوابط لازم است.'),
(4, 'purpose', '"childbirth"', 'هدف', 'مخصوص متقاضیان وام فرزندآوری.');
