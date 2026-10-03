<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_admin();
$available = banking_labels();
foreach (array_column(series_records(), 'title', 'slug') as $slug => $title) $available[$slug] = $title;
$slug = (string) ($_GET['series'] ?? $_POST['series'] ?? array_key_first($available));
$index = isset($_GET['index']) ? (int) $_GET['index'] : (int) ($_POST['index'] ?? -1);
$requestedTest = (string) ($_GET['test'] ?? $_POST['test'] ?? 'test-01');
$testId = preg_match('/^test-[0-9]+$/', $requestedTest) === 1 ? $requestedTest : 'test-01';
if (!isset($available[$slug])) exit('Series not found.');
$data = test_record($slug, $testId);
if (!is_array($data)) exit('Test not found.');
$formError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $options = array_map(static fn(int $option): string => trim((string) ($_POST['option_' . $option] ?? '')), range(0, 4));
    while (count($options) > 4 && end($options) === '') array_pop($options);
    $question = [
        'q' => trim((string) ($_POST['question'] ?? '')),
        'options' => $options,
        'answer' => filter_var($_POST['answer'] ?? null, FILTER_VALIDATE_INT),
        'topic' => trim((string) ($_POST['topic'] ?? '')),
        'section' => trim((string) ($_POST['section'] ?? '')),
        'direction' => trim((string) ($_POST['direction'] ?? '')),
    ];
    $optionsAreValid = count($options) >= 4 && count($options) <= 5;
    foreach ($options as $option) $optionsAreValid = $optionsAreValid && $option !== '';
    if ($question['q'] === '' || $question['topic'] === '' || !$optionsAreValid || $question['answer'] === false || $question['answer'] < 0 || $question['answer'] >= count($options)) {
        $formError = 'Enter the question, topic and at least four complete answer choices, then select a correct answer that exists.';
    } else {
        if ($index >= 0 && isset($data['questions'][$index])) $data['questions'][$index] = $question; else $data['questions'][] = $question;
        if (db_enabled()) save_test_record($slug, $testId, $data); else write_test_record_file($slug, $testId, $data);
        header('Location: admin-tests.php?series=' . rawurlencode($slug) . '&test=' . rawurlencode($testId) . '&notice=' . rawurlencode('Question saved.'));
        exit;
    }
}
$isEditing = $index >= 0 && isset($data['questions'][$index]);
$current = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? $question
    : ($isEditing ? $data['questions'][$index] : ['q' => '', 'options' => ['', '', '', '', ''], 'answer' => 0, 'topic' => '', 'section' => '', 'direction' => '']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isEditing ? 'Edit' : 'Add' ?> question | RankSetu Admin</title>
    <style>
        :root {
            color-scheme: light;
            --navy: #16233f;
            --navy-soft: #243a64;
            --maroon: #9c3b2e;
            --maroon-dark: #7f2e24;
            --paper: #f3f1e9;
            --card: #fffefa;
            --ink: #24231f;
            --muted: #68675f;
            --line: #dedbcc;
            --gold: #a97a24;
            --focus: #356ac3;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--paper);
            color: var(--ink);
            font: 16px/1.55 system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        a { color: var(--maroon); }
        .topbar {
            background: var(--navy);
            border-bottom: 3px solid var(--gold);
            color: #fff;
        }
        .topbar-inner {
            width: min(1080px, calc(100% - 40px));
            min-height: 68px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .brand {
            color: #fff;
            font: 700 1.25rem Georgia, serif;
            text-decoration: none;
            white-space: nowrap;
        }
        .nav { display: flex; flex-wrap: wrap; gap: 8px 22px; }
        .nav a { color: #f5ead2; font-size: .9rem; text-decoration: none; }
        .nav a:hover, .nav a:focus-visible { color: #fff; text-decoration: underline; }
        .wrap {
            width: min(820px, calc(100% - 40px));
            margin: 0 auto;
            padding: 34px 0 56px;
        }
        .breadcrumbs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
            color: var(--muted);
            font-size: .9rem;
        }
        .breadcrumbs a { text-decoration: none; }
        .breadcrumbs a:hover { text-decoration: underline; }
        .editor {
            overflow: hidden;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 12px 34px rgba(22, 35, 63, .08);
        }
        .editor-heading {
            padding: 28px 32px 24px;
            background: linear-gradient(135deg, #fffefa, #f7f4e9);
            border-bottom: 1px solid var(--line);
        }
        .eyebrow {
            margin: 0 0 6px;
            color: var(--maroon);
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        h1 {
            margin: 0;
            color: var(--navy);
            font: 700 clamp(1.75rem, 4vw, 2.35rem)/1.2 Georgia, serif;
        }
        .intro { margin: 10px 0 0; color: var(--muted); }
        .context {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }
        .context span {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 4px 10px;
            background: #f0eee5;
            border: 1px solid #e3dfd1;
            border-radius: 999px;
            color: #45443e;
            font-size: .82rem;
            font-weight: 650;
        }
        form { padding: 28px 32px 30px; }
        .field { margin-bottom: 22px; }
        .form-error {
            margin: 0 32px;
            padding: 13px 15px;
            border: 1px solid #e4b3aa;
            border-radius: 8px;
            background: #fff1ee;
            color: #70251c;
        }
        .field label, .section-label {
            display: block;
            margin-bottom: 7px;
            color: var(--navy);
            font-size: .94rem;
            font-weight: 750;
        }
        .hint { margin: -2px 0 9px; color: var(--muted); font-size: .84rem; }
        input, textarea, select {
            display: block;
            width: 100%;
            min-height: 48px;
            padding: 11px 13px;
            border: 1px solid #c9c6b9;
            border-radius: 8px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        textarea { min-height: 132px; resize: vertical; }
        input::placeholder, textarea::placeholder { color: #89877e; }
        input:focus-visible, textarea:focus-visible, select:focus-visible {
            border-color: var(--focus);
            outline: 3px solid rgba(53, 106, 195, .2);
            outline-offset: 1px;
        }
        .options-heading {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }
        .options-heading .section-label { margin: 0; }
        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .option-field {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            padding: 10px;
            background: #faf9f4;
            border: 1px solid var(--line);
            border-radius: 10px;
        }
        .option-letter {
            display: grid;
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            place-items: center;
            border-radius: 8px;
            background: #e9edf5;
            color: var(--navy);
            font-weight: 800;
        }
        .option-field input { min-width: 0; }
        .answer-field {
            padding: 16px;
            background: #f5f7fb;
            border: 1px solid #dce2ee;
            border-radius: 10px;
        }
        .answer-field label { margin-bottom: 8px; }
        .answer-field select { max-width: 300px; }
        .form-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid var(--line);
        }
        .btn {
            display: inline-flex;
            min-height: 46px;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border: 1px solid transparent;
            border-radius: 8px;
            font: inherit;
            font-weight: 750;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary { background: var(--maroon); color: #fff; }
        .btn-primary:hover { background: var(--maroon-dark); }
        .btn-secondary { border-color: var(--line); background: #fff; color: var(--navy); }
        .btn-secondary:hover { background: #f5f4ee; }
        .btn:focus-visible {
            outline: 3px solid rgba(53, 106, 195, .35);
            outline-offset: 2px;
        }
        @media (max-width: 600px) {
            .topbar-inner {
                width: min(100% - 28px, 820px);
                min-height: 0;
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
                padding: 14px 0;
            }
            .nav { gap: 8px 16px; }
            .wrap {
                width: min(100% - 24px, 820px);
                padding: 20px 0 36px;
            }
            .editor-heading { padding: 22px 20px 20px; }
            .form-error { margin: 0 20px; }
            form { padding: 22px 20px 24px; }
            .options-grid { grid-template-columns: 1fr; gap: 9px; }
            .options-heading { align-items: flex-start; flex-direction: column; gap: 2px; }
            .answer-field select { max-width: none; }
            .form-actions { align-items: stretch; flex-direction: column; }
            .form-actions .btn { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="admin.php">RankSetu Admin</a>
            <nav class="nav" aria-label="Admin navigation">
                <a href="admin-tests.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>">Test sequence</a>
                <a href="admin-questions.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>">All questions</a>
                <a href="logout.php">Log out</a>
            </nav>
        </div>
    </header>
    <main class="wrap">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="admin-tests.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>">Test sequence</a>
            <span aria-hidden="true">/</span>
            <a href="admin-questions.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>">Questions</a>
            <span aria-hidden="true">/</span>
            <span><?= $isEditing ? 'Edit question' : 'Add question' ?></span>
        </nav>
        <section class="editor" aria-labelledby="page-title">
            <div class="editor-heading">
                <p class="eyebrow"><?= $isEditing ? 'Update question' : 'Build your test' ?></p>
                <h1 id="page-title"><?= $isEditing ? 'Edit question' : 'Add a question' ?></h1>
                <p class="intro">Write the question, add four answer choices, then choose the correct answer.</p>
                <div class="context" aria-label="Test details">
                    <span><?= e($available[$slug]) ?></span>
                    <span><?= e($testId) ?></span>
                    <span><?= count($data['questions'] ?? []) ?> questions in this test</span>
                </div>
            </div>
            <?php if ($formError !== ''): ?>
                <p class="form-error" role="alert"><?= e($formError) ?></p>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="series" value="<?= e($slug) ?>">
                <input type="hidden" name="test" value="<?= e($testId) ?>">
                <input type="hidden" name="index" value="<?= $index ?>">

                <div class="field">
                    <label for="section">Section</label>
                    <p class="hint" id="section-hint">For example: English Language or Quantitative Aptitude.</p>
                    <input id="section" name="section" value="<?= e($current['section'] ?? '') ?>" placeholder="Enter a section" aria-describedby="section-hint">
                </div>

                <div class="field">
                    <label for="direction">Directions or shared passage</label>
                    <p class="hint" id="direction-hint">Use this for instructions shared by a group of questions. Mathematical symbols such as √ are supported.</p>
                    <textarea id="direction" name="direction" placeholder="Enter the directions or passage for this question group" aria-describedby="direction-hint"><?= e($current['direction'] ?? '') ?></textarea>
                </div>

                <div class="field">
                    <label for="question">Question text</label>
                    <p class="hint" id="question-hint">Keep the wording clear and include any details needed to answer.</p>
                    <textarea id="question" name="question" placeholder="Type your question here…" aria-describedby="question-hint" required><?= e($current['q'] ?? '') ?></textarea>
                </div>

                <div class="field">
                    <div class="options-heading">
                        <span class="section-label" id="options-label">Answer choices</span>
                        <span class="hint">Complete A–D; add E when the question has five choices.</span>
                    </div>
                    <div class="options-grid" role="group" aria-labelledby="options-label">
                        <?php for ($option = 0; $option < 5; $option++): ?>
                            <div class="option-field">
                                <span class="option-letter" aria-hidden="true"><?= chr(65 + $option) ?></span>
                                <input aria-label="Option <?= chr(65 + $option) ?>" name="option_<?= $option ?>" value="<?= e(preg_replace('/^\s*(?:\([A-E]\)|[A-E][.)])\s*/i', '', (string) ($current['options'][$option] ?? ''))) ?>" placeholder="Answer choice <?= chr(65 + $option) ?>" <?= $option < 4 ? 'required' : '' ?>>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="field answer-field">
                    <label for="answer">Correct answer</label>
                    <p class="hint" id="answer-hint">Select which answer choice is correct.</p>
                    <select id="answer" name="answer" aria-describedby="answer-hint">
                        <?php for ($option = 0; $option < 5; $option++): ?>
                            <option value="<?= $option ?>" <?= $option === 4 && trim((string) ($current['options'][4] ?? '')) === '' ? 'disabled' : '' ?> <?= (int) ($current['answer'] ?? 0) === $option ? 'selected' : '' ?>>Option <?= chr(65 + $option) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="topic">Topic or subject</label>
                    <p class="hint" id="topic-hint">For example: Reasoning, Quantitative Aptitude, or English.</p>
                    <input id="topic" name="topic" value="<?= e($current['topic'] ?? '') ?>" placeholder="Enter a topic" aria-describedby="topic-hint" required>
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit"><?= $isEditing ? 'Save changes' : 'Save question' ?></button>
                    <a class="btn btn-secondary" href="admin-questions.php?series=<?= e($slug) ?>&amp;test=<?= e($testId) ?>">Cancel</a>
                </div>
            </form>
        </section>
    </main>
    <script>
        const fifthOption = document.querySelector('[name="option_4"]');
        const fifthAnswer = document.querySelector('#answer option[value="4"]');
        if (fifthOption && fifthAnswer) {
            const updateFifthAnswer = () => { fifthAnswer.disabled = fifthOption.value.trim() === ''; };
            fifthOption.addEventListener('input', updateFifthAnswer);
            updateFifthAnswer();
        }
    </script>
</body>
</html>
