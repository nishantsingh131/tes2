<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'blog-core.php';

$currentUser = require_admin();
$posts = blog_all_posts();
$reviewOnly = (string) ($_GET['review'] ?? '') === 'pending';
$visiblePosts = $reviewOnly ? array_values(array_filter($posts, 'blog_post_has_pending_review')) : $posts;
$categories = blog_load_categories();
$tags = blog_load_tags();
$notice = (string) ($_GET['notice'] ?? '');
$error = '';

if (!isset($_SESSION['blog_admin_csrf'])) {
    $_SESSION['blog_admin_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedImage = null;
    $imageCommitted = false;
    try {
        $tokenValue = $_POST['csrf_token'] ?? '';
        $token = is_string($tokenValue) ? $tokenValue : '';
        if (!hash_equals((string) $_SESSION['blog_admin_csrf'], $token)) {
            throw new RuntimeException('Your session has expired. Refresh the page and try again.');
        }
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'approve_blog' || $action === 'reject_blog') {
          $id = (string) ($_POST['id'] ?? '');
          $reviewPosts = blog_all_posts();
          $found = false;
          $imageToDelete = '';
          $imageToKeep = '';
          foreach ($reviewPosts as &$reviewPost) {
            if (($reviewPost['id'] ?? '') !== $id) continue;
            if (!blog_post_has_pending_review($reviewPost)) {
              throw new RuntimeException('Only posts pending review can be approved or rejected.');
            }
            $revision = blog_post_pending_revision($reviewPost);
            if ($action === 'approve_blog') {
              if ($revision !== null) {
                $imageToDelete = (string) ($reviewPost['featured_image'] ?? '');
                unset($revision['review_state']);
                $reviewPost = array_merge($reviewPost, $revision);
              }
              $imageToKeep = (string) ($reviewPost['featured_image'] ?? '');
              $reviewPost['status'] = BLOG_STATUS_PUBLISHED;
              $reviewPost['published_at'] = date('Y-m-d H:i:s');
              $reviewPost['pending_revision'] = null;
              $reviewPost['review_comment'] = '';
            } else {
              $reviewComment = trim((string) ($_POST['review_comment'] ?? 'Rejected by an administrator.'));
              if (blog_text_length($reviewComment) > 2000) throw new InvalidArgumentException('Keep review notes to 2,000 characters or fewer.');
              $reviewPost['review_comment'] = $reviewComment;
              if ($revision !== null) {
                if (($reviewPost['status'] ?? '') === BLOG_STATUS_PUBLISHED) {
                  $revision['review_state'] = BLOG_STATUS_REJECTED;
                  $reviewPost['pending_revision'] = $revision;
                } else {
                  $imageToDelete = (string) ($revision['featured_image'] ?? '');
                  $reviewPost['pending_revision'] = null;
                  $reviewPost['status'] = BLOG_STATUS_REJECTED;
                  $reviewPost['published_at'] = null;
                }
              } else {
                $reviewPost['status'] = BLOG_STATUS_REJECTED;
                $reviewPost['published_at'] = null;
              }
            }
            $reviewPost['updated_at'] = date('Y-m-d H:i:s');
            $found = true;
            break;
          }
          unset($reviewPost);
          if (!$found) throw new RuntimeException('The submitted article could not be found.');
          blog_save_posts($reviewPosts);
          if ($imageToDelete !== '' && $imageToDelete !== $imageToKeep) blog_delete_unused_featured_image($imageToDelete, $reviewPosts);
            $noticeText = $action === 'approve_blog' ? 'Article approved and published.' : 'Article rejected.';
            $returnPageValue = $_POST['return_page'] ?? '1';
            $returnPage = is_string($returnPageValue) && ctype_digit($returnPageValue) ? max(1, (int) $returnPageValue) : 1;
            header('Location: blog-admin.php?review=pending&page=' . $returnPage . '&notice=' . rawurlencode($noticeText) . '#blog-list');
            exit;
        }
        if ($action === 'save_blog') {
            $postId = (string) ($_POST['id'] ?? '');
            $existing = $postId !== '' ? blog_get_post_by_id($postId) : null;
            $existingRevision = $existing !== null ? blog_post_pending_revision($existing) : null;
            $existingFormPost = $existingRevision !== null ? array_merge($existing, $existingRevision) : $existing;
            $imageInput = $_FILES['featured_image'] ?? [];
            if (!is_array($imageInput)) throw new InvalidArgumentException('The image upload is invalid.');
            foreach (['name', 'tmp_name', 'error', 'size', 'type'] as $imageKey) {
                if (isset($imageInput[$imageKey]) && !is_scalar($imageInput[$imageKey])) {
                    throw new InvalidArgumentException('The image upload is invalid.');
                }
            }
            $uploadedImage = blog_save_featured_image_upload($imageInput);
            $imagePath = $uploadedImage ?? (string) ($existingFormPost['featured_image'] ?? '');
            $removeImageValue = $_POST['remove_featured_image'] ?? '';
            if (is_string($removeImageValue) && $removeImageValue === '1' && $uploadedImage === null) $imagePath = '';
            $requestedStatus = (string) ($_POST['status'] ?? BLOG_STATUS_DRAFT);
            if (!in_array($requestedStatus, blog_status_options(), true)) {
              if ($uploadedImage !== null) blog_delete_featured_image($uploadedImage);
              throw new InvalidArgumentException('Choose a valid blog status.');
            }
            $payload = [
                  'id' => $postId !== '' ? $postId : 'blog-' . bin2hex(random_bytes(6)),
                  'title' => (string) ($_POST['title'] ?? ''),
                  'slug' => (string) ($_POST['slug'] ?? ''),
                  'excerpt' => (string) ($_POST['excerpt'] ?? ''),
                  'content' => (string) ($_POST['content'] ?? ''),
                  'status' => $requestedStatus,
                  'author_id' => (string) ($existing['author_id'] ?? $currentUser['id'] ?? ''),
                  'author_name' => trim((string) ($_POST['author_name'] ?? $existingFormPost['author_name'] ?? $currentUser['name'] ?? 'Editor')),
                  'category_id' => (string) ($_POST['category_id'] ?? ''),
                  'featured_image' => $imagePath,
                  'seo_title' => (string) ($_POST['seo_title'] ?? ''),
                  'seo_description' => (string) ($_POST['seo_description'] ?? ''),
                  'canonical_url' => (string) ($_POST['canonical_url'] ?? ''),
                  'og_title' => (string) ($_POST['og_title'] ?? ''),
                  'og_description' => (string) ($_POST['og_description'] ?? ''),
                  'published_at' => (string) ($_POST['published_at'] ?? ''),
                  'scheduled_at' => (string) ($_POST['scheduled_at'] ?? ''),
                  'created_at' => (string) ($existing['created_at'] ?? date('Y-m-d H:i:s')),
                  'updated_at' => date('Y-m-d H:i:s'),
                  'tags' => (array) ($_POST['tags'] ?? []),
              ];
              $post = blog_make_post($payload);
              if ($existing !== null && ($existing['status'] ?? '') === BLOG_STATUS_PUBLISHED && $post['slug'] !== (string) ($existing['slug'] ?? '')) {
                  throw new InvalidArgumentException('Keep the URL slug for published articles so existing search results and shared links continue to work.');
              }
              $post['pending_revision'] = null;
              if ($post['status'] === BLOG_STATUS_PUBLISHED && trim((string) ($post['published_at'] ?? '')) === '') {
                  $post['published_at'] = date('Y-m-d H:i:s');
              }
              $postList = blog_all_posts();
              $seen = false;
              foreach ($postList as &$item) {
                if (($item['id'] ?? '') === $post['id']) {
                    $item = $post;
                    $seen = true;
                    break;
                }
            }
            unset($item);
            if (!$seen) {
                $postList[] = $post;
            }
            blog_save_posts($postList);
            $imageCommitted = true;
            blog_set_tags_for_post($post, (array) $post['tags']);
            $previousImage = (string) ($existing['featured_image'] ?? '');
            if ($existingRevision !== null && $previousImage !== '') {
                $previousImage = (string) ($existingRevision['featured_image'] ?? $previousImage);
            }
            if ($previousImage !== '' && $previousImage !== $imagePath) blog_delete_unused_featured_image($previousImage, $postList);
            $returnPageValue = $_POST['return_page'] ?? '1';
            $returnPage = is_string($returnPageValue) && ctype_digit($returnPageValue) ? max(1, (int) $returnPageValue) : 1;
            header('Location: blog-admin.php?page=' . $returnPage . '&notice=' . rawurlencode('Blog saved successfully.') . '#blog-list');
            exit;
        }
        if ($action === 'delete_blog') {
            $id = (string) ($_POST['id'] ?? '');
            $filtered = [];
            $deletedPost = null;
            foreach ($posts as $post) {
                if (($post['id'] ?? '') !== $id) $filtered[] = $post;
                else $deletedPost = $post;
            }
            if ($deletedPost === null) throw new RuntimeException('The blog post could not be found.');
            blog_save_posts($filtered);
            $deletedRevision = blog_post_pending_revision($deletedPost);
            blog_delete_unused_featured_image((string) ($deletedPost['featured_image'] ?? ''), $filtered);
            if ($deletedRevision !== null && ($deletedRevision['featured_image'] ?? '') !== ($deletedPost['featured_image'] ?? '')) {
                blog_delete_unused_featured_image((string) ($deletedRevision['featured_image'] ?? ''), $filtered);
            }
            $returnPageValue = $_POST['return_page'] ?? '1';
            $returnPage = is_string($returnPageValue) && ctype_digit($returnPageValue) ? max(1, (int) $returnPageValue) : 1;
            header('Location: blog-admin.php?page=' . $returnPage . '&notice=' . rawurlencode('Blog removed.') . '#blog-list');
            exit;
        }
        if ($action === 'category_save') {
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '') {
                throw new InvalidArgumentException('Category name is required.');
            }
            $slug = blog_slugify((string) ($_POST['slug'] ?? $name));
            $row = ['id' => (string) ($_POST['id'] ?? 'cat-' . bin2hex(random_bytes(4))), 'name' => $name, 'slug' => $slug, 'description' => trim((string) ($_POST['description'] ?? ''))];
            $items = $categories;
            $updated = false;
            foreach ($items as &$item) {
                if (($item['id'] ?? '') === $row['id']) {
                    $item = $row;
                    $updated = true;
                    break;
                }
            }
            unset($item);
            if (!$updated) {
                $items[] = $row;
            }
            blog_save_categories($items);
            header('Location: blog-admin.php?notice=' . rawurlencode('Category saved.'));
            exit;
        }
        if ($action === 'tag_save') {
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '') {
                throw new InvalidArgumentException('Tag name is required.');
            }
            $slug = blog_slugify((string) ($_POST['slug'] ?? $name));
            $row = ['id' => (string) ($_POST['id'] ?? 'tag-' . bin2hex(random_bytes(4))), 'name' => $name, 'slug' => $slug];
            $items = $tags;
            $updated = false;
            foreach ($items as &$item) {
                if (($item['id'] ?? '') === $row['id']) {
                    $item = $row;
                    $updated = true;
                    break;
                }
            }
            unset($item);
            if (!$updated) {
                $items[] = $row;
            }
            blog_save_tags($items);
            header('Location: blog-admin.php?notice=' . rawurlencode('Tag saved.'));
            exit;
        }
    } catch (Throwable $exception) {
        if ($uploadedImage !== null && !$imageCommitted) blog_delete_featured_image($uploadedImage);
        $error = $exception->getMessage();
    }
}

