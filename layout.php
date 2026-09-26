<?php

declare(strict_types=1);

function site_header(string $title, string $section = 'site', bool $showUser = true): void
{
    echo site_header_markup($section, $showUser);
}

function site_header_markup(string $section = 'site', bool $showUser = true): string
{
    $user = function_exists('current_user') ? current_user() : null;
    $isAdmin = is_array($user) && (($user['role'] ?? 'student') === 'admin');
    $hasPremium = false;
    if (is_array($user) && function_exists('user_subscriptions')) foreach (user_subscriptions((string) $user['id']) as $subscription) {
        if (($subscription['status'] ?? '') === 'ACTIVE' && (empty($subscription['expires_at']) || strtotime((string) $subscription['expires_at']) > time())) { $hasPremium = true; break; }
    }
    $moreLinks = '<a href="index.php#features">Features</a><a href="index.php#faq">FAQ</a><a href="director.php">About Us</a><a href="info.php?page=contact">Contact</a>';
    if ($user !== null) {
        if ($hasPremium) $moreLinks .= '<a href="subscription.php">Premium user</a>';
        $moreLinks .= '<a href="student.php">Dashboard</a><a href="blog-submit.php">Write blog</a>';
        if ($isAdmin) $moreLinks .= '<a href="admin.php">Admin</a>';
        $moreLinks .= '<a href="logout.php">Log out</a>';
    } else {
        $moreLinks .= '<a href="auth.php">Log in</a>';
    }
    $links = '<a href="index.php">Home</a><a href="index.php#exams">Exams</a><a href="blog.php">Blog</a><a href="subscription.php">Subscriptions</a><details class="nav-more"><summary class="nav-more-toggle">More</summary><div class="nav-more-menu">' . $moreLinks . '</div></details>';
    if ($showUser && $user !== null) $links .= '<span class="site-user">' . e((string) ($user['name'] ?? '')) . '</span>';

    return '<header class="site-header"><div class="container"><nav id="primary-navigation" aria-label="Primary navigation"><a class="logo" href="index.php"><img class="brand-logo-image" src="img/fullmocktestseries.png" alt="' . e(APP_BRAND_NAME . ' - ' . APP_BRAND_TAGLINE) . '"></a><div class="nav-links" id="primary-links">' . $links . '</div><div class="nav-tools"><button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-links">Menu</button></div></nav></div></header>';
}

function site_footer(string $section = 'site'): void
{
    echo site_footer_markup($section);
}

function site_homepage_learning_section(): string
{
    return '<section id="features" class="section"><div class="container"><h2>A useful routine for mock-test practice</h2><p class="study-note">Series, question counts and time limits vary. Use the details shown on each published series page, and check the latest official notice for your exam.</p><div class="features"><article class="feature"><div class="icon">01</div><h3>Choose the right series</h3><p>Match the published series to your exam and stage. Review its question count and time limit before you begin.</p></article><article class="feature"><div class="icon">02</div><h3>Take a timed attempt</h3><p>Read the instructions, set aside uninterrupted time and answer without relying on notes during the attempt.</p></article><article class="feature"><div class="icon">03</div><h3>Review before repeating</h3><p>Use the result and saved attempt history to find missed questions, revisit the related topic and plan your next practice session.</p></article></div><p class="study-note">Practice scores are for learning, not official results or a prediction of selection. Confirm eligibility, exam dates and rules with the relevant authority.</p></div></section>';
}

function site_homepage_subscription_section(): string
{
    return '<section class="section" aria-labelledby="subscription-heading"><div class="container" style="background:#16233f;color:#fff;padding:42px 34px;border-radius:8px;text-align:center"><p style="color:#e0b34f;font-weight:700;letter-spacing:.08em;text-transform:uppercase;font-size:.78rem">Flexible access</p><h2 id="subscription-heading" style="color:#fff;margin:10px 0 12px">Unlock more. Pay less.</h2><p style="color:#e7e2d2;max-width:620px;margin:0 auto 24px">Choose a subscription with access to eligible mock tests and practice series. Plans and coverage are configured by the administrator.</p><a class="btn btn-white" href="subscription.php">View subscription plans</a></div></section>';
}

function site_homepage_blog_section_markup(): string
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'blog-core.php';
    $posts = blog_public_posts(['limit' => 3]);
    if ($posts === []) return '';

    $cards = '';
    foreach ($posts as $post) {
        $category = blog_category_by_id((string) ($post['category_id'] ?? ''));
        $categoryName = (string) ($category['name'] ?? 'Latest article');
        $slug = rawurlencode((string) ($post['slug'] ?? ''));
        $cards .= '<article class="feature-card" style="background:var(--card);border:1px solid var(--line);border-radius:8px;padding:20px">'
            . '<div style="font-size:.75rem;text-transform:uppercase;color:var(--maroon);font-weight:700;margin-bottom:10px">' . e($categoryName) . '</div>'
            . '<h3 style="margin-bottom:10px">' . e((string) ($post['title'] ?? '')) . '</h3>'
            . '<p style="color:var(--ink-soft);margin-bottom:16px">' . e((string) ($post['excerpt'] ?? '')) . '</p>'
            . '<a href="blog.php?slug=' . e($slug) . '" class="btn btn-primary btn-sm">Read article</a>'
            . '</article>';
    }

    return '<section class="section"><div class="container"><div class="section-head"><span class="kicker">BLOG</span><h2>Latest from our blog</h2></div>'
        . '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:18px">' . $cards . '</div>'
        . '<p style="margin-top:18px;text-align:right"><a class="link-arrow" href="blog.php">View all articles</a></p></div></section>';
}

