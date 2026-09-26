<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'blog-core.php';

$user = require_login();
$categories = blog_load_categories();
$error = '';
$notice = (string) ($_GET['notice'] ?? '');
$values = ['author_name' => (string) ($user['name'] ?? ''), 'title' => '', 'excerpt' => '', 'content' => '', 'category_id' => ''];

if (!isset($_SESSION['blog_submit_csrf'])) {
    $_SESSION['blog_submit_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        $values[$key] = trim((string) ($_POST[$key] ?? $default));
    }
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['blog_submit_csrf'], $token)) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } elseif ($values['author_name'] === '' || blog_text_length($values['author_name']) > 120) {
        $error = 'Enter a display name up to 120 characters.';
    } elseif ($values['title'] === '' || blog_text_length($values['title']) > 255) {
        $error = 'Enter a title up to 255 characters.';
    } elseif ($values['content'] === '') {
        $error = 'Write the article content before submitting.';
    } elseif ($values['category_id'] !== '' && blog_category_by_id($values['category_id']) === null) {
        $error = 'Choose a valid category.';
    } else {
      $featuredImage = null;
      $postSaved = false;
      try {
        $featuredImage = blog_save_featured_image_upload((array) ($_FILES['featured_image'] ?? []));
        $content = '<p>' . nl2br(e($values['content']), false) . '</p>';
        $post = blog_make_post([
          'id' => 'blog-' . bin2hex(random_bytes(8)),
          'title' => $values['title'],
          'excerpt' => $values['excerpt'],
          'content' => $content,
          'status' => BLOG_STATUS_PENDING_REVIEW,
          'author_id' => (string) ($user['id'] ?? ''),
          'author_name' => $values['author_name'],
          'category_id' => $values['category_id'],
          'featured_image' => (string) ($featuredImage ?? ''),
          'created_at' => date('Y-m-d H:i:s'),
          'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $posts = blog_all_posts();
        $posts[] = $post;
        blog_save_posts($posts);
        $postSaved = true;
        $_SESSION['blog_submit_csrf'] = bin2hex(random_bytes(32));
        header('Location: blog-submit.php?notice=' . rawurlencode('Submitted. Status: Pending review. An admin must publish it before it appears on the blog.') . '#my-submissions');
        exit;
      } catch (Throwable $exception) {
        if (!$postSaved) blog_delete_featured_image($featuredImage);
        $error = $exception->getMessage();
      }
    }
}

$myPosts = array_values(array_filter(blog_all_posts(), static fn(array $post): bool => (string) ($post['author_id'] ?? '') === (string) ($user['id'] ?? '')));
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Submit an article | FullMockTestSeries.com</title>
  <style>
    *{box-sizing:border-box}body{margin:0;background:#f4f1e8;color:#24231f;font:16px/1.6 Arial,sans-serif}.wrap{width:min(920px,calc(100% - 36px));margin:36px auto 64px}h1,h2{font-family:Georgia,serif;color:#16233f}h1{font-size:2.3rem;margin:0 0 8px}.intro{color:#625e50;margin:0 0 24px}.panel{background:#fff;border:1px solid #d7ccb1;padding:24px;margin:20px 0}.fields{display:grid;grid-template-columns:1fr 1fr;gap:16px}.field{display:grid;gap:7px}.wide{grid-column:1/-1}label{font-weight:700;color:#30343b;font-size:.92rem}input,select,textarea{width:100%;padding:12px;border:1px solid #cfc7ac;border-radius:6px;background:#fff;color:#1c1a15;font:inherit}textarea{min-height:280px;resize:vertical}.hint{font-size:.88rem;color:#625e50;margin:0}.button{display:inline-block;border:0;border-radius:6px;background:#9c3b2e;color:white;padding:12px 18px;font:700 1rem Arial,sans-serif;text-decoration:none;cursor:pointer}.notice,.error{padding:12px 14px;margin:14px 0;border-radius:4px}.notice{background:#e8f4ee;color:#234f3e;border:1px solid #b0d9bf}.error{background:#fbe8e5;color:#7d372f;border:1px solid #e4b7b0}.submission{display:flex;justify-content:space-between;gap:14px;padding:14px 0;border-top:1px solid #e5dfd2}.status{font-size:.8rem;font-weight:700;color:#625e50}.status.pending_review{color:#946117}.status.published{color:#1f654c}@media(max-width:640px){.wrap{margin-top:24px}.panel{padding:18px}.fields{grid-template-columns:1fr}.wide{grid-column:auto}.submission{display:grid;gap:4px}}
  </style>
</head>
<body>
<main class="wrap">
  <h1>Write for RankSetu</h1>
  <p class="intro">Share useful exam-preparation advice with learners. Every article is reviewed by our team and appears publicly only after approval.</p>
  <?php if ($notice !== ''): ?><div class="notice" role="status"><?= e($notice) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <section class="panel">
    <form method="post" action="blog-submit.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['blog_submit_csrf']) ?>">
      <div class="fields">
        <div class="field">
          <label for="author_name">Name to show as the author</label>
          <input id="author_name" name="author_name" maxlength="120" value="<?= e($values['author_name']) ?>" required>
        </div>
        <div class="field">
          <label for="title">Article title</label>
          <input id="title" name="title" maxlength="255" value="<?= e($values['title']) ?>" required>
        </div>
        <div class="field">
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id">
            <option value="">Choose a category</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?= e((string) ($category['id'] ?? '')) ?>" <?= $values['category_id'] === (string) ($category['id'] ?? '') ? 'selected' : '' ?>><?= e((string) ($category['name'] ?? '')) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="excerpt">Short summary</label>
          <input id="excerpt" name="excerpt" maxlength="500" value="<?= e($values['excerpt']) ?>" placeholder="Optional">
        </div>
        <div class="field wide">
          <label for="featured_image">Featured image (optional)</label>
          <input id="featured_image" name="featured_image" type="file" accept="image/jpeg,image/png,image/webp">
          <p class="hint">JPG, PNG or WebP, up to 3 MB.</p>
        </div>
        <div class="field wide">
          <label for="content">Your article</label>
          <textarea id="content" name="content" required><?= e($values['content']) ?></textarea>
          <p class="hint">Your article will be saved as pending review. It will not appear on the blog until an admin approves it.</p>
        </div>
      </div>
      <p style="margin:18px 0 0"><button class="button" type="submit">Submit for review</button></p>
    </form>
  </section>
  <section class="panel" id="my-submissions">
    <h2>Your submissions</h2>
    <?php if ($myPosts === []): ?><p class="intro">You have not submitted any articles yet.</p><?php else: ?>
      <?php foreach ($myPosts as $post): ?>
        <div class="submission"><strong><?= e((string) ($post['title'] ?? 'Untitled')) ?></strong><span class="status <?= e(strtolower((string) ($post['status'] ?? ''))) ?>"><?= e(str_replace('_', ' ', (string) ($post['status'] ?? ''))) ?></span></div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