$newPost = (string) ($_GET['new'] ?? '') === '1';
$selectedPostId = (string) ($_GET['post'] ?? '');
$pageValue = $_GET['page'] ?? ($_POST['return_page'] ?? '1');
$adminPage = is_string($pageValue) && ctype_digit($pageValue) ? max(1, (int) $pageValue) : 1;
$adminPageSize = 10;
$adminPostCount = count($visiblePosts);
$adminPageCount = max(1, (int) ceil($adminPostCount / $adminPageSize));
$adminPage = min($adminPage, $adminPageCount);
if (!$newPost && $selectedPostId !== '') {
    foreach ($visiblePosts as $index => $post) {
        if (($post['id'] ?? '') === $selectedPostId) {
            $adminPage = (int) floor($index / $adminPageSize) + 1;
            break;
        }
    }
}
$adminRangeStart = $adminPostCount === 0 ? 0 : (($adminPage - 1) * $adminPageSize) + 1;
$adminRangeEnd = min($adminPostCount, $adminPage * $adminPageSize);
$adminPageLinkStart = max(1, $adminPage - 2);
$adminPageLinkEnd = min($adminPageCount, $adminPage + 2);
$visiblePosts = array_slice($visiblePosts, ($adminPage - 1) * $adminPageSize, $adminPageSize);
$selectedPost = !$newPost && $selectedPostId !== '' ? blog_get_post_by_id($selectedPostId) : null;
if (!$newPost && $selectedPost === null && $visiblePosts !== []) {
  $selectedPost = $visiblePosts[0];
}
if ($selectedPost !== null) {
    $selectedRevision = blog_post_pending_revision($selectedPost);
    if ($selectedRevision !== null) $selectedPost = array_merge($selectedPost, $selectedRevision);
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Blog admin | RankSetu</title>
  <style>
    :root{--navy:#16233f;--navy-2:#243a64;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#d7ccb1;--gold:#a97a24;--maroon:#9c3b2e;--green:#1f654c;--red:#7a2d26;--shadow:0 18px 40px rgba(22,35,63,.08)}
    *{box-sizing:border-box} body{margin:0;background:var(--paper);color:var(--ink);font:15px/1.5 Arial,sans-serif} a{text-decoration:none;color:inherit} h1,h2,h3{font-family:Georgia,serif;color:var(--navy);margin:0 0 12px} .wrap{max-width:1280px;margin:0 auto;padding:32px 20px 60px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:26px;padding:18px 22px;background:var(--navy);border-bottom:3px solid var(--gold);color:#fff;border-radius:10px}.brand{font-size:1.45rem;font-weight:700;font-family:Georgia,serif} .nav{display:flex;flex-wrap:wrap;gap:14px;color:#f0ead7;font-size:.9rem} .notice,.error{padding:12px 14px;border-radius:8px;margin-bottom:18px;border:1px solid transparent} .notice{background:#e8f4ee;border-color:#b0d9bf;color:#234f3e}.error{background:#fbe8e5;border-color:#e4b7b0;color:#7d372f}.layout{display:grid;grid-template-columns:1.5fr .9fr;gap:22px}.panel{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:20px;box-shadow:var(--shadow)}.panel h2{margin-bottom:14px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.field{display:flex;flex-direction:column;gap:8px}.field.full{grid-column:1/-1}.label{font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);font-weight:700}.input,.textarea,.select{width:100%;padding:11px 12px;border:1px solid var(--line);background:#fff;border-radius:8px;color:var(--ink);font:inherit}.textarea{min-height:140px;resize:vertical}.toolbar{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}.btn{padding:11px 18px;border:none;border-radius:8px;cursor:pointer;font-weight:700;display:inline-flex;align-items:center;justify-content:center}.btn.primary{background:var(--maroon);color:#fff}.btn.secondary{background:var(--navy);color:#fff}.btn.ghost{background:#fff;border:1px solid var(--line);color:var(--navy)}.listing{display:grid;gap:12px;margin-top:16px}.item{padding:14px;border:1px solid var(--line);border-radius:10px;background:#fff}.item-head{display:flex;justify-content:space-between;gap:10px;align-items:center}.status{display:inline-block;padding:4px 8px;border-radius:999px;font-size:.72rem;font-weight:700;background:#ece5d3;color:var(--navy)}.status.published{background:#dff1e8;color:var(--green)}.status.draft{background:#ece8d9;color:var(--navy)}.status.pending{background:#f6e7cf;color:#7a5702}.status.changes{background:#f3dfd7;color:#8d3e2d}.status.rejected{background:#f1d9d6;color:#7f3028}.mini{font-size:.82rem;color:var(--muted)}.stack{display:grid;gap:18px}.chip-list{display:flex;flex-wrap:wrap;gap:8px}.chip{padding:5px 9px;border-radius:999px;border:1px solid var(--line);background:#fff;font-size:.8rem}.empty{padding:20px;border:1px dashed var(--line);border-radius:10px;color:var(--muted);background:#fff}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px}.stat{padding:18px;background:#fff;border:1px solid var(--line);border-radius:10px}.stat strong{display:block;font-size:1.65rem;font-family:Georgia,serif;color:var(--maroon)}.pagination{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:16px;padding-top:14px;border-top:1px solid var(--line)}.pagination-links{display:flex;flex-wrap:wrap;gap:7px}.pagination a{padding:7px 11px;border:1px solid var(--line);border-radius:6px;background:#fff;color:var(--navy)}.pagination a[aria-current=page]{background:var(--navy);color:#fff}.pagination .disabled{color:#aaa}@media (max-width:960px){.layout{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.stats{grid-template-columns:1fr}}@media(max-width:600px){.pagination{align-items:flex-start;flex-direction:column}.item-head{align-items:flex-start;flex-direction:column}} </style>
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <div>
        <div class="brand">Blog &amp; tutorials</div>
      </div>
      <nav class="nav" aria-label="Blog admin navigation">
        <a href="admin.php">Admin home</a>
        <a href="blog-admin.php">All articles</a>
        <a href="blog-admin.php?review=pending">Review queue</a>
        <a href="blog.php">Public blog</a>
        <a href="logout.php">Logout</a>
      </nav>
    </header>

    <?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

    <div class="stats">
      <div class="stat"><strong><?= count(array_filter($posts, static fn(array $post): bool => ($post['status'] ?? '') === BLOG_STATUS_PUBLISHED)) ?></strong><span>Published</span></div>
      <div class="stat"><strong><?= count(array_filter($posts, 'blog_post_has_pending_review')) ?></strong><span>Pending</span></div>
      <div class="stat"><strong><?= count(array_filter($posts, static fn(array $post): bool => ($post['status'] ?? '') === BLOG_STATUS_DRAFT)) ?></strong><span>Drafts</span></div>
    </div>

    <div class="layout">
      <section class="panel">
        <h2><?= $reviewOnly ? 'Pending review' : ($selectedPost !== null ? 'Edit blog' : 'Create blog') ?></h2>
        <?php if ($reviewOnly): ?><p><?= count($visiblePosts) ?> submission(s) waiting for review. A proposed edit leaves its current published article live until approval.</p><?php endif; ?>
        <form method="post" action="blog-admin.php" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['blog_admin_csrf']) ?>">
          <input type="hidden" name="action" value="save_blog">
          <input type="hidden" name="return_page" value="<?= $adminPage ?>">
          <input type="hidden" name="id" value="<?= e((string) ($selectedPost['id'] ?? 'blog-' . bin2hex(random_bytes(6)))) ?>">
          <input type="hidden" name="created_at" value="<?= e((string) (($selectedPost['created_at'] ?? '') ?: date('Y-m-d H:i:s'))) ?>">
          <div class="form-grid">
            <div class="field">
              <label class="label" for="title">Title</label>
              <input class="input" id="title" name="title" value="<?= e((string) ($selectedPost['title'] ?? '')) ?>" required>
            </div>
            <div class="field">
              <label class="label" for="author_name">Author name</label>
              <input class="input" id="author_name" name="author_name" value="<?= e((string) (($selectedPost['author_name'] ?? '') ?: $currentUser['name'])) ?>" required>
            </div>
            <div class="field">
              <label class="label" for="slug">Slug</label>
              <input class="input" id="slug" name="slug" value="<?= e((string) ($selectedPost['slug'] ?? '')) ?>" placeholder="bpsc-prelims-2026-strategy">
            </div>
            <div class="field full">
              <label class="label" for="excerpt">Excerpt</label>
              <textarea class="textarea" id="excerpt" name="excerpt" placeholder="Short summary for card and metadata"><?= e((string) ($selectedPost['excerpt'] ?? '')) ?></textarea>
            </div>
            <div class="field full">
              <label class="label" for="content">Content</label>
              <textarea class="textarea" id="content" name="content" placeholder="Write the article here..." required><?= e((string) ($selectedPost['content'] ?? '')) ?></textarea>
              <span class="mini">Answer the reader’s question early, use helpful section headings, add original examples, link to official sources for exam facts, and connect to relevant published guides. Do not repeat keywords unnaturally.</span>
            </div>
            <div class="field">
              <label class="label" for="category_id">Category</label>
              <select class="select" id="category_id" name="category_id">
                <option value="">Select category</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?= e((string) ($category['id'] ?? '')) ?>" <?= (($selectedPost['category_id'] ?? '') === ((string) ($category['id'] ?? '')) ? 'selected' : '') ?>><?= e((string) ($category['name'] ?? '')) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label class="label" for="status">Status</label>
              <select class="select" id="status" name="status">
                <?php foreach (blog_status_options() as $option): ?>
                  <option value="<?= e($option) ?>" <?= (($selectedPost['status'] ?? BLOG_STATUS_DRAFT) === $option ? 'selected' : '') ?>><?= e($option) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label class="label" for="featured_image">Featured image</label>
              <input class="input" id="featured_image" name="featured_image" type="file" accept="image/jpeg,image/png,image/webp">
              <span class="mini">Use an original, topic-relevant cover (JPG, PNG or WebP, up to 3 MB). A descriptive title-based alt text is added automatically; social image previews are available when a cover is set.</span>
              <?php if (trim((string) ($selectedPost['featured_image'] ?? '')) !== ''): ?>
                <img src="<?= e((string) $selectedPost['featured_image']) ?>" alt="Current featured image" style="max-width:220px;max-height:140px;object-fit:cover;border-radius:8px">
                <label><input type="checkbox" name="remove_featured_image" value="1"> Remove current featured image</label>
              <?php endif; ?>
            </div>
            <div class="field">
              <label class="label" for="tags">Tags</label>
              <input class="input" id="tags" name="tags[]" value="<?= e(implode(', ', (array) ($selectedPost['tags'] ?? []))) ?>" placeholder="BPSC, Current Affairs">
              <span class="mini">Use a few accurate topics for readers and article markup, not a list of keyword variants.</span>
            </div>
            <div class="field">
              <label class="label" for="seo_title">SEO title</label>
              <input class="input" id="seo_title" name="seo_title" value="<?= e((string) ($selectedPost['seo_title'] ?? '')) ?>" placeholder="Clear article topic and useful angle">
              <span class="mini">Write a concise, unique title that matches the article’s visible heading. Include one natural primary search phrase; avoid keyword lists and guarantees.</span>
            </div>
            <div class="field">
              <label class="label" for="seo_description">SEO description</label>
              <input class="input" id="seo_description" name="seo_description" value="<?= e((string) ($selectedPost['seo_description'] ?? '')) ?>" placeholder="Summarize what the reader will learn">
              <span class="mini">Summarize the article’s real takeaway in a distinct sentence. Search engines may choose a different snippet.</span>
            </div>
            <div class="field">
              <label class="label" for="canonical_url">Canonical URL</label>
              <input class="input" id="canonical_url" name="canonical_url" value="<?= e((string) ($selectedPost['canonical_url'] ?? '')) ?>" placeholder="Leave blank for the article’s own canonical URL">
              <span class="mini">The public article uses its own canonical URL. Do not enter another URL unless a deliberate duplicate-content migration has been reviewed.</span>
            </div>
            <div class="field">
              <label class="label" for="published_at">Published at</label>
              <input class="input" id="published_at" name="published_at" type="datetime-local" value="<?= e((string) (($selectedPost['published_at'] ?? '') !== '' ? date('Y-m-d\TH:i', strtotime((string) $selectedPost['published_at'])) : '')) ?>">
            </div>
            <div class="field">
              <label class="label" for="scheduled_at">Scheduled at</label>
              <input class="input" id="scheduled_at" name="scheduled_at" type="datetime-local" value="<?= e((string) (($selectedPost['scheduled_at'] ?? '') !== '' ? date('Y-m-d\TH:i', strtotime((string) $selectedPost['scheduled_at'])) : '')) ?>">
            </div>
            <div class="field">
              <label class="label" for="og_title">OG title</label>
              <input class="input" id="og_title" name="og_title" value="<?= e((string) ($selectedPost['og_title'] ?? '')) ?>">
            </div>
            <div class="field">
              <label class="label" for="og_description">OG description</label>
              <input class="input" id="og_description" name="og_description" value="<?= e((string) ($selectedPost['og_description'] ?? '')) ?>">
            </div>
            <div class="field full">
              <label class="label" for="review_comment">Review comment</label>
              <textarea class="textarea" id="review_comment" name="review_comment" placeholder="Admin review notes or requested changes..."><?= e((string) ($selectedPost['review_comment'] ?? '')) ?></textarea>
            </div>
          </div>

          <div class="toolbar">
            <button class="btn primary" type="submit">Save blog</button>
            <a class="btn ghost" href="blog-admin.php?new=1">New draft</a>
          </div>
        </form>
      </section>

      <aside class="panel stack">
        <section id="blog-list">
          <h2><?= $reviewOnly ? 'Review queue' : 'All articles' ?></h2>
          <p class="mini"><?= $adminPostCount === 0 ? 'No articles to show.' : 'Showing ' . $adminRangeStart . '–' . $adminRangeEnd . ' of ' . $adminPostCount . ' articles.' ?></p>
          <div class="listing">
            <?php if ($visiblePosts === []): ?>
              <div class="empty"><?= $reviewOnly ? 'No articles are waiting for review.' : 'No blog posts yet. Create the first article.' ?></div>
            <?php else: ?>
              <?php foreach ($visiblePosts as $post): ?>
                <?php $listRevision = blog_post_pending_revision($post); ?>
                <?php $displayPost = $listRevision ?? $post; ?>
                <div class="item">
                  <div class="item-head">
                    <strong><?= e((string) ($displayPost['title'] ?? 'Untitled')) ?></strong>
                    <span class="status <?= blog_post_has_pending_review($post) ? 'pending' : strtolower((string) ($post['status'] ?? BLOG_STATUS_DRAFT)) ?>"><?= e(blog_post_has_pending_review($post) && $listRevision !== null ? 'PENDING EDIT REVIEW' : (($listRevision['review_state'] ?? '') === BLOG_STATUS_REJECTED ? 'EDIT REJECTED' : (string) ($post['status'] ?? BLOG_STATUS_DRAFT))) ?></span>
                  </div>
                  <div class="mini">By <?= e(blog_post_author_name($post)) ?> · <?= e((string) ($displayPost['slug'] ?? '')) ?> · <?= e((string) (($displayPost['updated_at'] ?? $post['published_at'] ?? $post['created_at'] ?? '')) ) ?></div>
                  <div class="toolbar" style="margin-top:10px;">
                    <a class="btn secondary" href="blog-admin.php?post=<?= e(rawurlencode((string) ($post['id'] ?? ''))) ?>&amp;page=<?= $adminPage ?><?= $reviewOnly ? '&amp;review=pending' : '' ?>">Edit</a>
                    <?php if (blog_post_has_pending_review($post)): ?>
                      <form method="post" action="blog-admin.php?review=pending" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['blog_admin_csrf']) ?>">
                        <input type="hidden" name="return_page" value="<?= $adminPage ?>">
                        <input type="hidden" name="action" value="approve_blog">
                        <input type="hidden" name="id" value="<?= e((string) ($post['id'] ?? '')) ?>">
                        <button class="btn primary" type="submit">Approve &amp; publish</button>
                      </form>
                      <form method="post" action="blog-admin.php?review=pending" style="display:inline;" onsubmit="return confirm('Reject this article?');">
                        <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['blog_admin_csrf']) ?>">
                        <input type="hidden" name="return_page" value="<?= $adminPage ?>">
                        <input type="hidden" name="action" value="reject_blog">
                        <input type="hidden" name="id" value="<?= e((string) ($post['id'] ?? '')) ?>">
                        <input class="input" name="review_comment" placeholder="Reason (optional)" aria-label="Reason for rejecting this article">
                        <button class="btn ghost" type="submit">Reject</button>
                      </form>
                    <?php endif; ?>
                    <form method="post" action="blog-admin.php" onsubmit="return confirm('Delete this blog?');" style="display:inline;">
                      <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['blog_admin_csrf']) ?>">
                      <input type="hidden" name="return_page" value="<?= $adminPage ?>">
                      <input type="hidden" name="action" value="delete_blog">
                      <input type="hidden" name="id" value="<?= e((string) ($post['id'] ?? '')) ?>">
                      <button class="btn ghost" type="submit">Delete</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <?php if ($adminPageCount > 1): ?>
            <nav class="pagination" aria-label="Blog article pages">
              <span>Page <?= $adminPage ?> of <?= $adminPageCount ?></span>
              <div class="pagination-links">
                <?php if ($adminPage > 1): ?><a href="blog-admin.php?page=1<?= $reviewOnly ? '&amp;review=pending' : '' ?>#blog-list">First</a><a href="blog-admin.php?page=<?= $adminPage - 1 ?><?= $reviewOnly ? '&amp;review=pending' : '' ?>#blog-list" rel="prev">Previous</a><?php else: ?><span class="disabled">First · Previous</span><?php endif; ?>
                <?php for ($pageNumber = $adminPageLinkStart; $pageNumber <= $adminPageLinkEnd; $pageNumber++): ?>
                  <?php if ($pageNumber === $adminPage): ?><a href="blog-admin.php?page=<?= $pageNumber ?><?= $reviewOnly ? '&amp;review=pending' : '' ?>#blog-list" aria-current="page"><?= $pageNumber ?></a><?php else: ?><a href="blog-admin.php?page=<?= $pageNumber ?><?= $reviewOnly ? '&amp;review=pending' : '' ?>#blog-list"><?= $pageNumber ?></a><?php endif; ?>
                <?php endfor; ?>
                <?php if ($adminPage < $adminPageCount): ?><a href="blog-admin.php?page=<?= $adminPage + 1 ?><?= $reviewOnly ? '&amp;review=pending' : '' ?>#blog-list" rel="next">Next</a><a href="blog-admin.php?page=<?= $adminPageCount ?><?= $reviewOnly ? '&amp;review=pending' : '' ?>#blog-list">Last</a><?php else: ?><span class="disabled">Next · Last</span><?php endif; ?>
              </div>
            </nav>
          <?php endif; ?>
        </section>

        <section>
          <h2>Categories</h2>
          <form method="post" action="blog-admin.php">
            <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['blog_admin_csrf']) ?>">
            <input type="hidden" name="action" value="category_save">
            <div class="form-grid">
              <div class="field full"><label class="label" for="cat_name">Name</label><input class="input" id="cat_name" name="name"></div>
              <div class="field full"><label class="label" for="cat_slug">Slug</label><input class="input" id="cat_slug" name="slug"></div>
              <div class="field full"><label class="label" for="cat_desc">Description</label><textarea class="textarea" id="cat_desc" name="description"></textarea></div>
            </div>
            <div class="toolbar"><button class="btn primary" type="submit">Save category</button></div>
          </form>
          <div class="chip-list" style="margin-top:14px;">
            <?php foreach ($categories as $category): ?>
              <span class="chip"><?= e((string) ($category['name'] ?? '')) ?></span>
            <?php endforeach; ?>
          </div>
        </section>

        <section>
          <h2>Tags</h2>
          <form method="post" action="blog-admin.php">
            <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['blog_admin_csrf']) ?>">
            <input type="hidden" name="action" value="tag_save">
            <div class="form-grid">
              <div class="field full"><label class="label" for="tag_name">Name</label><input class="input" id="tag_name" name="name"></div>
              <div class="field full"><label class="label" for="tag_slug">Slug</label><input class="input" id="tag_slug" name="slug"></div>
            </div>
            <div class="toolbar"><button class="btn primary" type="submit">Save tag</button></div>
          </form>
          <div class="chip-list" style="margin-top:14px;">
            <?php foreach ($tags as $tag): ?>
              <span class="chip"><?= e((string) ($tag['name'] ?? '')) ?></span>
            <?php endforeach; ?>
          </div>
        </section>
      </aside>
    </div>
  </div>
</body>
</html>
