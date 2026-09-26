-- FullMockTestSeries.com production MySQL schema.
-- Import existing JSON data with scripts/import_json_to_mysql.php after creating the database.

CREATE TABLE IF NOT EXISTS users (
  id VARCHAR(48) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','admin') NOT NULL DEFAULT 'student',
  active TINYINT(1) NOT NULL DEFAULT 1,
  reset_token_hash CHAR(64) DEFAULT NULL,
  reset_expires_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role_active (role, active),
  INDEX idx_users_reset (reset_token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS series (
  id VARCHAR(48) PRIMARY KEY,
  slug VARCHAR(255) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  stage VARCHAR(100) NOT NULL DEFAULT 'Practice',
  description TEXT NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_series_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrollments (
  user_id VARCHAR(48) NOT NULL,
  series_id VARCHAR(48) NOT NULL,
  source ENUM('free','paid','admin') NOT NULL DEFAULT 'free',
  enrolled_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, series_id),
  CONSTRAINT fk_enrollments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_enrollments_series FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
  INDEX idx_enrollments_series (series_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tests (
  id VARCHAR(48) PRIMARY KEY,
  series_id VARCHAR(48) NOT NULL,
  test_key VARCHAR(64) NOT NULL,
  title VARCHAR(255) NOT NULL,
  duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tests_series_key (series_id, test_key),
  CONSTRAINT fk_tests_series FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
  INDEX idx_tests_series_active (series_id, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
  id VARCHAR(48) PRIMARY KEY,
  test_id VARCHAR(48) NOT NULL,
  position SMALLINT UNSIGNED NOT NULL,
  question_text TEXT NOT NULL,
  topic VARCHAR(255) NOT NULL DEFAULT '',
  correct_option TINYINT UNSIGNED NOT NULL,
  CONSTRAINT fk_questions_test FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
  UNIQUE KEY uq_questions_position (test_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_options (
  question_id VARCHAR(48) NOT NULL,
  position TINYINT UNSIGNED NOT NULL,
  option_text TEXT NOT NULL,
  PRIMARY KEY (question_id, position),
  CONSTRAINT fk_options_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id VARCHAR(64) PRIMARY KEY,
  user_id VARCHAR(48) NOT NULL,
  plan VARCHAR(255) NOT NULL,
  amount_paise INT UNSIGNED NOT NULL,
  original_amount_paise INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'INR',
  status VARCHAR(32) NOT NULL,
  provider VARCHAR(32) NOT NULL,
  provider_order_id VARCHAR(128) DEFAULT NULL,
  provider_payment_id VARCHAR(128) DEFAULT NULL,
  provider_signature VARCHAR(255) DEFAULT NULL,
  coupon_code VARCHAR(64) DEFAULT NULL,
  discount_paise INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_orders_provider_order (provider, provider_order_id),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_orders_user_date (user_id, created_at),
  INDEX idx_orders_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  order_id VARCHAR(64) NOT NULL,
  series_id VARCHAR(48) NOT NULL,
  amount_paise INT UNSIGNED NOT NULL,
  PRIMARY KEY (order_id, series_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_series FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attempts (
  id VARCHAR(48) PRIMARY KEY,
  user_id VARCHAR(48) NOT NULL,
  series_id VARCHAR(48) NOT NULL,
  test_id VARCHAR(48) NOT NULL,
  score SMALLINT UNSIGNED NOT NULL,
  total SMALLINT UNSIGNED NOT NULL,
  percentage DECIMAL(5,2) NOT NULL,
  time_taken_seconds INT UNSIGNED NOT NULL,
  timed_out TINYINT(1) NOT NULL DEFAULT 0,
  submitted_at DATETIME NOT NULL,
  CONSTRAINT fk_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_attempts_series FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE RESTRICT,
  CONSTRAINT fk_attempts_test FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE RESTRICT,
  INDEX idx_attempts_user_date (user_id, submitted_at),
  INDEX idx_attempts_series (series_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attempt_answers (
  attempt_id VARCHAR(48) NOT NULL,
  question_id VARCHAR(48) NOT NULL,
  answer_option TINYINT UNSIGNED DEFAULT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (attempt_id, question_id),
  CONSTRAINT fk_attempt_answers_attempt FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  CONSTRAINT fk_attempt_answers_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupons (
  code VARCHAR(64) PRIMARY KEY,
  type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  value INT UNSIGNED NOT NULL,
  product VARCHAR(255) DEFAULT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  expires_at DATETIME DEFAULT NULL,
  max_uses INT UNSIGNED DEFAULT NULL,
  used INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupon_allowed_emails (
  coupon_code VARCHAR(64) NOT NULL,
  email VARCHAR(255) NOT NULL,
  PRIMARY KEY (coupon_code, email),
  CONSTRAINT fk_coupon_emails_coupon FOREIGN KEY (coupon_code) REFERENCES coupons(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupon_redemptions (
  coupon_code VARCHAR(64) NOT NULL,
  user_id VARCHAR(48) NOT NULL,
  order_id VARCHAR(64) NOT NULL,
  discount_paise INT UNSIGNED NOT NULL,
  redeemed_at DATETIME NOT NULL,
  PRIMARY KEY (coupon_code, order_id),
  CONSTRAINT fk_redemptions_coupon FOREIGN KEY (coupon_code) REFERENCES coupons(code) ON DELETE RESTRICT,
  CONSTRAINT fk_redemptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_redemptions_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  token_hash CHAR(64) PRIMARY KEY,
  user_id VARCHAR(48) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reset_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_reset_tokens_user (user_id, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
