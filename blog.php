<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'blog-core.php';

$currentSlug = trim((string) ($_GET['slug'] ?? ''));
$posts = blog_public_posts();

if ($currentSlug !== '') {
    $post = blog_get_post_by_slug($currentSlug);
    if ($post === null) {
        http_response_code(404);
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Blog not found</title></head><body><h1>Blog not found</h1><p>The article you requested is not available.</p></body></html>';
        exit;
    }
    $category = blog_category_by_id((string) ($post['category_id'] ?? ''));
    $related = blog_related_posts($post);
    $canonical = blog_current_url('blog/' . rawurlencode($post['slug'] ?? ''));
    $shareLinks = blog_social_share_links((string) ($post['title'] ?? ''), $canonical);
    $readingTime = blog_post_reading_time((string) ($post['content'] ?? ''));
    ?><!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?= e((string) ($post['seo_title'] ?? $post['title'] ?? 'Blog')) ?></title>
      <meta name="description" content="<?= e((string) ($post['seo_description'] ?? $post['excerpt'] ?? '')) ?>">
      <meta property="og:title" content="<?= e((string) ($post['og_title'] ?? $post['title'] ?? '')) ?>">
      <meta property="og:description" content="<?= e((string) ($post['og_description'] ?? $post['excerpt'] ?? '')) ?>">
      <meta property="og:type" content="article">
      <meta property="og:url" content="<?= e($canonical) ?>">
      <meta name="twitter:card" content="summary_large_image">
      <link rel="canonical" href="<?= e($canonical) ?>">
      <style>
        :root{--navy:#16233f;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#d7ccb1;--gold:#a97a24;--maroon:#9c3b2e;--green:#1f654c;--shadow:0 18px 42px rgba(22,35,63,.08)} *{box-sizing:border-box} body{margin:0;background:var(--paper);color:var(--ink);font:16px/1.7 Arial,sans-serif} a{text-decoration:none;color:inherit}.wrap{max-width:1100px;margin:0 auto;padding:24px 20px 60px}.topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;background:var(--navy);padding:18px 20px;border-bottom:3px solid var(--gold);border-radius:10px;color:#fff}.brand{font-size:1.45rem;font-weight:700;font-family:Georgia,serif}.nav{display:flex;gap:14px;flex-wrap:wrap}.article{margin-top:30px;background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:28px}.eyebrow{display:inline-block;padding:5px 10px;border-radius:999px;background:#efe1cb;color:var(--navy);font-weight:700;text-transform:uppercase;font-size:.76rem}.hero{display:grid;grid-template-columns:1.15fr .85fr;gap:20px;align-items:center;margin:18px 0 22px}.hero h1{font-family:Georgia,serif;font-size:clamp(2.1rem,4vw,3.4rem);line-height:1.15;margin:0 0 12px}.meta{display:flex;flex-wrap:wrap;gap:18px;color:var(--muted);font-size:.9rem}.feature-image{width:100%;height:auto;border-radius:12px;border:1px solid var(--line);background:#f2efe7}.content{margin-top:24px} .content h2,.content h3,.content h4{font-family:Georgia,serif;color:var(--navy);margin:1.4em 0 .7em}.content p,.content li{font-size:1.02rem}.content img{max-width:100%;border-radius:10px;margin:18px 0}.content blockquote{margin:22px 0;padding:18px 18px 18px 22px;border-left:4px solid var(--gold);background:#f6f3ea;color:var(--ink);font-style:italic}.content pre{background:#1d2330;color:#f2f4f8;padding:16px;border-radius:10px;overflow:auto}.content table{width:100%;border-collapse:collapse;margin:18px 0}.content th,.content td{padding:10px 12px;border:1px solid var(--line);text-align:left}.share{display:flex;flex-wrap:wrap;gap:10px;margin:22px 0}.share a{padding:8px 12px;border:1px solid var(--line);border-radius:999px;background:#fff}.related{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:30px}.card{padding:18px;border:1px solid var(--line);border-radius:12px;background:#fff}.card h3{font-family:Georgia,serif;margin:0 0 10px}.tags{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px}.tag{padding:5px 10px;border-radius:999px;background:#f2efe6;border:1px solid var(--line);font-size:.78rem}.btn{display:inline-block;padding:10px 16px;border-radius:8px;background:var(--maroon);color:#fff;font-weight:700}.@media(max-width:900px){.hero,.related{grid-template-columns:1fr}.article{padding:18px}} </style>
    </head>
    <body>
      <div class="wrap">
        <article class="article">
          <div class="eyebrow"><?= e((string) ($category['name'] ?? 'Blog')) ?></div>
          <div class="hero">
            <div>
              <h1><?= e((string) ($post['title'] ?? '')) ?></h1>
              <div class="meta">
                <span>By <?= e(blog_post_author_name($post)) ?></span>
                <span><?= e((string) ($post['published_at'] ?? $post['created_at'] ?? '')) ?></span>
                <span><?= e($readingTime) ?></span>
              </div>
              <p style="margin-top:18px;color:var(--muted)"><?= e((string) ($post['excerpt'] ?? '')) ?></p>
            </div>
            <?php if (($post['featured_image'] ?? '') !== ''): ?><img class="feature-image" src="<?= e((string) $post['featured_image']) ?>" alt="<?= e((string) ($post['title'] ?? '')) ?>"> <?php endif; ?>
          </div>
          <div class="content">
            <?= $post['content'] ?? '' ?>
          </div>
          <div class="share">
            <?php foreach ($shareLinks as $label => $href): ?>
              <a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a>
            <?php endforeach; ?>
          </div>
          <?php if (($post['tags'] ?? []) !== []): ?>
            <div class="tags">
              <?php foreach ((array) $post['tags'] as $tag): ?><span class="tag"><?= e((string) $tag) ?></span><?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>

        <?php if ($related !== []): ?>
          <section style="margin-top:30px;">
            <h2>Related articles</h2>
            <div class="related">
              <?php foreach ($related as $relatedPost): ?>
                <article class="card">
                  <h3><?= e((string) ($relatedPost['title'] ?? '')) ?></h3>
                  <p><?= e((string) ($relatedPost['excerpt'] ?? '')) ?></p>
                  <p style="margin-top:12px;"><a class="btn" href="blog.php?slug=<?= e(rawurlencode((string) ($relatedPost['slug'] ?? ''))) ?>">Read more</a></p>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>
      </div>
    </body>
    </html>
    <?php exit; 
}

$featured = array_slice($posts, 0, 3);
$categories = blog_categories_with_counts();
$tags = blog_tag_suggestions();
$writeBlogUrl = current_user() !== null ? 'blog-submit.php' : 'auth.php?next=blog-submit.php';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Latest Articles | RankSetu Blog</title>
  <meta name="description" content="Read study strategy, exam guidance and current-affairs insights from RankSetu.">
  <style>
    :root{--navy:#16233f;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#d7ccb1;--gold:#a97a24;--maroon:#9c3b2e;--green:#1f654c;--shadow:0 18px 42px rgba(22,35,63,.08)} *{box-sizing:border-box} body{margin:0;background:var(--paper);color:var(--ink);font:16px/1.6 Arial,sans-serif} a{text-decoration:none;color:inherit} h1,h2,h3{font-family:Georgia,serif;color:var(--navy);margin:0 0 12px}.wrap{max-width:1220px;margin:0 auto;padding:24px 20px 60px}.topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;background:var(--navy);padding:18px 20px;border-bottom:3px solid var(--gold);border-radius:10px;color:#fff}.brand{font-size:1.45rem;font-weight:700;font-family:Georgia,serif}.nav{display:flex;gap:14px;flex-wrap:wrap}.hero{padding:40px 0 16px}.hero-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:20px;align-items:center}.eyebrow{display:inline-block;padding:5px 10px;background:#f1e5d1;border-radius:999px;color:var(--navy);font-size:.76rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.hero h1{font-size:clamp(2.3rem,4vw,4rem);line-height:1.1}.lede{font-size:1.05rem;color:var(--muted);margin-top:16px;max-width:60ch}.feature-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:22px}.feature-card,.post-card,.sidebar-card{border:1px solid var(--line);background:#fff;border-radius:14px;box-shadow:var(--shadow);padding:18px}.feature-card h3,.post-card h3{font-size:1.3rem}.feature-card p,.post-card p{color:var(--muted)} .grid{display:grid;grid-template-columns:1.7fr .9fr;gap:22px;margin-top:26px}.post-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.post-card{overflow:hidden}.post-card img{width:100%;height:220px;object-fit:cover;border-radius:10px;margin-bottom:12px}.meta{display:flex;flex-wrap:wrap;gap:11px;color:var(--muted);font-size:.82rem}.tag-list{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}.tag{padding:6px 10px;border-radius:999px;border:1px solid var(--line);background:#f5f1e8;font-size:.76rem}.btn{display:inline-block;padding:10px 16px;border-radius:8px;background:var(--maroon);color:#fff;font-weight:700;margin-top:12px}.sidebar-card{padding:18px}.chip-list{display:flex;flex-wrap:wrap;gap:8px}.chip{display:inline-block;padding:6px 9px;border-radius:999px;border:1px solid var(--line);background:#f7f3ea;font-size:.78rem}.@media(max-width:980px){.hero-grid,.grid,.feature-grid,.post-list{grid-template-columns:1fr}. } </style>
</head>
<body>
  <div class="wrap">
    <section class="hero">
      <div class="hero-grid">
        <div>
          <span class="eyebrow">Latest from our blog</span>
          <h1>Exam strategy, current affairs and smarter study routines.</h1>
          <p class="lede">Read practical guidance for banking, SSC, railway, BPSC, and competitive exam preparation.</p>
          <p style="margin:22px 0 10px;color:var(--muted)">Want to write a blog? Click below to start writing. You will need to log in or create an account first.</p>
          <a class="btn" href="<?= e($writeBlogUrl) ?>">Write a blog</a>
        </div>
        <div class="feature-card">
          <h3>Featured</h3>
          <?php if ($featured !== []): ?>
            <?php foreach (array_slice($featured, 0, 2) as $feature): ?>
              <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--line);">
                <strong><?= e((string) ($feature['title'] ?? '')) ?></strong>
                <p><?= e((string) ($feature['excerpt'] ?? '')) ?></p>
                <a class="btn" href="blog.php?slug=<?= e(rawurlencode((string) ($feature['slug'] ?? ''))) ?>">Read article</a>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No published articles yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <div class="grid">
      <section>
        <h2>Latest posts</h2>
        <div class="post-list">
          <?php if ($posts === []): ?><div class="feature-card"><p>No published blogs yet.</p></div><?php else: ?>
            <?php foreach ($posts as $post): ?>
              <article class="post-card">
                <?php if (($post['featured_image'] ?? '') !== ''): ?><img src="<?= e((string) $post['featured_image']) ?>" alt="<?= e((string) ($post['title'] ?? '')) ?>"><?php endif; ?>
                <div class="meta">
                  <span><?= e((string) ($post['published_at'] ?? $post['created_at'] ?? '')) ?></span>
                  <span><?= e(blog_post_reading_time((string) ($post['content'] ?? ''))) ?></span>
                </div>
                <h3><?= e((string) ($post['title'] ?? '')) ?></h3>
                <p><?= e((string) ($post['excerpt'] ?? '')) ?></p>
                <?php if (($post['tags'] ?? []) !== []): ?><div class="tag-list"><?php foreach ((array) $post['tags'] as $tag): ?><span class="tag"><?= e((string) $tag) ?></span><?php endforeach; ?></div><?php endif; ?>
                <a class="btn" href="blog.php?slug=<?= e(rawurlencode((string) ($post['slug'] ?? ''))) ?>">Read article</a>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
      <aside>
        <div class="sidebar-card">
          <h3>Categories</h3>
          <div class="chip-list">
            <?php foreach ($categories as $category): ?>
              <span class="chip"><?= e((string) ($category['name'] ?? '')) ?> (<?= (int) ($category['count'] ?? 0) ?>)</span>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="sidebar-card" style="margin-top:18px;">
          <h3>Popular tags</h3>
          <div class="chip-list">
            <?php foreach ($tags as $tag): ?>
              <span class="chip"><?= e((string) ($tag['name'] ?? '')) ?> (<?= (int) ($tag['count'] ?? 0) ?>)</span>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>
    </div>
  </div>
</body>
</html>
