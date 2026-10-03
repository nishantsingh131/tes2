<?php

declare(strict_types=1);

const BLOG_STATUS_DRAFT = 'DRAFT';
const BLOG_STATUS_PENDING_REVIEW = 'PENDING_REVIEW';
const BLOG_STATUS_CHANGES_REQUESTED = 'CHANGES_REQUESTED';
const BLOG_STATUS_APPROVED = 'APPROVED';
const BLOG_STATUS_SCHEDULED = 'SCHEDULED';
const BLOG_STATUS_PUBLISHED = 'PUBLISHED';
const BLOG_STATUS_UNPUBLISHED = 'UNPUBLISHED';
const BLOG_STATUS_REJECTED = 'REJECTED';
const BLOG_STATUS_ARCHIVED = 'ARCHIVED';

function blog_storage_dir(): string
{
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create blog data directory.');
    }
    return $dir;
}

function blog_posts_file(): string
{
    return blog_storage_dir() . DIRECTORY_SEPARATOR . 'blog-posts.json';
}

function blog_categories_file(): string
{
    return blog_storage_dir() . DIRECTORY_SEPARATOR . 'blog-categories.json';
}

function blog_tags_file(): string
{
    return blog_storage_dir() . DIRECTORY_SEPARATOR . 'blog-tags.json';
}

function blog_status_options(): array
{
    return [
        BLOG_STATUS_DRAFT,
        BLOG_STATUS_PENDING_REVIEW,
        BLOG_STATUS_CHANGES_REQUESTED,
        BLOG_STATUS_APPROVED,
        BLOG_STATUS_SCHEDULED,
        BLOG_STATUS_PUBLISHED,
        BLOG_STATUS_UNPUBLISHED,
        BLOG_STATUS_REJECTED,
        BLOG_STATUS_ARCHIVED,
    ];
}

function blog_transition_matrix(): array
{
    return [
        BLOG_STATUS_DRAFT => [BLOG_STATUS_PENDING_REVIEW],
        BLOG_STATUS_PENDING_REVIEW => [BLOG_STATUS_APPROVED, BLOG_STATUS_CHANGES_REQUESTED, BLOG_STATUS_REJECTED],
        BLOG_STATUS_CHANGES_REQUESTED => [BLOG_STATUS_PENDING_REVIEW],
        BLOG_STATUS_APPROVED => [BLOG_STATUS_PUBLISHED, BLOG_STATUS_SCHEDULED],
        BLOG_STATUS_SCHEDULED => [BLOG_STATUS_PUBLISHED, BLOG_STATUS_APPROVED],
        BLOG_STATUS_PUBLISHED => [BLOG_STATUS_UNPUBLISHED, BLOG_STATUS_ARCHIVED],
        BLOG_STATUS_UNPUBLISHED => [BLOG_STATUS_PUBLISHED, BLOG_STATUS_APPROVED],
        BLOG_STATUS_REJECTED => [BLOG_STATUS_DRAFT],
        BLOG_STATUS_ARCHIVED => [BLOG_STATUS_PUBLISHED],
    ];
}

function blog_is_valid_transition(string $from, string $to): bool
{
    if ($from === $to) {
        return true;
    }
    $transitions = blog_transition_matrix();
    return in_array($to, $transitions[$from] ?? [], true);
}

function blog_post_pending_revision(array $post): ?array
{
    $revision = $post['pending_revision'] ?? null;
    if (is_string($revision)) {
        try {
            $revision = json_decode($revision, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            error_log('Invalid pending blog revision for post ' . (string) ($post['id'] ?? '') . ': ' . $exception->getMessage());
            return null;
        }
    }
    return is_array($revision) && $revision !== [] ? $revision : null;
}

function blog_post_has_pending_review(array $post): bool
{
    if (($post['status'] ?? '') === BLOG_STATUS_PENDING_REVIEW) return true;
    $revision = blog_post_pending_revision($post);
    return $revision !== null && ($revision['review_state'] ?? BLOG_STATUS_PENDING_REVIEW) === BLOG_STATUS_PENDING_REVIEW;
}

function blog_read_json(string $filePath, array $fallback = []): array
{
    if (!is_file($filePath)) {
        return $fallback;
    }
    $contents = @file_get_contents($filePath);
    if ($contents === false || trim($contents) === '') {
        return $fallback;
    }
    try {
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        error_log('Invalid JSON in blog storage: ' . $filePath . ' - ' . $exception->getMessage());
        return $fallback;
    }
    return is_array($decoded) ? $decoded : $fallback;
}

function blog_write_json(string $filePath, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $tmp = $filePath . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write blog content.');
    }
    if (!@rename($tmp, $filePath)) {
        @unlink($tmp);
        throw new RuntimeException('Unable to finalize blog content.');
    }
}

