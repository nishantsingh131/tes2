INSERT INTO series (id, slug, title, stage, description, active, created_at, parent_group)
VALUES
  ('builtin-0ed81e94c06245a6', 'sbi-po', 'SBI PO', 'Prelims & Mains', 'Officer-level practice for reasoning, data analysis, English and banking awareness.', 1, NOW(), 'SBI'),
  ('builtin-edac509979024e8d', 'sbi-clerk', 'SBI Clerk', 'Prelims & Mains', 'Junior associate practice covering speed, accuracy and customer-facing banking aptitude.', 1, NOW(), 'SBI'),
  ('builtin-cdeb1b0d9673f19b', 'sbi-cbo', 'SBI CBO', 'Online Exam', 'Circle-based officer practice with banking, reasoning, English and professional knowledge.', 1, NOW(), 'SBI'),
  ('builtin-27dbcebbe7d3dccc', 'sbi-so', 'SBI SO', 'Online Exam', 'Specialist officer practice with aptitude, reasoning, English and role-ready banking knowledge.', 1, NOW(), 'SBI'),
  ('builtin-fb1b9610246900a0', 'sbi-apprentice', 'SBI Apprentice', 'Online Exam', 'Apprentice-level practice for general awareness, quantitative aptitude and local language readiness.', 1, NOW(), 'SBI')
ON DUPLICATE KEY UPDATE active = 1, parent_group = 'SBI';

INSERT INTO subscription_plans (
  id, name, slug, description, price_paise, original_price_paise, currency,
  duration, duration_unit, active, featured, display_order, all_access,
  max_enrollments, starts_at, ends_at, benefits, covered_groups, terms, created_at
)
VALUES (
  'plan-bank-premium', 'Bank Premium', 'bank-premium',
  '180-day access to the SBI PO, Clerk, CBO, SO and Apprentice series.',
  99900, 149900, 'INR', 180, 'day', 1, 1, 1, 0,
  NULL, NULL, NULL,
  JSON_ARRAY('180 days of SBI series access', 'SBI PO, Clerk, CBO, SO and Apprentice', 'Enroll covered courses for ₹0', 'No repeat course payment while active'),
  JSON_ARRAY('SBI'),
  'Premium access follows the active subscription period.', NOW()
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  price_paise = VALUES(price_paise),
  original_price_paise = VALUES(original_price_paise),
  currency = VALUES(currency),
  duration = VALUES(duration),
  duration_unit = VALUES(duration_unit),
  active = 1,
  featured = VALUES(featured),
  display_order = VALUES(display_order),
  all_access = 0,
  max_enrollments = VALUES(max_enrollments),
  starts_at = NULL,
  ends_at = NULL,
  benefits = VALUES(benefits),
  covered_groups = JSON_ARRAY('SBI'),
  terms = VALUES(terms);