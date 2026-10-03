<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$user = require_login();

$publishedCatalog = available_course_catalog();
$catalog = [];
$catalogImages = [];
foreach ($publishedCatalog as $slug => $series) {
    $catalog[$slug] = (string) ($series['title'] ?? $slug);
    $catalogImages[$slug] = isset($series['image_path']) && is_string($series['image_path']) ? $series['image_path'] : null;
}

$enrolled = array_values(array_filter(
    array_keys($catalog),
    static fn(string $slug): bool => user_has_course_access($user, $slug)
));
$rawAttempts = $user['attempts'] ?? [];
$attempts = is_array($rawAttempts) ? array_values(array_filter($rawAttempts, 'is_array')) : [];
$attempts = array_reverse($attempts);
$rawOrders = $user['orders'] ?? [];
$orders = is_array($rawOrders) ? array_reverse(array_values(array_filter($rawOrders, 'is_array'))) : [];
$subscriptions = array_values(array_filter(
    user_subscriptions((string) $user['id']),
    static fn(array $subscription): bool => subscription_is_current($subscription)
));

$validScores = [];
$scoresBySeries = [];
$completedTestsBySeries = [];
foreach ($attempts as $attempt) {
    if (isset($attempt['percentage']) && is_numeric($attempt['percentage'])) {
        $percentage = max(0, min(100, (int) $attempt['percentage']));
        $validScores[] = $percentage;
        $slug = (string) ($attempt['product'] ?? '');
        if (!isset($scoresBySeries[$slug])) $scoresBySeries[$slug] = [];
        $scoresBySeries[$slug][] = $percentage;
    }
    $slug = (string) ($attempt['product'] ?? '');
    $testId = (string) ($attempt['test_id'] ?? '');
    if ($slug !== '' && $testId !== '') $completedTestsBySeries[$slug][$testId] = true;
}
$averageScore = $validScores === [] ? null : (int) round(array_sum($validScores) / count($validScores));
$bestScore = $validScores === [] ? null : max($validScores);

$trendAttempts = array_reverse(array_slice($attempts, 0, 10));
$chartWidth = 720;
$chartLeft = 48;
$chartRight = 700;
$chartTop = 38;
$chartBottom = 198;
$trendPoints = [];
$trendPath = [];
foreach ($trendAttempts as $index => $attempt) {
    if (!isset($attempt['percentage']) || !is_numeric($attempt['percentage'])) continue;
    $percentage = max(0, min(100, (int) $attempt['percentage']));
    $x = count($trendAttempts) <= 1
        ? ($chartLeft + $chartRight) / 2
        : $chartLeft + ($chartRight - $chartLeft) * $index / (count($trendAttempts) - 1);
    $y = $chartBottom - ($chartBottom - $chartTop) * $percentage / 100;
    $trendPoints[] = ['x' => $x, 'y' => $y, 'percentage' => $percentage, 'attempt' => $attempt];
}
foreach ($trendPoints as $index => $point) {
    $trendPath[] = ($index === 0 ? 'M' : 'L') . number_format($point['x'], 1, '.', '') . ' ' . number_format($point['y'], 1, '.', '');
}
$trendLine = implode(' ', $trendPath);
$trendArea = $trendPoints === []
    ? ''
    : $trendLine . ' L ' . number_format($trendPoints[count($trendPoints) - 1]['x'], 1, '.', '') . ' ' . $chartBottom
        . ' L ' . number_format($trendPoints[0]['x'], 1, '.', '') . ' ' . $chartBottom . ' Z';

