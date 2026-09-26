ALTER TABLE series
  ADD COLUMN parent_group VARCHAR(255) NOT NULL DEFAULT 'Other exams' AFTER description;