function blog_save_featured_image_upload(array $file): ?string
{
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError === UPLOAD_ERR_NO_FILE) return null;
    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) throw new RuntimeException('The image exceeds the upload size limit configured by the server.');
    if ($uploadError !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) throw new RuntimeException('The image upload failed. Please try again.');

    $temporaryPath = (string) $file['tmp_name'];
    $actualSize = filesize($temporaryPath);
    if ($actualSize === false || $actualSize === 0 || $actualSize > 3 * 1024 * 1024) throw new RuntimeException('Choose a valid image no larger than 3 MB.');
    $imageInfo = @getimagesize($temporaryPath);
    if ($imageInfo === false) throw new RuntimeException('The uploaded file is not a valid image.');
    $mime = (string) ($imageInfo['mime'] ?? '');
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Use a JPG, PNG or WebP image.');
    if (class_exists('finfo') && (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath) !== $mime) throw new RuntimeException('The uploaded file is not a valid image.');
    $width = (int) ($imageInfo[0] ?? 0);
    $height = (int) ($imageInfo[1] ?? 0);
    if ($width < 1 || $height < 1 || $width > 8000 || $height > 8000 || $width * $height > 25000000) throw new RuntimeException('Use an image no larger than 8,000 pixels per side or 25 megapixels.');

    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'blog';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) throw new RuntimeException('Unable to create the blog image folder.');
    $filename = 'blog-' . bin2hex(random_bytes(10)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($temporaryPath, $directory . DIRECTORY_SEPARATOR . $filename)) throw new RuntimeException('Unable to save the blog image.');
    return 'uploads/blog/' . $filename;
}

function blog_delete_featured_image(?string $imagePath): void
{
    if ($imagePath === null || !str_starts_with($imagePath, 'uploads/blog/')) return;
    $root = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'blog');
    $target = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imagePath));
    if ($root !== false && $target !== false && str_starts_with($target, $root . DIRECTORY_SEPARATOR)) @unlink($target);
}

function blog_delete_unused_featured_image(?string $imagePath, array $posts): void
{
    if ($imagePath === null || $imagePath === '') return;
    foreach ($posts as $post) {
        if ((string) ($post['featured_image'] ?? '') === $imagePath) return;
        $revision = blog_post_pending_revision($post);
        if ($revision !== null && (string) ($revision['featured_image'] ?? '') === $imagePath) return;
    }
    blog_delete_featured_image($imagePath);
}

function blog_default_categories(): array
{
    return [
        ['id' => 'banking', 'name' => 'Banking', 'slug' => 'banking', 'description' => 'Banking exam strategy, current affairs and practice advice.'],
        ['id' => 'bihar-police', 'name' => 'Bihar Police', 'slug' => 'bihar-police', 'description' => 'Bihar Police recruitment preparation and exam guidance.'],
        ['id' => 'upsc', 'name' => 'UPSC', 'slug' => 'upsc', 'description' => 'UPSC Civil Services preparation, study strategy and exam insights.'],
        ['id' => 'state-pcs', 'name' => 'State PCS', 'slug' => 'state-pcs', 'description' => 'State public service commission exam preparation and guidance.'],
        ['id' => 'ssc', 'name' => 'SSC', 'slug' => 'ssc', 'description' => 'SSC prep guidance and exam insights.'],
        ['id' => 'railway', 'name' => 'Railway', 'slug' => 'railway', 'description' => 'Railway exam updates, methods and planning.'],
        ['id' => 'bpsc', 'name' => 'BPSC', 'slug' => 'bpsc', 'description' => 'Bihar state exam planning and strategy.'],
        ['id' => 'study-tips', 'name' => 'Study Tips', 'slug' => 'study-tips', 'description' => 'Study routines and productivity advice.'],
    ];
}

function blog_default_tags(): array
{
    return [
        ['id' => 'bpsc', 'name' => 'BPSC', 'slug' => 'bpsc'],
        ['id' => 'bihar-police', 'name' => 'Bihar Police', 'slug' => 'bihar-police'],
        ['id' => 'upsc', 'name' => 'UPSC Civil Services', 'slug' => 'upsc'],
        ['id' => 'state-pcs', 'name' => 'State PCS', 'slug' => 'state-pcs'],
        ['id' => 'ssc', 'name' => 'SSC', 'slug' => 'ssc'],
        ['id' => 'banking', 'name' => 'Banking', 'slug' => 'banking'],
        ['id' => 'current-affairs', 'name' => 'Current Affairs', 'slug' => 'current-affairs'],
        ['id' => 'strategy', 'name' => 'Strategy', 'slug' => 'strategy'],
    ];
}