$scoreBands = [
    ['label' => 'Strong (80–100%)', 'color' => '#2f7657', 'count' => 0],
    ['label' => 'On track (60–79%)', 'color' => '#a97a24', 'count' => 0],
    ['label' => 'Building (40–59%)', 'color' => '#e39a43', 'count' => 0],
    ['label' => 'Needs focus (0–39%)', 'color' => '#bf5945', 'count' => 0],
];
foreach ($validScores as $score) {
    if ($score >= 80) $scoreBands[0]['count']++;
    elseif ($score >= 60) $scoreBands[1]['count']++;
    elseif ($score >= 40) $scoreBands[2]['count']++;
    else $scoreBands[3]['count']++;
}
$bandStops = [];
$bandOffset = 0;
foreach ($scoreBands as $band) {
    $bandStart = $bandOffset;
    $bandOffset += count($validScores) > 0 ? $band['count'] / count($validScores) * 100 : 0;
    $bandStops[] = $band['color'] . ' ' . number_format($bandStart, 2, '.', '') . '% ' . number_format($bandOffset, 2, '.', '') . '%';
}
$scoreDonut = 'conic-gradient(' . implode(', ', $bandStops) . ')';

$monthTotals = [];
$monthCounts = [];
$monthStart = new DateTimeImmutable('first day of this month');
for ($offset = 5; $offset >= 0; $offset--) {
    $month = $monthStart->modify('-' . $offset . ' months');
    $monthKey = $month->format('Y-m');
    $monthTotals[$monthKey] = 0;
    $monthCounts[$monthKey] = 0;
}
foreach ($attempts as $attempt) {
    if (!isset($attempt['percentage'], $attempt['submitted_at']) || !is_numeric($attempt['percentage'])) continue;
    $timestamp = strtotime((string) $attempt['submitted_at']);
    if ($timestamp === false) continue;
    $monthKey = date('Y-m', $timestamp);
    if (!array_key_exists($monthKey, $monthTotals)) continue;
    $monthTotals[$monthKey] += max(0, min(100, (int) $attempt['percentage']));
    $monthCounts[$monthKey]++;
}
$monthlyScores = [];
foreach ($monthTotals as $monthKey => $total) {
    $monthlyScores[] = [
        'label' => date('M', strtotime($monthKey . '-01')),
        'average' => $monthCounts[$monthKey] > 0 ? (int) round($total / $monthCounts[$monthKey]) : 0,
        'count' => $monthCounts[$monthKey],
    ];
}

$seriesScores = [];
foreach ($scoresBySeries as $slug => $scores) {
    $seriesScores[] = [
        'slug' => $slug,
        'title' => $catalog[$slug] ?? (string) $slug,
        'average' => (int) round(array_sum($scores) / count($scores)),
        'count' => count($scores),
    ];
}
usort($seriesScores, static fn(array $left, array $right): int => $right['average'] <=> $left['average']);

