<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'blog-core.php';

$slugValue = $_GET['slug'] ?? '';
$invalidSlug = array_key_exists('slug', $_GET) && !is_string($slugValue);
$currentSlug = is_string($slugValue) ? trim($slugValue) : '';
$posts = blog_public_posts();
$writeBlogUrl = current_user() !== null ? 'blog-submit.php' : 'auth.php?next=blog-submit.php';

if ($currentSlug !== '' || $invalidSlug) {
    $post = $invalidSlug ? null : blog_get_post_by_slug($currentSlug);
    if ($post === null || (string) ($post['status'] ?? '') !== BLOG_STATUS_PUBLISHED) {
        http_response_code(404);
        header('X-Robots-Tag: noindex');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Story not found | RankSetu Journal</title><style>*{box-sizing:border-box}body{margin:0;background:#f8f7f3;color:#24231f;font:16px/1.6 Arial,sans-serif}.notice{max-width:700px;margin:12vh auto;padding:48px 28px;text-align:center;border-top:4px double #24231f;border-bottom:1px solid #dedbd3}.brand{color:#9c3b2e;font:700 1rem Georgia,serif;letter-spacing:.08em;text-transform:uppercase}.notice h1{margin:32px 0 10px;font:700 clamp(2.3rem,7vw,4rem)/1.05 Georgia,serif;letter-spacing:-.04em}.notice p{color:#716d64}.notice a{display:inline-block;margin-top:16px;color:#9c3b2e;font-weight:700;text-decoration:none}@media(max-width:740px){.notice{margin:9vh 20px;padding:36px 15px}}</style></head><body><main class="notice"><a class="brand" href="blog.php">RankSetu Journal</a><h1>We couldn’t find that story</h1><p>It may have moved, or the link may be out of date.</p><a href="blog.php">← Browse all stories</a></main></body></html>';
        exit;
    }
    $category = blog_category_by_id((string) ($post['category_id'] ?? ''));
    $related = blog_related_posts($post);
    $canonical = rtrim(APP_CANONICAL_URL, '/') . '/blog.php?slug=' . rawurlencode((string) ($post['slug'] ?? ''));
    $shareLinks = blog_social_share_links((string) ($post['title'] ?? ''), $canonical);
    $readingTime = blog_post_reading_time((string) ($post['content'] ?? ''));
    $articleTitle = trim((string) ($post['title'] ?? '')) ?: 'Untitled story';
    $publishedAtValue = blog_post_published_at($post);
    $modifiedAtValue = blog_post_public_modified_at($post);
    $publishedAtTimestamp = blog_post_timestamp($publishedAtValue);
    $modifiedAtTimestamp = blog_post_timestamp($modifiedAtValue);
    $articleContent = blog_sanitize_html((string) ($post['content'] ?? ''));
    if (trim($articleContent) !== '' && strip_tags($articleContent) === $articleContent) {
        $articleContent = '<p>' . nl2br(e($articleContent), false) . '</p>';
    }
    $articleContent = preg_replace('/<\s*(\/?)h1\b/i', '<$1h2', $articleContent) ?? $articleContent;
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
        :root{--ink:#24231f;--muted:#716d64;--paper:#f8f7f3;--white:#fff;--line:#dedbd3;--red:#9c3b2e;--navy:#16233f;--green:#315e4b;--serif:Georgia,"Times New Roman",serif}
        *{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:16px/1.6 Arial,sans-serif}a{color:inherit;text-decoration:none}.wrap{max-width:1140px;margin:0 auto;padding:0 30px 72px}
        .masthead{padding:20px 0 0;border-bottom:1px solid var(--ink)}.masthead-top{display:flex;justify-content:space-between;align-items:center;gap:16px;padding-bottom:12px;color:var(--muted);font-size:.76rem;letter-spacing:.08em;text-transform:uppercase}.brand{font:700 1.1rem var(--serif);letter-spacing:0;color:var(--red);text-transform:none}.nav{display:flex;gap:18px;align-items:center}.nav a:hover,.back-link:hover{color:var(--red)}.masthead-name{padding:15px 0 17px;border-top:1px solid var(--line);text-align:center;font:700 clamp(2.35rem,4.5vw,3.75rem)/1.05 var(--serif);letter-spacing:-.045em;overflow-wrap:anywhere}.masthead-name a{color:var(--ink)}.masthead-rule{height:4px;border-top:1px solid var(--ink);border-bottom:1px solid var(--ink)}
        .breadcrumbs{display:flex;flex-wrap:wrap;gap:8px;margin:20px auto 0;max-width:900px;color:var(--muted);font-size:.8rem}.breadcrumbs a{color:#315b8a;text-decoration:underline;text-underline-offset:2px}.breadcrumbs [aria-current=page]{color:var(--ink)}
        .article{margin:0 auto;padding:40px 0 0;max-width:900px}.article-head{text-align:center;max-width:790px;margin:0 auto}.eyebrow{display:inline-block;margin-bottom:13px;color:var(--red);font-size:.72rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase}.article h1{max-width:760px;margin:0 auto 18px;font:700 clamp(2rem,4.2vw,3.25rem)/1.12 var(--serif);letter-spacing:-.025em;text-wrap:balance}.dek{max-width:650px;margin:0 auto 20px;color:#646159;font:1.12rem/1.65 var(--serif)}.meta{display:flex;justify-content:center;flex-wrap:wrap;gap:8px 17px;color:var(--muted);font-size:.79rem}.meta strong{color:var(--ink)}.feature-image{display:block;width:100%;max-height:560px;object-fit:cover;margin:30px auto 0;background:#ece9e2}.article-body{max-width:700px;margin:36px auto 0;font:1.125rem/1.8 Georgia,"Times New Roman",serif;color:#302f2a;letter-spacing:.002em}.article-body p{margin:0 0 1.35em}.article-body>p:first-child:first-letter{float:left;padding:5px 8px 0 0;color:var(--red);font:700 3.35rem/.85 Georgia,"Times New Roman",serif}.article-body h2,.article-body h3,.article-body h4{margin:1.55em 0 .55em;color:var(--ink);font-family:var(--serif);line-height:1.28;letter-spacing:-.015em}.article-body h2{font-size:1.65rem}.article-body h3{font-size:1.35rem}.article-body li{padding-left:.25em;margin:.4em 0}.article-body a{color:#315b8a;text-decoration:underline;text-underline-offset:3px}.article-body img{display:block;max-width:100%;height:auto;margin:30px auto}.article-body blockquote{margin:1.8em 0;padding:4px 0 4px 22px;border-left:3px solid var(--red);color:#55534c;font:italic 1.25rem/1.65 var(--serif)}.article-body pre{padding:18px;background:#24231f;color:#f8f7f3;overflow:auto;font-size:.9rem}.article-body table{width:100%;border-collapse:collapse;font:1rem/1.5 Arial,sans-serif}.article-body th,.article-body td{padding:10px;border:1px solid var(--line);text-align:left}
        .share-row{max-width:700px;margin:34px auto 0;padding:18px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:12px}.share-label{font-size:.76rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase}.share{display:flex;flex-wrap:wrap;gap:8px}.share a,.share button{padding:7px 12px;border:1px solid var(--line);border-radius:3px;background:transparent;color:var(--ink);font: .82rem Arial,sans-serif;cursor:pointer}.share a:hover,.share button:hover{border-color:var(--ink)}.tags{max-width:700px;margin:20px auto;display:flex;flex-wrap:wrap;gap:8px}.tag{padding:5px 10px;border:1px solid var(--line);color:var(--muted);font-size:.75rem}.related-section{margin:58px auto 0;padding-top:22px;border-top:3px double var(--ink)}.section-heading{display:flex;justify-content:space-between;align-items:baseline;border-bottom:1px solid var(--line);padding-bottom:12px}.section-heading h2{margin:0;font:700 1.8rem var(--serif)}.related{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;margin-top:22px}.card{padding:0 20px 0 0;border-right:1px solid var(--line)}.card:last-child{border-right:0}.card h3{margin:0 0 9px;font:700 1.35rem/1.2 var(--serif)}.card p{margin:0;color:var(--muted);font-size:.9rem;line-height:1.6}.btn{display:inline-block;margin-top:14px;color:var(--red);font-weight:700;font-size:.82rem}.btn:hover{text-decoration:underline}.back-link{color:var(--ink);font-size:.84rem}
        .empty-edition{max-width:790px;margin:25px auto 32px;padding:27px 30px;border-top:3px double var(--ink);border-bottom:1px solid var(--line);text-align:center}.empty-edition .eyebrow{display:block}.empty-edition h1{margin:9px 0 8px;font:700 clamp(1.9rem,3.6vw,2.8rem)/1.1 var(--serif);letter-spacing:-.035em}.empty-edition p{max-width:54ch;margin:0 auto;color:var(--muted);font:1.02rem/1.6 var(--serif)}
        @media(max-width:760px){.wrap{padding:0 20px 52px}.masthead-top{align-items:flex-start}.masthead-top>span{display:none}.masthead-name{font-size:clamp(2.1rem,8.5vw,3rem)}.article{padding-top:30px}.article-head{max-width:620px}.eyebrow{margin-bottom:11px}.article h1{max-width:620px;font-size:clamp(1.85rem,6.5vw,2.5rem);line-height:1.16;letter-spacing:-.02em}.dek{max-width:560px;font-size:1.04rem;line-height:1.6}.feature-image{margin-top:24px}.article-body{margin-top:27px;font-size:1.0625rem;line-height:1.78}.article-body h2{font-size:1.5rem}.article-body h3{font-size:1.25rem}.article-body>p:first-child:first-letter{font-size:3rem}.related{grid-template-columns:1fr;gap:20px}.card{padding:0 0 18px;border-right:0;border-bottom:1px solid var(--line)}.card:last-child{border-bottom:0}.share-row{align-items:flex-start;flex-direction:column}.nav{gap:12px;font-size:.85rem}}
        @media(max-width:380px){.wrap{padding-right:16px;padding-left:16px}.article h1{font-size:1.8rem}.article-body{font-size:1.02rem}}
      </style>
    </head>
    <body>
      <div class="wrap">
        <div class="masthead">
          <div class="masthead-top">
            <span><?= e(date('l, F j, Y')) ?></span>
            <a class="brand" href="blog.php">Learning &amp; ideas</a>
            <nav class="nav" aria-label="Main navigation"><a href="blog.php">All stories</a><a href="<?= e($writeBlogUrl) ?>">Write</a></nav>
          </div>
          <div class="masthead-name"><a href="blog.php">The Learning Journal</a></div>
          <div class="masthead-rule"></div>
        </div>
        <nav class="breadcrumbs" aria-label="Breadcrumb">
          <a href="index.php">Home</a><span aria-hidden="true">/</span>
          <a href="blog.php">Blog</a><span aria-hidden="true">/</span>
          <span aria-current="page"><?= e($articleTitle) ?></span>
        </nav>
        <article class="article">
          <div class="article-head">
            <div class="eyebrow"><?= e((string) ($category['name'] ?? 'Journal')) ?></div>
            <h1><?= e($articleTitle) ?></h1>
            <?php if (trim((string) ($post['excerpt'] ?? '')) !== ''): ?><p class="dek"><?= e((string) $post['excerpt']) ?></p><?php endif; ?>
            <div class="meta">
              <span>By <strong><?= e(blog_post_author_name($post)) ?></strong></span>
              <?php if ($publishedAtTimestamp !== false): ?><time datetime="<?= e(date(DATE_ATOM, $publishedAtTimestamp)) ?>">Published <?= e(date('F j, Y', $publishedAtTimestamp)) ?></time><?php endif; ?>
              <?php if ($publishedAtTimestamp !== false && $modifiedAtTimestamp !== false && $modifiedAtTimestamp > $publishedAtTimestamp): ?><time datetime="<?= e(date(DATE_ATOM, $modifiedAtTimestamp)) ?>">Updated <?= e(date('F j, Y', $modifiedAtTimestamp)) ?></time><?php endif; ?>
              <span><?= e($readingTime) ?></span>
            </div>
          </div>
          <?php if (($post['featured_image'] ?? '') !== ''): ?><img class="feature-image" src="<?= e((string) $post['featured_image']) ?>" alt="<?= e($articleTitle) ?>"> <?php endif; ?>
          <div class="article-body">
            <?= $articleContent ?>
          </div>
          <div class="share-row">
            <span class="share-label">Enjoyed this story?</span>
            <div class="share">
              <?php foreach ($shareLinks as $label => $href): ?>
                <a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a>
              <?php endforeach; ?>
              <button type="button" id="copy-link">Copy link</button>
            </div>
          </div>
          <?php if (($post['tags'] ?? []) !== []): ?>
            <div class="tags">
              <?php foreach ((array) $post['tags'] as $tag): ?><span class="tag"><?= e((string) $tag) ?></span><?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>

        <?php if ($related !== []): ?>
          <section class="related-section">
            <div class="section-heading"><h2>More to explore</h2><a class="back-link" href="blog.php">All stories →</a></div>
            <div class="related">
              <?php foreach ($related as $relatedPost): ?>
                <article class="card">
                  <h3><?= e(trim((string) ($relatedPost['title'] ?? '')) ?: 'Untitled story') ?></h3>
                  <p><?= e((string) ($relatedPost['excerpt'] ?? '')) ?></p>
                  <p><a class="btn" href="blog.php?slug=<?= e(rawurlencode((string) ($relatedPost['slug'] ?? ''))) ?>">Continue reading →</a></p>
                </article>
              <?php endforeach; ?>
            </div>
            <script>
              document.getElementById('copy-link').addEventListener('click', async function () {
                try {
                  await navigator.clipboard.writeText(window.location.href);
                  this.textContent = 'Link copied';
                } catch (error) {
                  window.prompt('Copy this article link:', window.location.href);
                }
              });
            </script>
          </section>
        <?php endif; ?>
      </div>
    </body>
    </html>
    <?php exit; 
}

$blogPagination = blog_pagination($posts, $_GET['page'] ?? null);
$posts = $blogPagination['posts'];
$publishedStoryCount = $blogPagination['total_items'];
$blogPage = $blogPagination['page'];
$blogPageCount = $blogPagination['page_count'];
$featured = $blogPage === 1 ? array_slice($posts, 0, 3) : [];
$latestPosts = $blogPage === 1 ? array_slice($posts, count($featured)) : $posts;
$categories = array_values(array_filter(blog_categories_with_counts(), static fn(array $category): bool => (int) ($category['count'] ?? 0) > 0));
$tags = array_values(array_filter(blog_tag_suggestions(), static fn(array $tag): bool => (int) ($tag['count'] ?? 0) > 0));
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Latest Articles | RankSetu Blog</title>
  <meta name="description" content="Read study strategy, exam guidance and current-affairs insights from RankSetu.">
  <style>
    :root{--ink:#24231f;--muted:#716d64;--paper:#f8f7f3;--white:#fff;--line:#dedbd3;--red:#9c3b2e;--serif:Georgia,"Times New Roman",serif}
    *{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:16px/1.6 Arial,sans-serif}a{color:inherit;text-decoration:none}.wrap{max-width:1240px;margin:0 auto;padding:0 34px 72px}
    .masthead{padding:20px 0 0;border-bottom:1px solid var(--ink)}.masthead-top{display:flex;justify-content:space-between;align-items:center;gap:16px;padding-bottom:12px;color:var(--muted);font-size:.76rem;letter-spacing:.08em;text-transform:uppercase}.brand{font:700 1.1rem var(--serif);letter-spacing:0;color:var(--red);text-transform:none}.nav{display:flex;gap:18px;align-items:center}.nav a:hover{color:var(--red)}.nav .write-link{padding:8px 13px;background:var(--ink);color:white;letter-spacing:.04em}.nav .write-link:hover{background:var(--red);color:white}.masthead-name{padding:12px 0 16px;border-top:1px solid var(--line);text-align:center;font:700 clamp(2.7rem,7vw,5.6rem)/.98 var(--serif);letter-spacing:-.055em}.masthead-rule{height:4px;border-top:1px solid var(--ink);border-bottom:1px solid var(--ink)}.archive-title{margin:32px 0 0;font:700 clamp(1.8rem,3vw,2.5rem)/1.15 var(--serif)}
    .edition-line{display:flex;justify-content:space-between;gap:14px;padding:10px 0;border-bottom:1px solid var(--line);color:var(--muted);font-size:.72rem;letter-spacing:.11em;text-transform:uppercase}.edition-line strong{color:var(--red)}.lead-story{display:grid;grid-template-columns:1.12fr .88fr;min-height:390px;border-bottom:3px double var(--ink)}.lead-copy{display:flex;flex-direction:column;justify-content:center;padding:42px 48px 42px 0}.eyebrow{color:var(--red);font-size:.72rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase}.lead-copy h1{margin:13px 0 14px;font:700 clamp(2.5rem,4.5vw,4.6rem)/1.02 var(--serif);letter-spacing:-.045em}.lead-copy p{max-width:58ch;margin:0;color:#5f5c54;font:1.12rem/1.6 var(--serif)}.lead-meta{display:flex;flex-wrap:wrap;gap:8px 14px;margin-top:22px;color:var(--muted);font-size:.76rem}.lead-image{position:relative;min-height:310px;background:#e9e6df}.lead-image img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.lead-placeholder{height:100%;min-height:310px;display:grid;place-items:center;color:#9c978c;font:italic 1.4rem var(--serif)}.lead-more{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px;padding:18px 0 25px;border-bottom:1px solid var(--ink)}.brief{padding:8px 20px 8px 0;border-right:1px solid var(--line)}.brief:last-child{border-right:0}.brief h2{margin:8px 0;font:700 1.55rem/1.18 var(--serif)}.brief p{margin:0;color:var(--muted);font-size:.88rem}.brief .brief-label{color:var(--red);font-size:.7rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
    .section-heading{display:flex;justify-content:space-between;align-items:baseline;gap:14px;margin:35px 0 16px;padding-bottom:10px;border-bottom:3px double var(--ink)}.section-heading h2{margin:0;font:700 1.85rem var(--serif)}.section-heading span{color:var(--muted);font-size:.76rem}.content-grid{display:grid;grid-template-columns:minmax(0,1fr) 270px;gap:38px}.post-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 25px}.post-card{padding:20px 0 22px;border-bottom:1px solid var(--line)}.post-card:first-child,.post-card:nth-child(2){padding-top:4px}.post-card img{display:block;width:100%;height:190px;object-fit:cover;margin-bottom:14px}.post-card .meta{display:flex;flex-wrap:wrap;gap:10px;color:var(--muted);font-size:.72rem}.category-label{color:var(--red);font-weight:700;letter-spacing:.08em;text-transform:uppercase}.post-card h3{margin:9px 0;font:700 1.55rem/1.16 var(--serif);letter-spacing:-.015em}.post-card p{margin:0;color:#656158;font:1rem/1.5 var(--serif)}.read-link{display:inline-block;margin-top:13px;color:var(--red);font-size:.78rem;font-weight:700;letter-spacing:.04em}.read-link:hover{text-decoration:underline}.tag-list{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}.tag{padding:4px 8px;border:1px solid var(--line);color:var(--muted);font-size:.69rem}.sidebar{padding-top:4px}.sidebar-card{padding:16px 0 20px;border-bottom:1px solid var(--line)}.sidebar-card h2{margin:0 0 12px;font:700 1.35rem var(--serif)}.sidebar-card p{margin:0;color:var(--muted);font-size:.87rem}.chip-list{display:flex;flex-wrap:wrap;gap:7px}.chip{display:inline-block;padding:5px 8px;border:1px solid var(--line);color:#514e47;font-size:.72rem}.write-box{margin-top:19px;padding:18px;background:#eeece5}.write-box h2{margin:0 0 8px;font:700 1.28rem var(--serif)}.write-box p{margin:0;color:var(--muted);font-size:.84rem}.empty-state{grid-column:1/-1;padding:24px 0;color:var(--muted);font:1.1rem var(--serif)}
    @media(max-width:850px){.wrap{padding:0 22px 55px}.lead-story{grid-template-columns:1fr}.lead-copy{padding:32px 0 26px}.lead-image{min-height:260px}.lead-placeholder{min-height:260px}.content-grid{grid-template-columns:1fr}.sidebar{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px 24px}.sidebar-card{border-bottom:1px solid var(--line)}.write-box{margin-top:0}}@media(max-width:560px){.wrap{padding:0 16px 42px}.masthead-top{align-items:flex-start}.masthead-top>span{display:none}.masthead-name{font-size:clamp(2rem,8.5vw,2.8rem)}.nav{gap:11px;font-size:.82rem}.nav .write-link{padding:7px 9px}.edition-line{align-items:flex-start;flex-direction:column;gap:3px;font-size:.66rem;letter-spacing:.07em}.lead-copy{padding:28px 0 22px}.lead-copy h1{font-size:2.65rem}.lead-copy p{font-size:1rem}.lead-more{grid-template-columns:1fr;gap:12px}.brief{padding:10px 0 16px;border-right:0;border-bottom:1px solid var(--line)}.brief:last-child{border-bottom:0}.content-grid{gap:18px}.post-list{grid-template-columns:1fr}.post-card:first-child,.post-card:nth-child(2){padding-top:20px}.post-card h3{font-size:1.5rem}.sidebar{grid-template-columns:1fr;gap:0}.section-heading h2{font-size:1.55rem}.empty-edition{margin:16px auto 22px;padding:22px 8px}.empty-edition h1{font-size:1.9rem}}
    .blog-pagination{display:flex;justify-content:center;align-items:center;flex-wrap:wrap;gap:8px;margin:28px 0 12px}.blog-pagination a,.blog-pagination span{min-width:40px;padding:9px 12px;border:1px solid var(--line);text-align:center;color:var(--ink);font-size:.85rem}.blog-pagination a:hover,.blog-pagination [aria-current=page]{border-color:var(--red);background:#fff;color:var(--red)}.blog-pagination .pagination-label{min-width:0;border:0;background:transparent;color:var(--muted)}
    <style>
      .masthead-name{font-size:clamp(2.35rem,4.5vw,3.75rem);line-height:1.05;letter-spacing:-.045em;overflow-wrap:anywhere}
      .empty-edition{max-width:790px;margin:24px auto 28px;padding:26px 30px;border-top:3px double var(--ink);border-bottom:1px solid var(--line);text-align:center}
      .empty-edition .eyebrow{display:block}
      .empty-edition h1{margin:9px 0 8px;font:700 clamp(1.9rem,3.6vw,2.8rem)/1.1 var(--serif);letter-spacing:-.035em}
      .empty-edition p{max-width:54ch;margin:0 auto;color:var(--muted);font:1.02rem/1.6 var(--serif)}
      .masthead-top>.brand{margin:0 auto}
      @media(max-width:560px){
        .masthead-top{display:flex;justify-content:center;align-items:center;min-height:42px;padding:0 0 10px}
        .masthead-top>span{display:none}
        .masthead-top>.brand{font-size:.92rem;white-space:nowrap}
        .masthead-name{padding:12px 0 14px;font-size:clamp(1.7rem,7vw,2.45rem);line-height:1.05;letter-spacing:-.05em;white-space:nowrap}
        .empty-edition{margin:16px auto 22px;padding:22px 8px}
        .empty-edition h1{font-size:1.9rem}
      }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="masthead">
      <div class="masthead-top">
        <span><?= e(date('l, F j, Y')) ?></span>
        <a class="brand" href="blog.php">Learning &amp; ideas</a>
      </div>
      <div class="masthead-name">The Learning Journal</div>
      <div class="masthead-rule"></div>
    </div>
    <div class="edition-line"><span><strong>Learning, considered.</strong> Ideas to move your preparation forward.</span><span><?= $publishedStoryCount ?> published <?= $publishedStoryCount === 1 ? 'story' : 'stories' ?><?= $blogPageCount > 1 ? ' · Page ' . $blogPage . ' of ' . $blogPageCount : '' ?></span></div>
    <?php if ($blogPage > 1): ?><h1 class="archive-title">Exam preparation articles — page <?= $blogPage ?></h1><?php endif; ?>
    <?php if ($posts !== [] && $blogPage === 1): ?>
      <?php $leadPost = $featured[0]; ?>
      <?php $leadTitle = trim((string) ($leadPost['title'] ?? '')) ?: 'Untitled story'; ?>
      <?php $leadPublishedAt = blog_post_published_at($leadPost); ?>
      <?php $leadPublishedTimestamp = blog_post_timestamp($leadPublishedAt); ?>
      <section class="lead-story" aria-labelledby="lead-title">
        <div class="lead-copy">
          <span class="eyebrow">The lead story · <?= e((string) (blog_category_by_id((string) ($leadPost['category_id'] ?? ''))['name'] ?? 'From the journal')) ?></span>
          <h1 id="lead-title"><a href="blog.php?slug=<?= e(rawurlencode((string) ($leadPost['slug'] ?? ''))) ?>"><?= e($leadTitle) ?></a></h1>
          <p><?= e((string) (($leadPost['excerpt'] ?? '') ?: blog_excerpt_from_content((string) ($leadPost['content'] ?? '')))) ?></p>
          <div class="lead-meta"><span>By <?= e(blog_post_author_name($leadPost)) ?></span><?php if ($leadPublishedTimestamp !== false): ?><time datetime="<?= e(date(DATE_ATOM, $leadPublishedTimestamp)) ?>"><?= e(date('F j, Y', $leadPublishedTimestamp)) ?></time><?php endif; ?><span><?= e(blog_post_reading_time((string) ($leadPost['content'] ?? ''))) ?></span></div>
          <a class="read-link" href="blog.php?slug=<?= e(rawurlencode((string) ($leadPost['slug'] ?? ''))) ?>">Read the lead story →</a>
        </div>
        <a class="lead-image" href="blog.php?slug=<?= e(rawurlencode((string) ($leadPost['slug'] ?? ''))) ?>" aria-label="Read <?= e($leadTitle) ?>">
          <?php if (($leadPost['featured_image'] ?? '') !== ''): ?><img src="<?= e((string) $leadPost['featured_image']) ?>" alt=""><?php else: ?><span class="lead-placeholder">A thoughtful read for your next step</span><?php endif; ?>
        </a>
      </section>
      <?php if (count($featured) > 1): ?>
        <section class="lead-more" aria-label="More featured stories">
          <?php foreach (array_slice($featured, 1, 2) as $feature): ?>
            <?php $featureCategory = blog_category_by_id((string) ($feature['category_id'] ?? '')); ?>
            <article class="brief">
              <span class="brief-label"><?= e((string) ($featureCategory['name'] ?? 'Also in this issue')) ?></span>
              <h2><a href="blog.php?slug=<?= e(rawurlencode((string) ($feature['slug'] ?? ''))) ?>"><?= e(trim((string) ($feature['title'] ?? '')) ?: 'Untitled story') ?></a></h2>
              <p><?= e((string) (($feature['excerpt'] ?? '') ?: blog_excerpt_from_content((string) ($feature['content'] ?? ''), 135))) ?></p>
              <a class="read-link" href="blog.php?slug=<?= e(rawurlencode((string) ($feature['slug'] ?? ''))) ?>">Read story →</a>
            </article>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($posts === []): ?>
      <section class="empty-edition">
        <span class="eyebrow">A journal for curious minds</span>
        <h1>Good ideas for your next step.</h1>
        <p>We’re preparing the first stories for the journal. Please check back soon.</p>
      </section>
    <?php endif; ?>
    <?php if ($latestPosts !== [] || $posts === []): ?><div class="section-heading"><h2><?= $latestPosts !== [] ? 'More stories' : 'Explore the journal' ?></h2><span>Fresh perspectives for your preparation</span></div><?php endif; ?>
    <div class="content-grid">
      <section>
        <div class="post-list">
          <?php if ($posts === []): ?><div class="empty-state">New perspectives and practical advice are coming soon.</div><?php else: ?>
            <?php foreach ($latestPosts as $post): ?>
              <?php $postCategory = blog_category_by_id((string) ($post['category_id'] ?? '')); ?>
              <?php $postPublishedTimestamp = blog_post_timestamp(blog_post_published_at($post)); ?>
              <article class="post-card">
                <?php if (($post['featured_image'] ?? '') !== ''): ?><a href="blog.php?slug=<?= e(rawurlencode((string) ($post['slug'] ?? ''))) ?>"><img src="<?= e((string) $post['featured_image']) ?>" alt="<?= e((string) ($post['title'] ?? '')) ?>"></a><?php endif; ?>
                <div class="meta">
                  <?php if ($postCategory !== null): ?><span class="category-label"><?= e((string) ($postCategory['name'] ?? 'Journal')) ?></span><?php endif; ?>
                  <?php if ($postPublishedTimestamp !== false): ?><time datetime="<?= e(date(DATE_ATOM, $postPublishedTimestamp)) ?>"><?= e(date('F j, Y', $postPublishedTimestamp)) ?></time><?php endif; ?>
                  <span><?= e(blog_post_reading_time((string) ($post['content'] ?? ''))) ?></span>
                </div>
                <h3><a href="blog.php?slug=<?= e(rawurlencode((string) ($post['slug'] ?? ''))) ?>"><?= e(trim((string) ($post['title'] ?? '')) ?: 'Untitled story') ?></a></h3>
                <?php if (trim((string) ($post['excerpt'] ?? '')) !== ''): ?><p><?= e((string) $post['excerpt']) ?></p><?php endif; ?>
                <?php if (($post['tags'] ?? []) !== []): ?><div class="tag-list"><?php foreach ((array) $post['tags'] as $tag): ?><span class="tag"><?= e((string) $tag) ?></span><?php endforeach; ?></div><?php endif; ?>
                <a class="read-link" href="blog.php?slug=<?= e(rawurlencode((string) ($post['slug'] ?? ''))) ?>">Continue reading →</a>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
      <aside class="sidebar" aria-label="Browse the journal">
        <?php if ($categories !== []): ?>
          <section class="sidebar-card">
            <h2>Categories</h2>
            <div class="chip-list">
              <?php foreach ($categories as $category): ?>
                <span class="chip"><?= e((string) ($category['name'] ?? 'Category')) ?> (<?= (int) ($category['count'] ?? 0) ?>)</span>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>
        <?php if ($tags !== []): ?>
          <section class="sidebar-card">
            <h2>Popular tags</h2>
            <div class="chip-list">
              <?php foreach ($tags as $tag): ?>
                <span class="chip"><?= e((string) ($tag['name'] ?? 'Tag')) ?> (<?= (int) ($tag['count'] ?? 0) ?>)</span>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>
        <?php if ($categories === [] && $tags === []): ?>
          <section class="sidebar-card"><h2>Browse the journal</h2><p>New categories and topics will appear here as stories are published.</p></section>
        <?php endif; ?>
        <div class="write-box">
          <h2>Have something to share?</h2>
          <p>Bring your experience and ideas to the RankSetu community.</p>
          <a class="read-link" href="<?= e($writeBlogUrl) ?>">Pitch your story →</a>
        </div>
      </aside>
    </div>
    <?php if ($blogPageCount > 1): ?>
      <nav class="blog-pagination" aria-label="Blog pages">
        <span class="pagination-label">Page <?= $blogPage ?> of <?= $blogPageCount ?></span>
        <?php if ($blogPage > 1): ?><a href="blog.php<?= $blogPage === 2 ? '' : '?page=' . ($blogPage - 1) ?>" rel="prev" aria-label="Previous page">Previous</a><?php endif; ?>
        <?php $pageStart = max(1, $blogPage - 2); $pageEnd = min($blogPageCount, $blogPage + 2); ?>
        <?php for ($pageNumber = $pageStart; $pageNumber <= $pageEnd; $pageNumber++): ?>
          <?php if ($pageNumber === $blogPage): ?><span aria-current="page"><?= $pageNumber ?></span><?php else: ?><a href="blog.php<?= $pageNumber === 1 ? '' : '?page=' . $pageNumber ?>"><?= $pageNumber ?></a><?php endif; ?>
        <?php endfor; ?>
        <?php if ($blogPage < $blogPageCount): ?><a href="blog.php?page=<?= $blogPage + 1 ?>" rel="next" aria-label="Next page">Next</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  </div>
</body>
</html>
