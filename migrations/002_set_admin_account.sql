-- Set the production administrator account.
-- Run this after 001_create_tables.sql on an existing MySQL database.

INSERT INTO users (id, name, email, password, role, active, created_at)
VALUES (
  'bf43f7afbc3829a4',
  'Nishant Kumar Singh',
  'nishantsingh2jan1998@gmail.com',
  '$2y$12$ylMzYPxys.AwecpFV0WEtuqZvptXujLjPxpbmNJ96AHLf.VthGal6',
  'admin',
  1,
  NOW()
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  password = VALUES(password),
  role = 'admin',
  active = 1;
