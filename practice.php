<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$user = require_login();
$series = banking_labels();
foreach (series_records() as $record) {
    if (!empty($record['active']) && isset($record['slug'], $record['title'])) {
        $series[$record['slug']] = $record['title'];
    }
}

$slugValue = $_GET['product'] ?? '';
$slug = is_string($slugValue) ? $slugValue : '';
if (!isset($series[$slug]) || !user_has_course_access($user, $slug)) {
    header('Location: student.php');
    exit;
}
header('X-Robots-Tag: noindex, nofollow');

$tests = test_records($slug);
$attempted = [];
foreach ($user['attempts'] ?? [] as $attempt) {
    if (($attempt['product'] ?? '') === $slug) {
        $attempted[$attempt['test_id']] = $attempt;
    }
}

$testCount = count($tests);
$totalQuestions = 0;
$totalMinutes = 0;
foreach ($tests as $test) {
    $totalQuestions += count((array) ($test['questions'] ?? []));
    $totalMinutes += max(1, (int) ($test['duration_minutes'] ?? 10));
}
$productUrl = 'product.php?product=' . rawurlencode($slug);
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($series[$slug]) ?> practice dashboard: review published tests, question counts and time limits, then continue to instructions.">
    <title><?= e($series[$slug]) ?> Practice Tests | <?= e(APP_BRAND_NAME) ?></title>
    <style>
        :root {
            color-scheme: light;
            --navy: #16233f;
            --navy-soft: #243a64;
            --maroon: #9c3b2e;
            --paper: #f5f3ed;
            --card: #fffefa;
            --ink: #1c2432;
            --muted: #586272;
            --line: #e1e4e9;
            --gold: #e7b64e;
            --green: #286447;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--ink);
            background: var(--paper);
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-width: 320px; background: var(--paper); }
        a { color: inherit; }
        a:focus-visible { outline: 3px solid var(--gold); outline-offset: 3px; }
        .site-header { background: var(--navy); border-bottom: 4px solid var(--gold); color: #fff; }
        .header-inner, main { width: min(1120px, calc(100% - 48px)); margin: 0 auto; }
        .header-inner { min-height: 74px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .brand { color: #fff; font: 700 1.35rem Georgia, "Times New Roman", serif; text-decoration: none; }
        .nav { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .nav a { min-height: 42px; display: inline-flex; align-items: center; padding: 8px 13px; border-radius: 6px; color: #f5ead2; font-size: .92rem; font-weight: 700; text-decoration: none; }
        .nav a:hover { background: rgba(255,255,255,.1); }
        main { padding: 42px 0 72px; }
        .breadcrumbs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; color: var(--muted); font-size: .9rem; }
        .breadcrumbs a { color: var(--maroon); font-weight: 700; text-decoration: none; }
        .breadcrumbs a:hover { text-decoration: underline; }
        .page-heading { max-width: 760px; margin-bottom: 28px; }
        .eyebrow { margin: 0 0 9px; color: var(--maroon); font-size: .76rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1, h2 { font-family: Georgia, "Times New Roman", serif; }
        h1 { margin: 0; color: var(--navy); font-size: clamp(2.2rem, 5vw, 3.4rem); line-height: 1.08; text-wrap: balance; }
        .intro { margin: 14px 0 0; color: var(--muted); font-size: clamp(1rem, 2vw, 1.1rem); line-height: 1.7; }
        .overview { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(250px, .7fr); gap: 24px; margin: 28px 0 34px; }
        .overview-main, .overview-stat { min-width: 0; border-radius: 14px; }
        .overview-main { padding: clamp(22px, 4vw, 34px); background: linear-gradient(125deg, var(--navy), var(--navy-soft)); color: #fff; }
        .overview-main h2 { margin: 0; color: #fff; font-size: clamp(1.45rem, 3vw, 1.85rem); line-height: 1.2; }
        .overview-main p { max-width: 62ch; margin: 10px 0 0; color: #e7ebf3; line-height: 1.65; }
        .overview-stat { display: flex; flex-direction: column; justify-content: center; padding: 24px; border: 1px solid var(--line); background: var(--card); }
        .stat-label { color: var(--muted); font-size: .86rem; font-weight: 700; }
        .stat-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 12px; }
        .stat-item { display: flex; min-width: 0; flex-direction: column; gap: 3px; }
        .stat-value { color: var(--navy); font: 700 1.8rem/1.1 Georgia, "Times New Roman", serif; }
        .stat-detail { color: var(--muted); font-size: .78rem; line-height: 1.35; }
        .section-heading { display: flex; align-items: end; justify-content: space-between; gap: 16px; margin: 0 0 16px; }
        .section-heading h2 { margin: 0; color: var(--navy); font-size: clamp(1.45rem, 3vw, 1.9rem); }
        .section-heading p { margin: 0; color: var(--muted); font-size: .9rem; }
        .mobile-test-totals { display: none; }
        .tests { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .test { display: flex; min-width: 0; flex-direction: column; padding: clamp(20px, 3vw, 27px); border: 1px solid var(--line); border-radius: 12px; background: var(--card); box-shadow: 0 5px 18px rgba(22,35,63,.045); }
        .test-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 13px; }
        .test-number { color: var(--maroon); font-size: .75rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .completed-badge { padding: 5px 9px; border-radius: 999px; background: #eaf4ee; color: var(--green); font-size: .75rem; font-weight: 800; }
        .test h3 { margin: 0; color: var(--navy); font: 700 clamp(1.2rem, 2.5vw, 1.45rem)/1.28 Georgia, "Times New Roman", serif; overflow-wrap: anywhere; }
        .test-meta { display: flex; flex-wrap: wrap; gap: 8px; margin: 19px 0 21px; }
        .meta-chip { display: inline-flex; min-height: 32px; align-items: center; padding: 6px 10px; border: 1px solid var(--line); border-radius: 999px; color: var(--muted); font-size: .8rem; font-weight: 700; }
        .score { margin: -5px 0 18px; color: var(--green); font-size: .9rem; font-weight: 800; }
        .btn { display: inline-flex; width: fit-content; min-height: 46px; align-items: center; justify-content: center; margin-top: auto; padding: 11px 16px; border: 2px solid var(--maroon); border-radius: 6px; background: var(--maroon); color: #fff; font-size: .92rem; font-weight: 800; text-align: center; text-decoration: none; transition: background .15s ease, transform .15s ease; }
        .btn:hover { transform: translateY(-1px); background: #812d24; }
        .empty-state { padding: 26px; border: 1px solid var(--line); border-radius: 12px; background: var(--card); color: var(--muted); line-height: 1.65; }
        .details-link { margin-top: 26px; padding-top: 20px; border-top: 1px solid var(--line); color: var(--muted); font-size: .92rem; line-height: 1.6; }
        .details-link a { color: var(--maroon); font-weight: 800; text-underline-offset: 3px; }
        @media (max-width: 700px) {
            .header-inner, main { width: min(100% - 32px, 560px); }
            .header-inner { min-height: 66px; align-items: flex-start; flex-direction: column; gap: 5px; padding: 12px 0 9px; }
            .nav { width: 100%; gap: 2px; }
            .nav a { min-height: 40px; padding: 7px 10px; }
            main { padding: 22px 0 40px; }
            .breadcrumbs { gap: 6px; margin-bottom: 16px; font-size: .84rem; }
            .page-heading { margin-bottom: 20px; }
            h1 { font-size: clamp(1.85rem, 8vw, 2.15rem); line-height: 1.12; }
            .intro { margin-top: 10px; font-size: .98rem; line-height: 1.55; }
            .overview { display: none; }
            .tests { grid-template-columns: 1fr; gap: 13px; }
            .test { padding: 21px; }
            .section-heading { align-items: flex-start; flex-direction: column; gap: 5px; }
            .desktop-test-hint { display: none; }
            .mobile-test-totals { display: inline; }
        }
        @media (max-width: 380px) {
            .header-inner, main { width: calc(100% - 24px); }
            .nav a { padding-inline: 8px; font-size: .84rem; }
            .test { padding: 18px; }
            .btn { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; } }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="index.php"><?= e(APP_BRAND_NAME) ?></a>
            <nav class="nav" aria-label="Account navigation">
                <a href="student.php">Dashboard</a>
                <a href="<?= e($productUrl) ?>">Series details</a>
                <a href="history.php">Attempt history</a>
                <a href="logout.php">Log out</a>
            </nav>
        </div>
    </header>
    <main>
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="student.php">My dashboard</a>
            <span aria-hidden="true">/</span>
            <span><?= e($series[$slug]) ?> practice</span>
        </nav>
        <section class="page-heading">
            <p class="eyebrow">Your practice workspace</p>
            <h1><?= e($series[$slug]) ?> practice tests</h1>
            <p class="intro">Choose a test below to start or review a saved attempt.</p>
        </section>
        <section class="overview" aria-label="Practice series overview">
            <div class="overview-main">
                <h2><?= $testCount ?> <?= $testCount === 1 ? 'test' : 'tests' ?> ready to attempt</h2>
                <p>Start your next test or revisit one you have already taken.</p>
            </div>
            <aside class="overview-stat">
                <span class="stat-label">Test set totals</span>
                <div class="stat-grid">
                    <div class="stat-item"><span class="stat-value"><?= number_format($totalQuestions) ?></span><span class="stat-detail">Questions</span></div>
                    <div class="stat-item"><span class="stat-value"><?= number_format($testCount) ?></span><span class="stat-detail"><?= $testCount === 1 ? 'Test' : 'Tests' ?></span></div>
                    <div class="stat-item"><span class="stat-value"><?= number_format($totalMinutes) ?></span><span class="stat-detail">Total minutes</span></div>
                </div>
            </aside>
        </section>
        <section aria-labelledby="tests-heading">
            <div class="section-heading">
                <h2 id="tests-heading">Available tests</h2>
                <p><span class="desktop-test-hint">Complete them in order, or revisit a test you have already taken.</span><span class="mobile-test-totals"><?= number_format($testCount) ?> <?= $testCount === 1 ? 'test' : 'tests' ?> · <?= number_format($totalQuestions) ?> questions · <?= number_format($totalMinutes) ?> total minutes</span></p>
            </div>
            <?php if ($tests !== []): ?>
                <div class="tests">
                    <?php $position = 0; foreach ($tests as $id => $test): $position++; $questionCount = count((array) ($test['questions'] ?? [])); $duration = max(1, (int) ($test['duration_minutes'] ?? 10)); ?>
                        <article class="test">
                            <div class="test-top">
                                <span class="test-number">Test <?= $position ?> of <?= $testCount ?></span>
                                <?php if (isset($attempted[$id])): ?><span class="completed-badge">Attempt saved</span><?php endif; ?>
                            </div>
                            <h3><?= e((string) ($test['title'] ?? strtoupper((string) $id))) ?></h3>
                            <div class="test-meta" aria-label="Test details">
                                <span class="meta-chip"><?= number_format($questionCount) ?> <?= $questionCount === 1 ? 'question' : 'questions' ?></span>
                                <span class="meta-chip"><?= $duration ?> minutes</span>
                            </div>
                            <?php if (isset($attempted[$id])): ?><p class="score">Best saved score: <?= e((string) ($attempted[$id]['percentage'] ?? '0')) ?>%</p><?php endif; ?>
                            <a class="btn" href="instructions.php?product=<?= e(rawurlencode($slug)) ?>&amp;test=<?= e(rawurlencode((string) $id)) ?>"><?= isset($attempted[$id]) ? 'Review instructions' : 'Read instructions' ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">There are no practice tests published for this series yet. Please check back later.</div>
            <?php endif; ?>
        </section>
        <p class="details-link">Want to review the public course information? <a href="<?= e($productUrl) ?>">See <?= e($series[$slug]) ?> series details</a>.</p>
    </main>
</body>
</html>
