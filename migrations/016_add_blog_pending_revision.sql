ALTER TABLE blog_posts
  ADD COLUMN pending_revision LONGTEXT NULL AFTER review_comment;
