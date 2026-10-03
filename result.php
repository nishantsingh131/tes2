<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'attempt-review.php';
$guest = (string) ($_GET['guest'] ?? '') === '1';
$user = $guest ? current_user() : require_login();
$attemptId = (string) ($_GET['id'] ?? '');
$savedAttempts = $guest
    ? ($_SESSION['guest_attempts'] ?? [])
    : (is_array($user) && is_array($user['attempts'] ?? null) ? $user['attempts'] : []);
$attempt = null;
if (is_array($savedAttempts)) {
    foreach (array_reverse($savedAttempts) as $savedAttempt) {
        if (is_array($savedAttempt) && (string) ($savedAttempt['id'] ?? '') === $attemptId) {
            $attempt = $savedAttempt;
            break;
        }
    }
}
if ($attempt === null) {
    header('Location: ' . ($guest ? 'index.php' : 'student.php'));
    exit;
}
header('X-Robots-Tag: noindex, nofollow');

$score = max(0, (int) ($attempt['score'] ?? 0));
$total = max(0, (int) ($attempt['total'] ?? 0));
$score = min($score, $total);
$percentage = max(0, min(100, (int) ($attempt['percentage'] ?? 0)));
$timeTaken = max(0, (int) ($attempt['time_taken'] ?? 0));
$submittedTimestamp = strtotime((string) ($attempt['submitted_at'] ?? ''));
$submittedLabel = $submittedTimestamp === false ? 'Date unavailable' : date('d M Y, h:i A', $submittedTimestamp);
$reviewQuestions = attempt_review_questions(
    $attempt,
    !$guest && is_array($user) ? (string) ($user['id'] ?? '') : '',
    $guest
);
$reviewSummary = ['correct' => 0, 'wrong' => 0, 'unanswered' => 0];
if (is_array($reviewQuestions)) {
    foreach ($reviewQuestions as $reviewQuestion) {
        if (!is_array($reviewQuestion) || ($reviewQuestion['selected'] ?? null) === null) {
            $reviewSummary['unanswered']++;
        } elseif (!empty($reviewQuestion['is_correct'])) {
            $reviewSummary['correct']++;
        } else {
            $reviewSummary['wrong']++;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test result and answer review | <?= e(APP_BRAND_NAME) ?></title>
    <style>
        :root{--navy:#16233f;--maroon:#9c3b2e;--paper:#f5f3ed;--card:#fffefa;--ink:#1c2432;--muted:#586272;--line:#e1e4e9;--green:#286447;--red:#9c3b2e;--gold:#e7b64e}
        *{box-sizing:border-box}
        body{margin:0;background:var(--paper);color:var(--ink);font-family:Arial,Helvetica,sans-serif}
        .wrap{width:min(920px,calc(100% - 40px));margin:auto;padding:38px 0 64px}
        .hero{padding:clamp(22px,4vw,34px);border-radius:16px;background:linear-gradient(125deg,var(--navy),#243a64);color:#fff}
        .eyebrow{margin:0;color:#f3c45b;font-size:.75rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        .hero h1{margin:9px 0;color:#fff;font:700 clamp(1.75rem,4vw,2.5rem)/1.15 Georgia,"Times New Roman",serif;overflow-wrap:anywhere}
        .hero p{margin:8px 0 0;color:#e3e8f0;line-height:1.5}
        .score{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:16px 0}
        .metric,.panel{min-width:0;border:1px solid var(--line);border-radius:12px;background:var(--card);padding:20px}
        .metric strong{display:block;color:var(--navy);font:700 clamp(1.45rem,3vw,2rem)/1.2 Georgia,"Times New Roman",serif;overflow-wrap:anywhere}
        .metric span{color:var(--muted);font-size:.84rem}
        .panel h2{margin:0;color:var(--navy);font:700 1.35rem/1.25 Georgia,"Times New Roman",serif}
        .progress{height:10px;margin:16px 0;border-radius:999px;background:var(--line);overflow:hidden}
        .progress span{display:block;height:100%;border-radius:inherit;background:var(--maroon);width:<?= $percentage ?>%}
        .notice{color:var(--muted);font-size:.92rem;line-height:1.6}
        .actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}
        .btn{display:inline-flex;min-height:44px;align-items:center;justify-content:center;padding:10px 16px;border:2px solid var(--maroon);border-radius:7px;background:var(--maroon);color:#fff;font-size:.9rem;font-weight:800;text-align:center;text-decoration:none}
        .btn.secondary{background:transparent;color:var(--maroon)}
        .review-section{margin-top:28px}
        .review-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:14px}
        .review-heading h2{margin:0;color:var(--navy);font:700 clamp(1.4rem,3vw,1.8rem)/1.2 Georgia,"Times New Roman",serif}
        .review-heading p{margin:5px 0 0;color:var(--muted);font-size:.9rem;line-height:1.5}
        .review-section>h3{margin:20px 0 8px;color:var(--navy);font:700 1.05rem/1.3 Arial,Helvetica,sans-serif}
        .review-counts{display:flex;flex-wrap:wrap;gap:7px}
        .count-pill,.question-status,.option-tag{display:inline-flex;align-items:center;border-radius:999px;font-size:.76rem;font-weight:800}
        .count-pill{padding:7px 10px;background:#eef1f5;color:var(--navy)}
        .count-pill.correct,.question-status.correct,.option-tag.correct{background:#eaf4ee;color:var(--green)}
        .count-pill.wrong,.question-status.wrong,.option-tag.wrong{background:#f9ecea;color:var(--red)}
        .count-pill.unanswered,.question-status.unanswered{background:#f1f2f4;color:#596273}
        .question-review{margin:12px 0;border:1px solid var(--line);border-left:4px solid #9aa5b4;border-radius:10px;background:var(--card);padding:clamp(16px,3vw,22px)}
        .question-review.is-correct{border-left-color:var(--green)}
        .question-review.is-wrong{border-left-color:var(--red)}
        .question-review.is-unanswered{border-left-color:#9aa5b4}
        .question-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}
        .question-number{color:var(--maroon);font-size:.77rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase}
        .question-status{padding:6px 9px}
        .question-topic{margin:0 0 7px;color:var(--muted);font-size:.78rem;font-weight:700}
        .question-text{margin:0 0 14px;color:var(--navy);font-size:1rem;font-weight:700;line-height:1.55;overflow-wrap:anywhere}
        .review-direction{margin:0 0 14px;padding:12px 14px;border-left:3px solid var(--gold);border-radius:0 7px 7px 0;background:#faf7f0;color:#414959;font-size:.9rem;line-height:1.55}
        .review-options{display:grid;gap:8px}
        .review-option{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:10px 12px;border:1px solid var(--line);border-radius:8px;background:#fff}
        .review-option.is-answer{border-color:#b7d7c2;background:#f1f8f3}
        .review-option.is-selected:not(.is-answer){border-color:#e0b7b1;background:#fcf2f0}
        .option-copy{display:flex;min-width:0;gap:9px;line-height:1.5;overflow-wrap:anywhere}
        .option-letter{flex:none;color:var(--muted);font-weight:800}
        .option-tags{display:flex;flex:none;flex-wrap:wrap;justify-content:flex-end;gap:5px}
        .option-tag{padding:5px 7px}
        .review-unavailable{margin-top:24px}
        .review-unavailable p{margin:8px 0 0;color:var(--muted);line-height:1.6}
        @media(max-width:620px){
            .wrap{width:min(100% - 28px,520px);padding:24px 0 44px}
            .score{gap:8px}
            .metric{padding:14px 11px}
            .metric strong{font-size:1.35rem}
            .metric span{font-size:.74rem}
            .review-heading{align-items:flex-start;flex-direction:column;gap:10px}
        }
        @media(max-width:360px){
            .score{grid-template-columns:1fr}
            .metric{display:flex;align-items:center;justify-content:space-between;gap:12px}
            .question-top{align-items:flex-start;flex-direction:column;gap:7px}
            .review-option{flex-direction:column}
            .option-tags{justify-content:flex-start}
            .actions .btn{width:100%}
        }
    </style>
</head>
<body>
<header><a class="brand" href="<?= $guest ? 'index.php' : 'student.php' ?>"><?= e(APP_BRAND_NAME) ?></a></header>
<main class="wrap">
    <section class="hero">
        <p class="eyebrow">Test result</p>
        <h1><?= e((string) ($attempt['title'] ?? 'Practice test')) ?></h1>
        <p>Submitted <?= e($submittedLabel) ?><?php if (!empty($attempt['timed_out'])): ?> &bull; Time limit reached<?php endif; ?></p>
    </section>
    <section class="score" aria-label="Attempt score">
        <div class="metric"><strong><?= $score ?>/<?= $total ?></strong><span>Correct answers</span></div>
        <div class="metric"><strong><?= $percentage ?>%</strong><span>Overall score</span></div>
        <div class="metric"><strong><?= intdiv($timeTaken, 60) ?>m <?= $timeTaken % 60 ?>s</strong><span>Time used</span></div>
    </section>
    <section class="panel">
        <h2>Performance snapshot</h2>
        <div class="progress" role="img" aria-label="<?= $percentage ?> percent score"><span></span></div>
        <p class="notice"><?= $guest ? 'This free preview result is not saved. Create an account and enroll in a series to preserve future attempt history.' : 'Your attempt is saved in your personal history. Review every response below to see what you selected and the correct answer.' ?></p>
        <div class="actions">
            <?php if ($guest): ?>
                <a class="btn" href="instructions.php?sample=1">Try sample again</a>
                <a class="btn secondary" href="auth.php?mode=signup">Create free account</a>
            <?php else: ?>
                <a class="btn" href="practice.php?product=<?= e(rawurlencode((string) ($attempt['product'] ?? ''))) ?>">Try again</a>
                <a class="btn secondary" href="history.php">View history</a>
            <?php endif; ?>
        </div>
    </section>
    <section class="review-section" aria-labelledby="review-title">
        <div class="review-heading">
            <div><h2 id="review-title">Answer review</h2><p>Compare your response with the correct answer for this attempt.</p></div>
            <?php if (is_array($reviewQuestions)): ?>
                <div class="review-counts" aria-label="Answer summary">
                    <span class="count-pill correct"><?= $reviewSummary['correct'] ?> correct</span>
                    <span class="count-pill wrong"><?= $reviewSummary['wrong'] ?> incorrect</span>
                    <span class="count-pill unanswered"><?= $reviewSummary['unanswered'] ?> unanswered</span>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!is_array($reviewQuestions)): ?>
            <div class="panel review-unavailable"><h2>Answer details are unavailable</h2><p>This attempt’s score is still saved, but its question set is no longer available for detailed review.</p></div>
        <?php elseif ($reviewQuestions === []): ?>
            <div class="panel review-unavailable"><h2>No question details to display</h2><p>This attempt does not contain reviewable question records.</p></div>
        <?php else: ?>
            <?php $lastSection = ''; $lastDirection = ''; foreach ($reviewQuestions as $index => $question): if (!is_array($question)) continue; $selected = $question['selected'] ?? null; $isCorrect = (bool) ($question['is_correct'] ?? false); $status = $selected === null ? 'unanswered' : ($isCorrect ? 'correct' : 'wrong'); $statusLabel = $status === 'correct' ? 'Correct' : ($status === 'wrong' ? 'Incorrect' : 'Not answered'); $section = trim((string) ($question['section'] ?? '')); $direction = trim((string) ($question['direction'] ?? '')); $options = is_array($question['options'] ?? null) ? $question['options'] : []; $correctAnswer = (int) ($question['answer'] ?? -1); ?>
                <?php if ($section !== '' && $section !== $lastSection): ?><h3><?= e($section) ?></h3><?php endif; ?>
                <?php if ($direction !== '' && ($direction !== $lastDirection || $section !== $lastSection)): ?><div class="review-direction"><strong>Directions</strong><br><?= nl2br(e($direction)) ?></div><?php endif; ?>
                <article class="question-review is-<?= e($status) ?>">
                    <div class="question-top"><span class="question-number">Question <?= $index + 1 ?></span><span class="question-status <?= e($status) ?>"><?= e($statusLabel) ?></span></div>
                    <?php if (trim((string) ($question['topic'] ?? '')) !== ''): ?><p class="question-topic"><?= e((string) $question['topic']) ?></p><?php endif; ?>
                    <div class="question-text"><?= format_question_text((string) ($question['q'] ?? 'Question text unavailable')) ?></div>
                    <?php if ($options === []): ?><p class="notice">Answer choices are unavailable for this question.</p><?php else: ?>
                        <div class="review-options">
                            <?php foreach ($options as $optionIndex => $optionText): if (!is_numeric($optionIndex)) continue; $optionIndex = (int) $optionIndex; $isSelected = $selected !== null && (int) $selected === $optionIndex; $isAnswer = $correctAnswer === $optionIndex; ?>
                                <div class="review-option<?= $isAnswer ? ' is-answer' : '' ?><?= $isSelected && !$isAnswer ? ' is-selected' : '' ?>">
                                    <span class="option-copy"><span class="option-letter"><?= e($optionIndex >= 0 && $optionIndex < 26 ? chr(65 + $optionIndex) : (string) ($optionIndex + 1)) ?>.</span><span><?= e((string) $optionText) ?></span></span>
                                    <span class="option-tags">
                                        <?php if ($isSelected): ?><span class="option-tag <?= $isCorrect ? 'correct' : 'wrong' ?>">Your answer</span><?php endif; ?>
                                        <?php if ($isAnswer): ?><span class="option-tag correct">Correct answer</span><?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($selected !== null && !array_key_exists((int) $selected, $options)): ?><p class="notice">Your saved response is not one of the available choices.</p><?php endif; ?>
                    <?php endif; ?>
                </article>
            <?php $lastSection = $section; $lastDirection = $direction; endforeach; ?>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