function blog_slugify(string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return 'untitled-post-' . substr(bin2hex(random_bytes(3)), 0, 8);
    }
    $normalized = strtolower($value);
    $normalized = preg_replace('/[\x00-\x1F\x7F]/u', '', $normalized);
    $normalized = preg_replace('/[^a-z0-9]+/i', '-', $normalized);
    $normalized = trim((string) preg_replace('/-+/', '-', $normalized), '-');
    if ($normalized === '') {
        return 'untitled-post-' . substr(bin2hex(random_bytes(3)), 0, 8);
    }
    return $normalized;
}

function blog_excerpt_from_content(string $content, int $length = 180): string
{
    $plain = preg_replace('/\s+/', ' ', trim(strip_tags((string) $content)));
    $plain = trim((string) $plain);
    if ($plain === '') {
        return 'Read this article for practical guidance and exam-focused preparation ideas.';
    }
    if (blog_text_length($plain) <= $length) {
        return $plain;
    }
    return rtrim(blog_text_substr($plain, 0, $length)) . '…';
}

function blog_text_length(string $value): int
{
    if (function_exists('mb_strlen')) return mb_strlen($value, 'UTF-8');
    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? strlen($value) : $count;
}

function blog_text_substr(string $value, int $start, int $length): string
{
    if (function_exists('mb_substr')) return mb_substr($value, $start, $length, 'UTF-8');
    $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
    return $characters === false ? substr($value, $start, $length) : implode('', array_slice($characters, $start, $length));
}

function blog_sanitize_html(string $content): string
{
    $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'a', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'img', 'figure', 'figcaption', 'code', 'pre', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr', 'span', 'div', 'section', 'article', 'small'
    ];
    $allowedAttrs = ['href', 'title', 'alt', 'src', 'target', 'rel', 'class'];

    $content = preg_replace('/<script\b.*?<\/script>/is', '', $content);
    $content = preg_replace('/<iframe\b.*?<\/iframe>/is', '', $content);
    $content = preg_replace('/<object\b.*?<\/object>/is', '', $content);
    $content = preg_replace('/<embed\b.*?>/is', '', $content);
    $content = preg_replace('/on[a-z]+\s*=\s*["\'][^"\']*["\']/is', '', $content);
    $content = preg_replace('/on[a-z]+\s*=\s*[^\s>]+/is', '', $content);

    $content = preg_replace_callback('/<\s*\/\s*([^\s>]+)>/i', static function ($matches) use ($allowedTags): string {
        $tag = strtolower($matches[1]);
        return in_array($tag, $allowedTags, true) ? '</' . $tag . '>' : '';
    }, $content);

    $content = preg_replace_callback('/<\s*(?!\/)([^\s>]+)([^>]*)>/i', static function ($matches) use ($allowedTags, $allowedAttrs): string {
        $tag = strtolower($matches[1]);
        if (!in_array($tag, $allowedTags, true)) {
            return '';
        }
        $attrs = $matches[2];
        $safeAttrs = '';
        if (preg_match_all('/([a-zA-Z:-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrs, $attrMatches, PREG_SET_ORDER) === 0) {
            return '<' . $tag . '>';
        }
        foreach ($attrMatches as $attrMatch) {
            $name = strtolower($attrMatch[1]);
            if (!in_array($name, $allowedAttrs, true)) {
                continue;
            }
            $value = $attrMatch[2] !== '' ? $attrMatch[2] : ($attrMatch[3] !== '' ? $attrMatch[3] : ($attrMatch[4] ?? ''));
            if ($name === 'href' || $name === 'src') {
                $value = trim((string) $value);
                if ($value === '' || preg_match('/^(javascript:|data:|vbscript:)/i', $value) === 1) {
                    continue;
                }
                if (str_starts_with($value, 'javascript:')) {
                    continue;
                }
            }
            if ($name === 'target' && !in_array(strtolower($value), ['_blank', '_self'], true)) {
                continue;
            }
            $safeAttrs .= ' ' . e($name) . '="' . e($value) . '"';
        }
        return '<' . $tag . $safeAttrs . '>';
    }, $content);

    return $content;
}

