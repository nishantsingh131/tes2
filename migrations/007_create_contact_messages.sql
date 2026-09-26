CREATE TABLE IF NOT EXISTS contact_messages (
  id CHAR(32) PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(254) NOT NULL,
  subject VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new','reviewed','closed') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL,
  INDEX idx_contact_messages_created (created_at),
  INDEX idx_contact_messages_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;