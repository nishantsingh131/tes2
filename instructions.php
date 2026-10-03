<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$catalog = banking_labels();
foreach (series_records() as $record) if (!empty($record['active']) && isset($record['slug'], $record['title'])) $catalog[$record['slug']] = $record['title'];
$sample = (string) ($_GET['sample'] ?? '') === '1';
$questionCount = $sample ? 100 : 10;
$slug = (string) ($_GET['product'] ?? '');
$testId = preg_match('/^test-[0-9]+$/', (string) ($_GET['test'] ?? 'test-01')) === 1 ? (string) ($_GET['test'] ?? 'test-01') : 'test-01';
if ($sample) {
    $title = 'RankSetu Free Sample Mock';
    $testUrl = 'attempt.php?sample=1';
} else {
    $user = require_login();
    if (!isset($catalog[$slug]) || !user_has_course_access($user, $slug)) {
        header('Location: student.php');
        exit;
    }
    $title = $catalog[$slug] . ' ' . strtoupper(str_replace('test-', 'Test ', $testId));
    $testUrl = 'attempt.php?product=' . rawurlencode($slug) . '&test=' . rawurlencode($testId);
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Instructions | RankSetu</title><style>:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}*{box-sizing:border-box}body{margin:0;background:var(--paper);font-family:Arial,sans-serif;color:var(--ink)}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:18px 5%}.brand{color:#fff;text-decoration:none;font:700 1.5rem Georgia,serif}.wrap{width:min(760px,calc(100% - 40px));margin:auto;padding:56px 0}.eyebrow{color:var(--maroon);font-weight:700;letter-spacing:.08em;text-transform:uppercase;font-size:.78rem}h1,h2{font-family:Georgia,serif;color:var(--navy)}h1{font-size:clamp(2rem,5vw,3.2rem);margin:10px 0}.panel{background:var(--card);border:1px solid var(--line);padding:28px;margin-top:24px}.rules{display:grid;gap:12px;margin:20px 0}.rule{border-left:4px solid var(--gold);padding:10px 14px;background:rgba(169,122,36,.1)}.muted{color:var(--muted);line-height:1.6}.btn{display:inline-block;background:var(--maroon);color:#fff;text-decoration:none;padding:13px 20px;font-weight:700}.back{color:var(--maroon);text-decoration:none;margin-left:16px;font-size:.9rem}@media(max-width:600px){.back{display:block;margin:16px 0 0}}
<style>:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}*{box-sizing:border-box}body{margin:0;background:var(--paper);font-family:Arial,sans-serif;color:var(--ink)}header{background:var(--navy);border-bottom:3px solid var(--gold);padding:18px 5%}.brand{color:#fff;text-decoration:none;font:700 1.5rem Georgia,serif}.wrap{width:min(760px,calc(100% - 40px));margin:auto;padding:56px 0}.eyebrow{color:var(--maroon);font-weight:700;letter-spacing:.08em;text-transform:uppercase;font-size:.78rem}h1,h2{font-family:Georgia,serif;color:var(--navy)}h1{font-size:clamp(2rem,5vw,3.2rem);margin:10px 0}.panel{background:var(--card);border:1px solid var(--line);padding:28px;margin-top:24px}.rules{display:grid;gap:12px;margin:20px 0}.rule{border-left:4px solid var(--gold);padding:10px 14px;background:rgba(169,122,36,.1)}.muted{color:var(--muted);line-height:1.6}.btn{display:inline-block;background:var(--maroon);color:#fff;text-decoration:none;padding:13px 20px;font-weight:700}.back{color:var(--maroon);text-decoration:none;margin-left:16px;font-size:.9rem}@media(max-width:600px){.back{display:block;margin:16px 0 0}}
</style></head><body><header><a class="brand" href="<?= $sample ? 'index.php' : 'student.php' ?>">RankSetu</a></header><main class="wrap"><div class="eyebrow">Before you begin</div><h1><?= e($sample ? 'SBI PO Prelims Hard Level Practice Paper' : $title) ?></h1><p class="muted">Read the instructions carefully. The timer starts only after you press Start test.</p><section class="panel"><h2>Test instructions</h2><div class="rules"><div class="rule"><?= $sample ? '100 multiple-choice questions: Reasoning (30), Quantitative Aptitude (30), English Language (40). Total time: 60 minutes.' : 'There are ' . $questionCount . ' multiple-choice questions.' ?></div><div class="rule">Each question has one correct answer. Unanswered questions receive zero marks.</div><div class="rule">The timer cannot be paused. The test submits automatically when time ends.</div><div class="rule"><?= $sample ? 'This free sample result is shown now but is not saved to history.' : 'Your score, answers, time and submission date will be saved in your attempt history.' ?></div></div><a class="btn" href="<?= e($testUrl) ?>">Start test</a><a class="back" href="<?= $sample ? 'index.php' : 'practice.php?product=' . e($slug) ?>">Go back</a></section></main></body></html>

