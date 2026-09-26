<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$user = require_login();
$attempts = array_reverse($user['attempts'] ?? []);
$seriesScores = [];
foreach ($attempts as $attempt) {
    $series = (string) ($attempt['product'] ?? '');
    if (!isset($seriesScores[$series])) {
        $seriesScores[$series] = ['total' => 0, 'count' => 0];
    }
    $seriesScores[$series]['total'] += (int) ($attempt['percentage'] ?? 0);
    $seriesScores[$series]['count']++;
}
$labels = banking_labels();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Attempt history | RankSetu</title><style>:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Arial,sans-serif}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:18px 5%;display:flex;justify-content:space-between;align-items:center}.brand{color:#fff;text-decoration:none;font:700 1.5rem Georgia,serif}.nav a{color:#f5ead2;text-decoration:none;margin-left:18px;font-size:.9rem}.wrap{width:min(1000px,calc(100% - 40px));margin:auto;padding:52px 0}h1,h2{font-family:Georgia,serif;color:var(--navy)}h1{font-size:2.7rem;margin:0 0 8px}.muted{color:var(--muted)}.panel{background:var(--card);border:1px solid var(--line);padding:24px;margin-top:24px}.bars{display:grid;gap:18px}.bar-row{display:grid;grid-template-columns:150px 1fr 60px;gap:14px;align-items:center;font-size:.88rem}.bar{height:14px;background:var(--line)}.bar span{display:block;height:100%;background:var(--maroon)}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:13px 8px;border-bottom:1px solid var(--line);font-size:.88rem}th{color:var(--muted);font-size:.75rem;text-transform:uppercase;letter-spacing:.05em}.empty{padding:28px 0;color:var(--muted)}.btn{display:inline-block;background:var(--maroon);color:#fff;text-decoration:none;padding:8px 12px;font-weight:700;font-size:.82rem}@media(max-width:650px){.bar-row{grid-template-columns:1fr 55px;gap:6px}.bar-row .bar{grid-column:1/-1;grid-row:2}table{font-size:.8rem}.wrap{padding:36px 0}}
</style></head><body><header><a class="brand" href="student.php">RankSetu</a><nav class="nav"><a href="student.php">Dashboard</a><a href="logout.php">Log out</a></nav></header><main class="wrap"><h1>Attempt history</h1><p class="muted">Every submitted test is saved separately so you can track progress over time.</p><section class="panel"><h2>Average score by series</h2><?php if ($seriesScores === []): ?><div class="empty">No attempts yet. Start a practice test from your dashboard.</div><?php else: ?><div class="bars"><?php foreach ($seriesScores as $slug => $scoreData): $average = (int) round($scoreData['total'] / $scoreData['count']); ?><div class="bar-row"><strong><?= e($labels[$slug] ?? $slug) ?></strong><div class="bar"><span style="width:<?= $average ?>%"></span></div><strong><?= $average ?>%</strong></div><?php endforeach; ?></div><?php endif; ?></section><section class="panel"><h2>All submitted attempts</h2><?php if ($attempts === []): ?><div class="empty">Your submitted attempts will appear here.</div><?php else: ?><table><thead><tr><th>Test</th><th>Score</th><th>Time</th><th>Date</th><th></th></tr></thead><tbody><?php foreach ($attempts as $attempt): ?><tr><td><?= e($attempt['title']) ?></td><td><?= (int) $attempt['percentage'] ?>% (<?= (int) $attempt['score'] ?>/<?= (int) $attempt['total'] ?>)</td><td><?= floor((int) $attempt['time_taken'] / 60) ?>m <?= (int) $attempt['time_taken'] % 60 ?>s</td><td><?= e(date('d M Y', strtotime((string) $attempt['submitted_at']))) ?></td><td><a class="btn" href="result.php?id=<?= e($attempt['id']) ?>">View</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></section></main></body></html>
