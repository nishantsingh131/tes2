INSERT INTO subscription_plans (
  id, name, slug, description, price_paise, original_price_paise, currency,
  duration, duration_unit, active, featured, display_order, all_access,
  max_enrollments, starts_at, ends_at, benefits, covered_groups, terms, created_at
)
VALUES (
  'plan-bank-premium',
  'All Government Exams Premium',
  'bank-premium',
  '180-day access to every published government-exam practice series, including BPSC and banking courses.',
  99900,
  149900,
  'INR',
  180,
  'day',
  1,
  1,
  1,
  1,
  NULL,
  NULL,
  NULL,
  JSON_ARRAY(
    '180 days of access to every available exam series',
    'Includes all published BPSC, banking and other government-exam courses',
    'New eligible courses are included while your subscription is active',
    'No repeat course payment for covered courses'
  ),
  JSON_ARRAY(),
  'Access covers active exam series with at least one published practice test containing questions. Eligible courses published during an active subscription are included. Access ends at subscription expiry, and administrator access revocations still apply.',
  NOW()
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  price_paise = VALUES(price_paise),
  original_price_paise = VALUES(original_price_paise),
  currency = VALUES(currency),
  duration = VALUES(duration),
  duration_unit = VALUES(duration_unit),
  active = VALUES(active),
  featured = VALUES(featured),
  display_order = VALUES(display_order),
  all_access = 1,
  max_enrollments = NULL,
  starts_at = NULL,
  ends_at = NULL,
  benefits = VALUES(benefits),
  covered_groups = JSON_ARRAY(),
  terms = VALUES(terms);

DELETE FROM subscription_plan_courses
WHERE plan_id = 'plan-bank-premium';
