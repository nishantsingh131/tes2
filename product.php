<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

$catalog = [];
foreach (available_course_catalog() as $seriesSlug => $series) {
    $catalog[$seriesSlug] = [$series['title'], $series['stage'], $series['description'], '10-question starter test', '10 minutes', (int) ($series['price_paise'] ?? 25000), $series['image_path'] ?? null];
}

foreach (series_records() as $record) {
    if (!empty($record['active']) && isset($record['slug'], $record['title']) && isset(available_course_catalog()[$record['slug']])) {
        $catalog[$record['slug']] = [
            $record['title'],
            $record['stage'] ?? 'Practice',
            $record['description'] ?? 'Admin-published practice series.',
            '10 full-length questions',
            ((string) ($record['duration_minutes'] ?? 10)) . ' min each',
            max(0, (int) ($record['price_paise'] ?? 25000)),
            $record['image_path'] ?? null,
        ];
    }
}

$slugValue = $_GET['product'] ?? $_POST['product'] ?? '';
$slug = is_string($slugValue) ? $slugValue : '';
if (!isset($catalog[$slug]) || preg_match('/^(test|demo|sample|untitled)$/i', trim((string) ($catalog[$slug][0] ?? ''))) === 1) {
    http_response_code(404);
    require __DIR__ . DIRECTORY_SEPARATOR . '404.php';
    exit;
}

