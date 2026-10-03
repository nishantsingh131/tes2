<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'blog-core.php';

$user = require_login();
$categories = blog_load_categories();
$error = '';
$noticeValue = $_GET['notice'] ?? '';
$notice = is_string($noticeValue) ? $noticeValue : '';
$values = ['author_name' => (string) ($user['name'] ?? ''), 'title' => '', 'excerpt' => '', 'content' => '', 'category_id' => ''];
$userId = (string) ($user['id'] ?? '');
$editPostIdValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['edit_id'] ?? '') : ($_GET['edit'] ?? '');
$editPostId = is_string($editPostIdValue) ? trim($editPostIdValue) : '';
$editingPost = null;
$postForEditing = null;
$featuredImagePath = '';

if ($editPostId !== '') {
    $editingPost = blog_get_post_by_id($editPostId);
    if ($editingPost === null || (string) ($editingPost['author_id'] ?? '') !== $userId) {
        http_response_code(404);
        $error = 'That submission could not be found in your account.';
        $editPostId = '';
        $editingPost = null;
    } else {
        $pendingRevision = blog_post_pending_revision($editingPost);
        $postForEditing = $pendingRevision ?? $editingPost;
        $featuredImagePath = (string) ($postForEditing['featured_image'] ?? '');
        foreach (['title', 'excerpt', 'content', 'category_id'] as $field) {
            $values[$field] = (string) ($postForEditing[$field] ?? '');
        }
    }
}