function site_social_profiles(): array
{
    return [
        'Facebook' => ['env' => 'FACEBOOK_URL', 'constant' => 'HOSTINGER_FACEBOOK_URL', 'hosts' => ['facebook.com', 'www.facebook.com']],
        'LinkedIn' => ['env' => 'LINKEDIN_URL', 'constant' => 'HOSTINGER_LINKEDIN_URL', 'hosts' => ['linkedin.com', 'www.linkedin.com']],
        'Twitter' => ['env' => 'TWITTER_URL', 'constant' => 'HOSTINGER_TWITTER_URL', 'hosts' => ['twitter.com', 'www.twitter.com', 'x.com', 'www.x.com']],
        'Instagram' => ['env' => 'INSTAGRAM_URL', 'constant' => 'HOSTINGER_INSTAGRAM_URL', 'hosts' => ['instagram.com', 'www.instagram.com']],
        'YouTube' => ['env' => 'YOUTUBE_URL', 'constant' => 'HOSTINGER_YOUTUBE_URL', 'hosts' => ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be']],
    ];
}

function site_social_icon(string $network): string
{
    $paths = [
        'Facebook' => '<path d="M13.4 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.5-1.5h1.7V3.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V10H7.3v3h2.8v8h3.3z"/>',
        'LinkedIn' => '<path d="M5.1 3.7a2 2 0 1 1 0 4 2 2 0 0 1 0-4ZM3.4 9h3.4v11.6H3.4V9Zm5.5 0h3.2v1.6h.1c.5-.9 1.6-1.9 3.3-1.9 3.5 0 4.2 2.3 4.2 5.3v6.6h-3.4v-5.8c0-1.4 0-3.1-1.9-3.1s-2.2 1.5-2.2 3v5.9H8.9V9Z"/>',
        'Twitter' => '<path d="M18.9 3h2.9l-6.4 7.3 7.5 10h-5.9l-4.6-6-5.3 6H4.2l6.9-7.9L3.9 3h6l4.2 5.6L18.9 3Zm-1 15.5h1.6L9 4.7H7.3l10.6 13.8Z"/>',
        'Instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.6" cy="6.6" r="1.2"/>',
        'YouTube' => '<path d="M23 7.1a3 3 0 0 0-2.1-2.1C19.1 4.5 12 4.5 12 4.5s-7.1 0-8.9.5A3 3 0 0 0 1 7.1 31 31 0 0 0 .5 12c0 1.6.2 3.3.5 4.9a3 3 0 0 0 2.1 2.1c1.8.5 8.9.5 8.9.5s7.1 0 8.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .5-4.9 31 31 0 0 0-.5-4.9ZM9.8 15.5v-7l6 3.5-6 3.5Z"/>',
    ];

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . ($paths[$network] ?? '') . '</svg>';
}

function site_social_links_markup(): string
{
    $markup = '';
    foreach (site_social_profiles() as $network => $profile) {
        $url = getenv($profile['env']);
        if ($url === false || trim($url) === '') $url = defined($profile['constant']) ? constant($profile['constant']) : '';
        $url = is_string($url) ? trim($url) : '';
        $parts = $url !== '' ? parse_url($url) : false;
        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';
        $valid = filter_var($url, FILTER_VALIDATE_URL) !== false
            && is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && in_array($host, $profile['hosts'], true);
        $icon = site_social_icon($network);
        if ($valid) {
            $markup .= '<a class="social-link" href="' . e($url) . '" target="_blank" rel="noopener noreferrer" aria-label="Visit our ' . e($network) . ' profile" title="' . e($network) . '">' . $icon . '</a>';
        } else {
            $markup .= '<span class="social-link social-link-pending" role="img" aria-label="' . e($network) . ' profile link not configured" title="' . e($network) . ' link will be added">' . $icon . '</span>';
        }
    }
    return $markup;
}

function site_published_test_count(string $slug): int
{
    $count = 0;
    foreach (test_records($slug) as $test) {
        $questions = $test['questions'] ?? [];
        if (is_array($questions) && $questions !== []) $count++;
    }
    return $count;
}

function site_seo_head_markup(string $currentHtml): string
{
    $baseUrl = rtrim(APP_CANONICAL_URL, '/');
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $page = (string) ($_GET['page'] ?? 'about');
    $title = '';
    $description = '';
    $canonical = '';
    $robots = 'noindex,follow';
    $ogType = 'website';
    $imageUrl = '';
    $schema = null;

    if (in_array($script, ['index.php', 'banking-test-series.php'], true)) {
        $title = 'Banking Mock Tests and Test Series | ' . APP_BRAND_NAME;
        $description = 'Explore published competitive exam mock tests, compare series details and start timed practice. Check official notices for current exam requirements.';
        $canonical = $baseUrl . '/';
        $robots = 'index,follow,max-image-preview:large';
        $organizationId = $baseUrl . '/#organization';
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $organizationId,
                    'name' => APP_BRAND_NAME,
                    'url' => $baseUrl . '/',
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $baseUrl . '/img/fullmocktestseries.png',
                        'width' => 1536,
                        'height' => 1024,
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $baseUrl . '/#website',
                    'name' => APP_BRAND_NAME,
                    'url' => $baseUrl . '/',
                    'inLanguage' => 'en-IN',
                    'publisher' => ['@id' => $organizationId],
                ],
            ],
        ];
    } elseif ($script === 'product.php') {
        $slug = (string) ($_GET['product'] ?? $_POST['product'] ?? '');
        $catalog = published_banking_catalog();
        if (isset($catalog[$slug])) {
            $series = $catalog[$slug];
            $seriesTitle = (string) ($series['title'] ?? $slug);
            $title = $seriesTitle . ' Mock Tests and Test Series | ' . APP_BRAND_NAME;
            $availableTestCount = site_published_test_count($slug);
            $description = $availableTestCount > 0
                ? 'Explore ' . $availableTestCount . ' available ' . $seriesTitle . ' practice tests, with series stage and timing details. Review the series before enrolling.'
                : 'Review the ' . $seriesTitle . ' series description and stage. Practice tests will appear here when questions are published.';
            $imagePath = (string) ($series['image_path'] ?? '');
            if (preg_match('~^uploads/series/[A-Za-z0-9._/-]+$~', $imagePath) === 1 && !str_contains($imagePath, '..')) $imageUrl = $baseUrl . '/' . $imagePath;
            if (preg_match('/^(test|demo|sample|untitled)$/i', trim($seriesTitle)) !== 1 && $availableTestCount > 0) {
                $canonical = $baseUrl . '/product.php?product=' . rawurlencode($slug);
                $robots = 'index,follow,max-image-preview:large';
                $schema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $baseUrl . '/'],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Exam series', 'item' => $baseUrl . '/'],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $seriesTitle, 'item' => $canonical],
                    ],
                ];
            } else {
                $robots = 'noindex,follow';
            }
        }
    } elseif ($script === 'director.php') {
        $title = 'Meet Our Director | RankSetu';
        $description = 'Meet the founder and director behind RankSetu and learn the vision, mission and learner-first principles guiding the platform.';
        $canonical = $baseUrl . '/director.php';
        $robots = 'index,follow,max-image-preview:large';
        $imageUrl = $baseUrl . '/img/founder.png';
        $ogType = 'profile';
    } elseif ($script === 'info.php') {
        $infoPages = [
            'about' => ['About ' . APP_BRAND_NAME, 'Learn how FullMockTestSeries.com organizes timed mock tests and test series for competitive exam practice.'],
            'contact' => ['Contact ' . APP_BRAND_NAME, 'Contact FullMockTestSeries.com about accounts, enrollments, practice tests, accessibility or privacy requests.'],
            'faq' => ['Mock Test Practice FAQs | ' . APP_BRAND_NAME, 'Answers about choosing a test series, starting practice, saved results, accounts and support.'],
            'disclaimer' => ['Practice Test Disclaimer | ' . APP_BRAND_NAME, 'Read important information about independent practice materials, exam updates and official sources.'],
            'privacy' => ['Privacy Policy | ' . APP_BRAND_NAME, 'Learn what account, test, order and support information this service may process and how to contact the operator.'],
            'terms' => ['Terms of Use | ' . APP_BRAND_NAME, 'Review the proposed terms for using FullMockTestSeries.com mock tests, accounts and enrollment features.'],
            'refund' => ['Refund and Cancellation Policy | ' . APP_BRAND_NAME, 'Review the proposed digital-series cancellation and refund information before using paid services.'],
        ];
        if (isset($infoPages[$page])) {
            [$title, $description] = $infoPages[$page];
            $canonical = $baseUrl . '/info.php?page=' . rawurlencode($page);
            $robots = in_array($page, ['privacy', 'terms', 'refund'], true) ? 'noindex,follow' : 'index,follow,max-image-preview:large';
        }
    }

    if ($title === '' && preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $currentHtml, $titleMatch) === 1) {
        $title = html_entity_decode(strip_tags($titleMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    $tags = [
        '<title>' . e($title !== '' ? $title : APP_BRAND_NAME) . '</title>',
        '<meta name="robots" content="' . e($robots) . '">',
        '<meta property="og:site_name" content="' . e(APP_BRAND_NAME) . '">',
    ];
    if ($description !== '') {
        $tags[] = '<meta name="description" content="' . e($description) . '">';
        $tags[] = '<meta property="og:title" content="' . e($title) . '">';
        $tags[] = '<meta property="og:description" content="' . e($description) . '">';
        $tags[] = '<meta property="og:type" content="' . e($ogType) . '">';
        if ($imageUrl !== '') {
            $tags[] = '<meta property="og:image" content="' . e($imageUrl) . '">';
            $tags[] = '<meta property="og:image:alt" content="' . e($title . ' practice series cover') . '">';
            $tags[] = '<meta name="twitter:card" content="summary_large_image">';
            $tags[] = '<meta name="twitter:image" content="' . e($imageUrl) . '">';
        } else {
            $tags[] = '<meta name="twitter:card" content="summary">';
        }
        $tags[] = '<meta name="twitter:title" content="' . e($title) . '">';
        $tags[] = '<meta name="twitter:description" content="' . e($description) . '">';
    }
    if ($canonical !== '') $tags[] = '<link rel="canonical" href="' . e($canonical) . '">';
    if ($schema !== null) {
        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        if ($json !== false) $tags[] = '<script type="application/ld+json">' . $json . '</script>';
    }
    return implode("\n", $tags);
}

function site_footer_markup(string $section = 'site'): string
{
    return '<footer class="site-footer"><div class="site-footer-grid"><section class="footer-brand-column"><a class="footer-brand" href="index.php"><img class="footer-logo-image" src="img/fullmocktestseries.png" alt="' . e(APP_BRAND_NAME) . '"></a><p>' . e(APP_BRAND_TAGLINE) . '</p></section><section class="footer-links"><h2 class="footer-heading">Exams</h2><a href="index.php#exams">SBI</a><a href="index.php#exams">IBPS</a><a href="index.php#exams">RBI</a><a href="index.php#exams">Insurance &amp; finance</a></section><section class="footer-links"><h2 class="footer-heading">Quick Links</h2><a href="auth.php">Login</a><a href="instructions.php?sample=1">Free sample</a><a href="info.php?page=contact">Contact</a><a href="info.php?page=faq">FAQ</a></section><section class="footer-links"><h2 class="footer-heading">Policies</h2><a href="info.php?page=privacy">Privacy</a><a href="info.php?page=terms">Terms</a><a href="info.php?page=refund">Refund policy</a><a href="info.php?page=disclaimer">Disclaimer</a></section></div><div class="site-social-row"><div class="site-social-website"><span class="site-social-label">Website</span><a href="https://fullmocktestseries.com">fullmocktestseries.com</a></div><div class="site-social-block"><span class="site-social-label">Follow us</span><div class="social-links" aria-label="Social media profiles">' . site_social_links_markup() . '</div></div></div><div class="footer-bottom">&copy; ' . date('Y') . ' ' . e(APP_BRAND_NAME) . '. ' . e(APP_BRAND_TAGLINE) . '</div></footer>';
}

function site_layout_styles(): void
{
    echo site_layout_css();
}

function site_layout_css(): string
{
    return <<<'CSS'
<style>
html,body{max-width:100%;overflow-x:hidden}
header.site-header{position:sticky;top:0;z-index:1000;display:block!important;width:100%;max-width:100%;min-width:0;box-sizing:border-box;background:rgba(255,255,255,.97)!important;border-bottom:1px solid #e8e1d3!important;padding:0!important;box-shadow:0 12px 30px rgba(22,35,63,.08);color:#172033}
header.site-header>.container{width:100%;max-width:1680px;margin:0 auto;padding:0 24px;box-sizing:border-box}
header.site-header nav#primary-navigation{display:flex;min-height:78px;align-items:center;justify-content:space-between;gap:18px;width:100%}
header.site-header .logo{display:flex;align-items:center;flex:0 0 auto;padding-right:18px;white-space:nowrap}
header.site-header .brand-logo-image{display:block;width:230px;height:76px;max-width:100%;background:#fff;object-fit:contain;object-position:center}
header.site-header .nav-links{display:flex;flex:1 1 auto;align-items:center;justify-content:flex-end;flex-wrap:nowrap;gap:6px 10px;min-width:0;margin:0;padding:0;list-style:none;white-space:nowrap;overflow:visible}
header.site-header .nav-links a{display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:7px 9px;border-radius:999px;color:#1d2433;text-decoration:none;font:600 .84rem/1.2 Arial,sans-serif;white-space:nowrap;transition:color .18s ease,background-color .18s ease,transform .18s ease}
header.site-header .nav-links a:hover,header.site-header .nav-links a:focus-visible{color:#9c3b2e;background:#f9f0ea;text-decoration:none;transform:translateY(-1px)}
header.site-header .nav-more{position:relative;flex:0 0 auto}
header.site-header .nav-more-toggle{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:36px;padding:7px 10px;border:0;border-radius:999px;background:transparent;color:#1d2433;font:600 .84rem/1.2 Arial,sans-serif;white-space:nowrap;cursor:pointer;list-style:none}
header.site-header .nav-more-toggle::-webkit-details-marker{display:none}
header.site-header .nav-more-toggle::after{content:"";width:6px;height:6px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg) translateY(-2px)}
header.site-header .nav-more-toggle:hover,header.site-header .nav-more-toggle:focus-visible{color:#9c3b2e;background:#f9f0ea;outline:2px solid #d7bca5;outline-offset:1px}
header.site-header .nav-more-menu{position:absolute;top:calc(100% + 8px);right:0;z-index:1001;display:none;min-width:210px;padding:7px;border:1px solid #eadac9;border-radius:8px;background:#fff;box-shadow:0 12px 28px rgba(22,35,63,.14)}
header.site-header .nav-more[open]>.nav-more-menu{display:grid}
header.site-header .nav-links .nav-more-menu a{display:flex;justify-content:flex-start;min-height:40px;padding:10px 12px;border-radius:5px}
header.site-header .site-user{display:inline-flex;align-items:center;flex:0 0 auto;padding:7px 9px;border:1px solid #eadac9;background:#fffaf5;border-radius:999px;color:#9c3b2e;font-size:.82rem;font-weight:700;white-space:nowrap}
header.site-header .nav-tools{display:none}
header.site-header .nav-toggle{border:1px solid #d9d0c4;background:#f7efe7;color:#1d2433;padding:9px 12px;border-radius:8px;font:700 .85rem Arial,sans-serif;cursor:pointer;transition:all .18s ease}
header.site-header .nav-toggle:hover,header.site-header .nav-toggle:focus-visible{background:#f1e0d1;border-color:#d3b79d}
@media(min-width:381px) and (max-width:1000px){header.site-header>.container{padding:0 16px}header.site-header nav#primary-navigation{gap:8px}header.site-header .brand-logo-image{width:165px;height:62px}header.site-header .logo{padding-right:4px}header.site-header .nav-links{gap:2px 4px}header.site-header .nav-links>a{padding:6px 5px;font-size:.78rem}header.site-header .nav-more-toggle{padding:6px 6px;font-size:.78rem}header.site-header .site-user{padding:5px 6px;font-size:.72rem}}
@media(min-width:381px) and (max-width:720px){header.site-header nav#primary-navigation{flex-wrap:wrap;justify-content:center}header.site-header .logo{justify-content:center;width:100%;padding:0}header.site-header .nav-tools{display:none}header.site-header .nav-links{display:flex;flex:0 0 100%;width:100%;justify-content:center;flex-wrap:wrap;padding:0 0 8px;white-space:normal}header.site-header .nav-more{width:auto}header.site-header .nav-more-toggle{width:auto;border-bottom:0}header.site-header .nav-more-menu{position:absolute;right:0;min-width:190px;margin:0;background:#fff;box-shadow:0 12px 28px rgba(22,35,63,.14)}}
.site-footer{display:block;width:100%;max-width:100%;min-width:0;box-sizing:border-box;margin:0;padding:52px 24px 20px;background:linear-gradient(180deg,#181f2d 0%,#101827 100%);color:#ebedf2}
.site-footer-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:34px;width:100%;max-width:1240px;margin:0 auto}
.footer-brand{display:inline-flex;align-items:center;gap:10px;color:#fff;text-decoration:none;font:700 1.3rem Arial,sans-serif}
.footer-logo-image{display:block;width:230px;height:90px;max-width:100%;background:#fff;object-fit:contain;object-position:center}
.site-footer p{max-width:32ch;color:#d8deea;font-size:1rem;line-height:1.7;margin:22px 0 0}
.footer-links{display:grid;align-content:start;justify-items:start;gap:12px;min-width:0}
.footer-heading{margin:0 0 12px;color:#fff;font:700 1rem Arial,sans-serif;text-align:left;letter-spacing:.04em;text-transform:uppercase}
.footer-links a{color:#dfe6f1;text-decoration:none;font-size:.98rem;line-height:1.55;overflow-wrap:anywhere;padding:2px 0;transition:color .18s ease}
.footer-links a:hover,.footer-links a:focus-visible{color:#f8d68d;text-decoration:underline;text-underline-offset:3px}
.study-note{width:min(72ch,100%);margin:18px auto 24px;color:#4e5663;font-size:1rem;line-height:1.7;text-align:center}
.dashboard-shell{display:grid!important;grid-template-columns:250px minmax(0,1fr);gap:28px;align-items:start}
.dashboard-sidebar{background:#fff;border:1px solid #e4e7ef;padding:20px;position:sticky;top:100px;border-radius:14px;box-shadow:0 14px 30px rgba(22,35,63,.04)}
.dashboard-sidebar h2{font:700 1.1rem Georgia,serif;color:#16233f;margin:0 0 14px}
.dashboard-sidebar .premium-status{background:#fff3ed;border-left:4px solid #9c3b2e;padding:12px;margin-bottom:18px;color:#70251c;font-size:.86rem;line-height:1.45;border-radius:8px}
.dashboard-sidebar nav{display:grid;gap:5px}
.dashboard-sidebar nav a{color:#303740;text-decoration:none;padding:10px 8px;border-bottom:1px solid #eef0f4;font-size:.9rem;border-radius:6px}
.dashboard-sidebar nav a:hover{color:#9c3b2e;background:#fff8f4}
.dashboard-content{min-width:0}
.site-social-row{display:flex;align-items:center;justify-content:space-between;gap:24px;width:100%;max-width:1240px;margin:36px auto 0;padding-top:24px;border-top:1px solid rgba(255,255,255,.12)}
.site-social-block,.site-social-website{display:grid;gap:12px;justify-items:start}
.site-social-label{color:#d9dfec;font-size:.76rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em}
.site-social-website>a{color:#fff;font-size:.98rem;font-weight:700;text-decoration:none}
.site-social-website>a:hover,.site-social-website>a:focus-visible{color:#f8d68d;text-decoration:underline;text-underline-offset:3px}
.social-links{display:flex;align-items:center;gap:10px}
.social-link{display:grid;width:42px;height:42px;place-items:center;border:1px solid rgba(255,255,255,.18);border-radius:50%;background:rgba(255,255,255,.04);color:#f1f1f1;transition:all .18s ease}
a.social-link:hover,a.social-link:focus-visible{transform:translateY(-2px);border-color:#f8d68d;background:rgba(248,214,141,.12);color:#f8d68d}
.social-link-pending{opacity:.4;cursor:not-allowed}
.social-link svg{width:19px;height:19px;fill:currentColor}
.social-link:focus-visible,.site-social-website>a:focus-visible{outline:2px solid #f8d68d;outline-offset:3px}
.footer-bottom{width:100%;max-width:1240px;margin:42px auto 0;border-top:1px solid rgba(255,255,255,.12);padding-top:18px;color:#d2d9e6;font-size:.88rem;text-align:center}
@media(max-width:380px){header.site-header nav#primary-navigation{gap:12px}header.site-header .nav-links{gap:8px 12px}}
@media(max-width:380px){header.site-header>.container{padding:0 18px}header.site-header nav#primary-navigation{min-height:68px;flex-wrap:wrap;gap:0}header.site-header .logo{max-width:calc(100% - 64px);padding-right:12px}header.site-header .brand-logo-image{width:200px;height:64px;object-fit:contain}header.site-header .nav-tools{display:flex;align-items:center;margin-left:auto}header.site-header .nav-toggle{display:block;border-radius:8px}header.site-header .nav-links{display:none;flex:none;width:100%;min-width:0;box-sizing:border-box;align-items:stretch;flex-direction:column;justify-content:flex-start;gap:0;padding:8px 0 12px;overflow:visible;white-space:normal}header.site-header nav#primary-navigation.is-open .nav-links{display:flex}header.site-header .nav-links>a{display:block;width:100%;box-sizing:border-box;padding:12px 10px;border-radius:8px;border-bottom:1px solid #f0e6d9;font-size:.95rem;white-space:normal;overflow-wrap:anywhere}header.site-header .nav-more{width:100%}header.site-header .nav-more-toggle{display:flex;justify-content:space-between;width:100%;padding:12px 10px;border-radius:8px;border-bottom:1px solid #f0e6d9;font-size:.95rem}header.site-header .nav-more-menu{position:static;min-width:0;margin:4px 0 6px 10px;box-shadow:none;border-radius:6px;background:#fbf8f3}header.site-header .nav-links .nav-more-menu a{padding:11px 12px}header.site-header .nav-links .site-user{display:flex;max-width:100%;padding:10px 12px;margin-top:4px;white-space:normal;overflow-wrap:anywhere;border-radius:8px}}
@media(max-width:700px){.dashboard-shell{display:block!important}.dashboard-sidebar{position:static;margin-bottom:24px}.dashboard-sidebar nav{grid-template-columns:repeat(2,minmax(0,1fr));gap:0 8px}.dashboard-sidebar nav a{padding:9px 4px}}
@media(max-width:850px){.site-footer-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:36px 28px}.site-footer p{margin-top:18px}}
@media(max-width:520px){.site-footer{padding:38px 22px 18px}.site-footer-grid{grid-template-columns:minmax(0,1fr);gap:28px}.footer-links{gap:10px}.footer-heading{margin-bottom:6px}.site-social-row{align-items:flex-start;flex-direction:column;margin-top:28px;padding-top:20px}.social-links{gap:8px}.footer-bottom{margin-top:32px;padding-top:18px}}
</style>
CSS;
}

function site_layout_output_filter(string $html): string
{
    if (stripos($html, '<html') === false) return $html;

    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (in_array($script, ['index.php', 'banking-test-series.php'], true)) {
        $html = preg_replace_callback('/<section id="features" class="section">.*?<\/section>/is', static fn(array $matches): string => site_homepage_learning_section(), $html, 1) ?? $html;
        $html = str_replace('<section id="exams"', site_homepage_subscription_section() . '<section id="exams"', $html);
        $blogSection = site_homepage_blog_section_markup();
        if ($blogSection !== '') $html = preg_replace('/<footer\b/i', $blogSection . '<footer', $html, 1) ?? $html;
        $html = str_replace('📚 Choose Your Banking Exam', 'Choose a published exam series', $html);
        $html = str_replace('❓ Frequently Asked Questions', 'Frequently asked questions', $html);
    }
    if ($script === 'admin.php') {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'blog-core.php';
        $pendingPosts = array_values(array_filter(blog_all_posts(), static fn(array $post): bool => ($post['status'] ?? '') === BLOG_STATUS_PENDING_REVIEW));
        $pendingLinks = '';
        foreach (array_slice($pendingPosts, 0, 5) as $pendingPost) {
            $pendingLinks .= '<p style="display:flex;justify-content:space-between;gap:12px;align-items:center;border-top:1px solid #cfc7ac;padding:10px 0;margin:0"><span><strong>' . e((string) ($pendingPost['title'] ?? 'Untitled')) . '</strong><br><small>By ' . e(blog_post_author_name($pendingPost)) . '</small></span><a class="btn outline" href="blog-admin.php?post=' . e(rawurlencode((string) ($pendingPost['id'] ?? ''))) . '">Review</a></p>';
        }
        if (count($pendingPosts) > 5) $pendingLinks .= '<p class="muted">And ' . (count($pendingPosts) - 5) . ' more awaiting review.</p>';
        if ($pendingLinks === '') $pendingLinks = '<p class="muted">No articles are waiting for review.</p>';
        $reviewPanel = '<section class="panel" style="margin-bottom:18px"><h2>Blog review queue <span class="pill">' . count($pendingPosts) . ' pending</span></h2>' . $pendingLinks . '<a class="btn" href="blog-admin.php?review=pending">Open review queue</a></section>';
        $html = str_replace('<nav class="tabs">', $reviewPanel . '<nav class="tabs">', $html);
    }
    if ($script === 'dashboard.php') {
        $dashboardUser = function_exists('current_user') ? current_user() : null;
        $activeSubscription = null;
        if (is_array($dashboardUser) && function_exists('user_subscriptions')) foreach (user_subscriptions((string) $dashboardUser['id']) as $subscription) {
            if (($subscription['status'] ?? '') === 'ACTIVE' && (empty($subscription['expires_at']) || strtotime((string) $subscription['expires_at']) > time())) { $activeSubscription = $subscription; break; }
        }
        $sidebarStatus = $activeSubscription === null ? '<div class="premium-status">No active premium subscription.</div>' : '<div class="premium-status"><strong>Premium user</strong><br>Bank Premium<br>Valid until ' . e(date('d M Y', strtotime((string) $activeSubscription['expires_at']))) . '</div>';
        $adminSidebar = $dashboardUser !== null && (($dashboardUser['role'] ?? 'student') === 'admin') ? '<h2 style="margin-top:26px">Administration</h2><nav aria-label="Administration navigation"><a href="admin.php">Control centre</a><a href="admin-business.php">Business analytics</a><a href="admin-course-control.php">Users &amp; course access</a><a href="admin-subscriptions.php">Manage subscriptions</a><a href="admin-coupons.php">Manage coupons</a><a href="admin-tests.php">Manage tests</a></nav>' : '';
        $sidebar = '<aside class="dashboard-sidebar"><h2>My account</h2>' . $sidebarStatus . '<nav aria-label="Account navigation"><a href="dashboard.php">Dashboard</a><a href="blog-submit.php">Write a blog</a><a href="blog-submit.php#my-submissions">My blog submissions</a><a href="subscription.php">My subscription</a><a href="index.php#exams">Browse banking exams</a><a href="orders.php">Orders and invoices</a><a href="history.php">Test history</a></nav>' . $adminSidebar . '</aside>';
        $html = str_replace('<main class="wrap">', '<main class="dashboard-shell wrap">' . $sidebar . '<div class="dashboard-content">', $html, $mainReplaced);
        if ($mainReplaced > 0) $html = preg_replace('/<\/main>/i', '</div></main>', $html, 1) ?? $html;
    }
    $html = str_replace('Full-Length Tests', 'Available Practice Tests', $html);
    $html = str_replace('Published Exams', 'Available Series', $html);
    $html = str_replace('Questions Per Set', 'Questions Per Test', $html);
    $html = str_replace('Build a focused preparation plan for India\'s leading public-sector exams. Explore the active series below as new categories are published.', 'Build a focused preparation plan for India\'s leading public-sector exams. Explore currently published series below; new categories appear when their practice sets are ready.', $html);
    $html = str_replace('Each banking series is designed around 15 full-length child tests, with 10 questions per starter set. Admins can update the content and duration.', 'Counts vary by series. The catalog and product page show the tests that currently contain questions, along with their available timing details.', $html);
    $html = str_replace('Timed practice sets designed around the latest pattern and marking approach.', 'Use the questions and time limit shown for this series. Confirm current exam patterns and marking rules with the official exam authority.', $html);
    $html = str_replace('subscription-checkout.php?plan=', 'subscription-payment.php?plan=', $html);
    $html = str_replace('â‚¹', '₹', $html);

    $header = site_header_markup();
    $footerMarkup = site_footer_markup();

    $seoHead = site_seo_head_markup($html);
    $html = preg_replace('/<title\b[^>]*>.*?<\/title>/is', '', $html, 1) ?? $html;
    $html = preg_replace('/<\/head>/i', site_layout_css() . $seoHead . '</head>', $html, 1) ?? $html;
    if ($script === 'result.php') $html = preg_replace('/<\/head>/i', '<style>@media(max-width:600px){.panel .btn{display:block;width:100%;margin:0 0 10px;text-align:center}.panel .btn:last-child{margin-bottom:0}}</style></head>', $html, 1) ?? $html;
    $headerCount = 0;
    if ($script !== 'attempt.php') {
        $html = preg_replace_callback('/<header\b[^>]*>.*?<\/header>/is', static fn(array $matches): string => $header, $html, 1, $headerCount) ?? $html;
        if ($headerCount === 0) {
            $html = preg_replace_callback('/<body\b[^>]*>/i', static fn(array $matches): string => $matches[0] . $header, $html, 1) ?? $html;
        }
    }
    $html = preg_replace_callback('/<footer\b[^>]*>.*?<\/footer>/is', static fn(array $matches): string => $footerMarkup, $html) ?? $html;
    if (stripos($html, 'id="test-form"') !== false) {
        $timerPlacement = '<script>(()=>{const timer=document.getElementById("timer");const title=document.querySelector("main h1");if(timer&&title){title.insertAdjacentElement("afterend",timer);timer.style.position="static";timer.style.width="fit-content";timer.style.margin="12px 0 18px";timer.style.boxShadow="0 3px 10px rgba(0,0,0,.12)";}})();</script>';
        $confirmation = '<script>(()=>{const testForm=document.getElementById("test-form");if(!testForm)return;let submitted=false;const startedAt=testForm.querySelector("[name=started_at]")?.value||"0";const storageKey="active-quiz:"+location.pathname+location.search+":"+startedAt;const saved=JSON.parse(localStorage.getItem(storageKey)||"{}");Object.entries(saved).forEach(([name,value])=>{const input=testForm.querySelector("[name=\""+name+"\"][value=\""+value+"\"]");if(input)input.checked=true;});testForm.addEventListener("change",()=>{const answers={};testForm.querySelectorAll("input[type=radio]:checked").forEach(input=>answers[input.name]=input.value);localStorage.setItem(storageKey,JSON.stringify(answers));});testForm.addEventListener("submit",event=>{if(submitted)return;if(!window.confirm("Submit this test now? You will not be able to change your answers after submission.")){event.preventDefault();return;}submitted=true;localStorage.removeItem(storageKey);});const nativeSubmit=testForm.submit.bind(testForm);testForm.submit=()=>{submitted=true;testForm.dataset.autoSubmitting="true";localStorage.removeItem(storageKey);nativeSubmit();};window.addEventListener("beforeunload",event=>{if(!submitted&&!testForm.dataset.autoSubmitting){event.preventDefault();event.returnValue="Your test is still in progress.";}});})();</script>';
    } else {
        $timerPlacement = '';
        $confirmation = '';
    }
    $navigationScript = '<script>(()=>{const navigation=document.getElementById("primary-navigation");const toggle=navigation?.querySelector(".nav-toggle");const moreMenu=navigation?.querySelector(".nav-more");if(!navigation||!toggle||toggle.dataset.navigationBound==="true")return;toggle.dataset.navigationBound="true";const close=()=>{navigation.classList.remove("is-open");toggle.setAttribute("aria-expanded","false");toggle.textContent="Menu";};const closeMore=()=>{if(moreMenu)moreMenu.open=false;};toggle.addEventListener("click",()=>{closeMore();const isOpen=navigation.classList.toggle("is-open");toggle.setAttribute("aria-expanded",String(isOpen));toggle.textContent=isOpen?"Close":"Menu";});if(moreMenu){moreMenu.querySelectorAll("a").forEach(link=>link.addEventListener("click",()=>{closeMore();close();}));document.addEventListener("click",event=>{if(!moreMenu.contains(event.target))closeMore();});}navigation.querySelectorAll(".nav-links>a").forEach(link=>link.addEventListener("click",close));document.addEventListener("keydown",event=>{if(event.key==="Escape"){closeMore();close();}});})();</script>';
    $adminControlScript = $script === 'admin-course-control.php' ? '<script>document.querySelectorAll(".user-card").forEach(card=>{const userId=card.querySelector("input[name=user_id]")?.value;if(!userId)return;const link=document.createElement("a");link.href="admin-user-detail.php?user="+encodeURIComponent(userId);link.textContent="View full user details";link.className="btn outline";link.style.marginTop="12px";card.querySelector(".user-head")?.appendChild(link);if(card.textContent.includes("Premium:")){const premium=document.createElement("a");premium.href="admin-subscription-control.php?user="+encodeURIComponent(userId);premium.textContent="Manage premium";premium.className="btn danger";premium.style.margin="12px 0 0 8px";card.querySelector(".user-head")?.appendChild(premium);}if(!card.querySelector(".course-table")&&card.textContent.includes("Premium:")){const note=document.createElement("p");note.className="muted";note.textContent="Bank Premium covers every published banking course unless individually revoked. Use full user details to manage course access.";card.querySelector(".user-head")?.after(note);}});</script>' : '';
    $adminDetailScript = $script === 'admin-user-detail.php' ? '<script>const premiumBox=document.querySelector(".premium");const userId=new URLSearchParams(location.search).get("user");if(premiumBox&&userId){const link=document.createElement("a");link.href="admin-subscription-control.php?user="+encodeURIComponent(userId);link.textContent="Manage premium status";link.className="btn";link.style.marginTop="12px";premiumBox.appendChild(link);}</script>' : '';
    $adminAccountScript = $script === 'admin-user-detail.php' ? '<script>const detailUserId=new URLSearchParams(location.search).get("user");const detailPanel=document.querySelector("main .panel");if(detailUserId&&detailPanel){const link=document.createElement("a");link.href="admin-account-control.php?user="+encodeURIComponent(detailUserId);link.textContent="Deactivate user and remove credentials";link.className="btn";link.style.background="#70251c";link.style.marginTop="18px";detailPanel.parentElement?.appendChild(link);}</script>' : '';
    $html = str_replace('RankSetu', APP_BRAND_NAME, $html);
    $html = str_replace('Focused banking test series for serious preparation.', APP_BRAND_TAGLINE, $html);
    if (stripos($html, '<footer') === false) $footerMarkup = site_footer_markup();
    else $footerMarkup = '';
    return preg_replace('/<\/body>/i', $navigationScript . $adminControlScript . $adminDetailScript . $adminAccountScript . $timerPlacement . $confirmation . $footerMarkup . '</body>', $html, 1) ?? $html;
}
