<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'blog-core.php';

$currentUser = require_admin();
$posts = blog_all_posts();
$reviewOnly = (string) ($_GET['review'] ?? '') === 'pending';
$visiblePosts = $reviewOnly ? array_values(array_filter($posts, static fn(array $post): bool => ($post['status'] ?? '') === BLOG_STATUS_PENDING_REVIEW)) : $posts;
$categories = blog_load_categories();
$tags = blog_load_tags();
$notice = (string) ($_GET['notice'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'approve_blog' || $action === 'reject_blog') {
          $id = (string) ($_POST['id'] ?? '');
          $reviewPosts = blog_all_posts();
          $found = false;
          foreach ($reviewPosts as &$reviewPost) {
            if (($reviewPost['id'] ?? '') !== $id) continue;
            if (($reviewPost['status'] ?? '') !== BLOG_STATUS_PENDING_REVIEW) {
              throw new RuntimeException('Only posts pending review can be approved or rejected.');
            }
            $reviewPost['status'] = $action === 'approve_blog' ? BLOG_STATUS_PUBLISHED : BLOG_STATUS_REJECTED;
            $reviewPost['published_at'] = $action === 'approve_blog' ? date('Y-m-d H:i:s') : null;
            $reviewPost['review_comment'] = $action === 'reject_blog' ? trim((string) ($_POST['review_comment'] ?? 'Rejected by an administrator.')) : '';
            $reviewPost['updated_at'] = date('Y-m-d H:i:s');
            $found = true;
            break;
          }
          unset($reviewPost);
          if (!$found) throw new RuntimeException('The submitted article could not be found.');
          blog_save_posts($reviewPosts);
          $noticeText = $action === 'approve_blog' ? 'Article approved and published.' : 'Article rejected.';
          header('Location: blog-admin.php?review=pending&notice=' . rawurlencode($noticeText));
          exit;
        }
        if ($action === 'save_blog') {
          $existing = blog_get_post_by_id((string) ($_POST['id'] ?? ''));
            $payload = [
                'id' => (string) ($_POST['id'] ?? 'blog-' . bin2hex(random_bytes(6))),
                'title' => (string) ($_POST['title'] ?? ''),
                'slug' => (string) ($_POST['slug'] ?? ''),
                'excerpt' => (string) ($_POST['excerpt'] ?? ''),
                'content' => (string) ($_POST['content'] ?? ''),
                'status' => (string) ($_POST['status'] ?? BLOG_STATUS_DRAFT),
                'author_id' => (string) ($existing['author_id'] ?? $currentUser['id'] ?? ''),
                'author_name' => trim((string) ($_POST['author_name'] ?? $existing['author_name'] ?? $currentUser['name'] ?? 'Editor')),
                'category_id' => (string) ($_POST['category_id'] ?? ''),
                'featured_image' => (string) ($_POST['featured_image'] ?? ''),
                'seo_title' => (string) ($_POST['seo_title'] ?? ''),
                'seo_description' => (string) ($_POST['seo_description'] ?? ''),
                'canonical_url' => (string) ($_POST['canonical_url'] ?? ''),
                'og_title' => (string) ($_POST['og_title'] ?? ''),
                'og_description' => (string) ($_POST['og_description'] ?? ''),
                'published_at' => (string) ($_POST['published_at'] ?? ''),
                'scheduled_at' => (string) ($_POST['scheduled_at'] ?? ''),
                'created_at' => (string) ($_POST['created_at'] ?? date('Y-m-d H:i:s')),
                'updated_at' => date('Y-m-d H:i:s'),
                'tags' => (array) ($_POST['tags'] ?? []),
            ];
            $post = blog_make_post($payload);
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
            blog_set_tags_for_post($post, (array) $post['tags']);
            header('Location: blog-admin.php?notice=' . rawurlencode('Blog saved successfully.'));
            exit;
        }
        if ($action === 'delete_blog') {
            $id = (string) ($_POST['id'] ?? '');
            $filtered = [];
            foreach ($posts as $post) {
                if (($post['id'] ?? '') !== $id) {
                    $filtered[] = $post;
                }
            }
            blog_save_posts($filtered);
            header('Location: blog-admin.php?notice=' . rawurlencode('Blog removed.'));
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
        $error = $exception->getMessage();
    }
}