$user = current_user();
$entitlement = course_entitlement($user, $slug);
$pricePaise = max(0, (int) ($catalog[$slug][5] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enroll') {
    if ($user === null) {
        header('Location: auth.php?next=' . rawurlencode('product.php?product=' . $slug));
        exit;
    }
    if ($entitlement['source'] === 'owned') {
        header('Location: practice.php?product=' . rawurlencode($slug));
        exit;
    }
    if ($entitlement['source'] === 'subscription') {
        enroll_user_in_course($user, $slug, 'subscription', (string) ($entitlement['subscription_id'] ?? ''));
    } elseif ($pricePaise === 0) {
        enroll_user_in_course($user, $slug, 'free');
    } elseif ($entitlement['source'] === 'paid') {
        header('Location: checkout.php?plan=' . rawurlencode($slug));
        exit;
    } else {
        header('Location: product.php?product=' . rawurlencode($slug) . '&access=restricted');
        exit;
    }
    header('Location: product.php?product=' . rawurlencode($slug) . '&enrolled=1');
    exit;
}

[$title, $stage, $description, $tests, $duration, $pricePaise, $imagePath] = $catalog[$slug];
$searchAliases = site_exam_search_aliases(['title' => (string) $title]);
$availableTests = [];
foreach (test_records($slug) as $testKey => $test) {
    $questions = $test['questions'] ?? [];
    if (is_array($questions) && $questions !== []) $availableTests[$testKey] = $test;
}
$tests = count($availableTests) . ' available practice tests';
$duration = $availableTests !== [] ? ((string) ($availableTests[array_key_first($availableTests)]['duration_minutes'] ?? 60)) . ' minutes each' : 'Timing set by admin';
$isEnrolled = $user !== null && user_has_course_access($user, $slug);
$justEnrolled = ($_GET['enrolled'] ?? '') === '1';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> test series | RankSetu</title>
    <style>
        :root{--navy:#16233f;--navy2:#243a64;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Arial,sans-serif}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:18px 5%;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{font:700 1.5rem Georgia,serif;color:#fff;text-decoration:none}.nav{display:flex;gap:20px;align-items:center}.nav a{color:#f5ead2;text-decoration:none;font-size:.92rem}.user{color:#f5ead2;font-size:.9rem}.wrap{width:min(1050px,calc(100% - 40px));margin:auto;padding:60px 0}.crumb{color:var(--maroon);font-size:.85rem;font-weight:700;text-decoration:none}.hero{margin-top:18px;display:grid;grid-template-columns:1.3fr .7fr;gap:28px;align-items:stretch}.intro,.summary,.feature{background:var(--card);border:1px solid var(--line);padding:32px}.intro{border-top:5px solid var(--maroon)}.kicker{color:var(--maroon);font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}h1,h2{font-family:Georgia,serif;color:var(--navy)}h1{font-size:clamp(2.2rem,5vw,4rem);margin:12px 0}h2{margin-top:0}.intro p{font-size:1.08rem;line-height:1.7;color:var(--muted);max-width:58ch}.summary{background:var(--navy);color:#fff}.summary h2{color:#fff;margin-top:0}.price{font:700 2.4rem Georgia,serif;color:#e0b34f}.summary p{color:#d5d0c0}.btn{display:inline-block;border:0;background:var(--maroon);color:white;text-decoration:none;padding:13px 20px;font-weight:700;cursor:pointer;font-size:.95rem}.btn.secondary{background:transparent;border:1px solid var(--gold);color:var(--navy)}.summary .btn{width:100%;text-align:center;margin-top:12px}.summary .btn.secondary{background:#f5ead2;color:var(--navy);border-color:#f5ead2}.notice{margin-top:20px;padding:14px 16px;background:rgba(169,122,36,.14);border-left:4px solid var(--gold);color:var(--muted)}.features{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:22px}.feature h2{font-size:1.35rem}.feature p{color:var(--muted);line-height:1.7}@media (max-width:700px){.hero,.features{grid-template-columns:1fr}.nav{gap:10px;flex-wrap:wrap}.wrap{padding:32px 0}.summary .btn{width:auto;display:block}}
        .series-details{margin-top:28px;padding:26px 0;border-top:1px solid var(--line)}.series-details h2{font-size:1.55rem}.series-details p{color:var(--muted);line-height:1.7;max-width:72ch}.series-table-wrap{margin-top:18px;overflow-x:auto;background:var(--card);border:1px solid var(--line)}.series-details table{width:100%;min-width:480px;border-collapse:collapse;text-align:left}.series-details caption{text-align:left;padding:14px 16px;font-weight:700;color:var(--navy)}.series-details th,.series-details td{padding:12px 16px;border-top:1px solid var(--line)}.series-details thead th{background:#e7e2d2;color:var(--navy);font-size:.85rem}.series-details tbody th{font-weight:600}.series-note{margin-top:16px;font-size:.9rem}
    </style>
</head>
<body>
    <header>
        <a class="brand" href="index.php">RankSetu</a>
        <nav class="nav">
            <?php if ($user !== null): ?><span class="user"><?= e((string) $user['name']) ?></span><?php endif; ?>
            <a href="student.php">Dashboard</a>
            <a href="subscription.php">Subscriptions</a>
            <a href="logout.php">Log out</a>
        </nav>
    </header>
    <main class="wrap">
        <a class="crumb" href="index.php">&larr; All government exam series</a>
        <section class="hero">
            <div class="intro">
                <img src="<?= e($imagePath ?: 'assets/exam-card.svg') ?>" alt="<?= e($title) ?> practice overview" style="width:100%;max-height:220px;object-fit:cover;margin-bottom:20px">
                <div class="kicker"><?= e($stage) ?></div>
                <h1><?= e($title) ?> Mock Test Series</h1>
                <p><?= e($description) ?></p>
                <?php if ($searchAliases !== []): ?><p class="series-note">For Bank PO preparation, this page lists the published <?= e($stage) ?> practice tests with their question counts and time limits.</p><?php endif; ?>
                <a class="btn secondary" href="<?= $user === null ? 'auth.php?next=' . e(rawurlencode('product.php?product=' . $slug)) : 'student.php' ?>"><?= $user === null ? 'Sign in to track progress' : 'View my learning' ?></a>
            </div>
            <aside class="summary">
                <h2>Start preparing today</h2>
                <p><?= e($tests) ?> &bull; <?= e($duration) ?></p>
                <div class="price"><?= $entitlement['eligible'] || $pricePaise === 0 ? '₹0' : '₹' . number_format($pricePaise / 100, 2) ?></div>
                <?php if ($entitlement['source'] === 'subscription' && !$isEnrolled): ?><p style="color:#e0b34f;font-weight:700">You are a premium user. Included with your active subscription.</p><?php endif; ?>
                <?php if ($isEnrolled): ?>
                    <a class="btn secondary" href="practice.php?product=<?= e($slug) ?>">Start practice</a>
                <?php elseif ($user === null): ?>
                    <a class="btn" href="auth.php?next=<?= e(rawurlencode('product.php?product=' . $slug)) ?>">Sign in to enroll</a>
                <?php elseif ($entitlement['source'] === 'subscription' || $pricePaise === 0): ?>
                    <form method="post">
                        <input type="hidden" name="product" value="<?= e($slug) ?>">
                        <input type="hidden" name="action" value="enroll">
                        <button class="btn" type="submit">Enroll free</button>
                    </form>
                <?php elseif ($entitlement['source'] === 'paid'): ?>
                    <a class="btn" href="checkout.php?plan=<?= e($slug) ?>">Continue to checkout</a>
                <?php else: ?>
                    <p style="color:#e0b34f;font-weight:700">Course access is currently unavailable. Contact support for help.</p>
                <?php endif; ?>
            </aside>
        </section>

        <?php if ($justEnrolled): ?>
            <div class="notice"><strong>You are enrolled.</strong> This series is now available in your student dashboard.</div>
        <?php endif; ?>

        <section class="series-details" aria-labelledby="available-tests-heading">
            <h2 id="available-tests-heading">Available <?= e($title) ?> practice tests</h2>
            <?php if ($availableTests !== []): ?>
                <p>This table reflects the test sets currently published for this series. Question counts and durations are taken from each set's saved configuration.</p>
                <div class="series-table-wrap"><table><caption><?= e($title) ?> test set details</caption><thead><tr><th scope="col">Test set</th><th scope="col">Questions</th><th scope="col">Time limit</th></tr></thead><tbody>
                    <?php foreach ($availableTests as $testKey => $test): ?>
                        <tr><th scope="row"><?= e((string) ($test['title'] ?? $testKey)) ?></th><td><?= count((array) ($test['questions'] ?? [])) ?></td><td><?= max(1, (int) ($test['duration_minutes'] ?? 60)) ?> minutes</td></tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            <?php else: ?>
                <p>No populated tests are published for this series yet. Check back after the series is updated.</p>
            <?php endif; ?>
            <p class="series-note">These are independent practice materials, not official exam papers or a guarantee of an exam result. Verify current eligibility, syllabus, dates and marking rules with the relevant official authority.</p>
        </section>

        <section class="features">
            <article class="feature">
                <h2>Exam-style tests</h2>
                <p>Use each test set's saved questions and time limit as focused practice, then review your result.</p>
            </article>
            <article class="feature">
                <h2>Track your progress</h2>
                <p>Keep every attempt in one place and build a steady preparation habit.</p>
            </article>
            <article class="feature">
                <h2>Practice first</h2>
                <p>Enrollment and payment availability are shown in the current checkout flow. Review the displayed terms before continuing.</p>
            </article>
        </section>
    </main>
</body>
</html>
