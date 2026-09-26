CREATE TABLE IF NOT EXISTS subscription_plans (
  id VARCHAR(48) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT NOT NULL,
  price_paise INT UNSIGNED NOT NULL,
  original_price_paise INT UNSIGNED NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'INR',
  duration INT UNSIGNED NOT NULL,
  duration_unit ENUM('day','month','year') NOT NULL DEFAULT 'day',
  active TINYINT(1) NOT NULL DEFAULT 1,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  display_order INT NOT NULL DEFAULT 0,
  all_access TINYINT(1) NOT NULL DEFAULT 0,
  max_enrollments INT UNSIGNED DEFAULT NULL,
  starts_at DATETIME DEFAULT NULL,
  ends_at DATETIME DEFAULT NULL,
  benefits JSON NOT NULL,
  covered_groups JSON NOT NULL,
  terms TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_subscription_plans_active_order (active, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_plan_courses (
  plan_id VARCHAR(48) NOT NULL,
  series_id VARCHAR(48) NOT NULL,
  PRIMARY KEY (plan_id, series_id),
  CONSTRAINT fk_subscription_plan_courses_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE,
  CONSTRAINT fk_subscription_plan_courses_series FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
  id VARCHAR(64) PRIMARY KEY,
  user_id VARCHAR(48) NOT NULL,
  plan_id VARCHAR(48) NOT NULL,
  status ENUM('PENDING','ACTIVE','EXPIRED','CANCELLED','REFUNDED','SUSPENDED') NOT NULL DEFAULT 'PENDING',
  start_at DATETIME DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  amount_paise INT UNSIGNED NOT NULL,
  discount_paise INT UNSIGNED NOT NULL DEFAULT 0,
  tax_paise INT UNSIGNED NOT NULL DEFAULT 0,
  final_amount_paise INT UNSIGNED NOT NULL,
  coupon_code VARCHAR(64) DEFAULT NULL,
  order_id VARCHAR(64) DEFAULT NULL,
  payment_id VARCHAR(128) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_subscriptions_order (order_id),
  CONSTRAINT fk_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT,
  INDEX idx_subscriptions_user_status (user_id, status, expires_at),
  INDEX idx_subscriptions_plan (plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE enrollments
  ADD COLUMN enrollment_source VARCHAR(32) NOT NULL DEFAULT 'free' AFTER source,
  ADD COLUMN subscription_id VARCHAR(64) DEFAULT NULL AFTER enrollment_source;

CREATE INDEX idx_enrollments_subscription ON enrollments (subscription_id);
