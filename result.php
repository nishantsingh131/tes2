<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$guest = (string) ($_GET['guest'] ?? '') === '1';
$user = $guest ? current_user() : require_login();
$attemptId = (string) ($_GET['id'] ?? '');
$attempt = null;
$savedAttempts = $guest ? $_SESSION['guest_attempts'] ?? [] : $user['attempts'] ?? [];
foreach (array_reverse($savedAttempts) as $savedAttempt) {
    if (($savedAttempt['id'] ?? '') === $attemptId) {
        $attempt = $savedAttempt;
        break;
    }
}
if ($attempt === null) {
    header('Location: ' . ($guest ? 'index.php' : 'student.php'));
    exit;
}
$score = (int) $attempt['score'];
$total = (int) $attempt['total'];
$percentage = (int) $attempt['percentage'];
$timeTaken = (int) $attempt['time_taken'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Result | RankSetu</title><style>:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Arial,sans-serif}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:18px 5%;display:flex;justify-content:space-between;align-items:center}.brand{color:#fff;text-decoration:none;font:700 1.5rem Georgia,serif}.nav a{color:#f5ead2;text-decoration:none;margin-left:18px;font-size:.9rem}.wrap{width:min(900px,calc(100% - 40px));margin:auto;padding:54px 0}.hero{background:var(--navy);color:#fff;padding:32px;border-top:4px solid var(--gold)}.hero h1{font:600 2.6rem Georgia,serif;margin:8px 0;color:#fff}.hero p{color:#ded8c9}.score{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:20px 0}.metric,.panel{background:var(--card);border:1px solid var(--line);padding:24px}.metric strong{display:block;font:700 2rem Georgia,serif;color:var(--maroon)}.metric span{font-size:.82rem;color:var(--muted)}.panel h2{font:600 1.3rem Georgia,serif;color:var(--navy);margin-top:0}.progress{height:13px;background:var(--line);margin:16px 0}.progress span{display:block;height:100%;background:var(--maroon);width:<?= $percentage ?>%}.btn{display:inline-block;background:var(--maroon);color:#fff;text-decoration:none;padding:12px 18px;font-weight:700;margin-right:8px}.secondary{background:transparent;color:var(--maroon);border:1px solid var(--maroon)}.notice{color:var(--muted);font-size:.9rem}@media(max-width:600px){.score{grid-template-columns:1fr}.hero h1{font-size:2.1rem}}
</style></head><body><header><a class="brand" href="<?= $guest ? 'index.php' : 'student.php' ?>">RankSetu</a><nav class="nav"><?php if ($guest): ?><a href="auth.php?mode=signup">Create account</a><?php else: ?><a href="history.php">Attempt history</a><a href="logout.php">Log out</a><?php endif; ?></nav></header><main class="wrap"><section class="hero"><div>TEST RESULT</div><h1><?= e($attempt['title']) ?></h1><p>Submitted <?= e(date('d M Y, h:i A', strtotime((string) $attempt['submitted_at']))) ?><?php if (!empty($attempt['timed_out'])): ?> &bull; Time limit reached<?php endif; ?></p></section><section class="score"><div class="metric"><strong><?= $score ?>/<?= $total ?></strong><span>correct answers</span></div><div class="metric"><strong><?= $percentage ?>%</strong><span>overall score</span></div><div class="metric"><strong><?= floor($timeTaken / 60) ?>m <?= $timeTaken % 60 ?>s</strong><span>time used</span></div></section><section class="panel"><h2>Performance snapshot</h2><div class="progress"><span></span></div><p class="notice"><?= $guest ? 'This free preview result is not saved. Create an account and enroll in a series to preserve future attempt history.' : 'Your attempt has been saved in your personal history. Review your score and keep practicing to improve your accuracy.' ?></p><?php if ($guest): ?><a class="btn" href="instructions.php?sample=1">Try sample again</a><a class="btn secondary" href="auth.php?mode=signup">Create free account</a><?php else: ?><a class="btn" href="practice.php?product=<?= e($attempt['product']) ?>">Try again</a><a class="btn secondary" href="history.php">View history</a><?php endif; ?></section></main></body></html>

