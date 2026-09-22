<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$catalog = ['ssc-cgl' => 'SSC CGL', 'ssc-chsl' => 'SSC CHSL', 'rrb-ntpc' => 'RRB NTPC', 'rrb-group-d' => 'RRB Group D', 'ibps-po' => 'IBPS PO', 'sbi-clerk' => 'SBI Clerk', 'bpsc-prelims' => 'BPSC Prelims', 'state-psc-mains' => 'State PSC Mains', 'nda-cds' => 'NDA & CDS', 'ctet' => 'CTET'];
foreach (series_records() as $record) if (!empty($record['active']) && isset($record['slug'], $record['title'])) $catalog[$record['slug']] = $record['title'];
$sample = (string) ($_GET['sample'] ?? $_POST['sample'] ?? '') === '1';
$user = $sample ? current_user() : require_login();
$slug = (string) ($_GET['product'] ?? $_POST['product'] ?? '');
$requestedTest = (string) ($_GET['test'] ?? $_POST['test'] ?? 'test-01');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['test']) && !$sample) {
    foreach (array_keys($_SESSION['active_attempts'] ?? []) as $activeKey) {
        if (str_starts_with($activeKey, $slug . ':')) {
            $requestedTest = substr($activeKey, strlen($slug) + 1);
            break;
        }
    }
}
$testId = preg_match('/^test-[0-9]+$/', $requestedTest) === 1 ? $requestedTest : 'test-01';
$testSlug = $sample ? 'sample' : $slug;
$testFile = __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . $testSlug . DIRECTORY_SEPARATOR . $testId . '.json';
if ((!$sample && (!isset($catalog[$slug]) || !in_array($slug, $user['enrolled'] ?? [], true))) || !is_file($testFile)) {
    header('Location: student.php');
    exit;
}
$test = json_decode((string) file_get_contents($testFile), true);
if (!is_array($test) || !isset($test['questions']) || count($test['questions']) !== 10) {
    http_response_code(500);
    exit('Test data is unavailable.');
}
$attemptKey = ($sample ? 'guest-sample' : $slug) . ':' . $testId;
if (!isset($_SESSION['active_attempts'][$attemptKey]) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['active_attempts'][$attemptKey] = time();
}
$startedAt = (int) ($_SESSION['active_attempts'][$attemptKey] ?? $_POST['started_at'] ?? time());
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = [];
    $correct = 0;
    foreach ($test['questions'] as $index => $question) {
        $value = $_POST['answer_' . $index] ?? '';
        $answer = is_numeric($value) ? (int) $value : null;
        $answers[] = $answer;
        if ($answer !== null && $answer === (int) $question['answer']) {
            $correct++;
        }
    }
    $elapsed = max(1, time() - $startedAt);
    $durationSeconds = ((int) $test['duration_minutes']) * 60;
    $timedOut = $elapsed > $durationSeconds;
    $attempt = [
        'id' => bin2hex(random_bytes(8)),
        'product' => $slug,
        'test_id' => $testId,
        'title' => $test['title'],
        'score' => $correct,
        'total' => count($test['questions']),
        'percentage' => (int) round(($correct / count($test['questions'])) * 100),
        'answers' => $answers,
        'time_taken' => min($elapsed, $durationSeconds),
        'timed_out' => $timedOut,
        'submitted_at' => date('c'),
    ];
    if ($sample) {
        $_SESSION['guest_attempts'][$attempt['id']] = $attempt;
    } else {
        $history = $user['attempts'] ?? [];
        $history[] = $attempt;
        update_current_user(['attempts' => $history]);
    }
    unset($_SESSION['active_attempts'][$attemptKey]);
    header('Location: result.php?id=' . rawurlencode($attempt['id']) . ($sample ? '&guest=1' : ''));
    exit;
}
$durationSeconds = ((int) $test['duration_minutes']) * 60;
$remaining = max(1, $durationSeconds - (time() - $startedAt));
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($test['title']) ?> | RankSetu</title><style>:root{--navy:#16233f;--maroon:#9c3b2e;--paper:#efece2;--card:#f8f6ee;--ink:#1c1a15;--muted:#625e50;--line:#cfc7ac;--gold:#a97a24}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Arial,sans-serif}header{background:var(--navy);border-bottom:3px solid var(--gold);color:#fff;padding:18px 5%;display:flex;justify-content:space-between;align-items:center}.brand{color:#fff;text-decoration:none;font:700 1.5rem Georgia,serif}.timer{font:700 1.1rem monospace;color:#f3c45b}.wrap{width:min(850px,calc(100% - 40px));margin:auto;padding:40px 0}h1,h2{font-family:Georgia,serif;color:var(--navy)}h1{margin:8px 0}.muted{color:var(--muted)}.question{background:var(--card);border:1px solid var(--line);padding:24px;margin:16px 0}.question h2{font-size:1.1rem;margin:0 0 14px}.topic{color:var(--maroon);font-size:.76rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em}.option{display:block;border:1px solid var(--line);background:#fffdf7;padding:12px;margin:9px 0;cursor:pointer}.option:hover{border-color:var(--maroon)}.option input{margin-right:10px}.submit{background:var(--maroon);color:#fff;border:0;padding:14px 22px;font-weight:700;font-size:1rem;cursor:pointer}.submit:hover{background:#832f24}.bar{height:6px;background:var(--line);margin:20px 0}.bar span{display:block;height:100%;background:var(--gold);width:10%}@media(max-width:600px){.wrap{padding:28px 0}.timer{font-size:.9rem}}
</style></head>
<body><header><a class="brand" href="<?= $sample ? 'index.php' : 'student.php' ?>">RankSetu</a><div class="timer" id="timer" aria-live="polite">--:--</div></header><main class="wrap"><div class="muted"><?= e($sample ? 'Free sample' : $catalog[$slug]) ?> &bull; Test 01 &bull; 10 questions</div><h1><?= e($test['title']) ?></h1><p class="muted"><?= $sample ? 'This is a free preview. Your result will be shown now but will not be saved.' : 'Choose one answer for each question. Your result and attempt history will be saved after submission.' ?></p><div class="bar"><span></span></div><form method="post" id="test-form"><input type="hidden" name="product" value="<?= e($slug) ?>"><input type="hidden" name="sample" value="<?= $sample ? '1' : '0' ?>"><input type="hidden" name="started_at" value="<?= $startedAt ?>"><?php foreach ($test['questions'] as $index => $question): ?><section class="question"><div class="topic"><?= e($question['topic']) ?> &bull; Question <?= $index + 1 ?></div><h2><?= e($question['q']) ?></h2><?php foreach ($question['options'] as $optionIndex => $option): ?><label class="option"><input type="radio" name="answer_<?= $index ?>" value="<?= $optionIndex ?>"> <?= e($option) ?></label><?php endforeach; ?></section><?php endforeach; ?><button class="submit" type="submit">Submit test</button></form></main><script>const timer=document.getElementById('timer');let remaining=<?= $remaining ?>;const form=document.getElementById('test-form');function tick(){const minutes=Math.floor(remaining/60).toString().padStart(2,'0');const seconds=(remaining%60).toString().padStart(2,'0');timer.textContent=minutes+':'+seconds;if(remaining<=0){form.submit();return}remaining-=1;setTimeout(tick,1000)}tick();</script></body></html>

