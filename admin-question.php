<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_admin();
$available = [];
foreach (array_merge(['ssc-cgl' => 'SSC CGL', 'ssc-chsl' => 'SSC CHSL', 'rrb-ntpc' => 'RRB NTPC', 'rrb-group-d' => 'RRB Group D', 'ibps-po' => 'IBPS PO', 'sbi-clerk' => 'SBI Clerk', 'bpsc-prelims' => 'BPSC Prelims', 'state-psc-mains' => 'State PSC Mains', 'nda-cds' => 'NDA & CDS', 'ctet' => 'CTET'], array_column(series_records(), 'title', 'slug')) as $slug => $title) $available[$slug] = $title;
$slug = (string) ($_GET['series'] ?? $_POST['series'] ?? array_key_first($available));
$index = isset($_GET['index']) ? (int) $_GET['index'] : (int) ($_POST['index'] ?? -1);
$requestedTest = (string) ($_GET['test'] ?? $_POST['test'] ?? 'test-01');
$testId = preg_match('/^test-[0-9]+$/', $requestedTest) === 1 ? $requestedTest : 'test-01';
$file = __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . $testId . '.json';
if (!isset($available[$slug]) || !is_file($file)) exit('Series not found.');
$data = json_decode((string) file_get_contents($file), true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = ['q' => trim((string) $_POST['question']), 'options' => array_map('trim', [(string) $_POST['option_0'], (string) $_POST['option_1'], (string) $_POST['option_2'], (string) $_POST['option_3']]), 'answer' => max(0, min(3, (int) $_POST['answer'])), 'topic' => trim((string) $_POST['topic'])];
    if ($index >= 0 && isset($data['questions'][$index])) $data['questions'][$index] = $question; else $data['questions'][] = $question;
    if (count($data['questions']) > 10) exit('A test can contain a maximum of 10 questions.');
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    header('Location: admin-tests.php?series=' . rawurlencode($slug) . '&test=' . rawurlencode($testId) . '&notice=' . rawurlencode('Question saved.'));
    exit;
}
$current = ($index >= 0 && isset($data['questions'][$index])) ? $data['questions'][$index] : ['q' => '', 'options' => ['', '', '', ''], 'answer' => 0, 'topic' => ''];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Question editor | RankSetu</title><style>body{margin:0;background:#efece2;font-family:Arial;color:#1c1a15}.wrap{width:min(760px,calc(100% - 40px));margin:auto;padding:48px 0}.panel{background:#f8f6ee;border:1px solid #cfc7ac;padding:28px}h1{font:600 2.2rem Georgia;color:#16233f}label{display:block;font-weight:700;font-size:.8rem;margin:14px 0 5px}input,textarea,select{width:100%;box-sizing:border-box;padding:11px;border:1px solid #cfc7ac;background:#fffdf7;font:inherit}textarea{min-height:100px}.btn{display:inline-block;border:0;background:#9c3b2e;color:white;padding:12px 18px;font-weight:700;margin-top:20px;text-decoration:none;cursor:pointer}.back{color:#9c3b2e;text-decoration:none;margin-left:12px}</style></head><body><main class="wrap"><section class="panel"><h1><?= $index >= 0 ? 'Edit' : 'Add' ?> question</h1><p>Series: <?= e($available[$slug]) ?></p><form method="post"><input type="hidden" name="series" value="<?= e($slug) ?>"><input type="hidden" name="index" value="<?= $index ?>"><label>Question</label><textarea name="question" required><?= e($current['q']) ?></textarea><?php for ($option = 0; $option < 4; $option++): ?><label>Option <?= chr(65 + $option) ?></label><input name="option_<?= $option ?>" value="<?= e($current['options'][$option] ?? '') ?>" required><?php endfor; ?><label>Correct option</label><select name="answer"><?php for ($option = 0; $option < 4; $option++): ?><option value="<?= $option ?>" <?= (int) $current['answer'] === $option ? 'selected' : '' ?>><?= chr(65 + $option) ?></option><?php endfor; ?></select><label>Topic</label><input name="topic" value="<?= e($current['topic']) ?>" required><button class="btn" type="submit">Save question</button><a class="back" href="admin.php?series=<?= e($slug) ?>">Back to admin</a></form></section></main></body></html>