function blog_all_posts(): array
{
    if (db_enabled()) {
        $pdo = db_pdo();
        if ($pdo !== null) {
            try {
                $stmt = $pdo->query('SELECT * FROM blog_posts ORDER BY created_at DESC');
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (is_array($rows)) {
                    return array_map(static fn(array $row): array => [
                        'id' => (string) ($row['id'] ?? ''),
                        'title' => (string) ($row['title'] ?? ''),
                        'slug' => (string) ($row['slug'] ?? ''),
                        'excerpt' => (string) ($row['excerpt'] ?? ''),
                        'content' => (string) ($row['content'] ?? ''),
                        'status' => (string) ($row['status'] ?? BLOG_STATUS_DRAFT),
                        'author_id' => (string) ($row['author_id'] ?? ''),
                        'author_name' => (string) ($row['author_name'] ?? ''),
                        'category_id' => (string) ($row['category_id'] ?? ''),
                        'featured_image' => (string) ($row['featured_image'] ?? ''),
                        'seo_title' => (string) ($row['seo_title'] ?? ''),
                        'seo_description' => (string) ($row['seo_description'] ?? ''),
                        'canonical_url' => (string) ($row['canonical_url'] ?? ''),
                        'og_title' => (string) ($row['og_title'] ?? ''),
                        'og_description' => (string) ($row['og_description'] ?? ''),
                        'published_at' => (string) ($row['published_at'] ?? ''),
                        'scheduled_at' => (string) ($row['scheduled_at'] ?? ''),
                        'created_at' => (string) ($row['created_at'] ?? ''),
                        'updated_at' => (string) ($row['updated_at'] ?? ''),
                        'tags' => [],
                        'review_comment' => (string) ($row['review_comment'] ?? ''),
                        'pending_revision' => blog_post_pending_revision(['pending_revision' => $row['pending_revision'] ?? null]),
                    ], $rows);
                }
            } catch (Throwable $exception) {
                error_log('Blog DB listing failed: ' . $exception->getMessage());
            }
        }
    }

    blog_seed_default_posts_if_needed();
    $posts = blog_read_json(blog_posts_file(), []);
    if (!is_array($posts)) {
        return [];
    }
    return array_values(array_filter(array_map(static fn($post): ?array => is_array($post) ? $post : null, $posts), static fn($post): bool => is_array($post)));
}

