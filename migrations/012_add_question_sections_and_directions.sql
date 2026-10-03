ALTER TABLE questions
  ADD COLUMN section_title VARCHAR(255) NOT NULL DEFAULT '' AFTER topic,
  ADD COLUMN direction_text TEXT NULL AFTER section_title;
