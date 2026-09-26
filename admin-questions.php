<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_admin();

$names = banking_labels();
foreach (series_records() as $record) if (isset($record['slug'], $record['title'])) $names[$record['slug']] = $record['title'];
$slug = (string) ($_GET['series'] ?? $_POST['series'] ?? array_key_first($names));
$testId = (string) ($_GET['test'] ?? $_POST['test'] ?? 'test-01');
if (!preg_match('/^test-[0-9]+$/', $testId)) $testId = 'test-01';
$test = test_record($slug, $testId);
if (!isset($names[$slug]) || !is_array($test)) exit('Test not found.');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $index = (int) ($_POST['index'] ?? -1);
    if (isset($test['questions'][$index])) {
        array_splice($test['questions'], $index, 1);
        if (db_enabled()) save_test_record($slug, $testId, $test); else file_put_contents(__DIR__ . '/tests/' . $slug . '/' . $testId . '.json', json_encode($test, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
    header('Location: admin-questions.php?series=' . rawurlencode($slug) . '&test=' . rawurlencode($testId) . '&notice=Question+deleted');
    exit;
}
$notice = (string) ($_GET['notice'] ?? '');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Questions | RankSetu Admin</title><style>body{margin:0;background:#efece2;font:16px Arial;color:#1c1a15}header{background:#16233f;border-bottom:3px solid #a97a24;padding:18px 5%;display:flex;justify-content:space-between}.brand{font:700 1.5rem Georgia;color:#fff;text-decoration:none}.nav a{color:#f5ead2;text-decoration:none;margin-left:18px}.wrap{width:min(1050px,calc(100% - 40px));margin:auto;padding:42px 0}h1,h2{font-family:Georgia;color:#16233f}.muted{color:#625e50}.toolbar,.question{background:#f8f6ee;border:1px solid #cfc7ac;padding:22px;margin:18px 0}.actions{display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #cfc7ac;padding-top:14px}.btn{display:inline-block;background:#9c3b2e;color:#fff;border:0;padding:11px 15px;font-weight:700;text-decoration:none;cursor:pointer}.outline{background:transparent;color:#9c3b2e;border:1px solid #9c3b2e}.danger{background:#70251c}.notice{padding:13px;background:#e0f0e6;color:#174b32}.topic{color:#9c3b2e;text-transform:uppercase;font-size:.78rem;font-weight:bold}.options{display:grid;grid-template-columns:repeat(2,1fr);gap:8px}.option{padding:10px;background:#fffdf7;border:1px solid #cfc7ac}.correct{background:#e0f0e6;border-color:#286447}@media(max-width:650px){.options{grid-template-columns:1fr}.actions{justify-content:start}}</style></head><body><header><a class="brand" href="admin-tests.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>">RankSetu Admin</a><nav class="nav"><a href="admin-tests.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>">Test sequence</a><a href="admin.php">Control centre</a><a href="logout.php">Log out</a></nav></header><main class="wrap"><div class="muted"><?= e($names[$slug]) ?> / <?= e($testId) ?></div><h1><?= e($test['title'] ?? $testId) ?></h1><?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?><section class="toolbar"><strong><?= count($test['questions'] ?? []) ?> / 10 questions</strong> &bull; <?= e((string) ($test['duration_minutes'] ?? 10)) ?> minutes<br><a class="btn" href="admin-question.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>">Add question</a></section><?php foreach ($test['questions'] as $index => $question): ?><article class="question"><div class="topic">Question <?= $index + 1 ?> &bull; <?= e($question['topic'] ?? '') ?></div><h2><?= e($question['q'] ?? '') ?></h2><div class="options"><?php foreach (($question['options'] ?? []) as $optionIndex => $option): ?><div class="option <?= $optionIndex === (int) ($question['answer'] ?? -1) ? 'correct' : '' ?>"><strong><?= chr(65 + $optionIndex) ?>.</strong> <?= e($option) ?></div><?php endforeach; ?></div><div class="actions"><a class="btn outline" href="admin-question.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>&amp;index=<?= $index ?>">Edit question</a><form method="post" onsubmit="return confirm('Delete this question?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="series" value="<?= e($slug) ?>"><input type="hidden" name="test" value="<?= e($testId) ?>"><input type="hidden" name="index" value="<?= $index ?>"><button class="btn danger" type="submit">Delete question</button></form></div></article><?php endforeach; ?></main></body></html>
