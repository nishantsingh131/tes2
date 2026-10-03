<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$user = require_login();
header('X-Robots-Tag: noindex, nofollow');

$savedAttempts = is_array($user['attempts'] ?? null) ? $user['attempts'] : [];
$attempts = array_reverse(array_values(array_filter($savedAttempts, 'is_array')));
$seriesScores = [];
foreach ($attempts as $attempt) {
    $series = (string) ($attempt['product'] ?? '');
    if (!isset($seriesScores[$series])) $seriesScores[$series] = ['total' => 0, 'count' => 0];
    $seriesScores[$series]['total'] += max(0, min(100, (int) ($attempt['percentage'] ?? 0)));
    $seriesScores[$series]['count']++;
}
$labels = banking_labels();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test attempt history | <?= e(APP_BRAND_NAME) ?></title>
    <style>
        :root{--navy:#16233f;--navy-soft:#243a64;--maroon:#9c3b2e;--paper:#f5f3ed;--card:#fffefa;--ink:#1c2432;--muted:#586272;--line:#e1e4e9;--gold:#e7b64e;--green:#286447}
        *{box-sizing:border-box}
        body{margin:0;background:var(--paper);color:var(--ink);font-family:Arial,Helvetica,sans-serif}
        .wrap{width:min(1000px,calc(100% - 40px));margin:0 auto;padding:38px 0 64px}
        .page-heading{margin-bottom:24px}
        .eyebrow{margin:0 0 8px;color:var(--maroon);font-size:.75rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        h1,h2{font-family:Georgia,"Times New Roman",serif;color:var(--navy)}
        h1{margin:0;font-size:clamp(2rem,4vw,2.8rem);line-height:1.12}
        .intro{margin:10px 0 0;color:var(--muted);font-size:1rem;line-height:1.6}
        .panel{min-width:0;margin-top:18px;padding:clamp(18px,3vw,26px);border:1px solid var(--line);border-radius:13px;background:var(--card);box-shadow:0 5px 18px rgba(22,35,63,.04)}
        .panel-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:18px}
        .panel-heading h2{margin:0;font-size:clamp(1.25rem,2.5vw,1.6rem)}
        .panel-heading p{margin:0;color:var(--muted);font-size:.88rem}
        .bars{display:grid;gap:16px}
        .bar-row{display:grid;grid-template-columns:minmax(120px,190px) minmax(80px,1fr) 52px;gap:12px;align-items:center;font-size:.88rem}
        .bar-label{min-width:0;overflow-wrap:anywhere}
        .bar{height:10px;overflow:hidden;border-radius:999px;background:#e8eaf0}
        .bar span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--maroon),#c16847)}
        .attempt-list{display:grid;gap:12px}
        .attempt-card{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center;min-width:0;padding:18px;border:1px solid var(--line);border-radius:11px;background:#fff}
        .attempt-main{min-width:0}
        .attempt-series{margin:0 0 6px;color:var(--maroon);font-size:.75rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
        .attempt-title{margin:0;color:var(--navy);font:700 1.15rem/1.35 Georgia,"Times New Roman",serif;overflow-wrap:anywhere}
        .attempt-meta{display:flex;flex-wrap:wrap;gap:6px 14px;margin:9px 0 0;color:var(--muted);font-size:.84rem;line-height:1.5}
        .attempt-score{display:flex;flex-direction:column;align-items:flex-end;gap:4px;white-space:nowrap}
        .attempt-score strong{color:var(--navy);font:700 1.45rem Georgia,"Times New Roman",serif}
        .attempt-score span{color:var(--muted);font-size:.8rem}
        .attempt-action{grid-column:1/-1;display:flex;justify-content:flex-end}
        .btn{display:inline-flex;min-height:42px;align-items:center;justify-content:center;padding:9px 14px;border:2px solid var(--maroon);border-radius:7px;background:var(--maroon);color:#fff;font-size:.85rem;font-weight:800;text-decoration:none;text-align:center}
        .btn:hover{background:#812d24}
        .empty{padding:20px 0;color:var(--muted);font-size:.95rem;line-height:1.6}
        @media(max-width:620px){
            .wrap{width:min(100% - 28px,540px);padding:26px 0 44px}
            .panel{margin-top:14px}
            .panel-heading{align-items:flex-start;flex-direction:column;gap:5px}
            .bar-row{grid-template-columns:minmax(0,1fr) 48px;gap:7px 10px}
            .bar-row .bar{grid-column:1/-1;grid-row:2}
            .attempt-card{grid-template-columns:minmax(0,1fr) auto;gap:12px;padding:15px}
            .attempt-title{font-size:1.02rem}
            .attempt-meta{font-size:.8rem}
            .attempt-action{grid-column:1/-1}
            .attempt-action .btn{width:100%}
        }
        @media(max-width:360px){
            .attempt-card{grid-template-columns:1fr}
            .attempt-score{flex-direction:row;align-items:baseline;justify-content:space-between}
        }
    </style>
</head>
<body>
<header><a class="brand" href="student.php"><?= e(APP_BRAND_NAME) ?></a></header>
<main class="wrap">
    <section class="page-heading">
        <p class="eyebrow">Your progress</p>
        <h1>Test attempt history</h1>
        <p class="intro">Each submission is kept separately. Open any attempt to see your selected answers and the correct answers for that test.</p>
    </section>
    <section class="panel" aria-labelledby="series-scores-heading">
        <div class="panel-heading"><h2 id="series-scores-heading">Average score by series</h2><p><?= count($attempts) ?> <?= count($attempts) === 1 ? 'saved attempt' : 'saved attempts' ?></p></div>
        <?php if ($seriesScores === []): ?>
            <div class="empty">No attempts yet. Start a practice test from your dashboard and your results will appear here.</div>
        <?php else: ?>
            <div class="bars">
                <?php foreach ($seriesScores as $slug => $scoreData): $average = (int) round($scoreData['total'] / max(1, $scoreData['count'])); $seriesLabel = $labels[$slug] ?? ($slug !== '' ? ucwords(str_replace('-', ' ', $slug)) : 'Practice series'); ?>
                    <div class="bar-row">
                        <strong class="bar-label"><?= e((string) $seriesLabel) ?></strong>
                        <div class="bar" role="img" aria-label="<?= $average ?> percent average score"><span style="width:<?= $average ?>%"></span></div>
                        <strong><?= $average ?>%</strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <section class="panel" aria-labelledby="all-attempts-heading">
        <div class="panel-heading"><h2 id="all-attempts-heading">All submitted attempts</h2><p>Review answers for any attempt</p></div>
        <?php if ($attempts === []): ?>
            <div class="empty">Your submitted attempts will appear here.</div>
        <?php else: ?>
            <div class="attempt-list">
                <?php foreach ($attempts as $attempt):
                    $attemptId = (string) ($attempt['id'] ?? '');
                    $slug = (string) ($attempt['product'] ?? '');
                    $seriesLabel = $labels[$slug] ?? ($slug !== '' ? ucwords(str_replace('-', ' ', $slug)) : 'Practice series');
                    $scoreTotal = max(0, (int) ($attempt['total'] ?? 0));
                    $score = max(0, min($scoreTotal, (int) ($attempt['score'] ?? 0)));
                    $percentage = max(0, min(100, (int) ($attempt['percentage'] ?? 0)));
                    $timeTaken = max(0, (int) ($attempt['time_taken'] ?? 0));
                    $submittedTimestamp = strtotime((string) ($attempt['submitted_at'] ?? ''));
                    $submittedLabel = $submittedTimestamp === false ? 'Date unavailable' : date('d M Y, h:i A', $submittedTimestamp);
                    $title = trim((string) ($attempt['title'] ?? '')) ?: 'Practice test';
                ?>
                    <article class="attempt-card">
                        <div class="attempt-main">
                            <p class="attempt-series"><?= e((string) $seriesLabel) ?></p>
                            <h3 class="attempt-title"><?= e($title) ?></h3>
                            <div class="attempt-meta">
                                <span><?= e($submittedLabel) ?></span>
                                <span><?= intdiv($timeTaken, 60) ?>m <?= $timeTaken % 60 ?>s</span>
                                <?php if (!empty($attempt['timed_out'])): ?><span>Time limit reached</span><?php endif; ?>
                            </div>
                        </div>
                        <div class="attempt-score"><strong><?= $percentage ?>%</strong><span><?= $score ?>/<?= $scoreTotal ?> correct</span></div>
                        <div class="attempt-action">
                            <?php if ($attemptId !== ''): ?>
                                <a class="btn" href="result.php?id=<?= e(rawurlencode($attemptId)) ?>">Review answers</a>
                            <?php else: ?>
                                <span class="empty">Review not available for this saved entry.</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
