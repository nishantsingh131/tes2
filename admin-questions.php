<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_admin();

$names = banking_labels();
foreach (series_records() as $record) {
    if (isset($record['slug'], $record['title'])) $names[$record['slug']] = $record['title'];
}
$slug = (string) ($_GET['series'] ?? $_POST['series'] ?? array_key_first($names));
$testId = (string) ($_GET['test'] ?? $_POST['test'] ?? 'test-01');
if (!preg_match('/^test-[0-9]+$/', $testId)) $testId = 'test-01';
$test = test_record($slug, $testId);
if (!isset($names[$slug]) || !is_array($test)) exit('Test not found.');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $index = (int) ($_POST['index'] ?? -1);
    if (isset($test['questions'][$index])) {
        array_splice($test['questions'], $index, 1);
        if (db_enabled()) {
            save_test_record($slug, $testId, $test);
        } else {
            write_test_record_file($slug, $testId, $test);
        }
    }
    header('Location: admin-questions.php?series=' . rawurlencode($slug) . '&test=' . rawurlencode($testId) . '&notice=Question+deleted');
    exit;
}

$notice = (string) ($_GET['notice'] ?? '');
$questions = $test['questions'] ?? [];
$lastSection = '';
$lastDirection = '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Questions | RankSetu Admin</title>
    <style>
        :root {
            color-scheme: light;
            --navy: #16233f;
            --maroon: #9c3b2e;
            --paper: #efece2;
            --card: #fffefa;
            --ink: #1c1a15;
            --muted: #625e50;
            --line: #cfc7ac;
            --gold: #a97a24;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font: 16px/1.55 system-ui, sans-serif; }
        header { background: var(--navy); border-bottom: 3px solid var(--gold); padding: 17px 5%; display: flex; justify-content: space-between; gap: 18px; }
        .brand { color: #fff; font: 700 1.4rem Georgia, serif; text-decoration: none; }
        .nav { display: flex; flex-wrap: wrap; gap: 18px; }
        .nav a { color: #f5ead2; text-decoration: none; font-size: .9rem; }
        .nav a:hover, .nav a:focus-visible { color: #fff; text-decoration: underline; }
        .wrap { width: min(1050px, calc(100% - 36px)); margin: auto; padding: 38px 0 64px; }
        h1, h2, h3 { color: var(--navy); font-family: Georgia, serif; }
        h1 { margin: 6px 0; font-size: clamp(2rem, 4vw, 2.8rem); }
        .muted { color: var(--muted); }
        .toolbar, .question { margin: 18px 0; padding: 22px; background: var(--card); border: 1px solid var(--line); border-radius: 10px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .btn { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; padding: 9px 14px; border: 1px solid transparent; border-radius: 6px; background: var(--maroon); color: #fff; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .btn:hover { background: #7f2e24; }
        .outline { border-color: var(--maroon); background: transparent; color: var(--maroon); }
        .outline:hover { background: #f8eee9; }
        .danger { background: #70251c; }
        .notice { margin: 14px 0; padding: 13px; background: #e0f0e6; color: #174b32; }
        .section-heading { margin: 34px 0 8px; padding-bottom: 8px; border-bottom: 2px solid var(--gold); }
        .direction { margin: 12px 0; padding: 16px 18px; border-left: 4px solid var(--gold); background: #fffefa; line-height: 1.65; }
        .question-meta { color: var(--maroon); font-size: .78rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .question h3 { margin: 8px 0 16px; font-size: 1.1rem; }
        .question h3 br { display: block; margin: 0 0 8px; content: ""; }
        .options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
        .option { display: flex; gap: 10px; padding: 11px; border: 1px solid var(--line); border-radius: 6px; background: #fffdf7; }
        .option-letter { flex: 0 0 auto; color: var(--navy); font-weight: 800; }
        .correct { border-color: #286447; background: #e0f0e6; }
        .actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 9px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--line); }
        form { margin: 0; }
        @media (max-width: 650px) {
            header { align-items: flex-start; flex-direction: column; }
            .wrap { width: min(100% - 24px, 1050px); padding: 22px 0 40px; }
            .toolbar { align-items: flex-start; flex-direction: column; }
            .options { grid-template-columns: 1fr; }
            .actions { justify-content: flex-start; }
        }
    </style>
</head>
<body>
    <header>
        <a class="brand" href="admin.php">RankSetu Admin</a>
        <nav class="nav" aria-label="Admin navigation">
            <a href="admin-tests.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>">Test sequence</a>
            <a href="admin.php">Control centre</a>
            <a href="logout.php">Log out</a>
        </nav>
    </header>
    <main class="wrap">
        <div class="muted"><?= e($names[$slug]) ?> / <?= e($testId) ?></div>
        <h1><?= e($test['title'] ?? $testId) ?></h1>
        <p class="muted">Questions are grouped by section; shared directions appear once before their question group.</p>
        <?php if ($notice !== ''): ?><div class="notice" role="status"><?= e($notice) ?></div><?php endif; ?>
        <section class="toolbar" aria-label="Test summary">
            <div><strong><?= count($questions) ?> questions</strong> &bull; <?= e((string) ($test['duration_minutes'] ?? 10)) ?> minutes</div>
            <a class="btn" href="admin-question.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>">Add question</a>
        </section>
        <?php foreach ($questions as $index => $question): ?>
            <?php
            $section = trim((string) ($question['section'] ?? ''));
            $direction = trim((string) ($question['direction'] ?? ''));
            ?>
            <?php if ($section !== '' && $section !== $lastSection): ?>
                <h2 class="section-heading"><?= e($section) ?></h2>
            <?php endif; ?>
            <?php if ($direction !== '' && ($direction !== $lastDirection || $section !== $lastSection)): ?>
                <div class="direction"><strong>Directions</strong><br><?= nl2br(e($direction)) ?></div>
            <?php endif; ?>
            <article class="question">
                <div class="question-meta">Question <?= $index + 1 ?> &bull; <?= e($question['topic'] ?? '') ?></div>
                <h3><?= format_question_text((string) ($question['q'] ?? '')) ?></h3>
                <div class="options">
                    <?php foreach (($question['options'] ?? []) as $optionIndex => $option): ?>
                        <div class="option <?= $optionIndex === (int) ($question['answer'] ?? -1) ? 'correct' : '' ?>">
                            <span class="option-letter"><?= chr(65 + $optionIndex) ?>.</span>
                            <span><?= e(preg_replace('/^\s*(?:\([A-E]\)|[A-E][.)])\s*/i', '', (string) $option)) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="actions">
                    <a class="btn outline" href="admin-question.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>&amp;index=<?= $index ?>">Edit question</a>
                    <form method="post" onsubmit="return confirm('Delete this question?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="series" value="<?= e($slug) ?>">
                        <input type="hidden" name="test" value="<?= e($testId) ?>">
                        <input type="hidden" name="index" value="<?= $index ?>">
                        <button class="btn danger" type="submit">Delete question</button>
                    </form>
                </div>
            </article>
            <?php $lastSection = $section; $lastDirection = $direction; ?>
        <?php endforeach; ?>
    </main>
</body>
</html>