$selectedPostId = (string) ($_GET['post'] ?? '');
$selectedPost = $selectedPostId !== '' ? blog_get_post_by_id($selectedPostId) : null;
if ($selectedPost === null && $visiblePosts !== []) {
  $selectedPost = $visiblePosts[0];
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Blog admin | RankSetu</title>
  <style>
    :root{--navy:#16233f;--navy-2:#243a64;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#d7ccb1;--gold:#a97a24;--maroon:#9c3b2e;--green:#1f654c;--red:#7a2d26;--shadow:0 18px 40px rgba(22,35,63,.08)}
    *{box-sizing:border-box} body{margin:0;background:var(--paper);color:var(--ink);font:15px/1.5 Arial,sans-serif} a{text-decoration:none;color:inherit} h1,h2,h3{font-family:Georgia,serif;color:var(--navy);margin:0 0 12px} .wrap{max-width:1280px;margin:0 auto;padding:32px 20px 60px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:26px;padding:18px 22px;background:var(--navy);border-bottom:3px solid var(--gold);color:#fff;border-radius:10px}.brand{font-size:1.45rem;font-weight:700;font-family:Georgia,serif} .nav{display:flex;flex-wrap:wrap;gap:14px;color:#f0ead7;font-size:.9rem} .notice,.error{padding:12px 14px;border-radius:8px;margin-bottom:18px;border:1px solid transparent} .notice{background:#e8f4ee;border-color:#b0d9bf;color:#234f3e}.error{background:#fbe8e5;border-color:#e4b7b0;color:#7d372f}.layout{display:grid;grid-template-columns:1.5fr .9fr;gap:22px}.panel{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:20px;box-shadow:var(--shadow)}.panel h2{margin-bottom:14px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.field{display:flex;flex-direction:column;gap:8px}.field.full{grid-column:1/-1}.label{font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);font-weight:700}.input,.textarea,.select{width:100%;padding:11px 12px;border:1px solid var(--line);background:#fff;border-radius:8px;color:var(--ink);font:inherit}.textarea{min-height:140px;resize:vertical}.toolbar{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}.btn{padding:11px 18px;border:none;border-radius:8px;cursor:pointer;font-weight:700;display:inline-flex;align-items:center;justify-content:center}.btn.primary{background:var(--maroon);color:#fff}.btn.secondary{background:var(--navy);color:#fff}.btn.ghost{background:#fff;border:1px solid var(--line);color:var(--navy)}.listing{display:grid;gap:12px;margin-top:16px}.item{padding:14px;border:1px solid var(--line);border-radius:10px;background:#fff}.item-head{display:flex;justify-content:space-between;gap:10px;align-items:center}.status{display:inline-block;padding:4px 8px;border-radius:999px;font-size:.72rem;font-weight:700;background:#ece5d3;color:var(--navy)}.status.published{background:#dff1e8;color:var(--green)}.status.draft{background:#ece8d9;color:var(--navy)}.status.pending{background:#f6e7cf;color:#7a5702}.status.changes{background:#f3dfd7;color:#8d3e2d}.status.rejected{background:#f1d9d6;color:#7f3028}.mini{font-size:.82rem;color:var(--muted)}.stack{display:grid;gap:18px}.chip-list{display:flex;flex-wrap:wrap;gap:8px}.chip{padding:5px 9px;border-radius:999px;border:1px solid var(--line);background:#fff;font-size:.8rem}.empty{padding:20px;border:1px dashed var(--line);border-radius:10px;color:var(--muted);background:#fff}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px}.stat{padding:18px;background:#fff;border:1px solid var(--line);border-radius:10px}.stat strong{display:block;font-size:1.65rem;font-family:Georgia,serif;color:var(--maroon)}@media (max-width:960px){.layout{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.stats{grid-template-columns:1fr}} </style>
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <div>
        <div class="brand">RankSetu Blog</div>
      </div>
      <nav class="nav" aria-label="Blog admin navigation">
        <a href="admin.php">Admin home</a>
        <a href="blog.php">Public blog</a>
        <a href="logout.php">Logout</a>
      </nav>
    </header>

    <?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

    <div class="stats">
      <div class="stat"><strong><?= count(array_filter($posts, static fn(array $post): bool => ($post['status'] ?? '') === BLOG_STATUS_PUBLISHED)) ?></strong><span>Published</span></div>
      <div class="stat"><strong><?= count(array_filter($posts, static fn(array $post): bool => ($post['status'] ?? '') === BLOG_STATUS_PENDING_REVIEW)) ?></strong><span>Pending</span></div>
      <div class="stat"><strong><?= count(array_filter($posts, static fn(array $post): bool => ($post['status'] ?? '') === BLOG_STATUS_DRAFT)) ?></strong><span>Drafts</span></div>
    </div>

    <div class="layout">
      <section class="panel">
        <h2><?= $reviewOnly ? 'Pending review' : ($selectedPost !== null ? 'Edit blog' : 'Create blog') ?></h2>
        <?php if ($reviewOnly): ?><p><?= count($visiblePosts) ?> article(s) waiting for review. Approving publishes the article; rejecting keeps it off the public blog.</p><?php endif; ?>
        <form method="post" action="blog-admin.php">
          <input type="hidden" name="action" value="save_blog">
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
              <input class="input" id="featured_image" name="featured_image" value="<?= e((string) ($selectedPost['featured_image'] ?? '')) ?>" placeholder="img/article.png">
            </div>
            <div class="field">
              <label class="label" for="tags">Tags</label>
              <input class="input" id="tags" name="tags[]" value="<?= e(implode(', ', (array) ($selectedPost['tags'] ?? []))) ?>" placeholder="BPSC, Current Affairs">
            </div>
            <div class="field">
              <label class="label" for="seo_title">SEO title</label>
              <input class="input" id="seo_title" name="seo_title" value="<?= e((string) ($selectedPost['seo_title'] ?? '')) ?>">
            </div>
            <div class="field">
              <label class="label" for="seo_description">SEO description</label>
              <input class="input" id="seo_description" name="seo_description" value="<?= e((string) ($selectedPost['seo_description'] ?? '')) ?>">
            </div>
            <div class="field">
              <label class="label" for="canonical_url">Canonical URL</label>
              <input class="input" id="canonical_url" name="canonical_url" value="<?= e((string) ($selectedPost['canonical_url'] ?? '')) ?>">
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
            <a class="btn ghost" href="blog-admin.php">New draft</a>
          </div>
        </form>
      </section>

      <aside class="panel stack">
        <section>
          <h2>Blog list</h2>
          <div class="listing">
            <?php if ($visiblePosts === []): ?>
              <div class="empty"><?= $reviewOnly ? 'No articles are waiting for review.' : 'No blog posts yet. Create the first article.' ?></div>
            <?php else: ?>
              <?php foreach ($visiblePosts as $post): ?>
                <div class="item">
                  <div class="item-head">
                    <strong><?= e((string) ($post['title'] ?? 'Untitled')) ?></strong>
                    <span class="status <?= strtolower((string) ($post['status'] ?? BLOG_STATUS_DRAFT)) ?>"><?= e((string) ($post['status'] ?? BLOG_STATUS_DRAFT)) ?></span>
                  </div>
                  <div class="mini">By <?= e(blog_post_author_name($post)) ?> · <?= e((string) ($post['slug'] ?? '')) ?> · <?= e((string) (($post['published_at'] ?? $post['created_at'] ?? '')) ) ?></div>
                  <div class="toolbar" style="margin-top:10px;">
                    <a class="btn secondary" href="blog-admin.php?post=<?= e((string) ($post['id'] ?? '')) ?>">Edit</a>
                    <?php if (($post['status'] ?? '') === BLOG_STATUS_PENDING_REVIEW): ?>
                      <form method="post" action="blog-admin.php?review=pending" style="display:inline;">
                        <input type="hidden" name="action" value="approve_blog">
                        <input type="hidden" name="id" value="<?= e((string) ($post['id'] ?? '')) ?>">
                        <button class="btn primary" type="submit">Approve &amp; publish</button>
                      </form>
                      <form method="post" action="blog-admin.php?review=pending" style="display:inline;" onsubmit="return confirm('Reject this article?');">
                        <input type="hidden" name="action" value="reject_blog">
                        <input type="hidden" name="id" value="<?= e((string) ($post['id'] ?? '')) ?>">
                        <button class="btn ghost" type="submit">Reject</button>
                      </form>
                    <?php endif; ?>
                    <form method="post" action="blog-admin.php" onsubmit="return confirm('Delete this blog?');" style="display:inline;">
                      <input type="hidden" name="action" value="delete_blog">
                      <input type="hidden" name="id" value="<?= e((string) ($post['id'] ?? '')) ?>">
                      <button class="btn ghost" type="submit">Delete</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>

        <section>
          <h2>Categories</h2>
          <form method="post" action="blog-admin.php">
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
