ALTER TABLE enrollments
  ADD COLUMN access_status ENUM('active','revoked') NOT NULL DEFAULT 'active' AFTER subscription_id,
  ADD COLUMN deactivated_at DATETIME DEFAULT NULL AFTER access_status,
  ADD COLUMN deactivated_by VARCHAR(48) DEFAULT NULL AFTER deactivated_at,
  ADD COLUMN deactivation_reason VARCHAR(500) DEFAULT NULL AFTER deactivated_by;

CREATE INDEX idx_enrollments_access_status ON enrollments (user_id, access_status, series_id);