$dateLabel = static function (mixed $value): string {
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? 'Date unavailable' : date('d M Y', $timestamp);
};
$displayName = trim((string) ($user['name'] ?? 'Student'));
$initial = strtoupper(substr($displayName, 0, 1));
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#16233f">
    <title>Student dashboard | RankSetu</title>
    <style>
        :root{color-scheme:light;--navy:#16233f;--navy-soft:#243a64;--maroon:#9c3b2e;--paper:#f3f1ea;--card:#fffefa;--ink:#1c1a15;--muted:#68675f;--line:#e8e5dc;--gold:#c08a2e;--green:#2f7657;--orange:#e39a43;--red:#bf5945;--shadow:0 16px 44px rgba(22,35,63,.07)}
        *{box-sizing:border-box}
        body{margin:0;background:var(--paper);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;line-height:1.5}
        a{color:inherit}
        .site-header{background:var(--navy);border-bottom:3px solid var(--gold);color:#fff}
        .nav-wrap{width:min(1240px,calc(100% - 40px));min-height:72px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:24px}
        .brand{color:#fff;text-decoration:none;font:700 1.35rem Georgia,serif;letter-spacing:-.02em}
        .nav{display:flex;align-items:center;gap:22px}
        .nav a{color:#f5ead2;text-decoration:none;font-size:.9rem;font-weight:600}
        .nav a:hover,.nav a:focus-visible{color:#fff;text-decoration:underline;text-underline-offset:4px}
        .nav-user{max-width:190px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#ded9cc;font-size:.86rem}
        .wrap{width:min(1240px,calc(100% - 40px));margin:0 auto;padding:38px 0 64px}
        .dashboard-shell.wrap{grid-template-columns:230px minmax(0,1fr)!important;gap:20px!important}
        .dashboard-sidebar{top:92px;max-height:calc(100vh - 110px);overflow:auto;padding:19px!important;border:1px solid var(--line)!important;border-radius:18px!important;background:var(--card)!important;box-shadow:var(--shadow)!important}
        .dashboard-sidebar h2{color:var(--navy)!important}
        .dashboard-sidebar .premium-status{border-radius:10px!important}
        .dashboard-sidebar nav a{border-radius:9px!important}
        .welcome{display:flex;align-items:center;justify-content:space-between;gap:22px;margin-bottom:24px}
        .eyebrow{margin:0 0 5px;color:var(--maroon);font-size:.75rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        h1,h2,h3,p{margin-top:0}
        h1{margin-bottom:7px;color:var(--navy);font-size:clamp(1.8rem,4vw,2.65rem);line-height:1.14;letter-spacing:-.04em}
        .welcome-copy{margin:0;color:var(--muted)}
        .profile{display:flex;align-items:center;gap:12px;min-width:220px;padding:10px 14px;background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 6px 20px rgba(22,35,63,.04)}
        .avatar{display:grid;width:42px;height:42px;flex:none;place-items:center;border-radius:50%;background:linear-gradient(135deg,#243a64,#4d6695);color:#fff;font-size:1.1rem;font-weight:800}
        .profile-label{display:block;color:var(--muted);font-size:.72rem}
        .profile-name{display:block;max-width:190px;overflow:hidden;color:var(--navy);font-size:.9rem;font-weight:750;text-overflow:ellipsis;white-space:nowrap}
        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}
        .stat{position:relative;overflow:hidden;min-height:116px;padding:19px 20px;background:var(--card);border:1px solid var(--line);border-radius:17px;box-shadow:0 7px 22px rgba(22,35,63,.035)}
        .stat:after{position:absolute;right:-20px;bottom:-35px;width:100px;height:100px;border-radius:50%;background:rgba(192,138,46,.08);content:""}
        .stat-label{display:block;margin-bottom:8px;color:var(--muted);font-size:.78rem;font-weight:650}
        .stat-value{display:block;color:var(--navy);font-size:clamp(1.55rem,2.5vw,2rem);font-weight:800;line-height:1.15;letter-spacing:-.04em}
        .stat-note{display:block;margin-top:7px;color:#8a806c;font-size:.72rem}
        .dashboard-grid{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(300px,.85fr);gap:18px}
        .panel{min-width:0;padding:22px;background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow)}
        .panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:14px}
        .panel-title{margin:0;color:var(--navy);font-size:1.08rem;font-weight:780;letter-spacing:-.02em}
        .panel-subtitle{margin:4px 0 0;color:var(--muted);font-size:.8rem}
        .chart-badge{flex:none;padding:6px 10px;border-radius:999px;background:#f4efe3;color:#79633b;font-size:.7rem;font-weight:750}
        .trend-chart{display:block;width:100%;height:auto;overflow:visible}
        .trend-chart text{fill:#85837c;font-family:inherit;font-size:11px}
        .chart-grid{stroke:#ece9e1;stroke-width:1}
        .chart-area{fill:url(#trendFill)}
        .chart-line{fill:none;stroke:var(--maroon);stroke-width:3;stroke-linecap:round;stroke-linejoin:round}
        .chart-point{fill:var(--card);stroke:var(--maroon);stroke-width:3}
        .empty-chart{display:grid;min-height:215px;place-items:center;padding:24px;border:1px dashed var(--line);border-radius:13px;color:var(--muted);text-align:center}
        .empty-chart strong{display:block;margin-bottom:4px;color:var(--navy)}
        .score-panel{display:grid;grid-template-columns:140px 1fr;align-items:center;gap:18px;min-height:258px}
        .donut{position:relative;width:136px;height:136px;border-radius:50%}
        .donut:after{position:absolute;inset:20px;display:grid;place-items:center;border-radius:50%;background:var(--card);color:var(--navy);content:attr(data-total);font-size:1.2rem;font-weight:800}
        .donut-legend{display:grid;gap:12px}
        .legend-row{display:grid;grid-template-columns:10px minmax(0,1fr) auto;align-items:center;gap:8px;color:var(--muted);font-size:.74rem}
        .legend-dot{width:9px;height:9px;border-radius:50%}
        .legend-value{color:var(--navy);font-weight:750}
        .monthly{display:flex;align-items:flex-end;justify-content:space-between;gap:10px;min-height:186px;padding:18px 5px 0}
        .month-col{display:flex;flex:1;flex-direction:column;align-items:center;justify-content:flex-end;gap:8px;min-width:0;height:160px}
        .month-value{color:var(--muted);font-size:.68rem;font-weight:700}
        .month-bar-wrap{display:flex;width:min(34px,75%);height:112px;align-items:flex-end;border-radius:8px 8px 3px 3px;background:#f2f0e9}
        .month-bar{width:100%;min-height:0;border-radius:8px 8px 3px 3px;background:linear-gradient(180deg,#d49c48,#a97a24);transition:height .25s ease}
        .month-label{color:var(--muted);font-size:.7rem}
        .series-list{display:grid;gap:15px}
        .series-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:7px 12px;align-items:center}
        .series-name{overflow:hidden;color:var(--ink);font-size:.8rem;font-weight:700;text-overflow:ellipsis;white-space:nowrap}
        .series-score{color:var(--navy);font-size:.8rem;font-weight:800}
        .series-track{grid-column:1/-1;height:8px;overflow:hidden;border-radius:20px;background:#efede6}
        .series-track span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#b94d3d,#df8b52)}
        .section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:30px 0 14px}
        .section-head h2{margin:0;color:var(--navy);font-size:1.25rem;letter-spacing:-.025em}
        .section-link{color:var(--maroon);font-size:.83rem;font-weight:750;text-decoration:none}
        .section-link:hover{text-decoration:underline;text-underline-offset:4px}
        .learning-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
        .learning-card{overflow:hidden;background:var(--card);border:1px solid var(--line);border-radius:17px;box-shadow:0 7px 22px rgba(22,35,63,.035)}
        .learning-image{display:block;width:100%;height:138px;object-fit:cover;background:#e8e5dc}
        .learning-content{padding:17px}
        .learning-content h3{margin:0 0 5px;color:var(--navy);font-size:1rem}
        .learning-content p{margin:0;color:var(--muted);font-size:.78rem}
        .progress-head{display:flex;justify-content:space-between;gap:10px;margin:15px 0 6px;color:var(--muted);font-size:.72rem}
        .progress-head strong{color:var(--navy)}
        .progress-track{height:7px;overflow:hidden;border-radius:20px;background:#efede6}
        .progress-track span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#3b8765,#8ab17a)}
        .card-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:15px}
        .card-meta{color:var(--muted);font-size:.72rem}
        .btn{display:inline-flex;min-height:40px;align-items:center;justify-content:center;gap:7px;padding:9px 13px;border:0;border-radius:10px;background:var(--maroon);color:#fff;font-size:.8rem;font-weight:750;text-decoration:none;transition:background .18s ease,transform .18s ease}
        .btn:hover{transform:translateY(-1px);background:#812f25}
        .btn:focus-visible,.section-link:focus-visible,.nav a:focus-visible{outline:3px solid #edc971;outline-offset:3px}
        .empty{padding:24px;border:1px dashed #d4cebd;border-radius:14px;background:#fbfaf6;color:var(--muted);font-size:.9rem}
        .empty strong{display:block;margin-bottom:4px;color:var(--navy)}
        .activity-panel{padding:0;overflow:hidden}
        .activity-row{display:grid;grid-template-columns:minmax(0,1fr) auto auto;align-items:center;gap:18px;padding:15px 20px;border-top:1px solid var(--line)}
        .activity-row:first-of-type{border-top:0}
        .activity-name{display:block;overflow:hidden;color:var(--navy);font-size:.84rem;font-weight:750;text-overflow:ellipsis;white-space:nowrap}
        .activity-meta{display:block;margin-top:3px;color:var(--muted);font-size:.73rem}
        .activity-score{color:var(--maroon);font-size:.86rem;font-weight:800;white-space:nowrap}
        .activity-row .btn{min-height:34px;padding:7px 10px;font-size:.72rem}
        .orders{margin-top:24px}
        .order-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0;border-top:1px solid var(--line)}
        .order-row:first-of-type{border-top:0}
        .order-name{color:var(--navy);font-size:.84rem;font-weight:750}
        .order-meta{display:block;margin-top:3px;color:var(--muted);font-size:.72rem}
        .order-row .btn{flex:none;min-height:34px;padding:7px 10px;font-size:.72rem}
        .page-footer{margin-top:28px;color:var(--muted);font-size:.75rem;text-align:center}
        @media(max-width:900px){.dashboard-grid{grid-template-columns:1fr 1fr}.trend-panel{grid-column:1/-1}.learning-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:700px){.dashboard-shell.wrap{display:flex!important;flex-direction:column!important;gap:18px!important}.dashboard-shell.wrap>.dashboard-content{order:0!important;width:100%}.dashboard-shell.wrap>.dashboard-sidebar{order:1!important;width:100%;max-height:none;margin:0 0 20px!important}}
        @media(max-width:640px){.nav-wrap,.wrap{width:min(100% - 28px,1240px)}.nav-wrap{min-height:64px}.nav{gap:12px}.nav-user{display:none}.nav a{font-size:.8rem}.wrap{padding:27px 0 44px}.welcome{align-items:flex-start;flex-direction:column}.profile{width:100%}.stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.stat{min-height:104px;padding:15px}.stat-label{font-size:.72rem}.dashboard-grid{grid-template-columns:1fr;gap:13px}.trend-panel{grid-column:auto}.panel{padding:17px;border-radius:15px}.score-panel{grid-template-columns:120px 1fr;gap:12px;min-height:0}.donut{width:116px;height:116px}.donut:after{inset:17px;font-size:1.05rem}.donut-legend{gap:9px}.legend-row{font-size:.68rem}.learning-grid{grid-template-columns:1fr}.learning-image{height:156px}.section-head{align-items:flex-start}.activity-row{grid-template-columns:minmax(0,1fr) auto;gap:8px 12px;padding:13px 15px}.activity-score{grid-column:1;grid-row:2}.activity-row .btn{grid-column:2;grid-row:1/3}.order-row{align-items:flex-start}.order-row .btn{white-space:nowrap}}
        @media(max-width:360px){.nav{gap:8px}.nav a{font-size:.75rem}.score-panel{grid-template-columns:1fr;justify-items:center}.donut-legend{width:100%}.section-head h2{font-size:1.1rem}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition:none!important}}
    </style>
</head>
<body>
    <header class="site-header">
        <div class="nav-wrap">
            <a class="brand" href="index.php">RankSetu</a>
            <nav class="nav" aria-label="Student navigation">
                <span class="nav-user"><?= e($displayName) ?></span>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <a href="admin.php">Admin</a>
                    <a href="admin-coupons.php">Coupons</a>
                <?php endif; ?>
                <a href="history.php">Test history</a>
                <a href="orders.php">Orders</a>
                <a href="logout.php">Log out</a>
            </nav>
        </div>
    </header>
    <main class="wrap">
        <section class="welcome" aria-labelledby="welcome-title">
            <div>
                <p class="eyebrow">Your learning space</p>
                <h1 id="welcome-title">Welcome back, <?= e($displayName) ?>.</h1>
                <p class="welcome-copy">A clear view of your practice, progress and next steps.</p>
            </div>
            <div class="profile" aria-label="Student profile">
                <span class="avatar" aria-hidden="true"><?= e($initial) ?></span>
                <span><span class="profile-label">Signed in as</span><span class="profile-name"><?= e((string) ($user['email'] ?? $displayName)) ?></span></span>
            </div>
        </section>

        <section class="stats" aria-label="Learning overview">
            <article class="stat"><span class="stat-label">Accessible series</span><strong class="stat-value"><?= count($enrolled) ?></strong><span class="stat-note">Published courses available to practice</span></article>
            <article class="stat"><span class="stat-label">Tests attempted</span><strong class="stat-value"><?= count($attempts) ?></strong><span class="stat-note">Submitted practice results</span></article>
            <article class="stat"><span class="stat-label">Average score</span><strong class="stat-value"><?= $averageScore === null ? '—' : $averageScore . '%' ?></strong><span class="stat-note"><?= $averageScore === null ? 'Complete a test to start your trend' : 'Across scored attempts' ?></span></article>
            <article class="stat"><span class="stat-label">Personal best</span><strong class="stat-value"><?= $bestScore === null ? '—' : $bestScore . '%' ?></strong><span class="stat-note"><?= count($subscriptions) > 0 ? 'Premium access active' : 'Keep building your score' ?></span></article>
        </section>

        <section class="dashboard-grid" aria-label="Performance insights">
            <article class="panel trend-panel">
                <div class="panel-head"><div><h2 class="panel-title">Performance trend</h2><p class="panel-subtitle">Your latest submitted test scores</p></div><span class="chart-badge">Last <?= count($trendPoints) ?> attempts</span></div>
                <?php if ($trendPoints === []): ?>
                    <div class="empty-chart"><div><strong>Your score trend starts here</strong>Complete a practice test and your results will appear on this chart.</div></div>
                <?php else: ?>
                    <svg class="trend-chart" viewBox="0 0 <?= $chartWidth ?> 250" role="img" aria-labelledby="trend-title trend-description">
                        <title id="trend-title">Performance trend</title>
                        <desc id="trend-description">Score percentages for your latest <?= count($trendPoints) ?> submitted tests, shown from oldest to newest.</desc>
                        <defs><linearGradient id="trendFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#9c3b2e" stop-opacity=".22"/><stop offset="100%" stop-color="#9c3b2e" stop-opacity="0"/></linearGradient></defs>
                        <?php foreach ([0, 25, 50, 75, 100] as $tick): $tickY = $chartBottom - ($chartBottom - $chartTop) * $tick / 100; ?>
                            <line class="chart-grid" x1="<?= $chartLeft ?>" y1="<?= number_format($tickY, 1, '.', '') ?>" x2="<?= $chartRight ?>" y2="<?= number_format($tickY, 1, '.', '') ?>"/>
                            <text x="4" y="<?= number_format($tickY + 4, 1, '.', '') ?>"><?= $tick ?>%</text>
                        <?php endforeach; ?>
                        <?php if ($trendArea !== ''): ?><path class="chart-area" d="<?= e($trendArea) ?>"/><?php endif; ?>
                        <?php if ($trendLine !== ''): ?><path class="chart-line" d="<?= e($trendLine) ?>"/><?php endif; ?>
                        <?php foreach ($trendPoints as $point): ?>
                            <circle class="chart-point" cx="<?= number_format($point['x'], 1, '.', '') ?>" cy="<?= number_format($point['y'], 1, '.', '') ?>" r="5"><title><?= (int) $point['percentage'] ?>% score</title></circle>
                        <?php endforeach; ?>
                        <text x="<?= $chartLeft ?>" y="230" text-anchor="start"><?= e($dateLabel($trendPoints[0]['attempt']['submitted_at'] ?? '')) ?></text>
                        <?php if (count($trendPoints) > 1): ?><text x="<?= $chartRight ?>" y="230" text-anchor="end"><?= e($dateLabel($trendPoints[count($trendPoints) - 1]['attempt']['submitted_at'] ?? '')) ?></text><?php endif; ?>
                    </svg>
                <?php endif; ?>
            </article>

            <article class="panel">
                <div class="panel-head"><div><h2 class="panel-title">Score distribution</h2><p class="panel-subtitle">Attempts grouped by score range</p></div></div>
                <?php if ($validScores === []): ?>
                    <div class="empty-chart"><div><strong>No scores yet</strong>Submit a test to see your score ranges.</div></div>
                <?php else: ?>
                    <div class="score-panel">
                        <div class="donut" role="img" aria-label="Score distribution across <?= count($validScores) ?> attempts" data-total="<?= count($validScores) ?>"></div>
                        <div class="donut-legend">
                            <?php foreach ($scoreBands as $band): ?>
                                <div class="legend-row"><span class="legend-dot" style="background:<?= e($band['color']) ?>"></span><span><?= e($band['label']) ?></span><strong class="legend-value"><?= (int) $band['count'] ?></strong></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </article>

            <article class="panel">
                <div class="panel-head"><div><h2 class="panel-title">Monthly progress</h2><p class="panel-subtitle">Average score by month</p></div><span class="chart-badge">6 months</span></div>
                <?php if ($validScores === []): ?>
                    <div class="empty-chart"><div><strong>Progress will show here</strong>Your monthly average updates after each submitted test.</div></div>
                <?php else: ?>
                    <div class="monthly" role="img" aria-label="Monthly average scores for the last six months">
                        <?php foreach ($monthlyScores as $month): ?>
                            <div class="month-col" title="<?= e($month['label'] . ': ' . $month['average'] . '% average, ' . $month['count'] . ' tests') ?>">
                                <span class="month-value"><?= $month['count'] > 0 ? $month['average'] . '%' : '—' ?></span>
                                <span class="month-bar-wrap"><span class="month-bar" style="height:<?= $month['average'] ?>%"></span></span>
                                <span class="month-label"><?= e($month['label']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="panel">
                <div class="panel-head"><div><h2 class="panel-title">Performance by series</h2><p class="panel-subtitle">Average score from completed tests</p></div></div>
                <?php if ($seriesScores === []): ?>
                    <div class="empty-chart"><div><strong>No series scores yet</strong>Your series comparison appears after you submit a test.</div></div>
                <?php else: ?>
                    <div class="series-list">
                        <?php foreach (array_slice($seriesScores, 0, 5) as $series): ?>
                            <div class="series-row">
                                <span class="series-name" title="<?= e($series['title']) ?>"><?= e($series['title']) ?> <span style="font-weight:500;color:var(--muted)">· <?= (int) $series['count'] ?> <?= $series['count'] === 1 ? 'test' : 'tests' ?></span></span>
                                <strong class="series-score"><?= (int) $series['average'] ?>%</strong>
                                <span class="series-track" role="img" aria-label="<?= (int) $series['average'] ?> percent average"><span style="width:<?= (int) $series['average'] ?>%"></span></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </section>

        <div class="section-head"><h2>My learning</h2><a class="section-link" href="index.php#exams">Browse series <span aria-hidden="true">→</span></a></div>
        <?php if ($enrolled === []): ?>
            <div class="empty"><strong>No enrolled series yet</strong>Browse the catalog to choose a test series and start preparing.</div>
        <?php else: ?>
            <section class="learning-grid" aria-label="Enrolled test series">
                <?php foreach ($enrolled as $slug):
                    $tests = test_records($slug);
                    $availableTests = 0;
                    foreach ($tests as $test) {
                        if (is_array($test['questions'] ?? null) && $test['questions'] !== []) $availableTests++;
                    }
                    $completed = count($completedTestsBySeries[$slug] ?? []);
                    $progress = $availableTests > 0 ? min(100, (int) round($completed / $availableTests * 100)) : 0;
                    $average = $scoresBySeries[$slug] ?? [];
                    $seriesAverage = $average === [] ? null : (int) round(array_sum($average) / count($average));
                ?>
                    <article class="learning-card">
                        <img class="learning-image" src="<?= e($catalogImages[$slug] ?: 'assets/exam-card.svg') ?>" alt="<?= e($catalog[$slug]) ?> practice overview" loading="lazy">
                        <div class="learning-content">
                            <h3><?= e($catalog[$slug]) ?></h3>
                            <p><?= $availableTests ?> available <?= $availableTests === 1 ? 'test' : 'tests' ?> · Timed practice with saved results</p>
                            <div class="progress-head"><span>Tests completed</span><strong><?= $completed ?> / <?= $availableTests ?></strong></div>
                            <div class="progress-track" role="img" aria-label="<?= $progress ?> percent of available tests completed"><span style="width:<?= $progress ?>%"></span></div>
                            <div class="card-actions"><span class="card-meta"><?= $seriesAverage === null ? 'Ready to practice' : 'Average score ' . $seriesAverage . '%' ?></span><a class="btn" href="practice.php?product=<?= e($slug) ?>">Continue <span aria-hidden="true">→</span></a></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <div class="section-head"><h2>Recent test activity</h2><a class="section-link" href="history.php">View test history <span aria-hidden="true">→</span></a></div>
        <section class="panel activity-panel" aria-label="Recent test activity">
            <?php if ($attempts === []): ?>
                <div class="empty" style="margin:16px"><strong>Your activity will appear here</strong>Start a practice test to begin building your performance history.</div>
            <?php else: ?>
                <?php foreach (array_slice($attempts, 0, 5) as $attempt): ?>
                    <div class="activity-row">
                        <div><span class="activity-name"><?= e((string) ($attempt['title'] ?? 'Practice test')) ?></span><span class="activity-meta"><?= e($catalog[(string) ($attempt['product'] ?? '')] ?? 'Test series') ?> · <?= e($dateLabel($attempt['submitted_at'] ?? '')) ?></span></div>
                        <strong class="activity-score"><?= (int) ($attempt['percentage'] ?? 0) ?>% <span style="font-weight:500;color:var(--muted)">(<?= (int) ($attempt['score'] ?? 0) ?>/<?= (int) ($attempt['total'] ?? 0) ?>)</span></strong>
                        <a class="btn" href="result.php?id=<?= e((string) ($attempt['id'] ?? '')) ?>">View result</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="panel orders" aria-labelledby="orders-title">
            <div class="panel-head"><div><h2 class="panel-title" id="orders-title">Order history</h2><p class="panel-subtitle">Your recent purchases and receipts</p></div><a class="section-link" href="orders.php">View all <span aria-hidden="true">→</span></a></div>
            <?php if ($orders === []): ?>
                <div class="empty"><strong>No orders yet</strong>Your purchases and invoices will be listed here.</div>
            <?php else: ?>
                <?php foreach (array_slice($orders, 0, 3) as $order): ?>
                    <div class="order-row"><div><span class="order-name"><?= e((string) ($order['product'] ?? 'Course purchase')) ?></span><span class="order-meta"><?= e((string) ($order['id'] ?? '')) ?> · <?= e($dateLabel($order['created_at'] ?? '')) ?></span></div><a class="btn" href="invoice.php?id=<?= e((string) ($order['id'] ?? '')) ?>">Invoice</a></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <p class="page-footer">Keep showing up. Every practice session moves you forward.</p>
    </main>
</body>
</html>