if (!isset($_SESSION['blog_submit_csrf'])) {
    $_SESSION['blog_submit_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invalidFieldShape = false;
    $invalidEncoding = false;
    foreach (['title', 'excerpt', 'content', 'category_id'] as $key) {
        $default = $values[$key];
        $submittedValue = $_POST[$key] ?? $default;
        if (!is_string($submittedValue)) {
            $invalidFieldShape = true;
            $values[$key] = '';
            continue;
        }
        $values[$key] = trim($submittedValue);
        if (preg_match('//u', $values[$key]) !== 1) {
            $invalidEncoding = true;
            $values[$key] = '';
        }
    }
    $values['author_name'] = trim((string) ($user['name'] ?? ''));
    $values['content'] = blog_sanitize_html($values['content']);
    $articleText = html_entity_decode(strip_tags($values['content']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $hasArticleText = preg_match('/[^\s\p{Z}]/u', $articleText) === 1;
    $hasAuthorName = preg_match('/[^\s\p{Z}]/u', $values['author_name']) === 1;
    $hasTitleText = preg_match('/[^\s\p{Z}]/u', $values['title']) === 1;
    $tokenValue = $_POST['csrf_token'] ?? '';
    $token = is_string($tokenValue) ? $tokenValue : '';
    $featuredImageInput = $_FILES['featured_image'] ?? [];
    $invalidImageShape = !is_array($featuredImageInput);
    if (is_array($featuredImageInput)) {
        foreach (['name', 'tmp_name', 'error', 'size', 'type'] as $fileKey) {
            if (isset($featuredImageInput[$fileKey]) && !is_scalar($featuredImageInput[$fileKey])) {
                $invalidImageShape = true;
                break;
            }
        }
    }
    if (!hash_equals((string) $_SESSION['blog_submit_csrf'], $token)) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } elseif ($invalidFieldShape || $invalidImageShape) {
        $error = 'One of the submitted fields is invalid. Please check the form and try again.';
    } elseif ($invalidEncoding) {
        $error = 'Some text contains invalid characters. Please review it and try again.';
    } elseif (!$hasAuthorName || blog_text_length($values['author_name']) > 120) {
        $error = 'Enter a display name up to 120 characters.';
    } elseif (!$hasTitleText || blog_text_length($values['title']) > 255) {
        $error = 'Enter a title up to 255 characters.';
    } elseif (blog_text_length($values['excerpt']) > 500) {
        $error = 'Keep the summary to 500 characters or fewer.';
    } elseif (!$hasArticleText) {
        $error = 'Write the article content before submitting.';
    } elseif ($values['category_id'] !== '' && blog_category_by_id($values['category_id']) === null) {
        $error = 'Choose a valid category.';
    } elseif ($editPostId !== '' && ($editingPost === null || (string) ($editingPost['author_id'] ?? '') !== $userId)) {
        http_response_code(404);
        $error = 'That submission could not be found in your account.';
    } else {
      $featuredImage = null;
      $postSaved = false;
      try {
        $featuredImage = blog_save_featured_image_upload($featuredImageInput);
        $content = $values['content'];
        if (strip_tags($content) === $content) {
          $content = '<p>' . nl2br(e($content), false) . '</p>';
        }
        $removeImageValue = $_POST['remove_featured_image'] ?? '';
        $removeImage = is_string($removeImageValue) && $removeImageValue === '1';
        $imagePath = $featuredImage !== null ? $featuredImage : ($removeImage ? '' : $featuredImagePath);
        $post = blog_make_post([
          'id' => $editingPost !== null ? (string) $editingPost['id'] : 'blog-' . bin2hex(random_bytes(8)),
          'title' => $values['title'],
          'slug' => (string) ($postForEditing['slug'] ?? ''),
          'excerpt' => $values['excerpt'],
          'content' => $content,
          'status' => BLOG_STATUS_PENDING_REVIEW,
          'author_id' => $userId,
          'author_name' => $editingPost !== null ? (string) ($editingPost['author_name'] ?? $user['name'] ?? '') : $values['author_name'],
          'category_id' => $values['category_id'],
          'featured_image' => $imagePath,
          'seo_title' => (string) ($postForEditing['seo_title'] ?? ''),
          'seo_description' => (string) ($postForEditing['seo_description'] ?? ''),
          'canonical_url' => (string) ($postForEditing['canonical_url'] ?? ''),
          'og_title' => (string) ($postForEditing['og_title'] ?? ''),
          'og_description' => (string) ($postForEditing['og_description'] ?? ''),
          'tags' => (array) ($postForEditing['tags'] ?? []),
          'created_at' => (string) ($editingPost['created_at'] ?? date('Y-m-d H:i:s')),
          'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $posts = blog_all_posts();
        $replacedImage = '';
        $postFound = false;
        $keptLiveImage = '';
        foreach ($posts as &$storedPost) {
          if (($storedPost['id'] ?? '') !== $post['id']) continue;
          $postFound = true;
          $storedPostPendingRevision = blog_post_pending_revision($storedPost);
          if (($storedPost['status'] ?? '') === BLOG_STATUS_PUBLISHED) {
            $post['status'] = BLOG_STATUS_PUBLISHED;
            $post['review_state'] = BLOG_STATUS_PENDING_REVIEW;
            $storedPost['pending_revision'] = $post;
            $replacedImage = (string) ($storedPostPendingRevision['featured_image'] ?? '');
            $keptLiveImage = (string) ($storedPost['featured_image'] ?? '');
          } else {
            $post['review_comment'] = '';
            $post['pending_revision'] = null;
            $storedPost = $post;
            $replacedImage = (string) ($storedPostPendingRevision['featured_image'] ?? $editingPost['featured_image'] ?? '');
          }
          $storedPost['review_comment'] = '';
          $storedPost['updated_at'] = date('Y-m-d H:i:s');
          break;
        }
        unset($storedPost);
        if ($editingPost !== null && !$postFound) {
            throw new RuntimeException('This submission is no longer available. Refresh your submissions and try again.');
        }
        if (!$postFound) {
            $posts[] = $post;
        }
        blog_save_posts($posts);
        $postSaved = true;
        if ($replacedImage !== '' && $replacedImage !== $imagePath && ($editingPost === null || ($editingPost['status'] ?? '') !== BLOG_STATUS_PUBLISHED || $replacedImage !== $keptLiveImage)) {
            blog_delete_unused_featured_image($replacedImage, $posts);
        }
        $_SESSION['blog_submit_csrf'] = bin2hex(random_bytes(32));
        $noticeText = ($editingPost !== null && ($editingPost['status'] ?? '') === BLOG_STATUS_PUBLISHED)
            ? 'Your edit was submitted for admin review. The currently published article stays live until the edit is approved.'
            : 'Submitted. Status: Pending review. An admin must publish it before it appears on the blog.';
        $returnPageValue = $_POST['submissions_page'] ?? '1';
        $returnPage = is_string($returnPageValue) && ctype_digit($returnPageValue) ? max(1, (int) $returnPageValue) : 1;
        header('Location: blog-submit.php?notice=' . rawurlencode($noticeText) . '&submissions_page=' . $returnPage . '#my-submissions');
        exit;
      } catch (Throwable $exception) {
        if (!$postSaved) blog_delete_featured_image($featuredImage);
        $error = $exception->getMessage();
      }
    }
}

$myPosts = array_values(array_filter(blog_all_posts(), static fn(array $post): bool => (string) ($post['author_id'] ?? '') === $userId));
usort($myPosts, static fn(array $left, array $right): int => strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? '')));
$submissionPageSize = 10;
$submissionPageValue = $_GET['submissions_page'] ?? '1';
$submissionPage = is_string($submissionPageValue) && ctype_digit($submissionPageValue) ? max(1, (int) $submissionPageValue) : 1;
$submissionTotal = count($myPosts);
$submissionPages = max(1, (int) ceil($submissionTotal / $submissionPageSize));
$submissionPage = min($submissionPage, $submissionPages);
$submissionPosts = array_slice($myPosts, ($submissionPage - 1) * $submissionPageSize, $submissionPageSize);
$submissionStart = $submissionTotal === 0 ? 0 : (($submissionPage - 1) * $submissionPageSize) + 1;
$submissionEnd = min($submissionTotal, $submissionPage * $submissionPageSize);
$submissionPageLinkStart = max(1, $submissionPage - 2);
$submissionPageLinkEnd = min($submissionPages, $submissionPage + 2);
$authorName = trim((string) ($user['name'] ?? ''));
$authorInitial = $authorName !== '' ? strtoupper(blog_text_substr($authorName, 0, 1)) : '?';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $editingPost !== null ? 'Edit your article' : 'Blog &amp; tutorials' ?> | FullMockTestSeries.com</title>
  <style>
    *{box-sizing:border-box}body{margin:0;background:#fff;color:#24231f;font:16px/1.6 Arial,sans-serif}.wrap{width:min(860px,calc(100% - 40px));margin:0 auto 64px}.writing-header{padding:34px 0 20px}h1,h2{font-family:Georgia,serif;color:#16233f}h1{font-size:2.2rem;margin:0 0 6px}.intro{color:#625e50;margin:0}.notice,.error{padding:12px 14px;margin:14px 0;border-radius:6px}.notice{background:#e8f4ee;color:#234f3e;border:1px solid #b0d9bf}.error{background:#fbe8e5;color:#7d372f;border:1px solid #e4b7b0}.composer{border:1px solid #e8e5de;border-radius:10px;padding:26px 32px 24px}.byline{display:flex;align-items:center;gap:11px;margin-bottom:22px}.avatar{width:38px;height:38px;display:grid;place-items:center;border-radius:50%;background:#16233f;color:white;font-weight:700}.byline-copy{display:grid;line-height:1.35}.byline-copy strong{font-size:.92rem;color:#24231f}.byline-copy span{font-size:.82rem;color:#777}.fields{display:grid;grid-template-columns:1fr 1fr;gap:16px}.field{display:grid;gap:7px}.wide{grid-column:1/-1}label{font-weight:700;color:#30343b;font-size:.88rem}.visually-hidden{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}input,select,textarea{width:100%;padding:12px;border:1px solid #d8d5ce;border-radius:6px;background:#fff;color:#1c1a15;font:inherit}input:focus,select:focus,textarea:focus,.article-title:focus,.article-subtitle:focus,.article-body:focus{outline:2px solid #b7c8df;outline-offset:2px}.article-title,.article-subtitle{display:block;width:100%;border:0;border-radius:0;padding:0;background:transparent;color:#24231f;font-family:Georgia,serif}.article-title{font-size:2.4rem;line-height:1.2;font-weight:700}.article-subtitle{margin-top:12px;font-size:1.2rem;color:#625e50}.article-title::placeholder,.article-subtitle::placeholder{color:#aaa}.format-bar{position:sticky;top:0;z-index:2;display:flex;align-items:center;flex-wrap:wrap;gap:4px;margin:23px -32px 0;padding:9px 28px;border-top:1px solid #eee;border-bottom:1px solid #eee;background:rgba(255,255,255,.97)}.tool-button{min-width:36px;height:36px;padding:0 9px;border:0;border-radius:5px;background:transparent;color:#42464d;font:600 .92rem Arial,sans-serif;cursor:pointer}.tool-button:hover,.tool-button:focus-visible{background:#f1f2f3;outline:none}.tool-button[aria-pressed=true]{background:#e8edf4;color:#16233f}.tool-separator{height:22px;border-left:1px solid #dedede;margin:0 5px}.article-body{min-height:360px;padding:22px 0 14px;font:1.13rem/1.8 Georgia,serif;color:#292929;overflow-wrap:anywhere}.article-body:empty::before{content:attr(data-placeholder);color:#aaa;pointer-events:none}.article-body p{margin:0 0 1em}.article-body h2,.article-body h3{margin:1.4em 0 .5em;font-family:Georgia,serif;color:#24231f;line-height:1.3}.article-body blockquote{margin:1.2em 0;padding-left:18px;border-left:3px solid #9c3b2e;color:#625e50}.article-body a{color:#315b8a;text-decoration:underline}.article-body ul,.article-body ol{padding-left:1.5em}.editor-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;border-top:1px solid #eee;padding-top:12px;color:#777;font-size:.83rem}.hint{font-size:.88rem;color:#625e50;margin:0}.extra-fields{margin-top:20px;padding:20px 0 4px;border-top:1px solid #eee}.extra-heading{margin:0 0 15px;font:700 .91rem Arial,sans-serif;color:#30343b}.submit-row{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-top:20px}.review-hint{margin:0;color:#625e50;font-size:.85rem}.button{display:inline-block;border:0;border-radius:6px;background:#1f654c;color:white;padding:12px 18px;font:700 1rem Arial,sans-serif;text-decoration:none;cursor:pointer}.button:hover{background:#18523d}.submissions{margin-top:42px}.submissions h2{font-size:1.45rem;margin:0 0 8px}.submission{display:flex;justify-content:space-between;gap:14px;padding:14px 0;border-top:1px solid #e5e5e5}.status{font-size:.8rem;font-weight:700;color:#625e50;text-transform:capitalize}.status.pending_review{color:#946117}.status.published{color:#1f654c}.pagination{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:16px;padding-top:14px;border-top:1px solid #e5e5e5}.pagination-links{display:flex;flex-wrap:wrap;gap:7px}.pagination a{padding:7px 11px;border:1px solid #dedbd3;border-radius:6px;color:#16233f;text-decoration:none}.pagination a[aria-current=page]{background:#16233f;color:#fff;border-color:#16233f}.pagination .disabled{color:#aaa;border-color:#eee}@media(max-width:640px){.wrap{width:calc(100% - 28px)}.writing-header{padding:26px 0 16px}h1{font-size:1.85rem}.composer{padding:20px 18px}.fields{grid-template-columns:1fr}.wide{grid-column:auto}.article-title{font-size:2rem}.article-subtitle{font-size:1.08rem}.format-bar{margin:20px -18px 0;padding:8px 14px}.tool-button{min-width:34px}.article-body{min-height:300px;font-size:1.06rem}.submit-row{align-items:flex-start;flex-direction:column}.submission{display:grid;gap:4px}.pagination{align-items:flex-start;flex-direction:column}}
  </style>
</head>
<body>
<main class="wrap">
  <section class="writing-header" aria-labelledby="write-heading">
    <h1 id="write-heading"><?= $editingPost !== null ? 'Edit your submission' : 'Blog &amp; tutorials' ?></h1>
    <p class="intro"><?= $editingPost !== null ? 'Your changes will be reviewed before they replace the public article.' : 'Write a tutorial, submit it for review, and manage your articles here.' ?> <a href="#my-submissions" style="color:#9c3b2e;font-weight:700">My submissions</a> · <a href="#write-story" style="color:#9c3b2e;font-weight:700">Write a tutorial</a> · <a href="blog.php" style="color:#9c3b2e;font-weight:700">Read published tutorials</a></p>
  </section>
  <?php if ($notice !== ''): ?><div class="notice" role="status"><?= e($notice) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <section class="submissions" id="my-submissions">
    <h2>Your submissions</h2>
    <p class="intro"><?= $submissionTotal === 0 ? 'Your submitted tutorials will appear here.' : 'Showing ' . $submissionStart . '–' . $submissionEnd . ' of ' . $submissionTotal . ' submissions.' ?></p>
    <?php if ($myPosts === []): ?><p class="intro">You have not submitted any articles yet.</p><?php else: ?>
      <?php foreach ($submissionPosts as $post): ?>
        <?php $revision = blog_post_pending_revision($post); ?>
        <?php $displayPost = $revision ?? $post; ?>
        <?php $hasPendingRevision = $revision !== null && blog_post_has_pending_review($post); ?>
        <div class="submission">
          <strong><?= e((string) ($displayPost['title'] ?? 'Untitled')) ?></strong>
          <span class="status <?= e(strtolower((string) ($post['status'] ?? ''))) ?>">
            <?= e(str_replace('_', ' ', (string) ($post['status'] ?? ''))) ?><?= $hasPendingRevision ? ' · edit pending review' : (($revision['review_state'] ?? '') === BLOG_STATUS_REJECTED ? ' · edit rejected' : '') ?>
          </span>
          <a class="button" href="blog-submit.php?edit=<?= e(rawurlencode((string) ($post['id'] ?? ''))) ?>&amp;submissions_page=<?= $submissionPage ?>#write-story">Edit</a>
          <?php if (trim((string) ($post['review_comment'] ?? '')) !== ''): ?><span class="hint">Admin note: <?= e((string) $post['review_comment']) ?></span><?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if ($submissionPages > 1): ?>
        <nav class="pagination" aria-label="Submission pages">
          <span>Page <?= $submissionPage ?> of <?= $submissionPages ?></span>
          <div class="pagination-links">
            <?php if ($submissionPage > 1): ?><a href="blog-submit.php?submissions_page=1#my-submissions">First</a><a href="blog-submit.php?submissions_page=<?= $submissionPage - 1 ?>#my-submissions" rel="prev">Previous</a><?php else: ?><span class="disabled">First · Previous</span><?php endif; ?>
            <?php for ($pageNumber = $submissionPageLinkStart; $pageNumber <= $submissionPageLinkEnd; $pageNumber++): ?>
              <?php if ($pageNumber === $submissionPage): ?><a href="blog-submit.php?submissions_page=<?= $pageNumber ?>#my-submissions" aria-current="page"><?= $pageNumber ?></a><?php else: ?><a href="blog-submit.php?submissions_page=<?= $pageNumber ?>#my-submissions"><?= $pageNumber ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($submissionPage < $submissionPages): ?><a href="blog-submit.php?submissions_page=<?= $submissionPage + 1 ?>#my-submissions" rel="next">Next</a><a href="blog-submit.php?submissions_page=<?= $submissionPages ?>#my-submissions">Last</a><?php else: ?><span class="disabled">Next · Last</span><?php endif; ?>
          </div>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </section>
  <section class="composer" id="write-story">
    <h2>Write a tutorial</h2>
    <form method="post" action="blog-submit.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['blog_submit_csrf']) ?>">
      <input type="hidden" name="edit_id" value="<?= e($editPostId) ?>">
      <input type="hidden" name="submissions_page" value="<?= $submissionPage ?>">
      <div class="byline">
        <div class="avatar" aria-hidden="true"><?= e($authorInitial) ?></div>
        <div class="byline-copy">
          <strong>Writing as <?= e($authorName !== '' ? $authorName : 'your account') ?></strong>
          <span>Your story, in your own words</span>
        </div>
        <input type="hidden" id="author_name" name="author_name" value="<?= e($values['author_name']) ?>">
      </div>
      <label class="visually-hidden" for="title">Story title</label>
      <input class="article-title" id="title" name="title" maxlength="255" value="<?= e($values['title']) ?>" placeholder="Title" required>
      <label class="visually-hidden" for="excerpt">Story subtitle or short summary</label>
      <input class="article-subtitle" id="excerpt" name="excerpt" maxlength="500" value="<?= e($values['excerpt']) ?>" placeholder="Add a subtitle or a short summary...">
      <div class="format-bar" role="toolbar" aria-label="Story formatting">
        <button class="tool-button" type="button" data-command="bold" aria-label="Bold" title="Bold (Ctrl+B)"><strong>B</strong></button>
        <button class="tool-button" type="button" data-command="italic" aria-label="Italic" title="Italic (Ctrl+I)"><em>I</em></button>
        <button class="tool-button" type="button" data-command="underline" aria-label="Underline" title="Underline (Ctrl+U)"><u>U</u></button>
        <span class="tool-separator" aria-hidden="true"></span>
        <button class="tool-button" type="button" data-block="h2" aria-label="Heading" title="Heading">H2</button>
        <button class="tool-button" type="button" data-block="h3" aria-label="Subheading" title="Subheading">H3</button>
        <button class="tool-button" type="button" data-block="blockquote" aria-label="Quote" title="Quote">“ ”</button>
        <span class="tool-separator" aria-hidden="true"></span>
        <button class="tool-button" type="button" data-command="insertUnorderedList" aria-label="Bulleted list" title="Bulleted list">• List</button>
        <button class="tool-button" type="button" data-command="insertOrderedList" aria-label="Numbered list" title="Numbered list">1. List</button>
        <button class="tool-button" type="button" data-action="link" aria-label="Add link" title="Add link">Link</button>
        <button class="tool-button" type="button" data-action="removeFormat" aria-label="Clear formatting" title="Clear formatting">Tx</button>
        <span class="tool-separator" aria-hidden="true"></span>
        <button class="tool-button" type="button" data-action="undo" aria-label="Undo" title="Undo">↶</button>
        <button class="tool-button" type="button" data-action="redo" aria-label="Redo" title="Redo">↷</button>
      </div>
      <template id="initial-content"><?= blog_sanitize_html($values['content']) ?></template>
      <div class="article-body" id="article-body" contenteditable="true" role="textbox" aria-label="Story body" aria-multiline="true" aria-describedby="editor-hint" data-placeholder="Tell your story..."></div>
      <textarea id="content" name="content" hidden aria-hidden="true"><?= e($values['content']) ?></textarea>
      <p class="visually-hidden" id="editor-hint">Write your article here. Use the toolbar to format text, add headings, lists, quotes, and links.</p>
      <div class="editor-footer">
        <span id="word-count" aria-live="polite">0 words</span>
        <span>Formatting is here when you need it. Just start writing.</span>
      </div>
      <div class="extra-fields">
        <p class="extra-heading">A few details</p>
        <div class="fields">
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
            <label for="featured_image">Cover image (optional)</label>
            <input id="featured_image" name="featured_image" type="file" accept="image/jpeg,image/png,image/webp">
            <p class="hint">JPG, PNG or WebP, up to 3 MB.</p>
            <?php if ($featuredImagePath !== ''): ?>
              <img src="<?= e($featuredImagePath) ?>" alt="Current cover image" style="display:block;max-width:220px;max-height:140px;object-fit:cover;border-radius:8px">
              <label><input type="checkbox" name="remove_featured_image" value="1" style="width:auto"> Remove current cover image</label>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="submit-row">
        <p class="review-hint"><?= $editingPost !== null && ($editingPost['status'] ?? '') === BLOG_STATUS_PUBLISHED ? 'The existing published version remains live while your edit is reviewed.' : 'Your story goes to our team for review before it appears publicly.' ?></p>
        <div>
          <?php if ($editingPost !== null): ?><a class="button" href="blog-submit.php?submissions_page=<?= $submissionPage ?>#my-submissions" style="background:#ece8df;color:#16233f">Cancel</a><?php endif; ?>
          <button class="button" type="submit"><?= $editingPost !== null ? 'Submit edit for review' : 'Submit story' ?></button>
        </div>
      </div>
    </form>
  </section>
</main>
<script>
  (function () {
    const editor = document.getElementById('article-body');
    const content = document.getElementById('content');
    editor.innerHTML = document.getElementById('initial-content').innerHTML;
    const form = editor.closest('form');
    const wordCount = document.getElementById('word-count');
    let savedRange = null;

    function updateWordCount() {
      const text = editor.innerText.trim();
      const count = text ? text.split(/\s+/).length : 0;
      wordCount.textContent = count + (count === 1 ? ' word' : ' words');
      content.value = editor.innerHTML;
    }

    function rememberSelection() {
      const selection = window.getSelection();
      if (selection && selection.rangeCount && editor.contains(selection.anchorNode)) {
        savedRange = selection.getRangeAt(0).cloneRange();
      }
    }

    function restoreSelection() {
      if (!savedRange) {
        editor.focus();
        return;
      }
      const selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(savedRange);
      editor.focus();
    }

    editor.addEventListener('keyup', rememberSelection);
    editor.addEventListener('mouseup', rememberSelection);
    editor.addEventListener('input', updateWordCount);
    editor.addEventListener('paste', function (event) {
      event.preventDefault();
      const text = (event.clipboardData || window.clipboardData).getData('text/plain');
      document.execCommand('insertText', false, text);
    });

    document.querySelectorAll('.tool-button').forEach(function (button) {
      button.addEventListener('mousedown', function (event) {
        event.preventDefault();
        rememberSelection();
      });
      button.addEventListener('click', function () {
        restoreSelection();
        const command = button.dataset.command;
        const block = button.dataset.block;
        const action = button.dataset.action;
        if (command) {
          document.execCommand(command, false);
        } else if (block) {
          document.execCommand('formatBlock', false, '<' + block + '>');
        } else if (action === 'link') {
          const url = window.prompt('Paste a link (https://...)');
          if (url && /^(https?:\/\/|mailto:|\/|#)/i.test(url.trim())) {
            document.execCommand('createLink', false, url.trim());
          } else if (url) {
            window.alert('Please use a web link beginning with https:// or http://.');
          }
        } else if (action) {
          document.execCommand(action, false);
        }
        rememberSelection();
        updateWordCount();
      });
    });

    form.addEventListener('submit', function (event) {
      updateWordCount();
      if (!editor.innerText.trim()) {
        event.preventDefault();
        editor.focus();
        window.alert('Add your story before submitting.');
      }
    });

    updateWordCount();
  }());
</script>
</body>
</html>