function blog_save_posts(array $posts): void
{
    if (db_enabled()) {
        $pdo = db_pdo();
        if ($pdo !== null) {
            foreach ($posts as $post) {
                $pdo->prepare('INSERT INTO blog_posts (id,title,slug,excerpt,content,status,author_id,author_name,category_id,featured_image,seo_title,seo_description,canonical_url,og_title,og_description,published_at,scheduled_at,created_at,updated_at,review_comment,pending_revision) VALUES (:id,:title,:slug,:excerpt,:content,:status,:author_id,:author_name,:category_id,:featured_image,:seo_title,:seo_description,:canonical_url,:og_title,:og_description,:published_at,:scheduled_at,:created_at,:updated_at,:review_comment,:pending_revision) ON DUPLICATE KEY UPDATE title=VALUES(title),slug=VALUES(slug),excerpt=VALUES(excerpt),content=VALUES(content),status=VALUES(status),author_id=VALUES(author_id),author_name=VALUES(author_name),category_id=VALUES(category_id),featured_image=VALUES(featured_image),seo_title=VALUES(seo_title),seo_description=VALUES(seo_description),canonical_url=VALUES(canonical_url),og_title=VALUES(og_title),og_description=VALUES(og_description),published_at=VALUES(published_at),scheduled_at=VALUES(scheduled_at),updated_at=VALUES(updated_at),review_comment=VALUES(review_comment),pending_revision=VALUES(pending_revision)')->execute([
                    ':id' => $post['id'] ?? bin2hex(random_bytes(8)),
                    ':title' => $post['title'] ?? '',
                    ':slug' => $post['slug'] ?? '',
                    ':excerpt' => $post['excerpt'] ?? '',
                    ':content' => $post['content'] ?? '',
                    ':status' => $post['status'] ?? BLOG_STATUS_DRAFT,
                    ':author_id' => $post['author_id'] ?? '',
                    ':author_name' => $post['author_name'] ?? '',
                    ':category_id' => $post['category_id'] ?? '',
                    ':featured_image' => $post['featured_image'] ?? '',
                    ':seo_title' => $post['seo_title'] ?? '',
                    ':seo_description' => $post['seo_description'] ?? '',
                    ':canonical_url' => $post['canonical_url'] ?? '',
                    ':og_title' => $post['og_title'] ?? '',
                    ':og_description' => $post['og_description'] ?? '',
                    ':published_at' => $post['published_at'] ?? null,
                    ':scheduled_at' => $post['scheduled_at'] ?? null,
                    ':created_at' => $post['created_at'] ?? date('Y-m-d H:i:s'),
                    ':updated_at' => $post['updated_at'] ?? date('Y-m-d H:i:s'),
                    ':review_comment' => $post['review_comment'] ?? '',
                    ':pending_revision' => isset($post['pending_revision']) && is_array($post['pending_revision'])
                        ? json_encode($post['pending_revision'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
                        : null,
                ]);
            }
            return;
        }
    }

    blog_write_json(blog_posts_file(), $posts);
}

function blog_load_categories(): array
{
    $categories = blog_read_json(blog_categories_file(), blog_default_categories());
    if ($categories === []) {
        $categories = blog_default_categories();
        blog_write_json(blog_categories_file(), $categories);
    }
    return array_values($categories);
}

function blog_save_categories(array $categories): void
{
    blog_write_json(blog_categories_file(), $categories);
}

function blog_load_tags(): array
{
    $tags = blog_read_json(blog_tags_file(), blog_default_tags());
    if ($tags === []) {
        $tags = blog_default_tags();
        blog_write_json(blog_tags_file(), $tags);
    }
    return array_values($tags);
}

function blog_save_tags(array $tags): void
{
    blog_write_json(blog_tags_file(), $tags);
}

function blog_default_posts(): array
{
    $now = date('Y-m-d H:i:s');
    return [
        [
            'id' => 'blog-post-bpsc-strategy',
            'title' => 'How to build a realistic BPSC prelims strategy',
            'slug' => 'bpsc-prelims-2026-strategy',
            'excerpt' => 'When BPSC preparation feels scattered, a realistic revision rhythm and focused mock-test plan make a bigger difference than more notes.',
            'content' => '<p>Most aspirants lose momentum in BPSC because they keep stacking new material without building a repeatable revision cycle.</p><p>Start with a simple structure: cover a smaller subject set, revise it twice within the week, and test yourself with timed MCQs. The goal is not unlimited reading; it is consistent recall.</p><h2>Build a weekly rhythm</h2><ul><li>Study one core subject block each day.</li><li>Spend 30 minutes on revision before starting new material.</li><li>Finish with 15 minutes of MCQ practice and error review.</li></ul><p>When you review wrong answers, note the pattern: concept gap, memory gap, or careless reading. Fix the pattern before moving on.</p><blockquote>Quality revision beats quantity of reading.</blockquote><p>That habit is what turns preparation into confidence.</p>',
            'status' => BLOG_STATUS_PUBLISHED,
            'author_id' => 'editor',
            'category_id' => 'bpsc',
            'featured_image' => '',
            'seo_title' => 'BPSC prelims strategy for 2026 | RankSetu',
            'seo_description' => 'A realistic BPSC prelims strategy for revision, mock tests and smarter study planning.',
            'canonical_url' => '',
            'og_title' => 'BPSC prelims strategy for 2026 | RankSetu',
            'og_description' => 'A realistic BPSC prelims strategy for revision, mock tests and smarter study planning.',
            'published_at' => $now,
            'scheduled_at' => '',
            'created_at' => $now,
            'updated_at' => $now,
            'review_comment' => '',
            'tags' => ['BPSC', 'Strategy'],
        ],
        [
            'id' => 'blog-post-current-affairs-routine',
            'title' => 'A weekly current-affairs routine that actually sticks',
            'slug' => 'current-affairs-weekly-routine',
            'excerpt' => 'You do not need endless newspaper reading. A compact weekly routine makes current affairs manageable and exam-ready.',
            'content' => '<p>Current affairs becomes overwhelming when it is treated as a separate task. The better approach is to integrate it into your study rhythm.</p><h2>Use a steady weekly cycle</h2><ol><li>Read one high-quality source for topic summaries.</li><li>Note only the events that matter for prelims and mains.</li><li>Revise them after 48 hours and again on the weekend.</li></ol><p>Try to connect each fact with a theme such as governance, economy, international relations, or social issues. That improves retention and helps in answer writing.</p><p>Keep a small notebook of 4-5 key facts per week. Shorter notes are easier to revise and less likely to be forgotten.</p>',
            'status' => BLOG_STATUS_PUBLISHED,
            'author_id' => 'editor',
            'category_id' => 'study-tips',
            'featured_image' => '',
            'seo_title' => 'Current affairs weekly routine for competitive exams',
            'seo_description' => 'A simple weekly current affairs routine that helps aspirants revise faster and retain facts for exam days.',
            'canonical_url' => '',
            'og_title' => 'Current affairs weekly routine for competitive exams',
            'og_description' => 'A simple weekly current affairs routine that helps aspirants revise faster and retain facts for exam days.',
            'published_at' => $now,
            'scheduled_at' => '',
            'created_at' => $now,
            'updated_at' => $now,
            'review_comment' => '',
            'tags' => ['Current Affairs', 'Study Tips'],
        ],
        [
            'id' => 'blog-post-ssc-focus-strategy',
            'title' => 'SSC exam preparation: cut the noise and focus on scoring topics',
            'slug' => 'ssc-focus-strategy',
            'excerpt' => 'SSC success comes from disciplined scoring, not from trying to cover everything at once.',
            'content' => '<p>When SSC preparation turns noisy, the fix is to simplify. Pick a smaller set of scoring chapters and build consistency around them.</p><p>Start with the sections that deliver the most marks with the least uncertainty: arithmetic, grammar patterns, reading comprehension, and high-frequency current affairs.</p><h2>Prioritize scoring before quantity</h2><ul><li>Practice a short timed section every day.</li><li>Review mistakes immediately.</li><li>Keep a notebook of formula and grammar rules you repeat incorrectly.</li></ul><p>Once your accuracy becomes stable, increase the number of questions you solve. That progression helps you avoid the trap of doing too much without improving speed.</p>',
            'status' => BLOG_STATUS_PUBLISHED,
            'author_id' => 'editor',
            'category_id' => 'ssc',
            'featured_image' => '',
            'seo_title' => 'SSC exam strategy for better accuracy and score gain',
            'seo_description' => 'A focused SSC exam strategy that prioritizes scoring topics, daily practice and smarter revision.',
            'canonical_url' => '',
            'og_title' => 'SSC exam strategy for better accuracy and score gain',
            'og_description' => 'A focused SSC exam strategy that prioritizes scoring topics, daily practice and smarter revision.',
            'published_at' => $now,
            'scheduled_at' => '',
            'created_at' => $now,
            'updated_at' => $now,
            'review_comment' => '',
            'tags' => ['SSC', 'Strategy'],
        ],
    ];
}

function blog_seed_default_posts_if_needed(): void
{
    $file = blog_posts_file();
    $posts = blog_read_json($file, []);
    if ($posts !== []) {
        return;
    }
    blog_write_json($file, blog_default_posts());
}

function blog_make_post(array $payload): array
{
    $title = trim((string) ($payload['title'] ?? ''));
    $slug = trim((string) ($payload['slug'] ?? ''));
    $content = (string) ($payload['content'] ?? '');
    $excerpt = trim((string) ($payload['excerpt'] ?? ''));
    $status = strtoupper((string) ($payload['status'] ?? BLOG_STATUS_DRAFT));
    $authorId = (string) ($payload['author_id'] ?? '');
    $authorName = trim((string) ($payload['author_name'] ?? ''));
    $categoryId = (string) ($payload['category_id'] ?? '');
    $featuredImage = (string) ($payload['featured_image'] ?? '');
    $seoTitle = trim((string) ($payload['seo_title'] ?? ''));
    $seoDescription = trim((string) ($payload['seo_description'] ?? ''));
    $canonicalUrl = trim((string) ($payload['canonical_url'] ?? ''));
    $ogTitle = trim((string) ($payload['og_title'] ?? ''));
    $ogDescription = trim((string) ($payload['og_description'] ?? ''));
    $reviewComment = trim((string) ($payload['review_comment'] ?? ''));

    if ($title === '') {
        throw new InvalidArgumentException('Blog title is required.');
    }
    if (trim($content) === '') {
        throw new InvalidArgumentException('Blog content cannot be empty.');
    }
    if (!in_array($status, blog_status_options(), true)) {
        $status = BLOG_STATUS_DRAFT;
    }

    $slug = $slug !== '' ? blog_slugify($slug) : blog_slugify($title);
    $excerpt = $excerpt !== '' ? $excerpt : blog_excerpt_from_content($content);
    $publishedAt = isset($payload['published_at']) && (string) $payload['published_at'] !== '' ? (string) $payload['published_at'] : null;
    $scheduledAt = isset($payload['scheduled_at']) && (string) $payload['scheduled_at'] !== '' ? (string) $payload['scheduled_at'] : null;
    $createdAt = isset($payload['created_at']) && (string) $payload['created_at'] !== '' ? (string) $payload['created_at'] : date('Y-m-d H:i:s');
    $updatedAt = date('Y-m-d H:i:s');

    return [
        'id' => (string) ($payload['id'] ?? 'blog-' . bin2hex(random_bytes(6))),
        'title' => $title,
        'slug' => $slug,
        'excerpt' => $excerpt,
        'content' => blog_sanitize_html($content),
        'status' => $status,
        'author_id' => $authorId,
        'author_name' => $authorName,
        'category_id' => $categoryId,
        'featured_image' => $featuredImage,
        'seo_title' => $seoTitle !== '' ? $seoTitle : $title,
        'seo_description' => $seoDescription !== '' ? $seoDescription : $excerpt,
        'canonical_url' => $canonicalUrl,
        'og_title' => $ogTitle !== '' ? $ogTitle : $title,
        'og_description' => $ogDescription !== '' ? $ogDescription : $excerpt,
        'published_at' => $publishedAt,
        'scheduled_at' => $scheduledAt,
        'created_at' => $createdAt,
        'updated_at' => $updatedAt,
        'review_comment' => $reviewComment,
        'tags' => is_array($payload['tags'] ?? null) ? array_values(array_filter(array_map('strval', $payload['tags']))) : [],
    ];
}

function blog_get_post_by_id(string $id): ?array
{
    foreach (blog_all_posts() as $post) {
        if (($post['id'] ?? '') === $id) {
            return $post;
        }
    }
    return null;
}

function blog_get_post_by_slug(string $slug): ?array
{
    foreach (blog_all_posts() as $post) {
        if (($post['slug'] ?? '') === $slug) {
            return $post;
        }
    }
    return null;
}

function blog_post_author_name(array $post): string
{
    $authorName = trim((string) ($post['author_name'] ?? ''));
    if ($authorName !== '') {
        return $authorName;
    }

    $authorId = trim((string) ($post['author_id'] ?? ''));
    if ($authorId !== '' && $authorId !== 'editor') {
        foreach (users() as $user) {
            if (($user['id'] ?? '') === $authorId && trim((string) ($user['name'] ?? '')) !== '') {
                return trim((string) $user['name']);
            }
        }
    }

    return 'RankSetu Editorial Team';
}

function blog_public_posts(array $filters = []): array
{
    $posts = array_values(array_filter(blog_all_posts(), static function (array $post): bool {
        $status = (string) ($post['status'] ?? '');
        return $status === BLOG_STATUS_PUBLISHED;
    }));

    $query = trim((string) ($filters['query'] ?? ''));
    if ($query !== '') {
        $posts = array_values(array_filter($posts, static function (array $post) use ($query): bool {
            $needle = strtolower($query);
            $haystack = strtolower(((string) ($post['title'] ?? '') . ' ' . (string) ($post['excerpt'] ?? '') . ' ' . (string) ($post['content'] ?? '') . ' ' . implode(' ', (array) ($post['tags'] ?? []))));
            return str_contains($haystack, $needle);
        }));
    }

    usort($posts, static function (array $left, array $right): int {
        $leftTimestamp = blog_post_timestamp(blog_post_published_at($left));
        $rightTimestamp = blog_post_timestamp(blog_post_published_at($right));
        $leftUpdated = $leftTimestamp === false ? 0 : $leftTimestamp;
        $rightUpdated = $rightTimestamp === false ? 0 : $rightTimestamp;
        return $rightUpdated <=> $leftUpdated;
    });

    $limit = isset($filters['limit']) ? (int) $filters['limit'] : null;
    if ($limit !== null && $limit > 0) {
        $posts = array_slice($posts, 0, $limit);
    }

    return $posts;
}

function blog_pagination(array $posts, mixed $requestedPage, int $pageSize = 10): array
{
    $pageSize = max(1, $pageSize);
    $totalItems = count($posts);
    $pageCount = max(1, (int) ceil($totalItems / $pageSize));
    $pageValue = is_string($requestedPage) ? filter_var($requestedPage, FILTER_VALIDATE_INT) : false;
    $page = $pageValue === false || $pageValue < 1 ? 1 : min($pageValue, $pageCount);

    return [
        'page' => $page,
        'page_count' => $pageCount,
        'page_size' => $pageSize,
        'total_items' => $totalItems,
        'posts' => array_slice($posts, ($page - 1) * $pageSize, $pageSize),
    ];
}

function blog_post_timestamp(mixed $value): int|false
{
    if (!is_string($value) && !is_int($value)) return false;
    $value = trim((string) $value);
    if ($value === '' || preg_match('/^0000-/', $value) === 1) return false;
    return strtotime($value);
}

function blog_post_published_at(array $post): string
{
    foreach (['published_at', 'created_at'] as $field) {
        $value = $post[$field] ?? null;
        if (!is_string($value) && !is_int($value)) continue;
        $value = trim((string) $value);
        if (blog_post_timestamp($value) !== false) return $value;
    }
    return '';
}

function blog_post_public_modified_at(array $post): string
{
    $publishedAt = blog_post_published_at($post);
    if (blog_post_pending_revision($post) !== null) {
        return $publishedAt;
    }
    $updatedAt = $post['updated_at'] ?? null;
    $updatedAt = is_string($updatedAt) || is_int($updatedAt) ? trim((string) $updatedAt) : '';
    return blog_post_timestamp($updatedAt) !== false ? $updatedAt : $publishedAt;
}

function blog_post_reading_time(string $content): string
{
    $plain = trim(strip_tags($content));
    $words = count(preg_split('/\s+/', $plain ?: ''));
    $minutes = max(1, (int) ceil($words / 220));
    return $minutes . ' min read';
}

function blog_category_by_id(string $categoryId): ?array
{
    foreach (blog_load_categories() as $category) {
        if (($category['id'] ?? '') === $categoryId || ($category['slug'] ?? '') === $categoryId) {
            return $category;
        }
    }
    return null;
}

function blog_categories_with_counts(): array
{
    $categories = blog_load_categories();
    $counts = [];
    foreach (blog_all_posts() as $post) {
        $status = (string) ($post['status'] ?? '');
        if ($status !== BLOG_STATUS_PUBLISHED) {
            continue;
        }
        $categoryId = (string) ($post['category_id'] ?? '');
        if ($categoryId === '') {
            continue;
        }
        $counts[$categoryId] = ($counts[$categoryId] ?? 0) + 1;
    }
    foreach ($categories as &$category) {
        $category['count'] = (int) ($counts[(string) ($category['id'] ?? '')] ?? 0);
    }
    unset($category);
    return array_values($categories);
}

function blog_tag_suggestions(): array
{
    $tags = blog_load_tags();
    $counts = [];
    foreach (blog_all_posts() as $post) {
        $status = (string) ($post['status'] ?? '');
        if ($status !== BLOG_STATUS_PUBLISHED) {
            continue;
        }
        foreach ((array) ($post['tags'] ?? []) as $tag) {
            $key = strtolower((string) $tag);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
    }
    foreach ($tags as &$tag) {
        $tag['count'] = (int) ($counts[strtolower((string) ($tag['slug'] ?? ''))] ?? 0);
    }
    unset($tag);
    return array_values($tags);
}

function blog_related_posts(array $currentPost): array
{
    $posts = blog_public_posts();
    $related = [];
    foreach ($posts as $post) {
        if (($post['id'] ?? '') === ($currentPost['id'] ?? '')) {
            continue;
        }
        $score = 0;
        if (($post['category_id'] ?? '') === ($currentPost['category_id'] ?? '')) {
            $score += 3;
        }
        $currentTags = array_map('strtolower', (array) ($currentPost['tags'] ?? []));
        $postTags = array_map('strtolower', (array) ($post['tags'] ?? []));
        $overlap = count(array_intersect($currentTags, $postTags));
        $score += $overlap * 4;
        if ($score > 0) {
            $related[] = ['post' => $post, 'score' => $score];
        }
    }
    usort($related, static fn(array $left, array $right): int => $right['score'] <=> $left['score']);
    $items = [];
    foreach ($related as $item) {
        $items[] = $item['post'];
    }
    return array_slice($items, 0, 3);
}

function blog_current_url(string $path = ''): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
    $base = $scheme . '://' . $host;
    $path = $path === '' ? ($_SERVER['REQUEST_URI'] ?? '/blog') : $path;
    return rtrim($base, '/') . '/' . ltrim((string) $path, '/');
}

function blog_social_share_links(string $title, string $url): array
{
    return [
        'WhatsApp' => 'https://wa.me/?text=' . rawurlencode($title . ' ' . $url),
        'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url),
        'X' => 'https://twitter.com/intent/tweet?text=' . rawurlencode($title) . '&url=' . rawurlencode($url),
        'LinkedIn' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($url),
    ];
}

function blog_set_tags_for_post(array $post, array $tagNames): void
{
    $existing = blog_load_tags();
    $tagIndex = [];
    foreach ($existing as $tag) {
        $tagIndex[strtolower((string) ($tag['slug'] ?? ''))] = $tag;
    }
    foreach ($tagNames as $name) {
        $trimmed = trim((string) $name);
        if ($trimmed === '') {
            continue;
        }
        $slug = blog_slugify($trimmed);
        if (!isset($tagIndex[$slug])) {
            $tagIndex[$slug] = ['id' => 'tag-' . bin2hex(random_bytes(4)), 'name' => $trimmed, 'slug' => $slug];
            $existing[] = $tagIndex[$slug];
        }
    }
    blog_save_tags($existing);
}
